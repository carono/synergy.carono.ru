<?php

declare(strict_types=1);

namespace Carono\LmsParser;

/**
 * Проверка целостности скачанного.
 *
 * Обрыв закачки не всегда виден по журналу: файл переименовывается в готовый, а внутри
 * половина видео без moov-атома или HTML-страница вместо PDF. Проверять надо содержимое.
 */
final class Verifier
{
    public const OK = 'ok';
    public const MISSING = 'отсутствует';
    public const EMPTY_FILE = 'пустой';
    public const HTML = 'HTML вместо файла';
    public const TRUNCATED = 'обрезан';
    public const BROKEN = 'не читается';
    public const MISLABELED = 'содержимое не того формата, что расширение';

    public function __construct(private readonly string $ffprobe = 'ffprobe')
    {
    }

    /**
     * Быстрая классификация по краям файла — без внешних утилит, поэтому проверяема тестами.
     *
     * @param string $head первые байты (достаточно 1 КБ)
     * @param string $tail последние байты (достаточно 1 КБ)
     */
    public static function classify(string $head, string $tail, int $size, string $ext): ?string
    {
        if ($size === 0) {
            return self::EMPTY_FILE;
        }

        // Страница входа или ошибки, сохранённая под именем материала.
        if (preg_match('~^\s*(<!doctype\s+html|<html|<\?xml[^>]*>\s*<!doctype\s+html)~i', $head)) {
            return self::HTML;
        }

        // LMS отдаёт часть материалов под чужим расширением: ноутбук Jupyter под именем
        // .pdf, например. Файл при этом целый, перекачивать нечего — его надо переименовать.
        $real = self::realExtension($head);
        // Сигнатура даёт формат-семейство, а не расширение: ноутбук и docx по краям
        // неотличимы от JSON и ZIP, и подменой это считать нельзя.
        $family = match ($ext) {
            'ipynb' => 'json',
            'docx', 'xlsx', 'pptx' => 'zip',
            default => $ext,
        };
        if ($real !== null && $real !== $family) {
            return self::MISLABELED;
        }

        return match ($ext) {
            // %%EOF в хвосте — единственный признак, что PDF дописан до конца.
            'pdf' => str_contains($tail, '%%EOF') ? self::OK : self::TRUNCATED,
            // Целостность архива решает центральный каталог — его проверяет ZipArchive.
            'zip' => null,
            'ipynb', 'json' => self::OK,
            // Для mp4 и всего остального краёв мало — решает внешняя проверка.
            default => null,
        };
    }

    /**
     * Формат по сигнатуре первых байтов. null — сигнатура незнакома (mp4, docx, всё прочее):
     * тогда судить о подмене расширения нельзя.
     */
    public static function realExtension(string $head): ?string
    {
        $trimmed = ltrim($head);
        return match (true) {
            str_starts_with($head, '%PDF-') => 'pdf',
            str_starts_with($head, "PK\x03\x04"), str_starts_with($head, "PK\x05\x06") => 'zip',
            str_starts_with($trimmed, '{'), str_starts_with($trimmed, '[') => 'json',
            default => null,
        };
    }

    /**
     * Полная проверка одного файла. Возвращает Verifier::OK или причину отказа.
     *
     * @param int|null $declared размер по данным сервера, если известен
     */
    public function inspect(string $path, ?int $declared = null): string
    {
        if (!is_file($path)) {
            return self::MISSING;
        }

        $size = (int)filesize($path);
        $head = (string)file_get_contents($path, false, null, 0, 1024);
        $tail = $size > 1024 ? (string)file_get_contents($path, false, null, $size - 1024, 1024) : $head;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $quick = self::classify($head, $tail, $size, $ext);
        if ($quick !== null && $quick !== self::OK) {
            return $quick;
        }

        if ($declared !== null && $declared > 0 && $size < $declared) {
            return self::TRUNCATED;
        }

        if ($quick === self::OK) {
            return self::OK;
        }

        return match ($ext) {
            'mp4', 'm4v', 'mov' => $this->probeVideo($path),
            'zip', 'docx', 'xlsx', 'pptx' => $this->probeZip($path),
            default => self::OK,
        };
    }

    /** ffprobe читает длительность, только если контейнер дописан до конца (есть moov). */
    private function probeVideo(string $path): string
    {
        $cmd = [$this->ffprobe, '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', $path];
        $process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return self::OK; // без ffprobe судить не о чем — не объявлять же файл битым
        }
        $out = trim((string)stream_get_contents($pipes[1]));
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $out !== '' && (float)$out > 0 ? self::OK : self::BROKEN;
    }

    /** Целостность архива видна по центральному каталогу — он лежит в конце файла. */
    private function probeZip(string $path): string
    {
        $zip = new \ZipArchive();
        $code = $zip->open($path, \ZipArchive::CHECKCONS);
        if ($code !== true) {
            return self::TRUNCATED;
        }
        $zip->close();
        return self::OK;
    }
}
