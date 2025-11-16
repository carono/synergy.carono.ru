<?php
/**
 * @var Case1Form $model
 */

use app\modules\first\forms\Case1Form;
use app\widgets\Card;
use app\helpers\CodeHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

echo Card::widget([
    'caption' => 'Описание задачи',
    'content' => 'Дан одномерный массив А размерности N. Найти сумму отрицательных элементов, расположенных между максимальным и минимальным. Ответом на задачу будет ссылка на репозиторий GitHub, где хранится Ваша программа. Или иным удобным для Вас способом.'
]);

echo Card::widget([
    'caption' => 'Комментарий исполнителя',
    'content' => nl2br('По условию задачи, считается, что на вход подается валидный одномерный числовой массив, для упрощения, не будем ужесточать валидацию и выводить ошибку, если в данных есть не только числа, а разобьем входную строку по всем не числовым символам.
    
    Так же нет понимания, какой именно отрезок суммировать, если минимум и максимум будут встречаться несколько раз, поэтому суммируем только первое вхождение')
]);

Card::begin([
    'caption' => 'Решение',
    'headerOptions' => [
        'class' => 'card-header bg-success',
    ],
]);

$form = ActiveForm::begin();
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
?>
<hr>
<p>Ниже приведен код решения задачи</p>
<?= CodeHelper::outSource(Case1Form::class, 'sumNegativeBetweenMinMax') ?>
<?php


Card::end();
?>

