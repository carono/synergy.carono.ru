<?php

namespace app\modules\first\controllers;

use app\controllers\RbacController;
use app\modules\first\forms\Case1Form;
use Yii;

/**
 * Default controller for the `first` module
 */
class Semester2Controller extends RbacController
{
    /**
     * Renders the index view for the module
     *
     * @return string
     */
    public function actionCase1()
    {
        $model = new Case1Form();
        if ($model->load(Yii::$app->request->post())) {
            $model->process();
        }

        return $this->render('case1', ['model' => $model]);
    }
}
