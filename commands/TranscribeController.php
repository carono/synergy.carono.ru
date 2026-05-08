<?php

namespace app\commands;

use app\models\LmsVideo;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Транскрибация видео через Whisper ASR.
 *
 * Обрабатывает по одному: берёт видео с file_path != null и transcript_raw IS NULL,
 * отправляет файл на whisper.carono.site, сохраняет сырую и обработанную транскрипцию.
 */
class TranscribeController extends Controller
{
    /** Обработать не более N видео за запуск (0 = без ограничений) */
    public int $limit = 1;

    /** Обработать конкретное видео по id */
    public ?int $videoId = null;

    /** Принудительно перетранскрибировать даже если transcript_raw уже есть */
    public bool $force = false;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['limit', 'videoId', 'force']);
    }

    /**
     * Транскрибировать видео.
     *
     * Пример: php yii transcribe/run
     *         php yii transcribe/run --limit=5
     *         php yii transcribe/run --video-id=12
     *         php yii transcribe/run --force --video-id=12
     */
    public function actionRun(): int
    {
        $token = (string)(Yii::$app->params['whisperToken'] ?? '');
        $url   = (string)(Yii::$app->params['whisperUrl'] ?? '');

        if ($token === '' || $url === '') {
            $this->stderr("Не настроены params.whisperToken / params.whisperUrl\n", Console::FG_RED);
            return ExitCode::CONFIG;
        }

        $parserDir = Yii::getAlias('@app/lms-parser');
        $downloadsDir = $parserDir . '/downloads';

        $query = LmsVideo::find()
            ->andWhere(['not', ['file_path' => null]])
            ->andWhere(['status' => 'downloaded']);

        if ($this->videoId !== null) {
            $query->andWhere(['id' => $this->videoId]);
        } elseif (!$this->force) {
            $query->andWhere(['transcript_raw' => null]);
        }

        if ($this->limit > 0 && $this->videoId === null) {
            $query->limit($this->limit);
        }

        $videos = $query->all();

        if (empty($videos)) {
            $this->stdout("Нет видео для транскрибации.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout(sprintf("Найдено: %d видео\n", count($videos)), Console::FG_GREEN);

        $ok = 0;
        $fail = 0;

        foreach ($videos as $video) {
            /** @var LmsVideo $video */
            $filePath = $downloadsDir . '/' . $video->file_path;

            if (!is_file($filePath)) {
                $this->stderr(sprintf("[%d] Файл не найден: %s\n", $video->id, $video->file_path), Console::FG_RED);
                $fail++;
                continue;
            }

            $size = round(filesize($filePath) / 1024 / 1024, 1);
            $this->stdout(sprintf("[%d] %s — %s (%.1f МБ)... ", $video->id, $video->code, $video->title, $size));

            $result = $this->transcribe($filePath, $url, $token);

            if (!$result['ok']) {
                $this->stdout("ОШИБКА\n", Console::FG_RED);
                $this->stderr("  → " . $result['error'] . "\n", Console::FG_RED);
                $fail++;
                continue;
            }

            $raw = $result['text'];
            $video->transcript_raw = $raw;
            $video->transcript_clean = $this->buildClean($raw);
            $video->transcript_summary = null;
            $video->transcribed_at = date('Y-m-d H:i:s');

            if (!$video->save(false)) {
                $this->stdout("ОШИБКА СОХРАНЕНИЯ\n", Console::FG_RED);
                $this->stderr("  → " . json_encode($video->errors, JSON_UNESCAPED_UNICODE) . "\n", Console::FG_RED);
                $fail++;
                continue;
            }

            $words = str_word_count($raw);
            $this->stdout(sprintf("ОК (~%d слов)\n", $words), Console::FG_GREEN);
            $ok++;
        }

        $this->stdout(sprintf("\nГотово. Успешно: %d, ошибок: %d\n", $ok, $fail), Console::FG_GREEN);
        return $fail > 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }

    /**
     * Отправляет файл на Whisper ASR, возвращает ['ok' => bool, 'text' => string, 'error' => string].
     * Предварительно извлекает аудиодорожку через ffmpeg — encode=true у Whisper ненадёжен с MP4.
     */
    private function transcribe(string $filePath, string $url, string $token): array
    {
        $wavPath = sys_get_temp_dir() . '/whisper_' . md5($filePath) . '.wav';

        $ffmpegResult = $this->extractAudio($filePath, $wavPath);
        if (!$ffmpegResult['ok']) {
            return $ffmpegResult;
        }

        try {
            return $this->sendToWhisper($wavPath, $url, $token);
        } finally {
            @unlink($wavPath);
        }
    }

    private function extractAudio(string $videoPath, string $wavPath): array
    {
        $cmd = sprintf(
            'ffmpeg -y -i %s -vn -ar 16000 -ac 1 -c:a pcm_s16le %s 2>&1',
            escapeshellarg($videoPath),
            escapeshellarg($wavPath)
        );
        exec($cmd, $out, $ret);

        if ($ret !== 0) {
            return ['ok' => false, 'error' => 'ffmpeg: ' . implode(' | ', array_slice($out, -3)), 'text' => ''];
        }

        return ['ok' => true, 'error' => '', 'text' => ''];
    }

    private function sendToWhisper(string $audioPath, string $url, string $token): array
    {
        $endpoint = rtrim($url, '/') . '?task=transcribe&output=json&encode=false';

        $cfile = new \CURLFile($audioPath, 'audio/wav', 'audio.wav');

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
            CURLOPT_POSTFIELDS     => ['audio_file' => $cfile],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 600,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['ok' => false, 'error' => 'curl: ' . $cerr, 'text' => ''];
        }
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => "HTTP $code: " . substr((string)$body, 0, 300), 'text' => ''];
        }

        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'invalid JSON: ' . substr((string)$body, 0, 300), 'text' => ''];
        }

        $text = trim((string)($data['text'] ?? ''));
        if ($text === '') {
            return ['ok' => false, 'error' => 'пустой ответ от Whisper (lang=' . ($data['language'] ?? '?') . ')', 'text' => ''];
        }

        return ['ok' => true, 'text' => $text, 'error' => ''];
    }

    /**
     * Форматирует сырой текст для чтения: разбивает на абзацы по ~5 предложений.
     */
    private function buildClean(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        // Разбиваем по границам предложений (. ! ?)
        $sentences = preg_split('/(?<=[.!?])\s+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if ($sentences === false || count($sentences) <= 1) {
            return $raw;
        }

        $paragraphs = [];
        $chunk = [];
        foreach ($sentences as $i => $sentence) {
            $chunk[] = $sentence;
            if (count($chunk) >= 5) {
                $paragraphs[] = implode(' ', $chunk);
                $chunk = [];
            }
        }
        if (!empty($chunk)) {
            $paragraphs[] = implode(' ', $chunk);
        }

        return implode("\n\n", $paragraphs);
    }

}
