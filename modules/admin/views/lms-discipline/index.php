<?php

use app\models\LmsDiscipline;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\LmsDisciplineSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'LMS — дисциплины';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lms-discipline-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'lms_id',
            'semester',
            [
                'attribute' => 'title',
                'format' => 'raw',
                'value' => static function (LmsDiscipline $model) {
                    return Html::a(Html::encode($model->title), ['view', 'id' => $model->id]);
                },
            ],
            [
                'attribute' => 'videos',
                'label' => 'Видео',
                'format' => 'raw',
                'value' => static function (LmsDiscipline $model) {
                    $count = $model->getLmsVideos()->count();
                    return Html::a((string)$count, Url::to(['/admin/lms-video/index', 'LmsVideoSearch[discipline_id]' => $model->id]));
                },
            ],
            'created_at',
        ],
    ]); ?>
</div>
