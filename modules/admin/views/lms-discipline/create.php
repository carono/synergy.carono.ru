<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LmsDiscipline */

$this->title = 'Добавить дисциплину';
$this->params['breadcrumbs'][] = ['label' => 'LMS — дисциплины', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lms-discipline-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('partial/form', [
        'model' => $model,
    ]) ?>

</div>
