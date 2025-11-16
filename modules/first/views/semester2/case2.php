<?php
/**
 * @var Case1 $model
 * @var View $this
 */

use app\helpers\CodeHelper;
use app\modules\first\case1\Case1;
use app\widgets\Card;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


echo Card::widget([
    'caption' => 'Описание задачи',
    'content' => 'Написать тестовую программу, которая демонстрирует работу методов базового и производного классов.
Ответом на задачу будет ссылка на репозиторий GitHub, где хранится Ваша программа. Или иным удобным для Вас способом.'
]);

echo Card::widget([
    'caption' => 'Комментарий исполнителя',
    'content' => nl2br('Сделаем 2 класса, абстрактный "животное" и "собака", которая наседуется от "животного"')
]);

Card::begin([
    'caption' => 'Решение',
    'headerOptions' => [
        'class' => 'card-header bg-success',
    ],
]);
echo CodeHelper::outSource(Case1::class);

Card::end();

