<?php
declare(strict_types=1);

namespace Tivins\PhpCore;

use CurlHandle;
use ValueError;

/**
 * Client HTTP minimal basé sur cURL (GET/POST/autres verbes, en-têtes, corps, auth basique, options supplémentaires).
 */
class Request
{
    private string $method = 'GET';
    private string $url;
    /** @var array<string, string> */
    private array $headers = [];
    /** @var array<string, string|int|float|bool|array<int, string>> */
    private array $query = [];
    private string|array|null $body = null;
    private int $timeoutSeconds = 30;
    /**
     * `null` = automatique : on suit les redirections sauf si des identifiants (Authorization /
     * auth basique) sont présents, afin de ne pas les divulguer à une cible de redirection
     * potentiellement cross-origin. Une valeur explicite via {@see followRedirects()} prime.
     */
    private ?bool $followRedirects = null;
    private bool $verifySsl = true;
    private string $userAgent = 'tivins/php-core (+https://github.com/tivins/php-core)';
    /**
     * Masque binaire CURLPROTO_* autorisé pour la requête et les redirections.
     * Par défaut HTTP/HTTPS uniquement, afin de bloquer `file://`, `gopher://`, etc. (anti-SSRF).
     */
    private int $allowedProtocols = CURLPROTO_HTTP | CURLPROTO_HTTPS;
    /** @var array<int, mixed> Options CURLOPT_* => valeur */
    private array $curlOptions = [];

    final private function __construct(string $url)
    {
        $this->url = $url;
    }

    public static function create(string $url): self
    {
        if ($url === '') {
            throw new ValueError('URL must not be empty.');
        }
        return new static($url);
    }

    public static function get(string $url): self
    {
        return self::create($url)->method('GET');
    }

    public static function post(string $url): self
    {
        return self::create($url)->method('POST');
    }

    public static function put(string $url): self
    {
        return self::create($url)->method('PUT');
    }

    public static function patch(string $url): self
    {
        return self::create($url)->method('PATCH');
    }

    public static function delete(string $url): self
    {
        return self::create($url)->method('DELETE');
    }

    public function method(string $method): static
    {
        $m = strtoupper(trim($method));
        if ($m === '') {
            throw new ValueError('HTTP method must not be empty.');
        }
        $this->method = $m;
        return $this;
    }

    /**
     * Ajoute ou remplace un en-tête (une seule valeur par nom côté requête).
     *
     * @throws ValueError si le nom est vide, ou si le nom/la valeur contient un retour
     *                    chariot ou un saut de ligne (prévention de l'injection d'en-têtes / CRLF)
     */
    public function header(string $name, string $value): static
    {
        $name = trim($name);
        if ($name === '') {
            throw new ValueError('Header name must not be empty.');
        }
        if (self::containsCrlf($name) || self::containsCrlf($value)) {
            throw new ValueError('Header name and value must not contain CR or LF characters.');
        }
        $this->headers[$name] = $value;
        return $this;
    }

    private static function containsCrlf(string $value): bool
    {
        return str_contains($value, "\r") || str_contains($value, "\n");
    }

    /**
     * @param array<string, string|int|float|bool|array<int, string>> $query
     */
    public function query(array $query): static
    {
        $this->query = array_merge($this->query, $query);
        return $this;
    }

    public function body(string|array $payload): static
    {
        $this->body = $payload;
        return $this;
    }

    /**
     * Envoie un tableau comme JSON (UTF-8) et fixe `Content-Type: application/json` sauf déjà défini.
     */
    public function jsonBody(array $data, int $flags = JSON_UNESCAPED_UNICODE): static
    {
        if (!$this->hasHeader('Content-Type')) {
            $this->header('Content-Type', 'application/json; charset=utf-8');
        }
        $json = json_encode($data, JSON_THROW_ON_ERROR | $flags);
        $this->body = $json;
        return $this;
    }

    public function bearerToken(string $token): static
    {
        $this->header('Authorization', 'Bearer ' . $token);
        return $this;
    }

    public function basicAuth(string $username, string $password): static
    {
        $this->curlOptions[CURLOPT_USERPWD] = $username . ':' . $password;
        $this->curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        return $this;
    }

    public function timeout(int $seconds): static
    {
        if ($seconds < 0) {
            throw new ValueError('Timeout must be >= 0.');
        }
        $this->timeoutSeconds = $seconds;
        return $this;
    }

    /**
     * Force le suivi (ou non) des redirections.
     *
     * Passer `null` rétablit le mode automatique : suivi sauf si des identifiants sont présents.
     * Activer explicitement le suivi alors qu'un en-tête `Authorization` est posé peut divulguer
     * le jeton à la cible de redirection (cURL réémet les en-têtes personnalisés sur redirection).
     */
    public function followRedirects(?bool $follow = true): static
    {
        $this->followRedirects = $follow;
        return $this;
    }

