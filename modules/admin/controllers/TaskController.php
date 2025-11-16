<?php

namespace app\modules\admin\controllers;

/**
 * TaskController implements the CRUD actions for Task model.
 */
class TaskController extends CrudController
{
    public $modelClass = 'app\models\Task';
    public $modelSearchClass = '\app\models\search\TaskSearch';
}
