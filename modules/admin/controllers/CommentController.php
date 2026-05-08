<?php

namespace app\modules\admin\controllers;

/**
 * CommentController implements the CRUD actions for Comment model.
 */
class CommentController extends CrudController
{
    public $modelClass = 'app\models\Comment';
    public $modelSearchClass = 'app\models\search\CommentSearch';
}
