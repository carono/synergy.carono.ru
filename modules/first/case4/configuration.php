<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

echo Html::beginTag('div', ['class' => 'row']);
echo Html::beginTag('div', ['class' => 'col-lg-4 col-md-12']);
echo Html::tag('h3', 'Конфигурация сервера');
echo DetailView::widget([
    'model' => [
        'Операционная система' => 'Centos 7',
        'ЦПУ' => '2 ядра',
        'ОЗУ' => '4 Гб',
    ],
]);
echo Html::endTag('div');

echo Html::beginTag('div', ['class' => 'col-lg-4 col-md-12']);
echo Html::tag('h3', 'Серверный стек');
echo DetailView::widget([
    'model' => [
        'Язык программирования' => 'PHP 8.4',
        'Веб сервер' => 'nginx',
        'База данных' => 'Mysql 8',
    ],
]);
echo Html::endTag('div');

echo Html::beginTag('div', ['class' => 'col-lg-4 col-md-12']);
echo Html::tag('h3', 'Кодовый стек');
echo DetailView::widget([
    'model' => [
        'Бекенд фреймворк' => 'Yii2',
        'Фронтенд фреймвор' => 'boostrap v5',
    ],
]);
echo Html::endTag('div');

echo Html::endTag('div');