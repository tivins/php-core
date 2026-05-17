<?php

declare(strict_types=1);

namespace Tivins\PhpCore\Api;

/**
 * Mini dispatcher : table (méthode, motif → handler). Les motifs supportent `{name}` capturé.
 */
final class Router
{
    /** @var list<array{method:string,regex:string,handler:callable}> */
    private array $routes = [];

    /**
     * @param callable(array<string, string>): void $handler
     */
    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace('#\{([A-Za-z_][A-Za-z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    /**
     * Renvoie true si une route a matché et a été exécutée (peut ne pas revenir si le handler `exit`).
     */
    public function dispatch(string $method, string $path): bool
    {
        $method = strtoupper($method);
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $m)) {
                $params = [];
                foreach ($m as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = (string) $value;
                    }
                }
                ($route['handler'])($params);

                return true;
            }
        }

        return false;
    }
}
