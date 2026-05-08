<?php

use app\models\LmsVideo;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LmsDiscipline */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'LMS — дисциплины', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$lessons = $model->getLmsVideos()->orderBy('id')->all();
?>
<div class="lms-discipline-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'lms_id',
            'semester',
            'slug',
            'created_at',
        ],
    ]) ?>

    <h3>Уроки (<?= count($lessons) ?>)</h3>

    <?php if (empty($lessons)): ?>
        <p class="text-muted">Уроки не импортированы. Запустите <code>php yii lms-import/run</code>.</p>
    <?php else: ?>
    <table class="table table-bordered table-condensed table-striped">
        <thead>
        <tr>
            <th style="width:80px">Код</th>
            <th>Название</th>
            <th style="width:160px">Содержимое</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($lessons as $lesson): /** @var LmsVideo $lesson */ ?>
            <tr>
                <td><code><?= Html::encode($lesson->code) ?></code></td>
                <td><?= Html::encode($lesson->title) ?></td>
                <td><?= renderLessonStatus($lesson) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

</div>
<?php

function renderLessonStatus(LmsVideo $lesson): string
{
    // Есть скачанное видео
    if ($lesson->status === 'downloaded' && $lesson->file_path !== null) {
        $url = Url::to(['/admin/lms-video/view', 'id' => $lesson->id]);
        return Html::a('<span class="glyphicon glyphicon-film"></span> Видео', $url, ['class' => 'btn btn-xs btn-success']);
    }

    // Заблокировано
    if ($lesson->status === 'locked') {
        $reason = (string)($lesson->locked_reason ?? '');
        $isTest = mb_stripos($reason, 'тест') !== false || mb_stripos($reason, 'test') !== false;

        if ($isTest) {
            $label = 'Требуется тест';
            if ($lesson->required_minutes !== null) {
                $label .= ' ('.$lesson->required_minutes.' мин)';
            }
            $title = $reason !== '' ? Html::encode($reason) : $label;
            return '<span class="label label-warning" title="'.$title.'">'.$label.'</span>';
        }

        $label = 'Заблокировано';
        $title = $reason !== '' ? Html::encode($reason) : $label;
        if ($lesson->required_minutes !== null) {
            $label .= ' ('.$lesson->required_minutes.' мин)';
        }
        return '<span class="label label-default" title="'.$title.'">'.$label.'</span>';
    }

    // Просмотрено / помечено
    if ($lesson->status === 'watched') {
        return '<span class="label label-info">Просмотрено</span>';
    }

    // Видео нет, но урок доступен
    if ($lesson->status === 'no_video' || $lesson->status === 'downloaded') {
        return '<span class="label label-default">Нет видео</span>';
    }

    // Всё остальное
    return '<span class="label label-default">'.Html::encode($lesson->status ?: '—').'</span>';
}
