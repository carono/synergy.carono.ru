<?php

use app\modules\first\case2\Cat;
use app\modules\first\case2\Dog;

$dog = new Dog('Соня', 'Косточки', 'Королевская дворняжка');
$cat = new Cat('Мурзик', 'Сосиски');

?>

<div class="alert alert-info">
    Собачка, породы <strong><?= $dog->getBreed() ?></strong> по кличке <strong><?= $dog->getName() ?></strong> говорит <strong><?= $dog->speak() ?></strong> и ест
    <strong><?= $dog->getFood() ?></strong><br><br>

    Кошка по кличке <strong><?= $cat->getName() ?></strong> говорит <strong><?= $cat->speak() ?></strong> и ест <strong><?= $cat->getFood() ?></strong>
</div>