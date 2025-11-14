<?php use yii\bootstrap5\Breadcrumbs;

if (!empty($this->params['breadcrumbs'])): ?>
    <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
<?php endif ?>