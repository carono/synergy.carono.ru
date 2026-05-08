<?php

use app\models\Task;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var View $this
 */

if (Yii::$app->controller->module->id == 'basic') {
    return;
}
$items = Task::find()->notDeleted()
    ->joinWith(['semester.course'])
    ->andWhere(['module' => Yii::$app->controller->module->id])
    ->andWhere(['controller' => Yii::$app->controller->id])
    ->all();

$prev = null;
$current = null;
$next = null;
foreach ($items as $i => $item) {
    if (Url::to($item->getUrl('result')) == Yii::$app->request->url) {
        $prev = $items[$i - 1] ?? null;
        $current = $item;
        $next = $items[$i + 1] ?? null;
        break;
    }
}
$prev = $prev ? Html::a($prev->name, $prev->getUrl('result'), ['class' => 'btn btn-outline-primary prev-case']) : null;
$current = $current ? Html::tag('div', Html::tag('h2', $current->name)) : '';
$next = $next ? Html::a($next->name, $next->getUrl('result'), ['class' => 'btn btn-primary next-case']) : null;

?>

<div class="container my-5 text-center">
    <div class="d-flex justify-content-between">
        <?= $prev ?>
        <?= $current ?>
        <?= $next ?>
    </div>
</div>