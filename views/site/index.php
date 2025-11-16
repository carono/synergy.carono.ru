<?php

/** @var yii\web\View $this */

use app\models\Course;
use app\widgets\MainMenu;

$this->title = 'Практические задачи';

$items = [];
foreach (Course::find()->notDeleted()->orderBy(['pos' => SORT_ASC])->each() as $course) {
    $courseItem = ['label' => $course->name, 'items' => []];
    foreach ($course->getSemesters()->notDeleted()->each() as $semester) {
        $semesterItem = ['label' => $semester->name, 'items' => []];
        foreach ($semester->getTasks()->notDeleted()->each() as $task) {
            $semesterItem['items'][] = ['label' => $task->name, 'url' => $task->getUrl('result')];
        }
        $courseItem['items'][] = $semesterItem;
    }
    $items[] = $courseItem;
}
?>

<h1 class="text-center"><?= $this->title ?></h1>

<div class="container mt-4">
    <?= MainMenu::widget(['items' => $items]) ?>
</div>
