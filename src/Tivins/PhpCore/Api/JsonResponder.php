<?php

declare(strict_types=1);

namespace Tivins\PhpCore\Api;

final class JsonResponder
{
    public const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    /**
     * @param array<string, mixed>|list<mixed> $body
     */
    public static function send(int $code, array $body): never
    {
        if (! headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($code);
        }
        echo json_encode($body, self::JSON_FLAGS);

        exit($code === 200 ? 0 : 1);
    }
}
