<?php

declare(strict_types=1);

namespace Carono\LmsParser;

final class Runner
{
    /** Открывать вкладку «Пересдача», если по дисциплине есть задолженность или заблокированные уроки. */
    public const RETAKE_AUTO = 'auto';
    /** Всегда предпочитать вкладку «Пересдача», если она есть. */
    public const RETAKE_FORCE = 'force';
    /** Не трогать вкладку «Пересдача», работать только с «Текущими». */
    public const RETAKE_OFF = 'off';

    public function __construct(
        private readonly Client $client,
        private readonly Parser $parser,
        private readonly Downloader $downloader,
        private readonly Scorm $scorm,
        private readonly State $state,
        private readonly Logger $logger,
        private readonly string $outputDir,
        private readonly int $watchedMinutes,
        private readonly bool $emulateWatch,
        private readonly ?int $maxDisciplines = null,
        private readonly ?int $maxLessons = null,
        private readonly ?string $disciplineFilter = null,
        private readonly string $retakeMode = self::RETAKE_AUTO,
        private readonly bool $redo = false,
        private readonly ?DownloadQueue $queue = null,
    ) {
    }

    public function run(?int $semesterFilter = null): void
    {
        $this->client->login();

        $this->logger->info('Загружаю /student/up');
        $html = $this->client->getHtml('/student/up');
        $semesters = $this->parser->semesters($html);

        if (empty($semesters)) {
            $this->logger->err('Не нашёл ни одного семестра — структура страницы изменилась?');
            return;
        }

        // Если семестр не задан — берём «текущий» (expanded), иначе указанный
        $target = null;
        if ($semesterFilter !== null) {
            foreach ($semesters as $s) {
                if ($s['number'] === $semesterFilter) {
                    $target = $s;
                    break;
                }
            }
        } else {
            foreach ($semesters as $s) {
                if ($s['current']) {
                    $target = $s;
                    break;
                }
            }
        }

        if ($target === null) {
            $this->logger->err('Не найден целевой семестр');
            return;
        }

        $this->logger->ok("Семестр: {$target['label']} (".count($target['disciplines']).' дисциплин)');
        if (!empty($target['debt'])) {
            $this->logger->warn(sprintf(
                'Семестр помечен как задолженность (%s) — буду искать вкладки «Пересдача»',
                $target['debtCount'] > 0 ? 'задолженностей: '.$target['debtCount'] : 'по иконке статуса',
            ));
        }

        $stats = ['downloaded' => 0, 'skipped' => 0, 'failed' => 0, 'disciplines' => 0, 'retakes' => 0];

        foreach ($target['disciplines'] as $discipline) {
            if ($this->disciplineFilter !== null
                && stripos($discipline['title'], $this->disciplineFilter) === false
                && $discipline['id'] !== $this->disciplineFilter
            ) {
                continue;
            }
            if ($this->maxDisciplines !== null && $stats['disciplines'] >= $this->maxDisciplines) {
                break;
            }
            $stats['disciplines']++;
            $this->processDiscipline($target, $discipline, $stats);
        }

        $this->state->setSummary([
            'finishedAt' => date('c'),
            'semester' => $target['number'],
            'stats' => $stats,
        ]);

        $this->logger->ok(sprintf(
            'Готово. Дисциплин: %d (через пересдачу: %d), скачано: %d, пропущено: %d, ошибок: %d',
            $stats['disciplines'], $stats['retakes'], $stats['downloaded'], $stats['skipped'], $stats['failed']
        ));
    }

