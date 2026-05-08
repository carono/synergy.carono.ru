<?php

namespace app\widgets;

use ReflectionClass;
use yii\base\Widget;
use yii\helpers\Inflector;

class Card extends Widget
{
    public $caption;
    public $options = [];
    public $headerOptions = [];
    public $content;
    public $layout;
    public $toolbar = [];
    public $footer;

    public function init()
    {
        ob_start();
        ob_implicit_flush(false);
    }

    public function run()
    {
        $internalContent = ob_get_clean();
        $name = Inflector::slug((new ReflectionClass($this))->getShortName());
        $this->layout = $this->layout ?: '@app/widgets/' . $name . '/main';
        echo \Yii::$app->getView()->render($this->layout, ['content' => nl2br($this->content) ?: $internalContent], $this);
    }
}