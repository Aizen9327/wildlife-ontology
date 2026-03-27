<?php
/** @var \App\Core\Router $router */

$router->get('/', 'HomeController', 'index');
$router->post('/upload', 'HomeController', 'upload');

// API endpoints for D3.js
$router->get('/api/classes', 'ApiController', 'classes');
$router->get('/api/properties', 'ApiController', 'properties');
$router->get('/api/hierarchy/{concept}', 'ApiController', 'hierarchy');
$router->get('/api/property-hierarchy/{property}', 'ApiController', 'propertyHierarchy');
$router->get('/api/class-properties/{concept}', 'ApiController', 'classProperties');
$router->get('/api/combined', 'ApiController', 'combined');
$router->get('/api/graph', 'ApiController', 'fullGraph');
$router->get('/api/instances/{concept}', 'ApiController', 'instances');

// Wiki
$router->get('/wiki', 'WikiController', 'index');
$router->get('/wiki/{page}', 'WikiController', 'show');
