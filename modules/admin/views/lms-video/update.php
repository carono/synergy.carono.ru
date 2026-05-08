<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LmsVideo */

$this->title = 'Редактировать видео: '.$model->title;
$this->params['breadcrumbs'][] = ['label' => 'LMS — видео', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактировать';
?>
<div class="lms-video-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('partial/form', [
        'model' => $model,
    ]) ?>

</div>
