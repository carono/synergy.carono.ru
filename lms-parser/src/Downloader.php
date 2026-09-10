<?php

declare(strict_types=1);

namespace Carono\LmsParser;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\RequestOptions;

final class Downloader
{
    /**
     * CDN Synergy (`v.lscdn.ru`) регулярно обрывает выдачу на середине файла
     * (`cURL error 18: transfer closed`) — на длинных видео это ловится в каждом
     * десятом-двадцатом уроке. Файл при этом сохраняется как `.part`, поэтому
     * повтор продолжает с того же места, а не начинает заново.
     */
    private const MAX_ATTEMPTS = 5;

    private Guzzle $http;

    public function __construct(
        private readonly Logger $logger,
        string $userAgent,
        ?string $cookieFile = null,
    ) {
        $config = [
            'headers' => [
                'User-Agent' => $userAgent,
                'Referer' => 'https://lms.synergy.ru/',
            ],
            'timeout' => 0,
            'connect_timeout' => 30,
            'http_errors' => false,
        ];

        // Часть материалов лежит не в CDN, а на самой lms.synergy.ru — без cookie
        // сессии оттуда приходит 403 DDoS-Guard вместо файла.
        if ($cookieFile !== null && is_file($cookieFile)) {
            $config['cookies'] = new FileCookieJar($cookieFile, true);
        }

        $this->http = new Guzzle($config);
    }

    public function download(string $url, string $destPath): bool
    {
        @mkdir(dirname($destPath), 0o775, true);

        if (is_file($destPath) && filesize($destPath) > 0) {
            $this->logger->ok('Уже на диске: '.basename($destPath).' ('.$this->humanSize((int)filesize($destPath)).')');
            return true;
        }

        $before = -1;
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            if ($this->attempt($url, $destPath)) {
                return true;
            }

            $partial = is_file($destPath.'.part') ? (int)filesize($destPath.'.part') : 0;
            if ($attempt < self::MAX_ATTEMPTS && $partial <= $before) {
                // Попытка не добавила ни байта — дело не в обрыве, повторять бессмысленно
                $this->logger->err('Повтор не сдвинул закачку — прекращаю: '.basename($destPath));
                return false;
            }
            $before = $partial;

            if ($attempt < self::MAX_ATTEMPTS) {
                $this->logger->warn(sprintf(
                    'Повтор %d/%d с %s: %s',
                    $attempt + 1, self::MAX_ATTEMPTS, $this->humanSize($partial), basename($destPath)
                ));
                sleep(min(30, 2 ** $attempt));
            }
        }

