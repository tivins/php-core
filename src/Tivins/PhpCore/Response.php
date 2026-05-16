<?php
declare(strict_types=1);

namespace Tivins\PhpCore;

use JsonException;

final class Response
{
    /**
     * @param array<string, list<string>> $headers Keys en minuscules (ex. `content-type`).
     */
    public function __construct(
        private int $statusCode,
        private array $headers,
        private string $body,
        private int $curlErrno = 0,
        private string $curlError = '',
    ) {
    }

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Première valeur pour un en-tête (insensible à la casse du nom).
     */
    public function getHeader(string $name): ?string
    {
        $key = strtolower($name);
        if (!isset($this->headers[$key]) || $this->headers[$key] === []) {
            return null;
        }
        return $this->headers[$key][0];
    }

    /**
     * Toutes les valeurs d’un en-tête répété, séparées par ", " (usage courant côté client).
     */
    public function getHeaderLine(string $name): string
    {
        $values = $this->headers[strtolower($name)] ?? [];
        return implode(', ', $values);
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getCurlErrno(): int
    {
        return $this->curlErrno;
    }

    public function getCurlError(): string
    {
        return $this->curlError;
    }

    public function hasCurlError(): bool
    {
        return $this->curlErrno !== 0;
    }

    /**
     * Réponse HTTP considérée comme OK : pas d’erreur cURL et code 2xx.
     */
    public function isSuccessful(): bool
    {
        return !$this->hasCurlError()
            && $this->statusCode >= 200
            && $this->statusCode < 300;
    }

    /**
     * @throws JsonException si le corps n’est pas du JSON valide
     * @return mixed
     */
    public function decodeJson(bool $assoc = true, int $depth = 512, int $flags = 0): mixed
    {
        return json_decode(
            $this->body,
            $assoc,
            $depth,
            $flags | JSON_THROW_ON_ERROR,
        );
    }
}
