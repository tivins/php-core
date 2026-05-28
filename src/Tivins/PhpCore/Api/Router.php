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
        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => '#^' . self::compilePattern($pattern) . '$#',
            'handler' => $handler,
        ];
    }

    /**
     * Compile un motif en regex : les `{name}` deviennent des groupes capturés `[^/]+`,
     * tout le reste est échappé via {@see preg_quote()} pour éviter qu'un caractère spécial
     * (`.`, `#`, `+`, etc.) n'élargisse le matching ou ne casse le délimiteur.
     */
    private static function compilePattern(string $pattern): string
    {
        $segments = preg_split(
            '#(\{[A-Za-z_][A-Za-z0-9_]*\})#',
            $pattern,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if ($segments === false) {
            return preg_quote($pattern, '#');
        }

        $regex = '';
        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }
            if (preg_match('#^\{([A-Za-z_][A-Za-z0-9_]*)\}$#', $segment, $m) === 1) {
                $regex .= '(?P<' . $m[1] . '>[^/]+)';
                continue;
            }
            $regex .= preg_quote($segment, '#');
        }

        return $regex;
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
