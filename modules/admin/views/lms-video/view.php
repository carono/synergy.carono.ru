<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LmsVideo */

$this->title = ($model->code ? '['.$model->code.'] ' : '').$model->title;
$this->params['breadcrumbs'][] = ['label' => 'LMS — видео', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$fileUrl = Url::to(['file', 'id' => $model->id]);
$hasFile = $model->file_path !== null && $model->file_path !== '';
?>
<div class="lms-video-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Удалить запись о видео? Файл на диске не будет затронут.',
                'method' => 'post',
            ],
        ]) ?>
        <?php if ($hasFile): ?>
            <?= Html::a('Скачать файл', $fileUrl, ['class' => 'btn btn-default']) ?>
        <?php endif; ?>
    </p>

    <?php if ($hasFile): ?>
        <div class="lms-video-player" style="max-width:960px;margin-bottom:20px;">
            <video controls preload="metadata" style="width:100%;background:#000;">
                <source src="<?= Html::encode($fileUrl) ?>" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>
        </div>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            [
                'attribute' => 'discipline_id',
                'label' => 'Дисциплина',
                'format' => 'raw',
                'value' => static function ($m) {
                    return $m->discipline
                        ? Html::a(Html::encode($m->discipline->title), ['/admin/lms-discipline/view', 'id' => $m->discipline_id])
                        : '';
                },
            ],
            'lms_resource_id',
            'code',
            'title',
            'status',
            'locked_reason',
            'required_minutes',
            'watched_minutes',
            'video_url:url',
            'file_path',
            [
                'attribute' => 'file_size',
                'format' => 'shortSize',
            ],
            'downloaded_at',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
