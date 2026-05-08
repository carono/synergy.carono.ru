<?php

namespace app\modules\admin\controllers;

use carono\yii2crud\actions\CreateAction;
use Yii;

/**
 * SourceController implements the CRUD actions for Source model.
 */
class SourceController extends CrudController
{
    public $modelClass = '\app\models\Source';
    public $modelSearchClass = 'app\models\search\SourceSearch';

    public function actions()
    {
        return array_merge(parent::actions(), [
            'create' => [
                'class' => CreateAction::class,
                'view' => $this->createView,
                'loadGetFormName' => '',
                'redirect' => function ($model) {
                    return Yii::$app->request->referrer;
                }
            ]
        ]);
    }
}
