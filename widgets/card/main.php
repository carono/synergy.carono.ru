<?php
/**
 * @var View $this
 * @var string $content
 * @var Card $context
 */

use app\widgets\Card;
use yii\helpers\Html;
use yii\web\View;

$context = $this->context;
$headerOptions = Html::renderTagAttributes($context->headerOptions);
?>

<div class="card section">
    <div <?= $headerOptions ?>><?= $context->caption ?></div>
    <div class="card-body">
        <p>
            <?= $content ?>
        </p>
    </div>
</div>
