<?php

namespace app\modules\admin\controllers;

use app\models\LmsVideo;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * LmsVideoController implements the CRUD actions for LmsVideo model.
 */
class LmsVideoController extends CrudController
{
    public $modelClass = 'app\models\LmsVideo';
    public $modelSearchClass = 'app\models\search\LmsVideoSearch';

    /**
     * Отдаёт mp4-файл для inline-плеера, ограничивая путь подкаталогом downloads/.
     */
    public function actionFile(int $id): Response
    {
        /** @var LmsVideo $model */
        $model = LmsVideo::findOne($id);
        if ($model === null || $model->file_path === null || $model->file_path === '') {
            throw new NotFoundHttpException('Файл не найден.');
        }

        $base = Yii::getAlias('@app/lms-parser/downloads');
        $real = realpath($base . '/' . $model->file_path);
        if ($real === false || !is_file($real) || !str_starts_with($real, realpath($base) . DIRECTORY_SEPARATOR)) {
            throw new NotFoundHttpException('Файл вне дозволенного каталога.');
        }

        return Yii::$app->response->sendFile($real, basename($real), [
            'inline' => true,
            'mimeType' => 'video/mp4',
        ]);
    }
}
