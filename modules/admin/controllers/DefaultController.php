<?php

namespace app\modules\admin\controllers;

use app\controllers\RbacController;

class DefaultController extends RbacController
{
    public function actionIndex()
    {
        return $this->render('index');
    }
}