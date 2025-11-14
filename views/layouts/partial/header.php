<?php

use yii\bootstrap5\Html;
use app\widgets\Nav;
use yii\bootstrap5\NavBar;

?>

<header id="header">

    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
    ]);
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav'],
        'items' => [
            [
                'label' => 'Главная',
                'url' => ['/site/index'],
                'options' => [
                    'class' => 'nav-link btn btn-link',
                ]
            ],
            [
                'label' => 'О студенте',
                'url' => ['/site/about'],
                'options' => [
                    'class' => 'nav-link btn btn-link',
                ]
            ],
            [
                'label' => 'Вход',
                'url' => ['/site/login'],
                'visible' => Yii::$app->user->isGuest
            ],
            [
                'label' => 'Выход',
                'url' => ['/site/logout'],
                'visible' => !Yii::$app->user->isGuest,
                'linkOptions' => [
                    'data-method' => 'post'
                ],
                'options' => [
                    'class' => 'nav-link btn btn-link logout',
                ]
            ],
        ]
    ]);
    NavBar::end();
    ?>
</header>