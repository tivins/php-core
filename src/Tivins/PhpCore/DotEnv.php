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

    public static function tryLoadFile(string $filename): void
    { 
        try { self::loadFile($filename); } 
        catch (Exception) {}
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
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if ($key === '') {
                continue;
            }
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}