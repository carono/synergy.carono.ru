<?php

declare(strict_types=1);

namespace Carono\LmsParser;

use Symfony\Component\DomCrawler\Crawler;

final class Parser
{
    /**
     * @return array<int, array{
     *     number:int, label:string, current:bool, debt:bool, debtCount:int,
     *     disciplines: array<int, array{id:string, title:string, contentsUrl:string, debt:bool, control:?string}>
     * }>
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

            // Задолженности по семестру: иконка-алерт в строке заголовка + подсказка «Задолженностей : N»
            $debtCount = 0;
            $bubble = $tbody->filter('#bubble-semfail-'.$number);
            if ($bubble->count() > 0 && preg_match('~(\d+)~', $bubble->first()->text(), $mb)) {
                $debtCount = (int)$mb[1];
            }
            $semesterDebt = $debtCount > 0 || $tbody->filter('tr.semtab .icon-status-alert')->count() > 0;

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

                // Задолженность по дисциплине — иконка icon-upd-failed в столбце статуса
                $debt = $rowCrawler->filter('td.js-status .icon-upd-failed')->count() > 0;

                // Форма контроля — следующая ячейка после названия (Зачёт / Экзамен / Практика)
                $control = null;
                $cells = $rowCrawler->filter('td');
                if ($cells->count() > 2) {
                    $control = trim($cells->eq(2)->text()) ?: null;
                }

                $disciplines[] = [
                    'id' => $mm[1],
                    'title' => trim($link->first()->text()),
                    'contentsUrl' => $href,
                    'debt' => $debt,
                    'control' => $control,
                ];
            }

            $semesters[] = [
                'number' => $number,
                'label' => $number.' Семестр',
                'current' => $isCurrent,
                'debt' => $semesterDebt,
                'debtCount' => $debtCount,
                'disciplines' => $disciplines,
            ];
        }

        return $semesters;
    }

    /**
     * Вкладки учебных материалов на странице дисциплины (блок #tabs-events).
     *
     * Обычно их две: «Текущие» (основной набор, часть уроков заблокирована тестами и датами)
     * и «Пересдача» — тот же курс, открытый целиком на период ликвидации задолженности.
     *
     * @return array<int, array{label:string, url:string, index:?int, active:bool, retake:bool}>
     */
    public function disciplineTabs(string $html): array
    {
        $crawler = new Crawler($html);
        $tabs = [];

        foreach ($crawler->filter('#tabs-events .item') as $item) {
            $itemCrawler = new Crawler($item);
            $a = $itemCrawler->filter('a')->first();
            if ($a->count() === 0) {
                continue;
            }

            $url = (string)($a->attr('href') ?? '');
            if ($url === '') {
                continue;
            }
            // В href есть якорь #content — для запроса он не нужен
            $url = strtok($url, '#') ?: $url;

            $label = trim($a->text());
            $active = str_contains((string)$item->getAttribute('class'), 'active');

            // Номер вкладки — последний числовой сегмент пути:
            // /lntools/versiongroupassign/contents/student/{disciplineId}/{tab}
            // /student/updiscipline/{package}/{version}/{n}/{tab}
            $index = null;
            if (preg_match('~/(\d+)$~', $url, $m)) {
                $index = (int)$m[1];
            }

            $retake = (bool)preg_match('~пересдач~ui', $label) || ($index !== null && $index > 1);

            $tabs[] = [
                'label' => $label,
                'url' => $url,
                'index' => $index,
                'active' => $active,
                'retake' => $retake,
            ];
        }

        return $tabs;
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

    /** Расширения, которые считаем скачиваемым учебным файлом. */
    private const FILE_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx',
        'zip', 'rar', '7z', 'ipynb', 'csv', 'txt', 'rtf', 'mp3', 'djvu',
    ];

    /**
     * Всё, что можно забрать из материала урока.
     *
     * Видеоурок отдаёт `<video>`/`<source>`, а конспекты, практические задания и
     * дополнительные материалы — страницу с одной ссылкой: либо на файл в CDN
     * (`v.lscdn.ru/....pdf`, `e-biblio.ru/....zip`), либо на внешний ресурс
     * (ноутбук Google Colab). Файлы качаем, внешние ссылки только записываем.
     *
     * @return array<int, array{kind:string, url:string, ext:?string}>
     */
    public function materials(string $html): array
    {
        $materials = [];
        $seen = [];

        $add = static function (string $kind, string $url, ?string $ext) use (&$materials, &$seen): void {
            $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($url === '' || isset($seen[$url]) || str_starts_with($url, '#')) {
                return;
            }
            $seen[$url] = true;
            $materials[] = ['kind' => $kind, 'url' => $url, 'ext' => $ext];
        };

        if (($video = $this->videoUrl($html)) !== null) {
            $ext = strtolower(pathinfo((string)parse_url($video, PHP_URL_PATH), PATHINFO_EXTENSION));
            $add('video', $video, $ext !== '' ? $ext : 'mp4');
        }

        // Разбираем только <body>: в <head> лежат стили и скрипты самой LMS
        $body = $html;
        $bodyStart = stripos($html, '<body');
        if ($bodyStart !== false) {
            $body = substr($html, $bodyStart);
        }
        $body = (string)preg_replace('~(?s)<script.*?</script>~i', '', $body);

        $extensions = implode('|', self::FILE_EXTENSIONS);
        if (preg_match_all('~(?:href|src)="([^"]+\.('.$extensions.'))(?:\?[^"]*)?"~i', $body, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $add('file', $m[1], strtolower($m[2]));
            }
        }

        // Остальные внешние ссылки — Colab и прочие ресурсы вне LMS
        if (preg_match_all('~href="(https?://[^"]+)"~i', $body, $mm)) {
            foreach ($mm[1] as $url) {
                if (isset($seen[html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8')])) {
                    continue;
                }
                $host = (string)parse_url($url, PHP_URL_HOST);
                if ($host === '' || str_contains($host, 'lms.synergy.ru')) {
                    continue;
                }
                $add('link', $url, null);
            }
        }

        return $materials;
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
