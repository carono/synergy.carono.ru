<?php

namespace app\modules\first\controllers;

use app\controllers\RbacController;
use app\models\Source;
use app\models\Task;
use Yii;

/**
 * Default controller for the `first` module
 */
class Semester2Controller extends RbacController
{
    protected function getTask()
    {
        $url = parse_url(Yii::$app->request->url, PHP_URL_PATH);
        return Task::find()
            ->joinWith(['semester.course'])
            ->andWhere(['CONCAT("/course","/",module,"/",controller,"/",action)' => $url])
            ->one();
    }


    public function actionCase()
    {
        if ($this->request->post() && ($sourceId = $this->request->get('comment'))) {
            if (($source = Source::findOne($sourceId)) && $source->load($this->request->post())) {
                if ($source->save()) {
                    return Yii::$app->response->redirect($this->request->referrer, 302, false);
                }
            }
        }

        $model = $this->getTask();
        return $this->render('case', ['model' => $model]);
    }
}
