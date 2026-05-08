<?php

declare(strict_types=1);

namespace Carono\LmsParser;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Cookie\FileCookieJar;
use Psr\Http\Message\ResponseInterface;

final class Client
{
    private const BASE = 'https://lms.synergy.ru';

    private Guzzle $http;
    private FileCookieJar $jar;

    public function __construct(
        private readonly string $login,
        private readonly string $password,
        private readonly string $cookieFile,
        private readonly string $userAgent,
        private readonly Logger $logger,
    ) {
        $this->jar = new FileCookieJar($cookieFile, true);
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

    public function login(): void
    {
        if ($this->isLoggedIn()) {
            $this->logger->ok('Уже авторизованы (используем cookie)');
            return;
        }

        $this->logger->info('Авторизация...');
        $this->http->get('/');

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
        return $this->http->get($url, $options);
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->http->post($url, $options);
    }

    public function getHtml(string $url, array $headers = []): string
    {
        $response = $this->http->get($url, [
            'headers' => array_merge(['Referer' => self::BASE.'/student/up'], $headers),
        ]);
        return (string)$response->getBody();
    }

    public function ajaxGet(string $url, string $referer): array
    {
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
    }

    public function ajaxPost(string $url, array $form, string $referer): array
    {
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
    }
}
