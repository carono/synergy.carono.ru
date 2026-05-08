<?php
/**
 * @var View $this
 * @var Task $model
 */

use app\helpers\CodeHelper;
use app\models\Task;
use app\widgets\Card;
use carono\yii2rbac\RoleManager;
use yii\helpers\Html;
use yii\web\View;


$sources = $model->getSources()->notDeleted()->orderBy(['pos' => SORT_ASC])->all();

echo Card::widget([
    'caption' => 'Описание задачи',
    'toolbar' => [
        RoleManager::haveRole('admin') ? Html::a('Редактировать', ['/admin/task/update', 'id' => $model->id], ['class' => 'btn btn-primary btn-sm']) : ''
    ],
    'content' => $model->description
]);

if (RoleManager::haveRole('admin')) {
    echo Html::a('Добавить решение', ['/admin/source/create', 'task_id' => $model->id], ['class' => 'btn btn-primary']);
}

if (empty($sources)) {
    echo $this->render('//layouts/in-progress');
    return;
}

echo Card::widget([
    'caption' => 'Комментарий исполнителя',
    'content' => $model->comment
]);


foreach ($sources as $source) {
    Card::begin([
        'caption' => $source->name,
        'footer' => $this->render('partial/source-footer', ['model' => $source, 'form' => $form ?? null]),
        'headerOptions' => [
            'class' => ['bg-success'],
        ],
    ]);
    if ($source->view) {
        echo $this->render($source->view, ['model' => $model, 'source' => $source]);
    }
    if ($source->file) {
        echo CodeHelper::outSourceFile($source->file);
    }
    if ($source->class) {
        echo CodeHelper::outSource($source->class, $source->method);
    }
    if ($source->image) {
        echo Html::tag('div', Html::img($source->image, ['width' => '100%', 'height' => '930px']), ['class' => 'text-center']);
    }
    Card::end();
}

