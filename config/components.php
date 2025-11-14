<?php
return [
    'log' => [
        'targets' => [
            [
                'class' => 'yii\log\FileTarget',
                'levels' => ['error', 'warning'],
            ],
        ],
    ],
    'urlManager' => [
        'enablePrettyUrl' => true,
        'enableStrictParsing' => true,
        'showScriptName' => false,
        'rules' => [
            '/' => 'site/index',
            '<module>/<controller>/<action>' => '<module>/<controller>/<action>',
            '<controller>/<action>' => '<controller>/<action>',
            '<action>' => ''
        ],
    ],
];