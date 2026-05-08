<?php

use app\models\LmsDiscipline;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\LmsVideo */
/* @var $form yii\widgets\ActiveForm */

$disciplines = ArrayHelper::map(
    LmsDiscipline::find()->orderBy(['semester' => SORT_ASC, 'title' => SORT_ASC])->all(),
    'id',
    static fn(LmsDiscipline $d) => sprintf('С%d. %s', $d->semester, $d->title)
);

$statuses = [
    'downloaded' => 'Скачано',
    'watched' => 'Просмотрено',
    'locked' => 'Заблокировано',
    'skipped' => 'Пропущено',
    'failed' => 'Ошибка',
];
?>

<div class="lms-video-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'discipline_id')->dropDownList($disciplines, ['prompt' => '— выберите —']) ?>
    <?= $form->field($model, 'lms_resource_id')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'status')->dropDownList($statuses) ?>
    <?= $form->field($model, 'locked_reason')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'required_minutes')->textInput() ?>
    <?= $form->field($model, 'watched_minutes')->textInput() ?>
    <?= $form->field($model, 'video_url')->textarea(['rows' => 2]) ?>
    <?= $form->field($model, 'file_path')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'file_size')->textInput() ?>
    <?= $form->field($model, 'downloaded_at')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
