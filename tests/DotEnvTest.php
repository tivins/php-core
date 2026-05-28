<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tivins\PhpCore\DotEnv;

final class DotEnvTest extends TestCase
{
    /** @var list<string> */
    private const FIXTURE_KEYS = [
        'APP_NAME',
        'APP_ENV',
        'APP_DEBUG',
        'APP_URL',
        'DATABASE_URL',
        'REDIS_URL',
        'JWT_ALG',
        'QUOTED',
        'SINGLE',
        'WITH_COMMENT',
        'PRE_EXISTING',
        '1INVALID',
        'BAD KEY',
    ];

    protected function tearDown(): void
    {
        foreach (self::FIXTURE_KEYS as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }

        parent::tearDown();
    }
    
    public function testLoadFileThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File not found: /path/to/file.env');
        DotEnv::loadFile('/path/to/file.env');
    }

    public function testLoadFileParsesRealisticEnvFixture(): void
    {
        DotEnv::loadFile(__DIR__ . '/fixtures/.env.test');

        self::assertSame('php-core', $_ENV['APP_NAME']);
        self::assertSame('testing', $_ENV['APP_ENV']);
        self::assertSame('false', $_ENV['APP_DEBUG']);
        self::assertSame('http://localhost:8080', $_ENV['APP_URL']);

        self::assertSame(
            'postgresql://app:localdev@127.0.0.1:5432/php_core_test',
            $_ENV['DATABASE_URL']
        );
        self::assertSame('redis://127.0.0.1:6379/0', $_ENV['REDIS_URL']);
        self::assertSame('HS256=default', $_ENV['JWT_ALG']);
    }

    public function testLoadLinesSkipsMalformedLinesWithoutWarning(): void
    {
        DotEnv::loadLines([
            'APP_NAME=php-core',
            'this-line-has-no-equals-sign',
            '=novalue',
            '  APP_ENV = testing ',
        ]);

        self::assertSame('php-core', $_ENV['APP_NAME']);
        self::assertArrayNotHasKey('this-line-has-no-equals-sign', $_ENV);
        self::assertSame('testing', $_ENV['APP_ENV']);
    }

    public function testDoesNotOverwriteExistingEnvByDefault(): void
    {
        $_ENV['PRE_EXISTING'] = 'original';
        putenv('PRE_EXISTING=original');

        DotEnv::loadLines(['PRE_EXISTING=from-dotenv']);

        self::assertSame('original', $_ENV['PRE_EXISTING']);
        self::assertSame('original', getenv('PRE_EXISTING'));
    }

    public function testOverwriteFlagAllowsReplacingExistingEnv(): void
    {
        $_ENV['PRE_EXISTING'] = 'original';
        putenv('PRE_EXISTING=original');

        DotEnv::loadLines(['PRE_EXISTING=from-dotenv'], overwrite: true);

        self::assertSame('from-dotenv', $_ENV['PRE_EXISTING']);
    }

    public function testStripsSurroundingQuotesAndInterpretsEscapes(): void
    {
        DotEnv::loadLines([
            'QUOTED="hello\nworld"',
            "SINGLE='raw \\n value'",
        ]);

        self::assertSame("hello\nworld", $_ENV['QUOTED']);
        self::assertSame('raw \\n value', $_ENV['SINGLE']);
    }

    public function testStripsInlineCommentForUnquotedValue(): void
    {
        DotEnv::loadLines([
            'WITH_COMMENT=value # trailing comment',
        ]);

        self::assertSame('value', $_ENV['WITH_COMMENT']);
    }

    public function testSkipsInvalidKeyNames(): void
    {
        DotEnv::loadLines([
            '1INVALID=nope',
            'BAD KEY=nope',
        ]);

        self::assertArrayNotHasKey('1INVALID', $_ENV);
        self::assertArrayNotHasKey('BAD KEY', $_ENV);
    }
}
