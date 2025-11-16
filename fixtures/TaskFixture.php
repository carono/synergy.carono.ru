<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\fixtures;

class TaskFixture extends \yii\test\ActiveFixture
{
    public $modelClass = 'app\models\Task';
    public $depends = [
        SemesterFixture::class
    ];
}
