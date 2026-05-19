<?php
declare(strict_types=1);

namespace Tivins\PhpCore;

use RuntimeException;

class Tty
{
    public static function isCLI(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * @throws RuntimeException if context is not CLI.
     */
    public static function ensureIsCLI(): void
    {
        if (!self::isCLI()) {
            throw new RuntimeException('Must be run in CLI');
        }
    }
}