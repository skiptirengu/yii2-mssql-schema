<?php
define('YII_ENV', 'test');
define('TESTS_BASE_PATH', __DIR__);
defined('YII_DEBUG') or define('YII_DEBUG', true);

// config variables
putenv('CON_STRING=sqlsrv:Server=localhost;Database=testdb');
putenv('CON_DBUSER=sa');
putenv('CON_PASSWD=Admin1234!');

if (is_file($envfile = __DIR__ . '/local.env')) {
    $envVars = array_filter(explode("\n", file_get_contents($envfile)));
    foreach ($envVars as $item) {
        putenv($item);
    }
}

require_once(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../vendor/autoload.php');