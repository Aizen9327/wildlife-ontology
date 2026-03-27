<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');

require ROOT_PATH . '/vendor/autoload.php';

use App\Core\Router;
use App\Core\Request;

// Session
session_start();

// Load routes
$router = new Router();
require ROOT_PATH . '/config/routes.php';

// Dispatch
$request = new Request();
$router->dispatch($request);
