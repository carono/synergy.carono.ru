<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models;

use carono\yii2\behaviors\UrlBehavior;

/**
 * This is the model class for table "task".
 */
class Task extends base\Task
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'urls' => [
                'class' => UrlBehavior::class,
                'rules' => [
                    [
                        'result',
                        'url' => function (Task $model) {
                            return ['/course/' . $model->semester->course->module . '/' . $model->semester->controller . '/' . $model->action];
                        }
                    ]
                ]
            ]
        ]);
    }
}
