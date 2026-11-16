<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];
    /** @var array<string, array<string, array{regex:string,names:array,handler:callable}>> */
    private array $compiledRoutes = [];

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Compile parameterized route patterns once when routes are registered,
     * not again for every incoming request.
     */
    private function addRoute(string $method, string $path, callable $handler): void
    {
        $path = $this->normalize($path);
        $this->routes[$method][$path] = $handler;
        $names = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*\*?)\}/',
            static function (array $match) use (&$names): string {
                $names[] = rtrim($match[1], '*');
                return str_ends_with($match[1], '*') ? '(.+)' : '([^/]+)';
            },
            $path
        );
        $this->compiledRoutes[$method][$path] = [
            'regex' => '#^' . $regex . '$#',
            'names' => $names,
            'handler' => $handler,
        ];
    }

    /**
     * Check whether a path has a literal route (without placeholders).
     * Literal routes must take precedence over parameterized routes when
     * the application mount path overlaps a real route prefix.
     */
    public function hasExactRoute(string $method, string $path): bool
    {
        $path = $this->normalize($path);
        return isset($this->routes[strtoupper($method)][$path])
            && !str_contains($path, '{');
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

        $routes = $this->compiledRoutes[strtoupper($method)] ?? [];

        // Literal routes must win over parameterized routes regardless of
        // registration order (for example /admin/blog/categories vs /admin/blog/{id}).
        foreach ([true, false] as $literalPass) {
            foreach ($routes as $route) {
                if (($route['names'] === []) !== $literalPass) {
                    continue;
                }
                if (!preg_match($route['regex'], $path, $matches)) {
                    continue;
                }

                array_shift($matches);
                $params = [];
                foreach ($route['names'] as $index => $name) {
                    $params[$name] = rawurldecode($matches[$index] ?? '');
                }

                ($route['handler'])($params);
                return true;
            }
        }

        return false;
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