    private function processDiscipline(array $semester, array $discipline, array &$stats): void
    {
        $title = $discipline['title'];
        $id = $discipline['id'];
        $this->logger->info("=== Дисциплина: $title (id=$id) ===");

        $this->state->setDisciplineMeta($id, [
            'title' => $title,
            'semester' => $semester['number'],
            'slug' => Slug::make($title),
        ]);

        try {
            $html = $this->client->getHtml($discipline['contentsUrl']);
        } catch (\Throwable $e) {
            $this->logger->err("Не открыть дисциплину: ".$e->getMessage());
            $stats['failed']++;
            return;
        }

        $info = $this->parser->disciplineLessons($html);
        $lessons = $info['lessons'];
        $viaRetake = false;

        $retake = $this->pickRetakeTab($html, $discipline, $lessons);
        if ($retake !== null) {
            $this->logger->info("Вкладка «{$retake['label']}» доступна — беру материалы оттуда: {$retake['url']}");
            try {
                $retakeHtml = $this->client->getHtml($retake['url']);
                $retakeInfo = $this->parser->disciplineLessons($retakeHtml);
                if (empty($retakeInfo['lessons'])) {
                    $this->logger->warn('Во вкладке пересдачи уроков не найдено — остаюсь на «Текущих»');
                } else {
                    $html = $retakeHtml;
                    $info = $retakeInfo;
                    $lessons = $retakeInfo['lessons'];
                    $viaRetake = true;
                    $stats['retakes']++;
                    // Пересдача открывает курс целиком — прежний отказ «требуется тест» больше не актуален
                    $this->state->clearDisciplineSkip($id);
                    $this->state->setDisciplineMeta($id, [
                        'retake_tab' => $retake['label'],
                        'retake_url' => $retake['url'],
                    ]);
                }
            } catch (\Throwable $e) {
                $this->logger->warn('Не открыть вкладку пересдачи: '.$e->getMessage());
            }
        }

        if (!$viaRetake && $this->state->isDisciplineSkipped($id)) {
            $this->logger->warn("Пропущена ранее: ".$title);
            return;
        }

        if (empty($lessons)) {
            $this->logger->warn("Уроков не найдено в HTML дисциплины");
            return;
        }

        $disciplineDir = sprintf(
            '%s/semester_%02d/%s',
            rtrim($this->outputDir, '/'),
            $semester['number'],
            Slug::make($title),
        );

        $this->logger->info(sprintf('Уроков: %d (заблокированных: %d)%s',
            count($lessons),
            count(array_filter($lessons, fn($l) => $l['locked'])),
            $viaRetake ? ' — набор пересдачи' : ''
        ));

        // Сохраняем метаданные ВСЕХ уроков (включая locked) до основной обработки
        foreach ($lessons as $lesson) {
            $rid = $lesson['resourceId'] !== '' ? $lesson['resourceId'] : ('item_'.$lesson['itemId']);
            $this->state->saveLessonInfo($id, $rid, [
                'code' => $lesson['code'],
                'title' => $lesson['title'],
                'lesson_type' => $lesson['type'],
                'locked' => $lesson['locked'],
                'locked_reason' => $lesson['lockedReason'],
                'required_minutes' => $lesson['requiredMinutes'],
            ]);
        }

        $disciplineDoneCount = 0;

        // Разбор урока — три запроса к LMS (2–3 с), закачка — минуты. Поэтому разбор
        // идёт не пакетами, а по требованию: очередь просит следующий урок, когда у неё
        // подходит к концу работа, и потоки не простаивают на хвосте пакета.
        $cursor = 0;
        $processed = 0;
        /** @var array<string, array<string, mixed>> $planByDest */
        $planByDest = [];
        /** @var array<int, array<string, mixed>> $allPlans */
        $allPlans = [];

        $refill = function () use (
            &$cursor, &$processed, &$planByDest, &$allPlans,
            $lessons, $id, $disciplineDir, $viaRetake, &$stats
        ): array {
            while ($cursor < count($lessons)) {
                $lesson = $lessons[$cursor];
                $index = $cursor;
                $cursor++;

                if ($this->maxLessons !== null && $processed >= $this->maxLessons) {
                    $this->logger->debug("Достигнут лимит --max-lessons={$this->maxLessons}");
                    return [];
                }

                $code = $lesson['code'];

                if ($lesson['locked']) {
                    $this->logger->warn("[$code] Заблокирован: ".($lesson['lockedReason'] ?? 'причина неизвестна'));
                    // В наборе пересдачи замков быть не должно; если это «Текущие» и замок
                    // из-за теста — дальше по дисциплине смысла нет.
                    if (!$viaRetake && $lesson['lockedReason'] && str_contains($lesson['lockedReason'], 'тест')) {
                        $this->logger->warn('Дисциплина требует прохождения теста — дальше не иду');
                        $this->state->markDisciplineSkipped($id, 'требуется тест: '.$lesson['lockedReason']);
                        return [];
                    }
                    continue;
                }

                if ($lesson['viewUrl'] === null || $lesson['resourceId'] === '') {
                    $this->logger->debug("[$code] Нет ссылки/resourceId, пропускаю");
                    continue;
                }

                // --redo нужен после расширения набора материалов: уроки, отмеченные done
                // прошлым прогоном, могли отдать только видео (или вообще ничего).
                if (!$this->redo && $this->state->isLessonDone($id, $lesson['resourceId'])) {
                    $this->logger->ok("[$code] Уже обработан ранее");
                    continue;
                }

                $processed++;

                // Смотрим вперёд: следующий заблокированный урок скажет, сколько минут нужно просмотреть
                $nextRequired = null;
                for ($j = $index + 1; $j < count($lessons); $j++) {
                    if ($lessons[$j]['locked'] && $lessons[$j]['requiredMinutes'] !== null) {
                        $nextRequired = $lessons[$j]['requiredMinutes'];
                        break;
                    }
                }

                $plan = $this->resolveLesson($disciplineDir, $code, $lesson['title'], $lesson, $stats);
                if ($plan === null) {
                    continue;
                }
                $plan['minutes'] = $nextRequired ?? $this->watchedMinutes;
                $allPlans[] = $plan;

                if ($plan['jobs'] === []) {
                    // Только внешние ссылки — качать нечего, но урок закрыть надо
                    continue;
                }

                foreach ($plan['jobs'] as $job) {
                    $planByDest[$job['dest']] = $plan;
                }

                return $plan['jobs'];
            }

            return [];
        };

        $results = $this->queue !== null
            ? $this->queue->run([], $refill)
            : $this->runSequentially($refill);

        foreach ($allPlans as $plan) {
            $this->commitLesson($id, $plan, $results, $stats, $disciplineDoneCount);
        }

        $this->logger->ok(sprintf(
            "Дисциплина '%s'%s: обработано %d уроков",
            $title,
            $viaRetake ? ' (пересдача)' : '',
            $disciplineDoneCount,
        ));
    }

