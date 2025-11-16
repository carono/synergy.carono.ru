<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\fixtures;

class AuthItemFixture extends \yii\test\ActiveFixture
{
    public $modelClass = 'app\models\AuthItem';
    public $depends = [];
    public $dataFile = 'app/fixture/data/empty.php';

    public function loadData($file, $throwException = true)
    {
        system('php yii rbac');
        return [];
    }
}
