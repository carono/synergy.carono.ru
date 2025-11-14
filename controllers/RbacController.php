<?php

namespace app\controllers;

use carono\yii2rbac\RoleManagerFilter;
use yii\web\Controller;

abstract class RbacController extends Controller
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