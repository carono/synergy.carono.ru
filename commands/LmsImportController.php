<?php

namespace app\commands;

use app\models\LmsDiscipline;
use app\models\LmsVideo;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Импорт результатов работы lms-parser в БД.
 *
 * Источник правды:
 *  - lms-parser/state.json — журнал прогресса (дисциплины, уроки, статус done)
 *  - lms-parser/downloads/  — собственно скачанные файлы (имя файла = slug кода и заголовка)
 */
class LmsImportController extends Controller
{
    /** @var string Каталог с парсером (с корневой структурой downloads/, state.json) */
    public $parserDir = '@app/lms-parser';

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['parserDir']);
    }

    public function actionIndex(): int
    {
        return $this->actionRun();
    }

    /**
     * Импортирует state.json и сканирует каталог downloads/.
     */
    public function actionRun(): int
    {
        $root = Yii::getAlias($this->parserDir);
        $stateFile = $root.'/state.json';
        $downloadsDir = $root.'/downloads';

        if (!is_file($stateFile)) {
            $this->stderr("Не найден $stateFile\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $state = json_decode((string)file_get_contents($stateFile), true);
        if (!is_array($state)) {
            $this->stderr("state.json повреждён\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $semester = (int)($state['summary']['semester'] ?? 0);
        if ($semester <= 0) {
            $this->stdout("В state.json не указан semester — попробую угадать по downloads/\n", Console::FG_YELLOW);
        }

        $disciplines = $state['disciplines'] ?? [];

        $stats = ['disciplines' => 0, 'videos_new' => 0, 'videos_upd' => 0];

        $disciplineFiles = $this->scanDownloads($downloadsDir);

        foreach ($disciplines as $disciplineLmsId => $info) {
            $disciplineLmsId = (string)$disciplineLmsId;
            $title = (string)($info['title'] ?? '');
            $disciplineSemester = (int)($info['semester'] ?? $semester);

            $files = $this->matchDisciplineFiles($disciplineFiles, $disciplineSemester, $title);

            if ($title === '' && !empty($files)) {
                $title = $this->humanizeSlug($files['_slug'] ?? '');
            }
            if ($title === '') {
                $title = 'Дисциплина '.$disciplineLmsId;
            }

            $slug = $files['_slug'] ?? self::slug($title);

            $discipline = LmsDiscipline::find()->andWhere(['lms_id' => $disciplineLmsId])->one();
            if ($discipline === null) {
                $discipline = new LmsDiscipline();
                $discipline->lms_id = $disciplineLmsId;
            }
            $discipline->semester = $disciplineSemester ?: 0;
            $discipline->title = $title;
            $discipline->slug = $slug;
            if (!$discipline->save()) {
                $this->stderr("Дисциплина $disciplineLmsId: ".json_encode($discipline->errors, JSON_UNESCAPED_UNICODE)."\n", Console::FG_RED);
                continue;
            }
            $stats['disciplines']++;

            foreach ((array)($info['lessons'] ?? []) as $resourceId => $lesson) {
                $resourceId = (string)$resourceId;
                $code = (string)($lesson['code'] ?? '');
                $lessonTitle = (string)($lesson['title'] ?? '');
                $done = (bool)($lesson['done'] ?? false);
                $locked = (bool)($lesson['locked'] ?? false);
                $lessonType = (string)($lesson['lesson_type'] ?? '');
                $lockedReason = isset($lesson['locked_reason']) && $lesson['locked_reason'] !== null ? (string)$lesson['locked_reason'] : null;
                $requiredMinutes = isset($lesson['required_minutes']) ? (int)$lesson['required_minutes'] : null;

                $video = LmsVideo::find()
                    ->andWhere(['discipline_id' => $discipline->id, 'lms_resource_id' => $resourceId])
                    ->one();
                $isNew = $video === null;
                if ($isNew) {
                    $video = new LmsVideo();
                    $video->discipline_id = $discipline->id;
                    $video->lms_resource_id = $resourceId;
                }
                $video->code = $code;
                $video->title = $lessonTitle !== '' ? $lessonTitle : ($code !== '' ? $code : 'Урок '.$resourceId);
                $video->lesson_type = $lessonType !== '' ? $lessonType : null;
                $video->locked_reason = $lockedReason !== '' ? $lockedReason : null;
                $video->required_minutes = $requiredMinutes;

                $matched = $this->matchLessonFile($files, $code, $lessonTitle);
                if ($matched !== null) {
                    $video->file_path = $matched['rel'];
                    $video->file_size = $matched['size'];
                    $video->status = 'downloaded';
                    if ($video->downloaded_at === null) {
                        $video->downloaded_at = date('Y-m-d H:i:s', $matched['mtime']);
                    }
                } elseif ($done) {
                    if (!in_array($video->status, ['downloaded', 'watched'], true)) {
                        $video->status = 'watched';
                    }
                } elseif ($locked) {
                    $video->status = 'locked';
                } else {
                    if (!in_array($video->status, ['downloaded', 'watched'], true)) {
                        $video->status = 'no_video';
                    }
                }

                if (!$video->save()) {
                    $this->stderr("Видео $resourceId: ".json_encode($video->errors, JSON_UNESCAPED_UNICODE)."\n", Console::FG_RED);
                    continue;
                }
                $isNew ? $stats['videos_new']++ : $stats['videos_upd']++;
            }
        }

        $this->stdout(sprintf(
            "Импорт завершён. Дисциплин: %d, видео: +%d новых, %d обновлено\n",
            $stats['disciplines'], $stats['videos_new'], $stats['videos_upd']
        ), Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Скан каталога downloads/ → структура [semester => [disciplineSlug => [files...]]]
     */
    private function scanDownloads(string $downloadsDir): array
    {
        $result = [];
        if (!is_dir($downloadsDir)) {
            return $result;
        }
        foreach (glob($downloadsDir.'/semester_*', GLOB_ONLYDIR) ?: [] as $semDir) {
            if (!preg_match('~semester_(\d+)$~', $semDir, $m)) {
                continue;
            }
            $sem = (int)$m[1];
            foreach (glob($semDir.'/*', GLOB_ONLYDIR) ?: [] as $disDir) {
                $slug = basename($disDir);
                $files = [];
                foreach (glob($disDir.'/*') ?: [] as $f) {
                    if (!is_file($f)) {
                        continue;
                    }
                    $files[] = [
                        'name' => basename($f),
                        'rel' => 'semester_'.sprintf('%02d', $sem).'/'.$slug.'/'.basename($f),
                        'size' => filesize($f) ?: 0,
                        'mtime' => filemtime($f) ?: time(),
                    ];
                }
                $result[$sem][$slug] = $files;
            }
        }
        return $result;
    }

    /**
     * Возвращает массив файлов для дисциплины + её _slug.
     */
    private function matchDisciplineFiles(array $disciplineFiles, int $semester, string $title): array
    {
        $titleSlug = self::slug($title);
        $bySemester = $disciplineFiles[$semester] ?? [];

        if ($titleSlug !== '' && isset($bySemester[$titleSlug])) {
            return ['_slug' => $titleSlug, 'files' => $bySemester[$titleSlug]];
        }
        // Fallback: если не нашли по названию, но в семестре одна дисциплина — возьмём её
        if (count($bySemester) === 1) {
            $only = array_key_first($bySemester);
            return ['_slug' => $only, 'files' => $bySemester[$only]];
        }
        return ['_slug' => $titleSlug !== '' ? $titleSlug : '', 'files' => []];
    }

    /**
     * Подбирает файл урока по slug "{code} {title}".
     */
    private function matchLessonFile(array $disciplineMatch, string $code, string $title): ?array
    {
        $files = $disciplineMatch['files'] ?? [];
        if (empty($files)) {
            return null;
        }
        $expected = self::slug(trim($code.' '.$title));
        foreach ($files as $f) {
            $base = pathinfo($f['name'], PATHINFO_FILENAME);
            if (self::slug($base) === $expected) {
                return $f;
            }
        }
        // Пробуем по префиксу с кодом
        if ($code !== '') {
            $codeSlug = self::slug($code);
            foreach ($files as $f) {
                $base = pathinfo($f['name'], PATHINFO_FILENAME);
                if (str_starts_with(self::slug($base), $codeSlug)) {
                    return $f;
                }
            }
        }
        return null;
    }

    private function humanizeSlug(string $slug): string
    {
        if ($slug === '') {
            return '';
        }
        $s = str_replace(['_', '-'], ' ', $slug);
        return mb_convert_case(trim($s), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Транслитерация и нормализация — повторяет правила lms-parser/src/Slug.php,
     * чтобы slug-имена дисциплин и файлов совпадали один-в-один.
     */
    private static function slug(string $text): string
    {
        static $tr = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = strtr($text, $tr);
        $text = preg_replace('~[^a-z0-9._-]+~u', '_', $text) ?? $text;
        $text = preg_replace('~_+~', '_', $text) ?? $text;
        $text = trim($text, '_');
        return $text === '' ? 'item' : substr($text, 0, 100);
    }
}
