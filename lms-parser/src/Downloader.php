<?php

declare(strict_types=1);

namespace Carono\LmsParser;

use GuzzleHttp\Client as Guzzle;
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

    public function __construct(private readonly Logger $logger, string $userAgent)
    {
        $this->http = new Guzzle([
            'headers' => [
                'User-Agent' => $userAgent,
                'Referer' => 'https://lms.synergy.ru/',
            ],
            'timeout' => 0,
            'connect_timeout' => 30,
        ]);
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

        try {
            $this->http->get($url, [
                RequestOptions::HEADERS => $headers,
                RequestOptions::SINK => $sink,
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
            $this->logger->err('Ошибка скачивания: '.$e->getMessage());
            return false;
        }

        if (is_resource($sink)) {
            fclose($sink);
        }
        fwrite(STDERR, "\n");

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
