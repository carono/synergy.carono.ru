<?php
/**
 * @var Source $model
 * @var ActiveForm $form
 */

use app\models\Source;
use yii\db\Expression;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\ListView;
use yii\widgets\Pjax;

Pjax::begin();
if ($model->getComments()->notDeleted()->exists()) {
    echo Html::tag('h6', 'Комментарии', ['class' => 'mb-2']);

    echo ListView::widget([
        'dataProvider' => $model->getComments()->notDeleted()->orderBy(['created_at' => new Expression('NOW()')])->search(),
        'options' => ['tag' => false],
        'itemView' => 'comment-item'
    ]);
}

$url = parse_url(Yii::$app->request->url, PHP_URL_PATH) . '?comment=' . $model->id;

if (Yii::$app->request->get('comment') == $model->id) {
    $form = ActiveForm::begin(['options' => ['data-pjax' => 1]]);
    echo $form->field($model, 'new_comment')->textarea(['rows' => 10, 'placeholder' => 'Напишите комментарий для этого блока'])->label(false);
    echo Html::beginTag('div', ['class' => 'd-flex justify-content-end']);
    echo Html::a('Отменить', parse_url(Yii::$app->request->url, PHP_URL_PATH), ['class' => 'btn btn-warning me-2']);
    echo Html::submitButton('Добавить', ['class' => 'btn btn-primary']);
    echo Html::endTag('div');
    ActiveForm::end();
} else {
    echo Html::tag('div', '<a data-pjax="1" href="' . $url . '" class="btn btn-primary ">Комментировать</a>', ['class' => 'd-flex justify-content-end']);
}
Pjax::end();
