<?php


use yii\widgets\Menu;

echo Menu::widget([
    'items' => [
        ['label' => 'Управление курсами', 'url' => ['/admin/course/index']],
        ['label' => 'Управление семестрами', 'url' => ['/admin/semester/index']],
        ['label' => 'Управление курсами', 'url' => ['/admin/course/index']],
        ['label' => 'Кейс-задачи', 'url' => ['/admin/task/index']],
        ['label' => 'Блоки', 'url' => ['/admin/source/index']],
        ['label' => 'Комментарии', 'url' => ['/admin/comment/index']],
    ]
]);