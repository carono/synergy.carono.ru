<?php

namespace app\modules\admin\controllers;

use carono\yii2rbac\RoleManagerFilter;

abstract class CrudController extends \carono\yii2crud\CrudController
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => RoleManagerFilter::class
            ]
        ]);
    }
}