    /**
     * Закрывает урок в state.json — только если забраны ВСЕ его материалы.
     *
     * Иначе, например, при обрыве видео при уже скачанном PDF, отметка done закрыла бы
     * урок навсегда и недокачанный файл никто бы не подобрал.
     *
     * @param array<string, mixed> $plan
     * @param array<string, bool> $results
     */
    private function commitLesson(string $disciplineId, array $plan, array $results, array &$stats, int &$doneCount): void
    {
        $failed = 0;
        foreach ($plan['jobs'] as $job) {
            if (($results[$job['dest']] ?? false) === true) {
                $stats['downloaded']++;
            } else {
                $failed++;
            }
        }

        if ($failed > 0) {
            $stats['failed'] += $failed;
            $this->logger->warn(sprintf(
                '[%s] Забрано %d из %d — оставляю урок незакрытым для повторного прохода',
                $plan['code'], count($plan['jobs']) - $failed, count($plan['jobs'])
            ));
            return;
        }

        if ($plan['jobs'] === [] && $plan['links'] === 0) {
            return;
        }

        if ($this->emulateWatch) {
            $this->markWatched($plan['code'], $plan['ctx'], $plan['referer'], $plan['minutes']);
        }

        $this->state->markLessonDone($disciplineId, $plan['resourceId'], [
            'code' => $plan['code'],
            'title' => $plan['title'],
            'files' => count($plan['jobs']),
            'links' => $plan['links'],
        ]);
        $doneCount++;
    }

    /**
     * Последовательная закачка — резервный путь, если очередь не задана (--threads=1).
     *
     * @param callable():array<int, array{url:string, dest:string, label:string}> $refill
     * @return array<string, bool>
     */
    private function runSequentially(callable $refill): array
    {
        $results = [];
        while (($jobs = $refill()) !== []) {
            foreach ($jobs as $job) {
                $results[$job['dest']] = $this->downloader->download($job['url'], $job['dest']);
            }
        }
        return $results;
    }

    /**
     * Решает, надо ли переключиться на вкладку «Пересдача», и возвращает её.
     *
     * @param array<int, array<string, mixed>> $lessons уроки активной вкладки
     * @return array{label:string, url:string, index:?int, active:bool, retake:bool}|null
     */
    private function pickRetakeTab(string $html, array $discipline, array $lessons): ?array
    {
        if ($this->retakeMode === self::RETAKE_OFF) {
            return null;
        }

        $tabs = $this->parser->disciplineTabs($html);
        $retakeTab = null;
        foreach ($tabs as $tab) {
            if ($tab['retake'] && !$tab['active']) {
                $retakeTab = $tab;
                break;
            }
        }
        if ($retakeTab === null) {
            return null;
        }

        if ($this->retakeMode === self::RETAKE_FORCE) {
            return $retakeTab;
        }

        // auto: переключаемся, только если «Текущие» реально урезаны
        $hasDebt = !empty($discipline['debt']);
        $hasLocked = (bool)array_filter($lessons, fn($l) => $l['locked']);

        return ($hasDebt || $hasLocked) ? $retakeTab : null;
    }

