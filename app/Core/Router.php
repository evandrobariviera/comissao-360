<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:array{0:class-string, 1:string}}> */
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $trimmed = $path === '/' ? '/' : rtrim($path, '/');
        $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $trimmed);

        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $path === '/' ? '/' : rtrim($path, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                [$class, $action] = $route['handler'];
                $controller = new $class();
                call_user_func_array([$controller, $action], array_values($matches));
                return;
            }
        }

        http_response_code(404);
        require APP_DIR . '/Views/errors/404.php';
    }
}
