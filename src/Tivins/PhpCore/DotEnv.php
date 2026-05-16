<?php
declare(strict_types=1);

namespace Tivins\PhpCore;

use Exception;

class DotEnv
{
    /**
     * Override the environment variables with the lines in the file.
     * @throws Exception if the file does not exist
     */
    public static function loadFile(string $filename): void
    {
        if (!file_exists($filename)) {
            throw new Exception("File not found: $filename");
        }
        $lines = file($filename);
        self::loadLines($lines);
    }

    /**
     * Override the environment variables with the lines in the file.
     */
    public static function loadLines(array $lines): void
    {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $parts = explode('=', $line, 2);
            putenv($parts[0] . '=' . $parts[1]);
            $_ENV[$parts[0]] = $parts[1];
        }
    }
}