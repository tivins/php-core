<?php
declare(strict_types=1);

namespace Tivins\PhpCore\Io;

use Exception;
use JsonException;

class File
{
    /**
     * @throws Exception if the file does not exist or cannot be read
     */
    public static function read(string $path): string
    {
        if (!is_file($path)) {
            throw new Exception("File not found: $path");
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throw new Exception("Cannot read file: $path");
        }
        return $content;
    }

    /**
     * @throws Exception if the file does not exist, is empty, or cannot be read
     * @throws JsonException if the file content is not valid JSON
     */
    public static function readJSON(string $path): mixed
    {
        $raw = self::read($path);
        if ($raw === '') {
            throw new Exception("Empty file: $path");
        }
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @throws Exception if the file cannot be written
     * @return int the number of bytes written
     */
    public static function write(string $path, string $content): int
    {
        if (is_dir($path)) {
            throw new Exception("Cannot write file: $path");
        }
        $written = file_put_contents($path, $content, LOCK_EX);
        if ($written === false) {
            throw new Exception("Cannot write file: $path");
        }
        return $written;
    }

    /**
     * @throws JsonException if the data is not valid JSON
     * @throws Exception if the file cannot be written
     * @return int the number of bytes written
     */
    public static function writeJSON(string $path, mixed $data, bool $pretty = false): int
    {
        $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        return self::write($path, json_encode($data, $flags));
    }
}
