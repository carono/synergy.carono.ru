<?php

namespace app\modules\first\controllers;

use app\controllers\RbacController;
use app\modules\first\case1\Case1;
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
        $model = new Case1();
        $model->input = '12, -5, 7, -3, 0, 18, -4, 6, -1 -11, 4, -2, 9';
        if ($model->load(Yii::$app->request->post())) {
            $model->process();
        }

        return $this->render('case1', ['model' => $model]);
    }

    public function actionCase2()
    {
        return $this->render('case2');
    }

    public function actionCase3()
    {
        return $this->render('//layouts/in-progress');
    }

    public function actionCase4()
    {
        return $this->render('//layouts/in-progress');
    }

    public function actionCase5()
    {
        return $this->render('//layouts/in-progress');
    }
}
