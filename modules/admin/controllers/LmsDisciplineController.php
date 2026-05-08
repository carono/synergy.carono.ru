<?php

namespace app\modules\admin\controllers;

/**
 * LmsDisciplineController implements the CRUD actions for LmsDiscipline model.
 */
class LmsDisciplineController extends CrudController
{
    public $modelClass = 'app\models\LmsDiscipline';
    public $modelSearchClass = 'app\models\search\LmsDisciplineSearch';
}
