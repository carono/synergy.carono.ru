<?php


use carono\yii2rbac\RbacController;

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => array_merge(require __DIR__ . '/components.php', [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
    ]),
    'params' => array_merge(require __DIR__ . '/params.php', file_exists(__DIR__ . '/params-local.php') ? require __DIR__ . '/params-local.php' : []),
    'controllerMap' => [
        'migrate' => [
            'class' => \yii\console\controllers\MigrateController::class,
            'templateFile' => '@app/templates/migration.php',
            'migrationPath' => [
                '@app/migrations',
                '@vendor/yiisoft/yii2/rbac/migrations',
            ]
        ],
        'giix' => [
            'class' => 'carono\giix\GiixController',
            'generator' => \app\templates\Generator::class,
            'templatePath' => '@app/templates/giix',
            'exceptTables' => [
                '{{%migration}}'
            ],
        ],
        'rbac' => [
            'class' => RbacController::class,
            'roles' => [
                'user' => '',
            ],
            'permissions' => [
                'Basic:*:*' => ['user'],
                'Basic:*:*:*' => ['user'],
            ]
        ],
    ]
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
    // configuration adjustments for 'dev' environment
    // requires version `2.1.21` of yii2-debug module
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
