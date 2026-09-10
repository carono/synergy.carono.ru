<?php

declare(strict_types=1);

namespace Carono\LmsParser;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Cookie\FileCookieJar;
use Psr\Http\Message\ResponseInterface;

final class Client
{
    private const BASE = 'https://lms.synergy.ru';

    private const CHALLENGE_HINT = 'Сайт закрыт JS-челленджем DDoS-Guard — HTTP-клиент его не пройдёт. '
        .'Обновите сессию браузером: ./bin/cookies';

    /** Не чаще одного обновления в минуту: браузерный челлендж занимает ~80 секунд. */
    private const REFRESH_COOLDOWN = 60;

    /**
     * Сколько обновлений подряд пробуем, пока ни один запрос между ними не прошёл.
     * Если и после третьего LMS отвечает 403 — дело не в сессии, и молотить челлендж
     * дальше значит только злить DDoS-Guard.
     */
    private const REFRESH_MAX_STREAK = 3;

    /** Сколько раз повторять запрос при обрыве соединения, прежде чем сдаться. */
    private const NETWORK_RETRIES = 7;

    /**
     * Паузы между повторами сетевого сбоя, секунды.
     *
     * Когда DDoS-Guard закрывает адрес, это минуты простоя, а не секунды: короткая
     * лесенка 2-4-8-15 сгорала вся до разбана, и Runner шёл дальше без дисциплины.
     */
    private const NETWORK_PAUSES = [5, 15, 45, 90, 180, 300, 300];

    private Guzzle $http;
    private FileCookieJar $jar;
    private int $lastRefresh = 0;
    /** Обновлений подряд без успешного запроса между ними. */
    private int $refreshStreak = 0;
    /** Когда сессия получена — от этого считается её возраст. */
    private int $sessionSince = 0;
    /** @var (callable():void)|null освободить канал перед челленджем */
    private $pauseHook = null;
    /** @var (callable():void)|null вернуть параллелизм после челленджа */
    private $resumeHook = null;

    public function __construct(
        private readonly string $login,
        private readonly string $password,
        private readonly string $cookieFile,
        private readonly string $userAgent,
        private readonly Logger $logger,
    ) {
        $this->sessionSince = is_file($cookieFile) ? (int)filemtime($cookieFile) : time();
        $this->buildHttp();
    }

    /**
     * Что делать с закачками на время обновления сессии.
     *
     * Челлендж DDoS-Guard грузит страницу через тот же канал, что и закачки: на двадцати
     * параллельных потоках он не успевал пройти за отведённое время, и обновление
     * проваливалось. Поэтому канал на это время освобождается.
     */
    public function useDownloadHooks(callable $pause, callable $resume): void
    {
        $this->pauseHook = $pause;
        $this->resumeHook = $resume;
    }

    /**
     * Обновить сессию заранее, если она старше указанного срока.
     *
     * Протухание посреди дисциплины стоит дорого: реактивное обновление ловит уже
     * начавшийся поток 403. Под нагрузкой сессия живёт около 20 минут, поэтому в паузе
     * между дисциплинами дешевле обновиться самим.
     */
    public function refreshIfStale(int $maxAgeSeconds = 900): void
    {
        $age = time() - $this->sessionSince;
        if ($age < $maxAgeSeconds) {
            return;
        }
        $this->logger->info(sprintf('Сессии %d мин — обновляю заранее, не дожидаясь 403', intdiv($age, 60)));
        $this->refreshStreak = 0;
        $this->refreshSession();
    }

