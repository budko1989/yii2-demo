<?php

Yii::setAlias('@tests', dirname(__DIR__) . '/tests/codeception');

$params = require(__DIR__ . '/params.php');
$db = require(__DIR__ . '/db.php');

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories'=>[
                        'app\commands\SyncController*',
                    ],
                    'logVars' => [],
                    'logFile' => '@runtime/logs/console.log',
                    'levels' => ['error', 'warning', 'info'],
                ],
                [
                    'class' => 'yii\log\EmailTarget',
                    'levels' => ['info'],
                    'categories' => ['app\commands\SyncController*'],
                    'logVars' => [],
                    'message' => [
                       'from' => ['noreply@baby-one.com.ua'],
                       'to' => ['info@baby-one.com.ua', 'viola.shinkaryova@gmail.com'],
                       'subject' => 'Обновлён товар на сайте',
                    ],
                ],
                [
                    'class' => 'yii\log\EmailTarget',
                    'levels' => ['warning', 'error'],
                    'categories' => ['app\commands\SyncController*'],
                    'logVars' => [],
                    'message' => [
                       'from' => ['noreply@baby-one.com.ua'],
                       'to' => ['info@baby-one.com.ua', 'budko.v1989@gmail.com'],
                       'subject' => 'Ошибки при обмене на сайте',
                    ],
                ],
            ],
        ],
        'db' => $db,
        'web1c' => [
            'class' => 'mongosoft\soapclient\Client',
            'url' => 'http://server.local/ut11/ws/ProductsInfo?wsdl',
            'options' => [
                'login' => 'admin',
                'password' => 'admin',
                'features' => SOAP_USE_XSI_ARRAY_TYPE,
                'exceptions' => 1,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ],
        ],
        'consoleRunner' => [
            'class' => 'vova07\console\ConsoleRunner',
            'file' => '@app/yii' // or an absolute path to console file
        ],
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
