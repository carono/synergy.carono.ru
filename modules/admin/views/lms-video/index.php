<?php

use app\models\LmsDiscipline;
use app\models\LmsVideo;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\LmsVideoSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'LMS — видео';
$this->params['breadcrumbs'][] = $this->title;

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
<div class="lms-video-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Дисциплины', ['/admin/lms-discipline/index'], ['class' => 'btn btn-default']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'discipline_id',
                'label' => 'Дисциплина',
                'value' => static function (LmsVideo $model) {
                    return $model->discipline ? $model->discipline->title : '';
                },
                'filter' => $disciplines,
            ],
            'code',
            'title',
            [
                'attribute' => 'status',
                'value' => static function (LmsVideo $model) use ($statuses) {
                    return $statuses[$model->status] ?? $model->status;
                },
                'filter' => $statuses,
            ],
            [
                'attribute' => 'file_size',
                'label' => 'Размер',
                'format' => 'shortSize',
            ],
            [
                'label' => 'Файл',
                'format' => 'raw',
                'value' => static function (LmsVideo $model) {
                    if ($model->file_path === null || $model->file_path === '') {
                        return '';
                    }
                    return Html::a('Смотреть', Url::to(['view', 'id' => $model->id]), ['target' => '_blank']);
                },
            ],
            'downloaded_at',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