    private function buildHttp(): void
    {
        $this->jar = new FileCookieJar($this->cookieFile, true);
        $this->http = new Guzzle([
            'base_uri' => self::BASE.'/',
            'cookies' => $this->jar,
            'allow_redirects' => ['max' => 8, 'track_redirects' => true],
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ru,en;q=0.9',
            ],
            'timeout' => 60,
            'connect_timeout' => 15,
        ]);
    }

    /**
     * Сессия LMS живёт минуты, а полный прогон — часы. Когда она протухает, LMS отвечает
     * 403 (страницей DDoS-Guard) на любой запрос, и без обновления cookies остаток
     * прогона превращается в поток ошибок «не удалось открыть урок».
     *
     * Обновление возможно только настоящим браузером — тот же ./bin/cookies. Значит,
     * родительскому процессу нужен доступ к X-серверу: запускать ./bin/parse с DISPLAY.
     * Без DISPLAY метод честно сообщает, что обновиться не может.
     */
    public function refreshSession(): bool
    {
        if ($this->refreshStreak >= self::REFRESH_MAX_STREAK) {
            $this->logger->warn(sprintf(
                'Сессия не восстановилась за %d обновления подряд — больше не пробую',
                self::REFRESH_MAX_STREAK,
            ));
            return false;
        }

        // Backoff: каждое следующее обновление подряд ждёт вдвое дольше — 60, 120, 240 с.
        $now = time();
        $cooldown = self::REFRESH_COOLDOWN << $this->refreshStreak;
        $wait = $this->lastRefresh + $cooldown - $now;
        if ($this->lastRefresh > 0 && $wait > 0) {
            $this->logger->info("Жду {$wait} с перед следующим обновлением сессии");
            sleep($wait);
            $now = time();
        }
        $this->lastRefresh = $now;
        $this->refreshStreak++;

        if ((string)getenv('DISPLAY') === '') {
            $this->logger->warn('Сессия LMS протухла, но DISPLAY не задан — обновить cookies браузером нельзя');
            return false;
        }

        $this->logger->warn('Обновляю сессию LMS браузером...');

        if ($this->pauseHook !== null) {
            ($this->pauseHook)();
        }

        // FileCookieJar пишет содержимое в деструкторе. Если не опустошить старую банку,
        // она затрёт файл, который прямо сейчас перезапишет ./bin/cookies.
        $this->jar->clear();
        unset($this->jar);

        $root = dirname(__DIR__);
        $env = array_filter([
            'PATH' => getenv('PATH'),
            'HOME' => getenv('HOME'),
            'DISPLAY' => getenv('DISPLAY'),
            'LMS_LOGIN' => $this->login,
            'LMS_PASSWORD' => $this->password,
            'LMS_PLAYWRIGHT_DIR' => getenv('LMS_PLAYWRIGHT_DIR'),
            'LMS_NODE_BIN' => getenv('LMS_NODE_BIN'),
        ], static fn($v) => $v !== false && $v !== '');

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open([PHP_BINARY, $root.'/bin/cookies'], $descriptors, $pipes, $root, $env);
        if (!is_resource($process)) {
            $this->buildHttp();
            if ($this->resumeHook !== null) {
                ($this->resumeHook)();
            }
            $this->logger->warn('Не удалось запустить ./bin/cookies');
            return false;
        }
        $out = (string)stream_get_contents($pipes[1]).(string)stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        $this->buildHttp();

        if ($this->resumeHook !== null) {
            ($this->resumeHook)();
        }

        if ($code !== 0) {
            $this->logger->warn('./bin/cookies завершился с кодом '.$code.': '.trim($out));
            return false;
        }

        $this->sessionSince = time();
        $this->logger->ok('Сессия LMS обновлена');
        return true;
    }

    /**
     * Один повтор запроса после обновления сессии. Больше одного не нужно: если и свежие
     * cookies получают 403, дело не в сессии, и ошибку надо показать как есть.
     *
     * @template T
     * @param callable():T $request
     * @return T
     */
    private function retryOnExpiry(callable $request): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                $result = $request();
                // Запрос прошёл — серия обновлений закончилась, следующий сбой начнёт отсчёт заново.
                $this->refreshStreak = 0;
                return $result;
            } catch (\Throwable $e) {
                // Обрыв соединения — не повод валить прогон целиком: тот же CDN рвёт TLS
                // и родителю. Раньше такая ошибка уходила наружу и убивала процесс.
                if ($this->looksTransient($e) && $attempt <= self::NETWORK_RETRIES) {
                    // DDoS-Guard не отвечает 403, а молча закрывает соединение (cURL error 35)
                    // и держит адрес закрытым минутами, а не секундами. Пятнадцати секунд
                    // не хватало: повторы сгорали вхолостую, и дисциплина пропускалась целиком.
                    $pause = self::NETWORK_PAUSES[min($attempt, count(self::NETWORK_PAUSES)) - 1];
                    $this->logger->warn(sprintf(
                        'Сеть подвела (%s), повтор %d из %d через %d с',
                        self::shortError($e), $attempt, self::NETWORK_RETRIES, $pause
                    ));
                    sleep($pause);
                    continue;
                }
                if ($this->looksExpired($e) && $this->refreshSession()) {
                    // Обновились — даём запросу ещё один шанс на общих условиях.
                    continue;
                }
                throw $e;
            }
        }
    }

    private function looksTransient(\Throwable $e): bool
    {
        if ($e instanceof \GuzzleHttp\Exception\ConnectException) {
            return true;
        }
        if (!$e instanceof \GuzzleHttp\Exception\RequestException) {
            return false;
        }
        return (bool)preg_match('~cURL error (7|18|28|35|52|56|3\d)~', $e->getMessage());
    }

    private static function shortError(\Throwable $e): string
    {
        $message = $e->getMessage();
        return preg_match('~cURL error \d+~', $message, $m) ? $m[0] : substr($message, 0, 60);
    }

    private function looksExpired(\Throwable $e): bool
    {
        if ($e instanceof \GuzzleHttp\Exception\ClientException) {
            return $e->getResponse()->getStatusCode() === 403;
        }
        return $e instanceof \RuntimeException && str_contains($e->getMessage(), 'DDoS-Guard');
    }

    public function login(): void
    {
        if ($this->isLoggedIn()) {
            $this->logger->ok('Уже авторизованы (используем cookie)');
            return;
        }

        // Сессии в cookies.json может быть уже несколько десятков минут — тогда LMS отдаёт
        // челлендж вместо страницы входа, и логин из PHP невозможен в принципе. Раньше
        // прогон падал на этом с трассировкой, хотя обновиться умеет сам.
        $this->logger->info('Сессия в cookies недействительна — обновляю браузером');
        if ($this->refreshSession() && $this->isLoggedIn()) {
            $this->logger->ok('Авторизация успешна (обновлённая сессия)');
            return;
        }

        $this->logger->info('Авторизация...');
        // Форма входа — такой же сетевой запрос, как все прочие: DDoS-Guard рвёт соединение
        // и здесь. Без общей обёртки прогон падал трассировкой на первом же обрыве.
        $this->retryOnExpiry(function (): void {
            $this->performLogin();
        });
    }

    private function performLogin(): void
    {
        try {
            $this->assertNotChallenged((string)$this->http->get('/')->getBody());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->assertNotChallenged((string)$e->getResponse()->getBody());
            throw $e;
        }

        $response = $this->http->post('/user/login', [
            'headers' => [
                'X-Requested-With' => 'XMLHttpRequest',
                'Origin' => self::BASE,
                'Referer' => self::BASE.'/',
            ],
            'form_params' => [
                'popupUsername' => $this->login,
                'popupPassword' => $this->password,
                'currentUrl' => '',
            ],
        ]);

        $body = (string)$response->getBody();
        $this->assertNotChallenged($body);
        $json = json_decode($body, true);
        $alert = $json['alertMessage'] ?? '';

        if ($alert !== '' || empty($json['redirect'])) {
            throw new \RuntimeException('Не удалось войти: '.($alert ?: $body));
        }

        if (!$this->isLoggedIn()) {
            throw new \RuntimeException('Авторизация не подтверждена /student/up');
        }

        $this->jar->save($this->cookieFile);
        $this->logger->ok('Авторизация успешна');
    }

    public function isLoggedIn(): bool
    {
        try {
            $response = $this->http->get('/student/up', [
                'allow_redirects' => false,
                'headers' => ['Referer' => self::BASE.'/'],
            ]);
            $code = $response->getStatusCode();
            if ($code !== 200) {
                return false;
            }
            $body = (string)$response->getBody();
            return str_contains($body, '/user/logout') || str_contains($body, 'curatorPanel');
        } catch (\Throwable) {
            return false;
        }
    }

    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->retryOnExpiry(fn() => $this->http->get($url, $options));
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->retryOnExpiry(fn() => $this->http->post($url, $options));
    }

    public function getHtml(string $url, array $headers = []): string
    {
        return $this->retryOnExpiry(function () use ($url, $headers): string {
            $response = $this->http->get($url, [
                'headers' => array_merge(['Referer' => self::BASE.'/student/up'], $headers),
            ]);
            $body = (string)$response->getBody();
            $this->assertNotChallenged($body);
            return $body;
        });
    }

    /**
     * DDoS-Guard отвечает 403 со своей HTML-страницей вместо запрошенной.
     * Без этой проверки ошибка выглядит как «страница изменилась» и уводит в сторону.
     */
    private function assertNotChallenged(string $body): void
    {
        if (preg_match('~<title>\s*ddos.?guard~i', $body)) {
            throw new \RuntimeException(self::CHALLENGE_HINT);
        }
    }

    public function ajaxGet(string $url, string $referer): array
    {
        return $this->retryOnExpiry(function () use ($url, $referer): array {
            $response = $this->http->get($url, [
                'headers' => [
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Accept' => 'application/json, text/javascript, */*; q=0.01',
                    'Referer' => $referer,
                ],
            ]);
            $body = (string)$response->getBody();
            $json = json_decode($body, true);
            if (!is_array($json)) {
                throw new \RuntimeException("Ожидался JSON от $url, получено: ".substr($body, 0, 200));
            }
            return $json;
        });
    }

    public function ajaxPost(string $url, array $form, string $referer): array
    {
        return $this->retryOnExpiry(function () use ($url, $form, $referer): array {
            $response = $this->http->post($url, [
                'headers' => [
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Accept' => 'application/json, text/javascript, */*; q=0.01',
                    'Referer' => $referer,
                    'Origin' => self::BASE,
                ],
                'form_params' => $form,
            ]);
            $body = (string)$response->getBody();
            $json = json_decode($body, true);
            if (!is_array($json)) {
                // Некоторые эндпоинты возвращают пустую строку или 'true'
                return ['raw' => $body];
            }
            return $json;
        });
    }
}
