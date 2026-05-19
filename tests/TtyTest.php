<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tivins\PhpCore\Tty;

final class TtyTest extends TestCase
{
    public function testIsCLIReturnsTrueWhenRunningUnderCliSapi(): void
    {
        if (PHP_SAPI !== 'cli') {
            self::markTestSkipped('PHPUnit is not running under the CLI SAPI.');
        }

        self::assertTrue(Tty::isCLI());
    }

    public function testEnsureIsCLIDoesNotThrowWhenRunningUnderCliSapi(): void
    {
        if (PHP_SAPI !== 'cli') {
            self::markTestSkipped('PHPUnit is not running under the CLI SAPI.');
        }

        Tty::ensureIsCLI();

        self::assertTrue(true);
    }
}
