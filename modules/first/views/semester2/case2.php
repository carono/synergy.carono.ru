<?php
/**
 * @var Case1 $model
 * @var View $this
 */

use app\helpers\CodeHelper;
use app\modules\first\case1\Case1;
use app\modules\first\case2\Animal;
use app\modules\first\case2\Cat;
use app\modules\first\case2\Dog;
use app\widgets\Card;
use yii\web\View;


echo Card::widget([
    'caption' => 'Описание задачи',
    'content' => 'Написать тестовую программу, которая демонстрирует работу методов базового и производного классов.
Ответом на задачу будет ссылка на репозиторий GitHub, где хранится Ваша программа. Или иным удобным для Вас способом.'
]);

echo Card::widget([
    'caption' => 'Комментарий исполнителя',
    'content' => nl2br('Сделаем 2 класса, абстрактный "животное" и "собака", "кошка", которая наследуется от "животного"')
]);

Card::begin([
    'caption' => 'Решение',
    'headerOptions' => [
        'class' => 'card-header bg-success',
    ],
]);

echo $this->render('@app/modules/first/case2/result.php');

echo CodeHelper::outSourceFile('@app/modules/first/case2/result.php');

echo CodeHelper::outSource(Animal::class);

echo CodeHelper::outSource(Dog::class);

echo CodeHelper::outSource(Cat::class);


Card::end();

