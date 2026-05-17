<?php

declare(strict_types=1);

namespace Tivins\PhpCore\Api;

final class Auth
{
    public static function bearerToken(): ?string
    {
        $header = self::authorizationHeader();
        if ($header === '') {
            return null;
        }
        if (! preg_match('/^\s*Bearer\s+(\S+)/i', $header, $m)) {
            return null;
        }

        return $m[1];
    }

    /**
     * Vérifie qu'un Bearer valide est présent ; renvoie le user id, ou termine la requête en 401.
     */
    public static function requireUserId(): int
    {
        $token = self::bearerToken();
        if ($token === null) {
            JsonResponder::send(401, ['error' => 'Missing or invalid Authorization Bearer token']);
        }
        $userId = AccessToken::verify($token);
        if ($userId === null) {
            JsonResponder::send(401, ['error' => 'Invalid or expired access token']);
        }

        return $userId;
    }

    private static function authorizationHeader(): string
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && is_string($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && is_string($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strcasecmp((string) $name, 'Authorization') === 0) {
                        return (string) $value;
                    }
                }
            }
        }

        return '';
    }
}