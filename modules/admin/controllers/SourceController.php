<?php

namespace app\modules\admin\controllers;

/**
 * SourceController implements the CRUD actions for Source model.
 */
class SourceController extends CrudController
{
    public $modelClass = '\app\models\Source';
    public $modelSearchClass = 'app\models\search\SourceSearch';
}
