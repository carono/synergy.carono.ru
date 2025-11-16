<?php

/** @var yii\web\View $this */

use app\widgets\MainMenu;

$this->title = 'Практические задачи';

?>

<h1 class="text-center"><?= $this->title ?></h1>

<div class="container mt-4">
    <?= MainMenu::widget(['items' => Yii::$app->params['menu']]) ?>
</div>
