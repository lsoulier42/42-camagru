<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Routeur minimal : correspondance méthode + chemin, avec support
 * des paramètres dynamiques (`/image/{id}`). Les requêtes inconnues
 * répondent 404 avec une vue dédiée.
 */
final class Router
{
    /** @var array<string, array<int, array{path: string, regex: ?string, handler: string}>> */
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, string $handler): void
    {
        $regex = null;
        if (str_contains($path, '{')) {
            $pattern = preg_replace(
                '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                '(?P<$1>[^/]+)',
                $path
            );
            $regex = '#^' . $pattern . '$#';
        }

        $this->routes[$method][] = [
            'path' => $path,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes[strtoupper($method)] ?? [] as $route) {
            if ($route['regex'] === null) {
                if ($route['path'] === $path) {
                    $this->handle($route['handler'], []);
                    return;
                }
            } elseif (preg_match($route['regex'], $path, $matches) === 1) {
                // Ne garder que les groupes nommés, dans l'ordre.
                $named = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->handle($route['handler'], array_values($named));
                return;
            }
        }

        http_response_code(404);
        View::render('error/404');
    }

    /** @param list<string> $params */
    private function handle(string $handler, array $params): void
    {
        [$controller, $method] = explode('@', $handler);
        $class = 'App\\Controllers\\' . $controller . 'Controller';

        if (!class_exists($class) || !method_exists($class, $method)) {
            http_response_code(500);
            error_log("[Camagru] Route invalide : {$handler}");
            View::render('error/500');
            return;
        }

        $instance = new $class();
        $instance->$method(...$params);
    }
}
