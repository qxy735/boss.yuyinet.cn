<?php

use Phalcon\DI;
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Application;
use Phalcon\Config\Adapter\Ini as Config;
use Phalcon\Logger\Adapter\File as FileAdapter;

// 获取根目录地址
define('ROOT', rtrim(dirname(__DIR__), '/'));

// 实例服务容器对象
$di = new DI();

// 读取配置项
$config = new Config('../app/config/config.ini');

// 注册配置项服务
$di->set('config', function () use ($config) {
    return $config;
});

// 设置错误显示
error_reporting(E_ALL);
ini_set('display_errors', $config->error->display);

// 实例化加载对象
$loader = new Loader();

// 获取需要自动载入的目录项
$autoloads = json_decode(json_encode($config->autoload), true);
$autoloads and $loader->registerDirs($autoloads);
$loader->register();

// 获取日志文件路径
$logPath = $config->log->path . date('Y-m-d') . '.log';

$di->set('log', function () use ($logPath) {
    return new FileAdapter($logPath);
});

// 获取加密 key
$ckey = $config->base->key;

// Registering a Phalcon\Crypt
$di->set('crypt', function () use ($ckey) {
    $crypt = new Phalcon\Crypt();
    $crypt->setKey($ckey);

    return $crypt;
});

//Registering a router
$di->set('router', function () {
    $router = new Router();

    $router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

    return $router;
});

// Registering a dispatcher
$di->set('dispatcher', 'Phalcon\Mvc\Dispatcher');

// Registering a Http\Response
$di->set('response', 'Phalcon\Http\Response');

// Registering a Http\Request
$di->set('request', 'Phalcon\Http\Request');

// Registering a Phalcon\Http\Response\Cookies
$di->set('cookies', function () {
    $cookies = new Phalcon\Http\Response\Cookies();
    $cookies->useEncryption(false);

    return $cookies;
});

// Registering a Phalcon\Flash\Direct
$di->set('flash', 'Phalcon\Flash\Direct');

// Registering a Phalcon\Mvc\Url
$di->set('url', function () {
    $url = new Phalcon\Mvc\Url();
    $url->setBaseUri('/');

    return $url;
});

// Registering a Phalcon\Session\Adapter\Files
$di->setShared('session', function () {
    $session = new Phalcon\Session\Adapter\Files();
    $session->start();

    return $session;
});

// get database config
$db = $config->database;

// Register the DB component
$di->set('db', function () use ($db) {
    return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
        "host" => $db->host,
        "username" => $db->username,
        "password" => $db->password,
        "dbname" => $db->dbname,
        "options" => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8",
            PDO::ATTR_CASE => PDO::CASE_LOWER
        )
    ));
});

// Registering the Models-Manager
$di->set('modelsManager', function () {
    return new Phalcon\Mvc\Model\Manager();
});

// Registering the Models-Metadata
$di->set("modelsMetadata", function () {
    return new Phalcon\Mvc\Model\Metadata\Memory();
});

// Registering the view component
$di->set('view', function () {
    $view = new View();
    $view->setViewsDir('../app/views/');
    $view->registerEngines(array(
        ".phtml" => function ($view, $di) {
                $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);

                //set some options here
                $volt->setOptions([
                    'compiledPath' => '../app/storage/compile/',
                    'compiledExtension' => '.cmp',
                ]);

                $volt->getCompiler()->addFunction('isset', 'isset');

                return $volt;
            }
    ));

    return $view;
});

try {
    $application = new Application($di);

    echo $application->handle()->getContent();
} catch (Exception $e) {
    // 记录错误日志信息
    Log::error($e);

    header('Location:/error/page');
}