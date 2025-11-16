<?php

namespace app\modules\first\forms;

use yii\base\Model;

class Case1Form extends Model
{
    public $input;
    public $sum;
    public $min_index;
    public $max_index;
    public $min_value;
    public $max_value;
    public $array = [];
    public $between = [];

    public function rules()
    {
        return [
            [['input'], 'safe'],
            [['input'], 'required'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'input' => 'Входные данные'
        ];
    }

    public function sumNegativeBetweenMinMax()
    {
        $array = preg_split('/[^\d.-]+/', $this->input, -1, PREG_SPLIT_NO_EMPTY);
        $size = count($array);

        $array = array_filter($array, 'is_numeric');

        if (empty($array)) {
            return [];
        }

        // Находим индексы минимального и максимального элементов
        $min_index = 0;
        $max_index = 0;
        $min_value = $array[0];
        $max_value = $array[0];
        $between = [];

        for ($i = 1; $i < $size; $i++) {
            if (empty($array[$i])) {
                continue;
            }
            if ($array[$i] < $min_value) {
                $min_value = $array[$i];
                $min_index = $i;
            }
            if ($array[$i] > $max_value) {
                $max_value = $array[$i];
                $max_index = $i;
            }
        }
        if ($min_index > $max_index) {
            $k = $min_index;
            $min_index = $max_index;
            $max_index = $k;
        }

        // Суммируем отрицательные элементы между границами
        $sum = 0;
        for ($i = $min_index + 1; $i < $max_index; $i++) {
            if (empty($array[$i]) || $array[$i] > 0) {
                continue;
            }
            $sum += $array[$i];
            $between[] = $array[$i];
        }

        return [
            'sum' => $sum,
            'array' => $array,
            'min_index' => $min_index,
            'max_index' => $max_index,
            'min_value' => $min_value,
            'max_value' => $max_value,
            'between' => $between
        ];
    }

    public function process()
    {
        $data = $this->sumNegativeBetweenMinMax();
        $this->setAttributes($data, false);
    }
}