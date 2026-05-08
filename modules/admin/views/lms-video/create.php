<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LmsVideo */

$this->title = 'Добавить видео';
$this->params['breadcrumbs'][] = ['label' => 'LMS — видео', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lms-video-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('partial/form', [
        'model' => $model,
    ]) ?>

</div>
