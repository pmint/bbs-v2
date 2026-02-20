<?php

declare(strict_types=1);

namespace App\Support;

final class Router
{
    /** @var array<string, list<array{path:string, regex:string, handler:callable}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = str_replace('\\', '/', dirname($scriptName));
        if ($basePath === '/' || $basePath === '.' || $basePath === '') {
            $basePath = '';
        } else {
            $basePath = rtrim($basePath, '/');
        }
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
            if ($path === false || $path === '') {
                $path = '/';
            }
        }
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
            if ($path === false || $path === '') {
                $path = '/';
            }
        }
        $method = strtoupper($method);

        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            ($route['handler'])($params);
            return;
        }

        if (empty($routes)) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }
        http_response_code(404);
        echo '404 Not Found';
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn(array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $path
        );
        $regex = '#^' . $regex . '$#';
        $this->routes[$method][] = [
            'path' => $path,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }
}
