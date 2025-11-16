<?php

namespace app\modules\admin\controllers;

/**
 * SemesterController implements the CRUD actions for Semester model.
 */
class SemesterController extends CrudController
{
    public $modelClass = '\app\models\Semester';
    public $modelSearchClass = 'app\models\search\SemesterSearch';
}
