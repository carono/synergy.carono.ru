<?php

declare(strict_types=1);

namespace Carono\LmsParser;

/**
 * Пул дочерних процессов для параллельного скачивания.
 *
 * CDN Synergy отдаёт один файл на 175–500 КБ/с, поэтому последовательная закачка
 * упирается не в диск и не в канал, а в скорость одного соединения. Набор
 * «Пересдача» открыт целиком, порядок уроков в нём не важен — файлы можно тянуть
 * параллельно.
 *
 * Каждое задание уходит отдельному процессу `bin/download-worker`, который внутри
 * использует тот же {@see Downloader}. Отсюда бесплатно наследуются докачка `.part`
 * по Range, пять попыток и отсев обрезанных по Content-Length файлов. Гонок нет по
 * построению: у каждого процесса свой `.part`, а `state.json` пишет только родитель.
 */
final class DownloadQueue
{
    /** Как часто печатать сводку по активным закачкам, секунд. */
    private const PROGRESS_EVERY = 15;

    public function __construct(
        private readonly Logger $logger,
        private readonly string $workerScript,
        private readonly int $concurrency,
        private readonly string $phpBin = PHP_BINARY,
    ) {
    }

    /**
     * @param array<int, array{url:string, dest:string, label:string}> $jobs
     * @return array<string, bool> результат по каждому dest
     */
    /** @var array<int, array{proc:resource}> процессы, которые надо добить при остановке */
    private array $active = [];

    private bool $stopping = false;

    public function run(array $jobs): array
    {
        $results = [];
        if ($jobs === []) {
            return $results;
        }

        $this->installSignalHandlers();

        $queue = array_values($jobs);
        $total = count($queue);
        $running = [];
        $done = 0;
        $lastProgress = time();
        $startedAt = microtime(true);
        $bytesAtStart = $this->activeBytes($jobs);

        $this->logger->info(sprintf('Очередь загрузок: %d файлов, потоков %d', $total, $this->concurrency));

        while ($queue !== [] || $running !== []) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            if ($this->stopping) {
                $this->terminateAll($running);
                $this->logger->warn('Остановка: дочерние загрузки прерваны, .part сохранены для докачки');
                return $results;
            }

            while ($queue !== [] && !$this->stopping && count($running) < $this->concurrency) {
                $job = array_shift($queue);
                $handle = $this->start($job);
                if ($handle === null) {
                    $results[$job['dest']] = false;
                    $done++;
                    continue;
                }
                $running[] = $handle;
                $this->active[] = $handle;
            }

            foreach ($running as $i => $handle) {
                $this->drain($running[$i]);

                $status = proc_get_status($handle['proc']);
                if ($status['running']) {
                    continue;
                }

                $this->drain($running[$i]);
                fclose($running[$i]['stdout']);
                proc_close($handle['proc']);

                $ok = $status['exitcode'] === 0;
                $results[$handle['job']['dest']] = $ok;
                $done++;

                foreach ($this->interestingLines($running[$i]['buffer']) as $line) {
                    $this->logger->debug('  '.$handle['job']['label'].': '.$line);
                }
                if ($ok) {
                    $this->logger->ok(sprintf('[%d/%d] %s', $done, $total, basename($handle['job']['dest'])));
                } else {
                    $this->logger->err(sprintf('[%d/%d] не скачан: %s', $done, $total, basename($handle['job']['dest'])));
                }

                unset($running[$i]);
            }
            $running = array_values($running);

            if (time() - $lastProgress >= self::PROGRESS_EVERY && $running !== []) {
                $lastProgress = time();
                $bytes = $this->activeBytes($jobs);
                $elapsed = max(0.1, microtime(true) - $startedAt);
                $this->logger->info(sprintf(
                    'В работе %d, готово %d/%d, суммарно %s, средняя %s/s',
                    count($running), $done, $total,
                    $this->humanSize($bytes),
                    $this->humanSize((int)(($bytes - $bytesAtStart) / $elapsed)),
                ));
            }

            if ($running !== []) {
                usleep(300_000);
            }
        }

        return $results;
    }

    /**
     * Без этого при падении или остановке родителя воркеры остаются сиротами и
     * продолжают качать: несколько прогонов начинают тянуть одни и те же файлы.
     */
    private function installSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            $this->logger->warn('pcntl недоступен — дочерние загрузки не остановятся сами при завершении');
            return;
        }

        $handler = function (): void {
            $this->stopping = true;
        };
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGHUP, $handler);
    }

    /** @param array<int, array{proc:resource, stdout:resource}> $running */
    private function terminateAll(array $running): void
    {
        foreach ($running as $handle) {
            if (is_resource($handle['proc'])) {
                proc_terminate($handle['proc'], SIGTERM);
            }
        }
        // Даём воркерам закрыть файлы, потом добиваем оставшихся
        usleep(500_000);
        foreach ($running as $handle) {
            if (!is_resource($handle['proc'])) {
                continue;
            }
            $status = proc_get_status($handle['proc']);
            if ($status['running']) {
                proc_terminate($handle['proc'], SIGKILL);
            }
            if (is_resource($handle['stdout'])) {
                fclose($handle['stdout']);
            }
            proc_close($handle['proc']);
        }
        $this->active = [];
    }

    /**
     * @param array{url:string, dest:string, label:string} $job
     * @return array{proc:resource, stdout:resource, buffer:string, job:array{url:string, dest:string, label:string}}|null
     */
    private function start(array $job): ?array
    {
        // Задание отдаём через stdin: в argv его увидел бы `ps` любой на машине.
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ];

        $proc = proc_open([$this->phpBin, $this->workerScript], $descriptors, $pipes);
        if (!is_resource($proc)) {
            $this->logger->err('Не удалось запустить воркер загрузки для '.basename($job['dest']));
            return null;
        }

        fwrite($pipes[0], json_encode(['url' => $job['url'], 'dest' => $job['dest']], JSON_UNESCAPED_SLASHES));
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);

        return ['proc' => $proc, 'stdout' => $pipes[1], 'buffer' => '', 'job' => $job];
    }

    /** @param array{stdout:resource, buffer:string} $handle */
    private function drain(array &$handle): void
    {
        $chunk = stream_get_contents($handle['stdout']);
        if (is_string($chunk) && $chunk !== '') {
            $handle['buffer'] .= $chunk;
        }
    }

    /**
     * Из вывода воркера оставляем только события, за которыми стоит следить.
     *
     * @return array<int, string>
     */
    private function interestingLines(string $buffer): array
    {
        $keep = [];
        foreach (explode("\n", $buffer) as $line) {
            $line = trim(preg_replace('~\033\[[0-9;]*m~', '', $line) ?? '');
            if ($line === '') {
                continue;
            }
            if (preg_match('~Повтор |Недокачано|Не удалось скачать|Докачиваю с|Повтор не сдвинул~u', $line)) {
                $keep[] = $line;
            }
        }
        return $keep;
    }

    /** @param array<int, array{dest:string}> $jobs */
    private function activeBytes(array $jobs): int
    {
        $bytes = 0;
        foreach ($jobs as $job) {
            foreach ([$job['dest'], $job['dest'].'.part'] as $path) {
                if (is_file($path)) {
                    $bytes += (int)filesize($path);
                }
            }
        }
        return $bytes;
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
