<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    /**
     * Dispatch a matching route.
     *
     * Returns false without emitting a 404 when no route matches. This lets
     * the front controller try both the URL as requested and, for genuine
     * subdirectory installs, the path with the installation prefix removed.
     */
    public function dispatch(string $method, string $path): bool
    {
        $path = $this->normalize($path);

        foreach ($this->routes[strtoupper($method)] ?? [] as $pattern => $handler) {
            $names = [];
            $regex = preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*\*?)\}/',
                static function (array $match) use (&$names): string {
                    $names[] = rtrim($match[1], '*');
                    return str_ends_with($match[1], '*') ? '(.+)' : '([^/]+)';
                },
                $pattern
            );

            if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($names as $index => $name) {
                $params[$name] = rawurldecode($matches[$index] ?? '');
            }

            $handler($params);
            return true;
        }

        return false;
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
