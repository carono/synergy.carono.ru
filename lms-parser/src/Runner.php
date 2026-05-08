<?php

declare(strict_types=1);

namespace Carono\LmsParser;

final class Runner
{
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

        $stats = ['downloaded' => 0, 'skipped' => 0, 'failed' => 0, 'disciplines' => 0];

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
            'Готово. Дисциплин: %d, скачано: %d, пропущено: %d, ошибок: %d',
            $stats['disciplines'], $stats['downloaded'], $stats['skipped'], $stats['failed']
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

        if ($this->state->isDisciplineSkipped($id)) {
            $this->logger->warn("Пропущена ранее: ".$title);
            return;
        }

        try {
            $html = $this->client->getHtml($discipline['contentsUrl']);
        } catch (\Throwable $e) {
            $this->logger->err("Не открыть дисциплину: ".$e->getMessage());
            $stats['failed']++;
            return;
        }

        $info = $this->parser->disciplineLessons($html);
        $lessons = $info['lessons'];

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

        $this->logger->info(sprintf('Уроков: %d (заблокированных: %d)',
            count($lessons),
            count(array_filter($lessons, fn($l) => $l['locked']))
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
        $disciplineProcessed = 0;

        foreach ($lessons as $lesson) {
            if ($this->maxLessons !== null && $disciplineProcessed >= $this->maxLessons) {
                $this->logger->debug("Достигнут лимит --max-lessons={$this->maxLessons}, перехожу к следующей дисциплине");
                break;
            }
            $disciplineProcessed++;
            $code = $lesson['code'];
            $title = $lesson['title'];

            if ($lesson['locked']) {
                $this->logger->warn("[$code] Заблокирован: ".($lesson['lockedReason'] ?? 'причина неизвестна'));
                // Если урок требует пройти тест — выходим из дисциплины (для последующих уроки тоже бесполезно дёргать).
                if ($lesson['lockedReason'] && str_contains($lesson['lockedReason'], 'тест')) {
                    $this->logger->warn("Дисциплина требует прохождения теста — пропускаю целиком");
                    $this->state->markDisciplineSkipped($id, 'требуется тест: '.$lesson['lockedReason']);
                    return;
                }
                // Иначе пропускаем урок — может быть, разблокируется при следующем проходе.
                continue;
            }

            if ($lesson['viewUrl'] === null || $lesson['resourceId'] === '') {
                $this->logger->debug("[$code] Нет ссылки/resourceId, пропускаю");
                continue;
            }

            if ($this->state->isLessonDone($id, $lesson['resourceId'])) {
                $this->logger->ok("[$code] Уже обработан ранее");
                $disciplineDoneCount++;
                continue;
            }

            // Смотрим вперёд: следующий заблокированный урок скажет, сколько минут нужно просмотреть
            $nextRequired = null;
            for ($j = $disciplineProcessed; $j < count($lessons); $j++) {
                if ($lessons[$j]['locked'] && $lessons[$j]['requiredMinutes'] !== null) {
                    $nextRequired = $lessons[$j]['requiredMinutes'];
                    break;
                }
            }
            $minutesForThis = $nextRequired ?? $this->watchedMinutes;

            $ok = $this->processLesson($disciplineDir, $code, $title, $lesson, $stats, $minutesForThis);
            if ($ok) {
                $this->state->markLessonDone($id, $lesson['resourceId'], [
                    'code' => $code,
                    'title' => $title,
                ]);
                $disciplineDoneCount++;
            }
        }

        $this->logger->ok("Дисциплина '$title': обработано $disciplineDoneCount уроков");
    }

    private function processLesson(string $disciplineDir, string $code, string $title, array $lesson, array &$stats, int $watchMinutes): bool
    {
        $this->logger->info("[$code] $title");

        try {
            // Открываем страницу урока — она перенаправит на /learning/view/...
            $lessonHtml = $this->client->getHtml($lesson['viewUrl'], [
                'Referer' => 'https://lms.synergy.ru/student/up',
            ]);
        } catch (\Throwable $e) {
            $this->logger->err("[$code] Ошибка открытия урока: ".$e->getMessage());
            $stats['failed']++;
            return false;
        }

        $ctx = $this->parser->learningContext($lessonHtml);
        if ($ctx['learningPackageId'] === '') {
            $this->logger->warn("[$code] Не нашёл learningPackageId — это, видимо, не видео-урок");
            $stats['skipped']++;
            return false;
        }

        if ($ctx['firstItemId'] === null) {
            $this->logger->warn("[$code] Нет tocItem — пропускаю");
            $stats['skipped']++;
            return false;
        }

        $referer = 'https://lms.synergy.ru/learning/view/'.$ctx['learningPackageId'];

        // 1. Получаем itemLink через navigation_request/choice
        try {
            $opened = $this->scorm->chooseItem($ctx['firstItemId'], $ctx['learningPackageId'], $referer);
        } catch (\Throwable $e) {
            $this->logger->err("[$code] navigation_request: ".$e->getMessage());
            $stats['failed']++;
            return false;
        }

        $itemLink = $opened['itemLink'] ?? null;
        if ($itemLink === null) {
            $this->logger->warn("[$code] Сервер не отдал itemLink (возможно, тест/документ)");
            $stats['skipped']++;
            return false;
        }

        // 2. Получаем содержимое iframe
        try {
            $itemHtml = $this->client->getHtml($itemLink, ['Referer' => $referer]);
        } catch (\Throwable $e) {
            $this->logger->err("[$code] Не открыть iframe: ".$e->getMessage());
            $stats['failed']++;
            return false;
        }

        $videoUrl = $this->parser->videoUrl($itemHtml);
        if ($videoUrl === null) {
            $this->logger->warn("[$code] Нет видео в материале (текст/презентация/тест)");
            $stats['skipped']++;
            // Всё равно отметим просмотренным, чтобы разблокировать следующий урок
            if ($this->emulateWatch) {
                $this->markWatched($code, $ctx, $referer, $watchMinutes);
            }
            return true;
        }

        // 3. Скачиваем видео
        $ext = pathinfo(parse_url($videoUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'mp4';
        $filename = sprintf('%s.%s', Slug::make($code.' '.$title), $ext);
        $destPath = $disciplineDir.'/'.$filename;

        $ok = $this->downloader->download($videoUrl, $destPath);
        if (!$ok) {
            $stats['failed']++;
            return false;
        }
        $stats['downloaded']++;

        // 4. Эмулируем просмотр и завершение, чтобы разблокировать следующий урок
        if ($this->emulateWatch) {
            $this->markWatched($code, $ctx, $referer, $watchMinutes);
        }

        return true;
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
