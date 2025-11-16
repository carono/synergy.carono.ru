<?php

namespace app\templates\giix;

use carono\giix\ClassGenerator;

class Fixture extends ClassGenerator
{
    public $skipIfExist = true;

    protected function formClassNamespace()
    {
        return 'app\fixtures';
    }

    protected function formClassName()
    {
        return $this->params['className'] . 'Fixture';
    }

    protected function formExtends()
    {
        return \yii\test\ActiveFixture::class;
    }

    protected function formOutputPath()
    {
        return \Yii::getAlias('@app/fixtures/' . $this->formClassName() . '.php');
    }

    protected function classProperties()
    {
        return [
            'modelClass' => [
                'value' => $this->params['ns'] . '\\' . $this->params['className'],
                'visibility' => 'public'
            ],
            'depends' => [
                'value' => [],
                'visibility' => 'public'
            ]
        ];
    }
}