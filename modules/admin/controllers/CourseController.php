<?php

namespace app\modules\admin\controllers;


/**
 * CourseController implements the CRUD actions for Course model.
 */
class CourseController extends CrudController
{
    public $modelClass = 'app\models\Course';
    public $modelSearchClass = 'app\models\search\CourseSearch';
}
