<?php

namespace app\modules\first\controllers;

use app\controllers\RbacController;
use app\models\Task;
use Yii;

/**
 * Default controller for the `first` module
 */
class Semester2Controller extends RbacController
{
    protected function getTask()
    {
        return Task::find()->joinWith(['semester.course'])
            ->andWhere(['CONCAT("/course","/",module,"/",controller,"/",action)' => Yii::$app->request->url])
            ->one();
    }


    /**
     * Renders the index view for the module
     *
     * @return string
     */
    public function actionCase()
    {
        $model = $this->getTask();
        return $this->render('case', ['model' => $model]);
    }
}