    /**
     * Разбирает урок и готовит план: что скачать и куда, сколько внешних ссылок записано.
     *
     * Сама закачка здесь не выполняется — она уходит в {@see DownloadQueue}, поэтому
     * файлы разных уроков тянутся параллельно.
     *
     * @return array{
     *     code:string, title:string, resourceId:string, ctx:array<string,mixed>,
     *     referer:string, links:int, jobs:array<int, array{url:string, dest:string, label:string}>
     * }|null
     */
    private function resolveLesson(
        string $disciplineDir,
        string $code,
        string $title,
        array $lesson,
        array &$stats,
    ): ?array {
        $this->logger->info("[$code] $title");

        try {
            // Открываем страницу урока — она перенаправит на /learning/view/...
            $lessonHtml = $this->client->getHtml($lesson['viewUrl'], [
                'Referer' => 'https://lms.synergy.ru/student/up',
            ]);
        } catch (\Throwable $e) {
            $this->logger->err("[$code] Ошибка открытия урока: ".$e->getMessage());
            $stats['failed']++;
            return null;
        }

        $ctx = $this->parser->learningContext($lessonHtml);
        if ($ctx['learningPackageId'] === '') {
            $this->logger->warn("[$code] Не нашёл learningPackageId — это, видимо, не видео-урок");
            $stats['skipped']++;
            return null;
        }

        if ($ctx['firstItemId'] === null) {
            $this->logger->warn("[$code] Нет tocItem — пропускаю");
            $stats['skipped']++;
            return null;
        }

        $referer = 'https://lms.synergy.ru/learning/view/'.$ctx['learningPackageId'];

        try {
            $opened = $this->scorm->chooseItem($ctx['firstItemId'], $ctx['learningPackageId'], $referer);
        } catch (\Throwable $e) {
            $this->logger->err("[$code] navigation_request: ".$e->getMessage());
            $stats['failed']++;
            return null;
        }

        $itemLink = $opened['itemLink'] ?? null;
        if ($itemLink === null) {
            $this->logger->warn("[$code] Сервер не отдал itemLink (возможно, тест/документ)");
            $stats['skipped']++;
            return null;
        }

        try {
            $itemHtml = $this->client->getHtml($itemLink, ['Referer' => $referer]);
        } catch (\Throwable $e) {
            $this->logger->err("[$code] Не открыть iframe: ".$e->getMessage());
            $stats['failed']++;
            return null;
        }

        $materials = $this->parser->materials($itemHtml);
        if ($materials === []) {
            $this->logger->warn("[$code] В материале нечего забрать (пустая страница или тест)");
            $stats['skipped']++;
            if ($this->emulateWatch) {
                $this->markWatched($code, $ctx, $referer, $this->watchedMinutes);
            }
            return null;
        }

        $base = Slug::make($code.' '.$title);
        $jobs = [];
        $links = 0;
        $fileIndex = 0;

        foreach ($materials as $material) {
            if ($material['kind'] === 'link') {
                $this->saveLink($disciplineDir, $code, $title, $material['url']);
                $links++;
                continue;
            }

            // Несколько файлов в одном материале — нумеруем, чтобы не перетирать друг друга
            $suffix = $fileIndex === 0 ? '' : '_'.$fileIndex;
            $fileIndex++;
            $ext = $material['ext'] ?: ($material['kind'] === 'video' ? 'mp4' : 'bin');

            $jobs[] = [
                'url' => $material['url'],
                'dest' => sprintf('%s/%s%s.%s', $disciplineDir, $base, $suffix, $ext),
                'label' => $code,
            ];
        }

        if ($links > 0) {
            $this->logger->ok("[$code] Внешних ссылок записано в materials.md: $links");
        }

        return [
            'code' => $code,
            'title' => $title,
            'resourceId' => $lesson['resourceId'],
            'ctx' => $ctx,
            'referer' => $referer,
            'links' => $links,
            'jobs' => $jobs,
        ];
    }

    /**
     * Внешние ссылки (ноутбуки Colab и прочее вне LMS) скачать нельзя — складываем
     * их в materials.md рядом с файлами дисциплины, чтобы набор был полным.
     */
    private function saveLink(string $disciplineDir, string $code, string $title, string $url): void
    {
        @mkdir($disciplineDir, 0o775, true);
        $path = $disciplineDir.'/materials.md';

        $line = sprintf('- [%s] %s — %s', $code, $title, $url);
        if (is_file($path) && str_contains((string)file_get_contents($path), $url)) {
            return;
        }
        if (!is_file($path)) {
            file_put_contents($path, "# Внешние материалы\n\nСсылки вне LMS — скачать нельзя, открывать вручную.\n\n");
        }
        file_put_contents($path, $line.PHP_EOL, FILE_APPEND);
    }

    private function markWatched(string $code, array $ctx, string $referer, int $minutes): void
    {
        try {
            $this->scorm->completeLesson(
                $ctx['firstItemId'],
                $ctx['learningPackageId'],
                $referer,
                $minutes,
            );
            $this->logger->ok("[$code] Отмечен просмотренным ($minutes мин)");
        } catch (\Throwable $e) {
            $this->logger->warn("[$code] Не получилось отметить просмотр: ".$e->getMessage());
        }
    }
}
