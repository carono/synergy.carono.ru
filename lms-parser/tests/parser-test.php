#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Проверка Parser на сохранённых фрагментах реальных страниц LMS.
 *
 * Полноценного тест-раннера в парсере нет, поэтому это самодостаточный скрипт:
 *   php tests/parser-test.php
 * Возвращает 0, если все проверки прошли, иначе 1 и список расхождений.
 */

use Carono\LmsParser\Parser;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

$failed = 0;
$passed = 0;

function check(string $what, mixed $actual, mixed $expected): void
{
    global $failed, $passed;
    if ($actual === $expected) {
        $passed++;
        return;
    }
    $failed++;
    fwrite(STDERR, sprintf(
        "FAIL %s\n  ожидалось: %s\n  получено:  %s\n",
        $what,
        var_export($expected, true),
        var_export($actual, true),
    ));
}

$parser = new Parser();
$fixtures = $root.'/tests/fixtures';

// --- semesters(): задолженности семестра и дисциплин ---
$semesters = $parser->semesters((string)file_get_contents($fixtures.'/student_up_semesters.html'));
check('semesters(): найдено семестров', count($semesters), 2);

$byNumber = [];
foreach ($semesters as $s) {
    $byNumber[$s['number']] = $s;
}

check('семестр 3 без задолженности', $byNumber[3]['debt'] ?? null, false);
check('семестр 3: счётчик задолженностей', $byNumber[3]['debtCount'] ?? null, 0);
check('семестр 4 с задолженностью', $byNumber[4]['debt'] ?? null, true);
check('семестр 4: счётчик задолженностей', $byNumber[4]['debtCount'] ?? null, 3);

$sem4 = $byNumber[4]['disciplines'] ?? [];
check('семестр 4: дисциплин', count($sem4), 6);
check('семестр 4: все дисциплины помечены задолженностью',
    array_values(array_unique(array_column($sem4, 'debt'))), [true]);
check('семестр 4: форма контроля первой дисциплины', $sem4[0]['control'] ?? null, 'Зачёт');
check('семестр 3: дисциплины без задолженности',
    array_values(array_unique(array_column($byNumber[3]['disciplines'], 'debt'))), [false]);

// --- disciplineTabs(): вкладка «Пересдача» ---
$discHtml = (string)file_get_contents($fixtures.'/discipline_tabs.html');
$tabs = $parser->disciplineTabs($discHtml);
check('disciplineTabs(): вкладок', count($tabs), 2);
check('вкладка 1: подпись', $tabs[0]['label'] ?? null, 'Текущие');
check('вкладка 1: активна', $tabs[0]['active'] ?? null, true);
check('вкладка 1: не пересдача', $tabs[0]['retake'] ?? null, false);
check('вкладка 2: подпись', $tabs[1]['label'] ?? null, 'Пересдача');
check('вкладка 2: это пересдача', $tabs[1]['retake'] ?? null, true);
check('вкладка 2: не активна', $tabs[1]['active'] ?? null, false);
check('вкладка 2: номер', $tabs[1]['index'] ?? null, 2);
check('вкладка 2: url без якоря',
    $tabs[1]['url'] ?? null, '/lntools/versiongroupassign/contents/student/10000007/2');

// --- disciplineLessons(): замки не сломались ---
$lessons = $parser->disciplineLessons($discHtml)['lessons'];
// В фикстуре один открытый урок и заблокированная тема с двумя вложенными уроками.
check('disciplineLessons(): уроков', count($lessons), 4);
check('урок 1 открыт', $lessons[0]['locked'] ?? null, false);
check('урок 1: resourceId вытащен', $lessons[0]['resourceId'] !== '', true);
check('урок 2 заблокирован', $lessons[1]['locked'] ?? null, true);
check('урок 2: причина про тест',
    str_contains((string)($lessons[1]['lockedReason'] ?? ''), 'тест'), true);
check('вложенные уроки заблокированной темы тоже locked',
    array_values(array_unique(array_column(array_slice($lessons, 1), 'locked'))), [true]);

// --- materials(): видео, файлы и внешние ссылки ---
$video = $parser->materials('<body><video><source src="https://cdn.example/x/y.mp4?t=1" type="video/mp4"></video></body>');
check('materials(): видео найдено', count($video), 1);
check('materials(): вид — video', $video[0]['kind'] ?? null, 'video');
check('materials(): расширение видео', $video[0]['ext'] ?? null, 'mp4');

$pdf = $parser->materials('<body><a href="https://cdn.example/fip/k_01.pdf">Конспект</a></body>');
check('materials(): pdf как файл', $pdf[0]['kind'] ?? null, 'file');
check('materials(): расширение pdf', $pdf[0]['ext'] ?? null, 'pdf');

$zip = $parser->materials('<body><a href="https://e-biblio.example/book/1_1.zip">Доп</a></body>');
check('materials(): zip как файл', $zip[0]['kind'] ?? null, 'file');

// Скрипты в <head> не должны давать ложных срабатываний
$colab = $parser->materials(
    '<html><head><script>var a="/fake.pdf";</script></head>'
    .'<body><p><a href="https://colab.research.google.com/drive/1Qw" target="_blank">Смотреть конспект</a></p></body></html>'
);
check('materials(): внешняя ссылка одна', count($colab), 1);
check('materials(): вид — link', $colab[0]['kind'] ?? null, 'link');
check('materials(): скрипт из <head> не попал в файлы',
    array_column($colab, 'ext'), [null]);

check('materials(): ссылки внутрь LMS игнорируются',
    $parser->materials('<body><a href="https://lms.synergy.ru/student/up">Обучение</a></body>'), []);
check('materials(): пустая страница', $parser->materials('<body><p>&nbsp;</p></body>'), []);

printf("\nПроверок пройдено: %d, провалено: %d\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);
