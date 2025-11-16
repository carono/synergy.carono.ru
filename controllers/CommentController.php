<?php

namespace app\controllers;

use app\models\Comment;
use carono\yii2crud\actions\DeleteAction;
use Yii;

class CommentController extends RbacController
{
    public function actions()
    {
        return [
            'delete' => [
                'class' => DeleteAction::class,
                'modelClass' => Comment::class,
                'primaryKeyParam' => ['id'],
                'messageOnDelete' => 'Комментарий удален',
                'redirect' => function ($model) {
                    return Yii::$app->request->referrer;
                }
            ]
        ];
    }
}