<?php

declare(strict_types=1);

use App\Controllers\LogController;
use App\Controllers\PostController;
use App\Infrastructure\Persistence\SqlitePostRepository;
use App\Services\LogService;
use App\Services\PostService;
use App\Support\Router;

require __DIR__ . '/../app/bootstrap.php';

$config = require __DIR__ . '/../config/app.php';
$repository = new SqlitePostRepository($config['database']['path']);
$service = new PostService($repository);
$logService = new LogService($repository);
$controller = new PostController($service);
$logController = new LogController($logService);

$router = new Router();
$router->get('/', static fn(array $params): mixed => $controller->index());
$router->get('/posts', static fn(array $params): mixed => $controller->index());
$router->get('/posts/create', static fn(array $params): mixed => $controller->create());
$router->post('/posts', static fn(array $params): mixed => $controller->store());
$router->get('/posts/thread/{id}', static fn(array $params): mixed => $controller->thread($params));
$router->get('/posts/{id}/edit', static fn(array $params): mixed => $controller->edit($params));
$router->post('/posts/{id}/update', static fn(array $params): mixed => $controller->update($params));
$router->post('/posts/{id}/delete', static fn(array $params): mixed => $controller->destroy($params));
$router->post('/posts/{id}/like', static fn(array $params): mixed => $controller->toggleLike($params));
$router->get('/logs', static fn(array $params): mixed => $logController->index());
$router->get('/logs/download', static fn(array $params): mixed => $logController->download());

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
