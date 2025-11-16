<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var View $this
 */

if (Yii::$app->controller->module->id == 'basic') {
    return;
}
$items = extractSemesterLessons(Yii::$app->params['menu'], Yii::$app->controller->module->getUniqueId());
$prev = null;
$current = null;
$next = null;
foreach ($items as $i => $item) {
    if (Url::to($item['url']) == Yii::$app->request->url) {
        $prev = $items[$i - 1] ?? null;
        $current = $item;
        $next = $items[$i + 1] ?? null;
        break;
    }
}
$prev = $prev ? Html::a($prev['label'], $prev['url'], ['class' => 'btn btn-outline-primary']) : null;
$current = $current ? Html::tag('div', Html::tag('h2', $current['label'])) : '';
$next = $next ? Html::a($next['label'], $next['url'], ['class' => 'btn btn-primary']) : null;

?>

<div class="container my-5 text-center">
    <div class="d-flex justify-content-between">
        <?= $prev ?>
        <?= $current ?>
        <?= $next ?>
    </div>
</div>

<?php

function extractSemesterLessons($menu, $module_id)
{

    $result = [];
    foreach ($menu as $item) {
        if (isset($item['url']) && str_contains($item['url'], $module_id)) {
            $result[] = $item;
        }
        if (isset($item['items'])) {
            $result = array_merge($result, extractSemesterLessons($item['items'], $module_id));
        }
    }

    return $result;
}

?>

