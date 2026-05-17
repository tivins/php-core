<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tivins\PhpCore\CliArgv;

final class CliArgvTest extends TestCase
{
    public function testNormalizeGetoptValueHandlesScalarFalseNullAndArray(): void
    {
        $argv = new CliArgv(['script.php']);

        self::assertSame('', $argv->normalizeGetoptValue(false));
        self::assertSame('', $argv->normalizeGetoptValue(null));
        self::assertSame('last', $argv->normalizeGetoptValue(['a', 'last']));
        self::assertSame('x', $argv->normalizeGetoptValue(' x '));
    }

    public function testLongFlagValueReadsEqualsFormAndSeparateValue(): void
    {
        $argv = new CliArgv(['bin/cli.php', '--theme=dark', '--count', '3']);

        self::assertSame('dark', $argv->longFlagValue('theme'));
        self::assertSame('3', $argv->longFlagValue('count'));
        self::assertNull($argv->longFlagValue('missing'));
    }

    public function testLongFlagValueWithoutValueUsesEmptyStringWhenNextIsAnotherFlag(): void
    {
        $argv = new CliArgv(['cli.php', '--verbose', '--theme', 'blue']);

        self::assertSame('', $argv->longFlagValue('verbose'));
        self::assertSame('blue', $argv->longFlagValue('theme'));
    }

    public function testShortFlagValueReadsEqualsFormAndSeparateValue(): void
    {
        $argv = new CliArgv(['cli.php', '-o', 'out.txt', '-j={"a":1}']);

        self::assertSame('out.txt', $argv->shortFlagValue('o'));
        self::assertSame('{"a":1}', $argv->shortFlagValue('j'));
        self::assertNull($argv->shortFlagValue('x'));
    }

    public function testSplitDoubleDashSeparatesOperands(): void
    {
        $argv = new CliArgv(['cli.php', '-v', '--', 'input.txt', '--not-a-flag']);

        self::assertSame(
            ['cli.php', '-v'],
            $argv->splitDoubleDash()['before']
        );
        self::assertSame(
            ['input.txt', '--not-a-flag'],
            $argv->splitDoubleDash()['after']
        );
    }

    public function testSplitDoubleDashWithoutSeparatorReturnsFullArgvInBefore(): void
    {
        $argv = new CliArgv(['cli.php', 'run']);

        self::assertSame(['cli.php', 'run'], $argv->splitDoubleDash()['before']);
        self::assertSame([], $argv->splitDoubleDash()['after']);
    }

    public function testHasLongFlagAndHasShortFlag(): void
    {
        $argv = new CliArgv(['x.php', '--a', '-b']);

        self::assertTrue($argv->hasLongFlag('a'));
        self::assertFalse($argv->hasLongFlag('z'));
        self::assertTrue($argv->hasShortFlag('b'));
        self::assertFalse($argv->hasShortFlag('z'));
    }

    public function testConstructorRejectsNonStringEntries(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CliArgv(['ok', 1]);
    }

    public function testLongFlagNameValidationRejectsInvalidNames(): void
    {
        $argv = new CliArgv(['cli.php']);

        $this->expectException(InvalidArgumentException::class);
        $argv->longFlagValue('');
    }

    public function testShortFlagMustBeSingleCharacter(): void
    {
        $argv = new CliArgv(['cli.php']);

        $this->expectException(InvalidArgumentException::class);
        $argv->shortFlagValue('ab');
    }
}
