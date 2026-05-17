<?php

declare(strict_types=1);

namespace Tivins\PhpCore\Api;

/**
 * Clé symétrique HS256 pour les JWT. Valeur lue dans la variable d'environnement {@see ENV_NAME}.
 *
 * Pour HS256, une clé d'au moins 256 bits (32 octets) est recommandée.
 */
final class JwtSigningSecret
{
    public const ENV_NAME = 'JWT_SECRET';

    private const MIN_KEY_LENGTH = 32;

    /**
     * @throws \RuntimeException si la clé est absente ou trop courte pour HS256
     *
     * @return non-empty-string
     */
    public static function get(): string
    {
        $raw = $_ENV[self::ENV_NAME] ?? getenv(self::ENV_NAME);
        if (! is_string($raw) || $raw === '') {
            throw new \RuntimeException(
                sprintf(
                    'La variable d\'environnement %s doit être définie (chaîne non vide) pour signer ou vérifier les JWT.',
                    self::ENV_NAME
                )
            );
        }
        if (strlen($raw) < self::MIN_KEY_LENGTH) {
            throw new \RuntimeException(
                sprintf(
                    '%s doit faire au moins %d octets pour une utilisation HS256 sûre.',
                    self::ENV_NAME,
                    self::MIN_KEY_LENGTH
                )
            );
        }

        return $raw;
    }
}
