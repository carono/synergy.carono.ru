<?php
/**
 * @var Task $model
 */

use app\models\Task;
use app\modules\first\case1\Case1;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

$model = new Case1();
$model->input = '12, -5, 7, -3, 0, 18, -4, 6, -1 -11, 4, -2, 9';
if ($model->load(Yii::$app->request->post())) {
    $model->process();
}

Pjax::begin();
$form = ActiveForm::begin(['options' => ['data-pjax' => 1]]);
echo Html::tag('div', 'Введите строку из чисел, разделенных запятыми или другими символами', ['class' => 'alert alert-info']);
echo $form->field($model, 'input')->textarea(['placeholder' => 'Введите числа через запятую'])->label(false);
echo Html::submitButton('Отправить данные', ['class' => 'btn btn-primary']);

if ($model->input) {
    echo '<hr>';
    echo Html::tag('div', '<strong>Входная строка: </strong>' . $model->input);
    echo Html::tag('div', '<strong>Входной массив: </strong>' . implode(', ', $model->array));
    echo Html::tag('div', '<strong>Минимум: </strong>' . $model->min_value);
    echo Html::tag('div', '<strong>Максимум: </strong>' . $model->max_value);
    echo Html::tag('div', '<strong>Суммируемый отрезок: </strong>' . implode(', ', $model->between));
    echo Html::tag('div', '<strong>Результат: </strong>' . $model->sum);

}
ActiveForm::end();
Pjax::end();
