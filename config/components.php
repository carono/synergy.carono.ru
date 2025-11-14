<?php

use app\components\Formatter;

return [
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'targets' => [
            [
                'class' => 'yii\log\FileTarget',
                'levels' => ['error', 'warning'],
            ],
        ],
    ],
    'authManager' => [
        'class' => 'yii\rbac\DbManager',
        'defaultRoles' => ['guest', 'user'],
    ],
    'formatter' => [
        'class' => Formatter::class,
        'datetimeFormat' => 'dd LLL yyyy, HH:mm',
        'defaultTimeZone' => 'Europe/Moscow',
        'nullDisplay' => '',
    ],
    'i18n' => [
        'translations' => [
            '*' => [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => '@app/messages',
                'sourceLanguage' => 'en',
            ],
        ],
    ],
    'db' => file_exists(__DIR__ . '/db-local.php') ? require __DIR__ . '/db-local.php' : [],
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