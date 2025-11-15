<?php

namespace app\assets;


use yii\web\AssetBundle;

class HighlightAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'lib/highlight/styles/1c-light.min.css',
    ];
    public $js = [
        'lib/highlight/highlight.min.js',
        'lib/highlight/languages/php.min.js',
    ];

    public $depends = [];
}