    public function verifySsl(bool $verify = true): static
    {
        $this->verifySsl = $verify;
        return $this;
    }

    public function userAgent(string $agent): static
    {
        $this->userAgent = $agent;
        return $this;
    }

    /**
     * Restreint les protocoles autorisés (requête et redirections) via un masque CURLPROTO_*.
     *
     * Par défaut, seuls `http`/`https` sont permis. N'élargir cette liste (ex. `CURLPROTO_FILE`)
     * que pour des URL de confiance, jamais construites à partir d'entrées utilisateur (risque SSRF).
     */
    public function allowedProtocols(int $protocols): static
    {
        $this->allowedProtocols = $protocols;
        return $this;
    }

    /**
     * Fusionne des options cURL (écrase une clé déjà définie via cette méthode).
     *
     * @param array<int, mixed> $options
     */
    public function curlOptions(array $options): static
    {
        $this->curlOptions = $options + $this->curlOptions;
        return $this;
    }

    public function send(): Response
    {
        $url = $this->buildUrl();
        $ch = curl_init($url);
        if ($ch === false) {
            return new Response(0, [], '', -1, 'curl_init failed');
        }

        return $this->execHandle($ch);
    }

    private function execHandle(CurlHandle $ch): Response
    {
        /** @var array<string, list<string>> $responseHeaders */
        $responseHeaders = [];

        $options = $this->buildCurlOptions();
        $options[CURLOPT_HEADERFUNCTION] = static function ($curl, string $headerLine) use (&$responseHeaders): int {
            $len = strlen($headerLine);
            if (str_contains($headerLine, ':')) {
                [$name, $value] = explode(':', $headerLine, 2);
                $name = strtolower(trim($name));
                $value = trim($value);
                $responseHeaders[$name][] = $value;
            }
            return $len;
        };

        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch) ?: '';
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($body === false) {
            $body = '';
        }

        return new Response($status, $responseHeaders, is_string($body) ? $body : '', $errno, $error);
    }

    /**
     * Construit le tableau d'options cURL final.
     *
     * Les options fournies via {@see curlOptions()} sont fusionnées par-dessus les défauts, MAIS
     * les garde-fous de sécurité (protocoles autorisés et vérification TLS) sont ré-appliqués
     * ensuite : un appelant ne peut donc pas désactiver silencieusement l'anti-SSRF ou la
     * vérification TLS via `curlOptions()`. Utilisez {@see allowedProtocols()} / {@see verifySsl()}.
     *
     * @return array<int, mixed>
     */
    private function buildCurlOptions(): array
    {
        $options = [
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => $this->shouldFollowRedirects(),
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_UNRESTRICTED_AUTH => false,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_USERAGENT => $this->userAgent,
        ];

        // Magasin de certificats natif du système (souvent utile sous Windows si curl.cainfo n’est pas défini).
        if ($this->verifySsl && defined('CURLSSLOPT_NATIVE_CA')) {
            $options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
        }

        if ($this->body !== null) {
            $options[CURLOPT_POSTFIELDS] = $this->body;
        }

        $headerLines = [];
        foreach ($this->headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }
        if ($headerLines !== []) {
            $options[CURLOPT_HTTPHEADER] = $headerLines;
        }

        $merged = array_replace($options, $this->curlOptions);

        // Garde-fous inviolables : appliqués APRÈS la fusion pour primer sur `curlOptions()`.
        $merged[CURLOPT_PROTOCOLS] = $this->allowedProtocols;
        $merged[CURLOPT_REDIR_PROTOCOLS] = $this->allowedProtocols;
        $merged[CURLOPT_SSL_VERIFYPEER] = $this->verifySsl;
        $merged[CURLOPT_SSL_VERIFYHOST] = $this->verifySsl ? 2 : 0;

        return $merged;
    }

    private function shouldFollowRedirects(): bool
    {
        if ($this->followRedirects !== null) {
            return $this->followRedirects;
        }

        return !$this->hasSensitiveCredentials();
    }

    private function hasSensitiveCredentials(): bool
    {
        if (isset($this->curlOptions[CURLOPT_USERPWD])) {
            return true;
        }

        return $this->hasHeader('Authorization');
    }

    private function buildUrl(): string
    {
        if ($this->query === []) {
            return $this->url;
        }
        $qs = http_build_query($this->query);
        if ($qs === '') {
            return $this->url;
        }
        $sep = str_contains($this->url, '?') ? '&' : '?';
        return $this->url . $sep . $qs;
    }

    private function hasHeader(string $name): bool
    {
        $want = strtolower($name);
        foreach (array_keys($this->headers) as $key) {
            if (strtolower($key) === $want) {
                return true;
            }
        }
        return false;
    }
}
