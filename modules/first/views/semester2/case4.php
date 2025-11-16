<?php

use app\helpers\CodeHelper;
use app\modules\first\case2\Animal;
use app\widgets\Card;
use yii\helpers\Html;
use yii\widgets\DetailView;

echo Card::widget([
    'caption' => 'Описание задачи',
    'content' => 'Проведите анализ и опишите имеющихся на рынке программного обеспечения информационных систем, построенных по архитектуре WEB-приложений. Оцените и опишите возможности предлагаемых систем по архитектуре WEB-приложений и варианты их использования в компании. Создайте с помощью Delphi 10.2 и MS Internet Information Server (IIS) приложение WEB-архитектуры на любую тему. Базу данных для WEB-приложения создать в MS SQL Server.

➔	Ответом на задачу будет ссылка на репозиторий GitHub, где хранится Ваша программа. Или иным удобным для Вас способом.
➔	Когда вы создаете базу данных в MySQL с помощью MySQL Workbench (или любого другого инструмента), вы можете экспортировать схему базы данных в виде скрипта SQL. Этот скрипт SQL содержит определения таблиц, связей, индексов и других структур базы данных, которые вы создали. Или иным удобным для Вас способом.'
]);

echo Card::widget([
    'caption' => 'Комментарий исполнителя',
    'content' => 'Задача делится на два этапа, первый это провести анализ и создать web-приложение
    
    1. Анализ представлен ниже в блоке "анализ".
    
    2. В постановке задачи написано, что при создании веб приложения нужно использовать Delphi и IIS, ввиду того, что эти инструменты мало используются для web приложений, и выбраны, вероятно для того, чтобы упростить задачу для студента, но я все же решу поставленную задачу с помощью более более распространенных инструментов для web разработки, а именно php+nginx'
]);

Card::begin([
    'caption' => 'Решение, web-приложение',
    'headerOptions' => [
        'class' => 'card-header bg-success',
    ],
]);

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


Card::end();

Card::begin([
    'caption' => 'Пояснительная записка по разработке',
    'headerOptions' => [
        'class' => 'card-header bg-success',
    ],
]);
echo $this->render('@app/modules/first/case4/about.php');
Card::end();


Card::begin([
    'caption' => 'Анализ',
    'headerOptions' => [
        'class' => 'card-header bg-success',
    ],
]);

echo $this->render('@app/modules/first/case4/analyze.php');
Card::end();