        $this->logger->err('Не удалось скачать за '.self::MAX_ATTEMPTS.' попыток: '.basename($destPath));
        return false;
    }

    private function attempt(string $url, string $destPath): bool
    {
        $tmp = $destPath.'.part';
        $existing = is_file($tmp) ? filesize($tmp) : 0;

        try {
            $head = $this->http->head($url, [RequestOptions::HTTP_ERRORS => false]);
        } catch (\Throwable $e) {
            $head = null;
        }

        $totalSize = null;
        if ($head !== null && $head->getStatusCode() === 200) {
            $cl = $head->getHeaderLine('Content-Length');
            if ($cl !== '') {
                $totalSize = (int)$cl;
            }
        }

        if ($totalSize !== null && $existing >= $totalSize && $totalSize > 0) {
            rename($tmp, $destPath);
            $this->logger->ok('Уже скачано: '.basename($destPath).' ('.$this->humanSize($totalSize).')');
            return true;
        }

        $headers = ['User-Agent' => $this->http->getConfig('headers')['User-Agent'] ?? ''];
        if ($existing > 0) {
            $headers['Range'] = "bytes=$existing-";
            $this->logger->info('Докачиваю с '.$this->humanSize($existing).': '.basename($destPath));
        } else {
            $this->logger->info('Скачиваю: '.basename($destPath).($totalSize ? ' ('.$this->humanSize($totalSize).')' : ''));
        }

        // При докачке нужно открыть поток в режиме append, иначе sink-как-путь обрежет файл.
        $sink = $tmp;
        if ($existing > 0) {
            $sink = fopen($tmp, 'ab');
            if ($sink === false) {
                $this->logger->err("Не открыть файл $tmp для докачки");
                return false;
            }
        }

        $progressLast = 0;
        $startTime = microtime(true);
        $status = 0;
        $rangeIgnored = false;

        try {
            $response = $this->http->get($url, [
                RequestOptions::HEADERS => $headers,
                RequestOptions::SINK => $sink,
                RequestOptions::ON_HEADERS => function ($resp) use (&$status, &$rangeIgnored, $existing, $sink): void {
                    $status = $resp->getStatusCode();
                    // Мы просили продолжение, а сервер отдаёт файл с начала (200 вместо 206).
                    // Дописывать такое в конец — гарантированно битый файл, поэтому чистим .part
                    // и пишем с нуля в этой же попытке.
                    if ($existing > 0 && $status === 200 && is_resource($sink)) {
                        $rangeIgnored = true;
                        ftruncate($sink, 0);
                        rewind($sink);
                    }
                },
                RequestOptions::PROGRESS => function ($total, $down) use (&$progressLast, $existing, $totalSize, $startTime) {
                    if ($down === 0) {
                        return;
                    }
                    $now = microtime(true);
                    if ($now - $progressLast < 1.0) {
                        return;
                    }
                    $progressLast = $now;
                    $real = $existing + $down;
                    $totalReal = $totalSize ?: ($total > 0 ? $existing + $total : 0);
                    $pct = $totalReal > 0 ? floor($real / $totalReal * 100) : 0;
                    $speed = $down / max(0.1, $now - $startTime);
                    fprintf(STDERR, "\r  %s%% — %s / %s — %s/s    ",
                        $pct,
                        $this->humanSize($real),
                        $totalReal ? $this->humanSize($totalReal) : '?',
                        $this->humanSize((int)$speed)
                    );
                },
            ]);
        } catch (\Throwable $e) {
            if (is_resource($sink)) {
                fclose($sink);
            }
            // Тело ошибочного ответа Guzzle успевает записать в sink — откатываем
            // файл к состоянию до попытки, иначе в .part копятся страницы ошибок.
            $this->rollback($tmp, $existing);
            $this->logger->err('Ошибка скачивания: '.$e->getMessage());
            return false;
        }

        if (is_resource($sink)) {
            fclose($sink);
        }
        fwrite(STDERR, "\n");

        if ($rangeIgnored) {
            $this->logger->warn('Сервер не поддержал Range — файл перекачан с начала: '.basename($destPath));
            $existing = 0;
        }

        if ($status >= 300 || $status < 200) {
            $body = (string)$response->getBody();
            $hint = preg_match('~<title>\s*ddos.?guard~i', $body)
                ? ' (страница DDoS-Guard — нужна свежая сессия: ./bin/cookies)'
                : '';
            $this->rollback($tmp, $existing);
            $this->logger->err('HTTP '.$status.' вместо файла'.$hint.': '.basename($destPath));
            return false;
        }

        if (!is_file($tmp) || filesize($tmp) === 0) {
            $this->logger->err('Пустой файл после скачивания');
            return false;
        }

        // Соединение может закрыться без исключения, отдав меньше заявленного —
        // такой обрезанный файл нельзя принимать за готовый, иначе половина видео
        // навсегда останется как «скачано».
        $got = (int)filesize($tmp);
        if ($totalSize !== null && $totalSize > 0 && $got < $totalSize) {
            $this->logger->warn(sprintf(
                'Недокачано: %s из %s — оставляю .part',
                $this->humanSize($got), $this->humanSize($totalSize)
            ));
            return false;
        }

        rename($tmp, $destPath);
        $this->logger->ok('Готово: '.basename($destPath).' ('.$this->humanSize((int)filesize($destPath)).')');
        return true;
    }

    /**
     * Возвращает `.part` к размеру до попытки. Нужно потому, что Guzzle пишет в sink
     * и тело ответа с ошибкой: без отката файл растёт склеенными страницами 403.
     */
    private function rollback(string $tmp, int $size): void
    {
        if (!is_file($tmp)) {
            return;
        }
        if ($size <= 0) {
            @unlink($tmp);
            return;
        }
        if ((int)filesize($tmp) <= $size) {
            return;
        }
        $fh = fopen($tmp, 'r+b');
        if ($fh === false) {
            return;
        }
        ftruncate($fh, $size);
        fclose($fh);
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $n = (float)$bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }
        return sprintf('%.1f %s', $n, $units[$i]);
    }
}
