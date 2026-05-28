<?php

declare(strict_types=1);

namespace Tivins\PhpCore\Api;

/**
 * Mini dispatcher : table (méthode, motif → handler).
 *
 * Les motifs supportent les paramètres `{name}` (capturé, par défaut `[^/]+`, donc un seul segment)
 * et `{name:contrainte}` où `contrainte` est une sous-expression regex de confiance définie par
 * l'application (jamais issue d'une entrée utilisateur), p. ex. `{id:\d+}` ou `{code:\d{4}}`.
 *
 * Par défaut, `{name}` accepte n'importe quel caractère hors `/` (y compris `.`/`..`). Si la valeur
 * sert à construire un chemin de fichier, validez-la (ou contraignez-la via `{name:...}`) côté appelant.
 */
final class Router
{
    /** @var list<array{method:string,regex:string,handler:callable}> */
    private array $routes = [];

    /**
     * @param callable(array<string, string>): void $handler
     *
     * @throws \InvalidArgumentException si un nom de paramètre apparaît plusieurs fois dans le motif
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
     * Compile un motif en regex : les `{name}` / `{name:contrainte}` deviennent des groupes
     * capturés nommés, tout le reste est échappé via {@see preg_quote()} pour éviter qu'un
     * caractère spécial (`.`, `#`, `+`, etc.) n'élargisse le matching ou ne casse le délimiteur.
     *
     * @throws \InvalidArgumentException si un nom de paramètre est dupliqué
     */
    private static function compilePattern(string $pattern): string
    {
        $regex = '';
        $literal = '';
        $names = [];
        $length = strlen($pattern);
        $i = 0;

        while ($i < $length) {
            if ($pattern[$i] === '{') {
                $end = self::findPlaceholderEnd($pattern, $i);
                if ($end !== null) {
                    if ($literal !== '') {
                        $regex .= preg_quote($literal, '#');
                        $literal = '';
                    }
                    $token = substr($pattern, $i + 1, $end - $i - 1);
                    [$name, $constraint] = self::splitPlaceholder($token);
                    if (isset($names[$name])) {
                        throw new \InvalidArgumentException(
                            sprintf('Duplicate route parameter name "%s" in pattern "%s".', $name, $pattern)
                        );
                    }
                    $names[$name] = true;
                    $regex .= '(?P<' . $name . '>' . ($constraint ?? '[^/]+') . ')';
                    $i = $end + 1;
                    continue;
                }
            }
            $literal .= $pattern[$i];
            $i++;
        }

        if ($literal !== '') {
            $regex .= preg_quote($literal, '#');
        }

        return $regex;
    }

    /**
     * Renvoie l'index de l'accolade fermante d'un placeholder valide démarrant à `{`, ou `null`.
     * Gère les contraintes contenant des accolades équilibrées (quantifieurs comme `\d{2,4}`).
     */
    private static function findPlaceholderEnd(string $pattern, int $start): ?int
    {
        $length = strlen($pattern);
        $i = $start + 1;
        if ($i >= $length || preg_match('/[A-Za-z_]/', $pattern[$i]) !== 1) {
            return null;
        }
        $i++;
        while ($i < $length && preg_match('/[A-Za-z0-9_]/', $pattern[$i]) === 1) {
            $i++;
        }
        if ($i >= $length) {
            return null;
        }
        if ($pattern[$i] === '}') {
            return $i;
        }
        if ($pattern[$i] !== ':') {
            return null;
        }

        $depth = 0;
        for ($i++; $i < $length; $i++) {
            $char = $pattern[$i];
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                if ($depth === 0) {
                    return $i;
                }
                $depth--;
            }
        }

        return null;
    }

    /**
     * @return array{0:string,1:?string} nom et contrainte (ou `null` si absente/vide)
     */
    private static function splitPlaceholder(string $token): array
    {
        $pos = strpos($token, ':');
        if ($pos === false) {
            return [$token, null];
        }
        $name = substr($token, 0, $pos);
        $constraint = substr($token, $pos + 1);

        return [$name, $constraint === '' ? null : $constraint];
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
