<?php

declare(strict_types=1);

namespace Carono\LmsParser;

use DateTimeImmutable;

final class Schedule
{
    /**
     * Календарный график обучения (страница /students/calend).
     *
     * @return array<int, array{
     *   course: int|null, semester: int, start: DateTimeImmutable, end: DateTimeImmutable, event: string
     * }>
     */
    public function calendar(string $html): array
    {
        $html = self::stripScripts($html);
        if (!preg_match_all('~<tr[\s\S]*?</tr>~i', $html, $rm)) {
            return [];
        }

        $events = [];
        $course = null;
        foreach ($rm[0] as $tr) {
            preg_match_all('~<t[dh][^>]*>([\s\S]*?)</t[dh]>~i', $tr, $cm);
            if (!$cm[1]) {
                continue;
            }
            $cells = array_map([self::class, 'cellText'], $cm[1]);

            // Строка «N курс» — обычно один-два td с текстом
            $joined = trim(implode(' ', array_filter($cells, fn($c) => $c !== '')));
            if (preg_match('~^(\d+)\s*курс$~iu', $joined, $m)) {
                $course = (int)$m[1];
                continue;
            }

            // Строка с периодом: семестр | start | end | мероприятие
            $dates = [];
            $semester = null;
            $event = null;
            foreach ($cells as $c) {
                if (preg_match('~^(\d{2})\.(\d{2})\.(\d{4})$~', $c)) {
                    $dt = DateTimeImmutable::createFromFormat('!d.m.Y', $c);
                    if ($dt instanceof DateTimeImmutable) {
                        $dates[] = $dt;
                    }
                } elseif ($semester === null && preg_match('~^\d{1,2}$~', $c)) {
                    $semester = (int)$c;
                } elseif ($c !== '' && !preg_match('~^семестр$|^начало$|^окончание$|^мероприятие$~iu', $c)) {
                    $event = $c;
                }
            }

            if (count($dates) !== 2 || $semester === null || $event === null) {
                continue;
            }

            $events[] = [
                'course' => $course,
                'semester' => $semester,
                'start' => $dates[0],
                'end' => $dates[1],
                'event' => $event,
            ];
        }

        return $events;
    }

    /**
     * Список занятий из вкладок расписания (/schedule/academ, /schedule/webinar и др.).
     * Возвращает массив строк-ячеек таблицы без шапок и заглушек.
     *
     * @return array<int, array<int, string>>
     */
    public function classes(string $html): array
    {
        $html = self::stripScripts($html);
        if (!preg_match_all('~<tr[\s\S]*?</tr>~i', $html, $rm)) {
            return [];
        }
        $rows = [];
        foreach ($rm[0] as $tr) {
            preg_match_all('~<td[^>]*>([\s\S]*?)</td>~i', $tr, $cm);
            if (count($cm[1]) < 3) {
                continue;
            }
            $cells = array_map([self::class, 'cellText'], $cm[1]);
            $joined = implode(' | ', $cells);
            if ($joined === '' || mb_strlen($joined) < 5) {
                continue;
            }
            if (stripos($joined, 'ничего не найдено') !== false
                || stripos($joined, 'По вашему запросу') !== false) {
                continue;
            }
            $rows[] = $cells;
        }
        return $rows;
    }

    /**
     * @param array<int, array{start: DateTimeImmutable, end: DateTimeImmutable}> $events
     */
    public function findCurrent(array $events, DateTimeImmutable $today): ?array
    {
        foreach ($events as $e) {
            if ($e['start'] <= $today && $today <= $e['end']) {
                return $e;
            }
        }
        return null;
    }

    /**
     * Ближайшая будущая сессия (events с 'event', содержащим «сесси»).
     *
     * @param array<int, array{event: string, start: DateTimeImmutable, end: DateTimeImmutable}> $events
     */
    public function findUpcomingSession(array $events, DateTimeImmutable $today): ?array
    {
        foreach ($events as $e) {
            if (stripos($e['event'], 'сесси') === false) {
                continue;
            }
            if ($e['end'] >= $today) {
                return $e;
            }
        }
        return null;
    }

    private static function stripScripts(string $html): string
    {
        $html = preg_replace('~<script[\s\S]*?</script>~i', '', $html);
        return preg_replace('~<style[\s\S]*?</style>~i', '', $html);
    }

    private static function cellText(string $cell): string
    {
        $c = preg_replace('~<[^>]+>~', ' ', $cell);
        $c = html_entity_decode($c, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('~\s+~u', ' ', $c));
    }
}
