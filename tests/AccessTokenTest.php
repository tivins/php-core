<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tivins\PhpCore\Api\AccessToken;
use Tivins\PhpCore\Api\JwtSigningSecret;

final class AccessTokenTest extends TestCase
{
    private const SECRET =
        '0123456789abcdef0123456789abcdef'; // 32 octets — minimum HS256

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV[JwtSigningSecret::ENV_NAME] = self::SECRET;
        putenv(JwtSigningSecret::ENV_NAME . '=' . self::SECRET);
    }

    protected function tearDown(): void
    {
        unset($_ENV[JwtSigningSecret::ENV_NAME]);
        putenv(JwtSigningSecret::ENV_NAME);

        parent::tearDown();
    }

    public function testIssueAndVerifyRoundTrip(): void
    {
        $token = AccessToken::issue(42);
        self::assertNotSame('', $token);
        self::assertSame(42, AccessToken::verify($token));
    }

    public function testVerifyReturnsNullForEmptyAndGarbage(): void
    {
        self::assertNull(AccessToken::verify(''));
        self::assertNull(AccessToken::verify('   '));
        self::assertNull(AccessToken::verify('not-a-jwt'));
    }

    public function testIssueRejectsNonPositiveUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AccessToken::issue(0);
    }

    public function testVerifyRejectsNonPositiveSubject(): void
    {
        $secret = self::SECRET;
        $now = time();
        $forged = \Firebase\JWT\JWT::encode(
            ['sub' => '0', 'iat' => $now, 'exp' => $now + 3600],
            $secret,
            'HS256'
        );

        self::assertNull(AccessToken::verify($forged));
    }

    public function testIssueThrowsWhenSecretMissing(): void
    {
        unset($_ENV[JwtSigningSecret::ENV_NAME]);
        putenv(JwtSigningSecret::ENV_NAME);

        $this->expectException(RuntimeException::class);
        AccessToken::issue(1);
    }

    public function testIssueThrowsWhenSecretTooShort(): void
    {
        $_ENV[JwtSigningSecret::ENV_NAME] = str_repeat('a', 31);
        putenv(JwtSigningSecret::ENV_NAME . '=' . str_repeat('a', 31));

        $this->expectException(RuntimeException::class);
        AccessToken::issue(1);
    }
}
