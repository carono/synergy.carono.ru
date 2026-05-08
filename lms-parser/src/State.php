<?php

declare(strict_types=1);

namespace Carono\LmsParser;

final class State
{
    private array $data;

    public function __construct(private string $path)
    {
        $this->data = is_file($path)
            ? (json_decode((string)file_get_contents($path), true) ?? [])
            : [];
    }

    public function isLessonDone(string $disciplineId, string $resourceId): bool
    {
        return !empty($this->data['disciplines'][$disciplineId]['lessons'][$resourceId]['done']);
    }

    public function setDisciplineMeta(string $disciplineId, array $meta): void
    {
        foreach ($meta as $k => $v) {
            $this->data['disciplines'][$disciplineId][$k] = $v;
        }
        $this->save();
    }

    public function saveLessonInfo(string $disciplineId, string $resourceId, array $info): void
    {
        $existing = $this->data['disciplines'][$disciplineId]['lessons'][$resourceId] ?? [];
        $this->data['disciplines'][$disciplineId]['lessons'][$resourceId] = array_merge($info, $existing);
        $this->save();
    }

    public function markLessonDone(string $disciplineId, string $resourceId, array $info): void
    {
        $this->data['disciplines'][$disciplineId]['lessons'][$resourceId] = $info + ['done' => true];
        $this->save();
    }

    public function isDisciplineSkipped(string $disciplineId): bool
    {
        return !empty($this->data['disciplines'][$disciplineId]['skipped']);
    }

    public function markDisciplineSkipped(string $disciplineId, string $reason): void
    {
        $this->data['disciplines'][$disciplineId]['skipped'] = true;
        $this->data['disciplines'][$disciplineId]['skip_reason'] = $reason;
        $this->save();
    }

    public function setSummary(array $summary): void
    {
        $this->data['summary'] = $summary;
        $this->save();
    }

    public function save(): void
    {
        @mkdir(dirname($this->path), 0o775, true);
        file_put_contents(
            $this->path,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
