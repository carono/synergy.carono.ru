<?php

declare(strict_types=1);

namespace Carono\LmsParser;

use Symfony\Component\DomCrawler\Crawler;

final class Parser
{
    /**
     * @return array<int, array{number:int, label:string, current:bool, disciplines: array<int, array{id:string, title:string, contentsUrl:string}>}>
     */
    public function semesters(string $html): array
    {
        $crawler = new Crawler($html);
        $semesters = [];

        foreach ($crawler->filter('tbody.semester') as $node) {
            $cl = (string)$node->getAttribute('class');
            if (!preg_match('~\bs(\d+)\b~', $cl, $m)) {
                continue;
            }
            $number = (int)$m[1];
            $isCurrent = str_contains($cl, 'expanded');

            $tbody = new Crawler($node);

            $disciplines = [];
            foreach ($tbody->filter('tr.discipl') as $row) {
                $rowCrawler = new Crawler($row);
                $link = $rowCrawler->filter('a[href*="/lntools/versiongroupassign/contents/student/"]');
                if ($link->count() === 0) {
                    continue;
                }
                $href = $link->first()->attr('href') ?? '';
                if (!preg_match('~/lntools/versiongroupassign/contents/student/(\d+)~', $href, $mm)) {
                    continue;
                }
                $disciplines[] = [
                    'id' => $mm[1],
                    'title' => trim($link->first()->text()),
                    'contentsUrl' => $href,
                ];
            }

            $semesters[] = [
                'number' => $number,
                'label' => $number.' Семестр',
                'current' => $isCurrent,
                'disciplines' => $disciplines,
            ];
        }

        return $semesters;
    }

    /**
     * Из страницы дисциплины (после редиректа на /student/updiscipline/...) собираем список уроков.
     *
     * @return array{
     *     disciplineTitle: ?string,
     *     lessons: array<int, array{
     *         resourceId:string, itemId:string, type:string, code:string, title:string,
     *         locked:bool, lockedReason:?string, requiredMinutes:?int, viewUrl:?string
     *     }>
     * }
     */
    public function disciplineLessons(string $html): array
    {
        $crawler = new Crawler($html);

        $disciplineTitle = null;
        $titleNode = $crawler->filter('.packageTitle .uppercase, h1.uppercase, h1');
        if ($titleNode->count() > 0) {
            $disciplineTitle = trim($titleNode->first()->text());
        }

        $lessons = [];
        foreach ($crawler->filter('.frame-content .sidebar__wrap li[data-index], li[data-index]') as $li) {
            $liCrawler = new Crawler($li);
            $a = $liCrawler->filter('a')->first();
            if ($a->count() === 0) {
                continue;
            }

            $href = (string)($a->attr('href') ?? '');
            $class = (string)($a->attr('class') ?? '');
            $alt = (string)($a->attr('alt') ?? '');
            $type = (string)($a->attr('data-type') ?? '');
            $resourceId = (string)($a->attr('data-resource-id') ?? '');

            $spans = $liCrawler->filter('span');
            $code = $spans->count() > 0 ? trim($spans->eq(0)->text()) : '';
            $title = $spans->count() > 1 ? trim($spans->eq(1)->text()) : $code;

            $locked = str_contains($class, 'resourse_blocked');
            $requiredMinutes = null;
            $lockedReason = null;
            if ($locked && $alt !== '') {
                $lockedReason = $alt;
                if (preg_match('~просмотрен\s+(\d+)\s*\(минут\)~u', $alt, $mm)) {
                    $requiredMinutes = (int)$mm[1];
                }
            }

            $viewUrl = null;
            if ($href !== '' && preg_match('~/lntools/mcresource/view/(\d+)~', $href, $mm2)) {
                $viewUrl = $href;
                if ($resourceId === '') {
                    $resourceId = $mm2[1];
                }
            }

            // itemId из data-index (первичный TOC item id, который потом сравним со startItem)
            $itemId = (string)$li->getAttribute('data-index');

            if ($resourceId === '' && $viewUrl === null && !$locked) {
                continue;
            }

            $lessons[] = [
                'resourceId' => $resourceId,
                'itemId' => $itemId,
                'type' => $type,
                'code' => $code,
                'title' => $title,
                'locked' => $locked,
                'lockedReason' => $lockedReason,
                'requiredMinutes' => $requiredMinutes,
                'viewUrl' => $viewUrl,
            ];
        }

        return [
            'disciplineTitle' => $disciplineTitle,
            'lessons' => $lessons,
        ];
    }

    /**
     * Извлекает из HTML страницы /learning/view параметры:
     *  - learningPackageId, courseVersionId, courseUserId
     *  - первый tocItem id (item_NNN)
     *
     * @return array{learningPackageId:string, courseVersionId:string, courseUserId:string, scormCourseStatus:string, firstItemId:?string}
     */
    public function learningContext(string $html): array
    {
        $context = [
            'learningPackageId' => '',
            'courseVersionId' => '',
            'courseUserId' => '0',
            'scormCourseStatus' => '',
            'firstItemId' => null,
        ];

        if (preg_match('~php\s*=\s*(\{.+?\});~s', $html, $m)) {
            $php = json_decode($m[1], true) ?? [];
            $context['learningPackageId'] = (string)($php['learningPackageId'] ?? '');
            $context['courseVersionId'] = (string)($php['courseVersionId'] ?? '');
            $context['courseUserId'] = (string)($php['courseUserId'] ?? '0');
            $context['scormCourseStatus'] = (string)($php['scormCourseStatus'] ?? '');
        }

        // Берём первый tocItem с id != item_0 (item_0 — это блок, не пункт)
        if (preg_match_all('~<p\s+class="tocItem"\s+id="item_(\d+)"~', $html, $mm)) {
            foreach ($mm[1] as $id) {
                if ($id !== '0') {
                    $context['firstItemId'] = $id;
                    break;
                }
            }
        }

        return $context;
    }

    /**
     * Из HTML iframe-контента вытаскивает прямую ссылку на видео.
     */
    public function videoUrl(string $html): ?string
    {
        if (preg_match('~<source[^>]+src="([^"]+\.mp4[^"]*)"~i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('~<video[^>]+src="([^"]+\.mp4[^"]*)"~i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('~"([^"]+\.m3u8[^"]*)"~i', $html, $m)) {
            return $m[1];
        }
        return null;
    }
}
