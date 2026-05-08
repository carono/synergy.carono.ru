<?php
/**
 * @var View $this
 * @var string $content
 * @var Card $context
 */

use app\widgets\Card;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

$context = $this->context;
$defaultOptions = ['class' => ['card-header', 'd-flex', 'justify-content-between', 'align-items-center']];
$headerOptions = Html::renderTagAttributes(ArrayHelper::merge($defaultOptions, $context->headerOptions));
?>

<div class="card section">
    <div
        <?= $headerOptions ?>><?= $context->caption ?>
        <?php if ($context->toolbar) { ?>
            <div class="card-header-actions">
                <?= implode("\n", $context->toolbar) ?>
            </div>
        <?php } ?>
    </div>


    <div class="card-body">
        <p>
            <?= $content ?>
        </p>
    </div>

    <?php if ($context->footer) { ?>
        <div class="card-footer">
            <?= $context->footer ?>
        </div>
    <?php } ?>
</div>
