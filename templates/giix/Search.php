<?php


namespace app\templates\giix;


use carono\giix\ClassGenerator;
use Nette\PhpGenerator\Method;

class Search extends ClassGenerator
{
    public $skipIfExist = true;


    protected function formExtends()
    {
        return $this->params['ns'] . '\\search\\base\\' . $this->params['className'] . 'Search';
    }

    /**
     * @param Method $method
     */
    public function rules($method)
    {
        $body = 'return array_merge(parent::rules(), []);';
        $method->addBody($body);
    }


    protected function formClassNamespace()
    {
        return 'app\models\search';
    }

    protected function formClassName()
    {
        return $this->params['className'] . 'Search';
    }

    protected function formOutputPath()
    {
        return \Yii::getAlias('@app/models/search/' . $this->formClassName() . '.php');
    }

}