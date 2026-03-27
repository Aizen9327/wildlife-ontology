<?php
session_start();

define('ROOT_PATH', __DIR__);
define('APP_PATH',  ROOT_PATH . '/app');
define('DATA_PATH', ROOT_PATH . '/data');

spl_autoload_register(function ($class) {
    foreach ([APP_PATH . '/Controllers/', APP_PATH . '/Models/'] as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});

$route  = $_GET['route']  ?? 'landing';
$action = $_GET['action'] ?? 'index';

$map = [
    'landing' => 'LandingController',
    'app'     => 'AppController',
    'upload'  => 'UploadController',
    'api'     => 'ApiController',
];

$controllerClass = $map[$route] ?? 'LandingController';
require_once APP_PATH . '/Controllers/' . $controllerClass . '.php';

$controller = new $controllerClass();
method_exists($controller, $action) ? $controller->$action() : $controller->index();
