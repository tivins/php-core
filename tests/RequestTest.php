<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tivins\PhpCore\Request;
use Tivins\PhpCore\Response;

final class RequestTest extends TestCase
{
    public function testEmptyUrlThrows(): void
    {
        $this->expectException(\ValueError::class);
        Request::create('');
    }

    public function testGetLocalFileReturnsBody(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'phpcore_req_');
        self::assertNotFalse($tmp);
        try {
            self::assertNotFalse(file_put_contents($tmp, 'hello-curl'));
            $url = self::pathToFileUrl($tmp);
            $response = Request::get($url)
                ->allowedProtocols(CURLPROTO_FILE)
                ->send();

            self::assertFalse($response->hasCurlError(), $response->getCurlError());
            self::assertSame(0, $response->getCurlErrno());
            self::assertStringContainsString('hello-curl', $response->getBody());
        } finally {
            unlink($tmp);
        }
    }

    public function testFileProtocolBlockedByDefault(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'phpcore_req_');
        self::assertNotFalse($tmp);
        try {
            self::assertNotFalse(file_put_contents($tmp, 'secret-data'));
            $url = self::pathToFileUrl($tmp);
            $response = Request::get($url)->send();

            self::assertTrue($response->hasCurlError());
            self::assertStringNotContainsString('secret-data', $response->getBody());
        } finally {
            unlink($tmp);
        }
    }

    public function testGetPublicHostReturns200(): void
    {
        $response = Request::get('https://example.com')->timeout(20)->send();
        if ($response->hasCurlError()) {
            self::markTestSkipped('Réseau / TLS indisponible : ' . $response->getCurlError());
        }
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Example Domain', $response->getBody());
        self::assertTrue($response->isSuccessful());
    }

    public function testQueryParamsDoNotBreakRequest(): void
    {
        $response = Request::get('https://example.com')
            ->query(['utm_test' => 'php-core', 'x' => '1'])
            ->timeout(20)
            ->send();

        if ($response->hasCurlError()) {
            self::markTestSkipped('Réseau / TLS indisponible : ' . $response->getCurlError());
        }
        self::assertSame(200, $response->getStatusCode());
    }

    public function testDecodeJson(): void
    {
        $response = new Response(200, [], '{"ok":true,"n":3}');
        $data = $response->decodeJson();
        self::assertTrue(is_array($data));
        self::assertTrue($data['ok']);
        self::assertSame(3, $data['n']);
    }

    public function testHeaderLineMergesRepeatedHeaders(): void
    {
        $response = new Response(200, [
            'set-cookie' => ['a=1', 'b=2'],
        ], '');
        self::assertSame('a=1, b=2', $response->getHeaderLine('Set-Cookie'));
        self::assertSame('a=1', $response->getHeader('set-cookie'));
    }

    public function testSafetyOptionsCannotBeOverriddenByCurlOptions(): void
    {
        $request = Request::get('https://example.com')
            ->allowedProtocols(CURLPROTO_HTTP | CURLPROTO_HTTPS)
            ->curlOptions([
                CURLOPT_PROTOCOLS => CURLPROTO_FILE,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_FILE,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);

        $options = self::buildCurlOptions($request);

        self::assertSame(CURLPROTO_HTTP | CURLPROTO_HTTPS, $options[CURLOPT_PROTOCOLS]);
        self::assertSame(CURLPROTO_HTTP | CURLPROTO_HTTPS, $options[CURLOPT_REDIR_PROTOCOLS]);
        self::assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        self::assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
    }

    public function testRedirectsNotFollowedWhenBearerTokenPresent(): void
    {
        $withToken = Request::get('https://example.com')->bearerToken('secret');
        self::assertFalse(self::buildCurlOptions($withToken)[CURLOPT_FOLLOWLOCATION]);

        $withoutToken = Request::get('https://example.com');
        self::assertTrue(self::buildCurlOptions($withoutToken)[CURLOPT_FOLLOWLOCATION]);
    }

    public function testExplicitFollowRedirectsOverridesCredentialHeuristic(): void
    {
        $request = Request::get('https://example.com')
            ->bearerToken('secret')
            ->followRedirects(true);

        self::assertTrue(self::buildCurlOptions($request)[CURLOPT_FOLLOWLOCATION]);
    }

    public function testHeaderRejectsCrlfInValue(): void
    {
        $this->expectException(\ValueError::class);
        Request::get('https://example.com')->header('X-Test', "evil\r\nInjected: 1");
    }

    public function testHeaderRejectsCrlfInName(): void
    {
        $this->expectException(\ValueError::class);
        Request::get('https://example.com')->header("X-Test\r\nInjected", 'value');
    }

    /**
     * @return array<int, mixed>
     */
    private static function buildCurlOptions(Request $request): array
    {
        $method = new \ReflectionMethod(Request::class, 'buildCurlOptions');

        /** @var array<int, mixed> $options */
        $options = $method->invoke($request);

        return $options;
    }

    /**
     * Convertit un chemin absolu local en URL `file:` utilisable par cURL (Windows inclus).
     */
    private static function pathToFileUrl(string $absolutePath): string
    {
        $resolved = realpath($absolutePath);
        self::assertNotFalse($resolved);
        $normalized = str_replace('\\', '/', $resolved);
        if ($normalized[0] === '/') {
            return 'file://' . $normalized;
        }
        if (preg_match('#^[A-Za-z]:/#', $normalized) === 1) {
            return 'file:///' . $normalized;
        }
        self::fail('Impossible de fabriquer une URL file: pour ' . $resolved);
    }
}
