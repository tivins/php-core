<?php

declare(strict_types=1);

namespace Tivins\PhpCore\Api;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class AccessToken
{
    private const ALGORITHM = 'HS256';

    public static function ttlSeconds(): int
    {
        return 3600;
    }

    /**
     * @throws \InvalidArgumentException si $userId n'est pas strictement positif
     * @throws \RuntimeException si {@see JwtSigningSecret::ENV_NAME} n'est pas configurée correctement
     */
    public static function issue(int $userId): string
    {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('User id must be a positive integer.');
        }
        $now = time();
        $payload = [
            'sub' => (string) $userId,
            'iat' => $now,
            'exp' => $now + self::ttlSeconds(),
        ];

        return JWT::encode($payload, JwtSigningSecret::get(), self::ALGORITHM);
    }

    /** @return int|null identifiant utilisateur si le JWT est valide et non expiré */
    public static function verify(string $jwt): ?int
    {
        $jwt = trim($jwt);
        if ($jwt === '') {
            return null;
        }
        $secret = JwtSigningSecret::get();
        try {
            $decoded = JWT::decode($jwt, new Key($secret, self::ALGORITHM));
            if (! isset($decoded->sub)) {
                return null;
            }
            $userId = (int) $decoded->sub;

            return $userId > 0 ? $userId : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
