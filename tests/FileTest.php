<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tivins\PhpCore\Exception\MkDirException;
use Tivins\PhpCore\Io\File;

final class FileTest extends TestCase
{
    private ?string $tempPath = null;

    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        if ($this->tempPath !== null && is_file($this->tempPath)) {
            unlink($this->tempPath);
        }
        $this->tempPath = null;

        foreach (array_reverse($this->tempDirs) as $dir) {
            $this->removeDirRecursive($dir);
        }
        $this->tempDirs = [];

        parent::tearDown();
    }

    public function testReadReturnsFileContents(): void
    {
        $path = $this->createTempFile('plain-text');

        self::assertSame('plain-text', File::read($path));
    }

    public function testReadThrowsWhenFileDoesNotExist(): void
    {
        $path = sys_get_temp_dir() . '/phpcore_missing_' . uniqid('', true) . '.txt';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("File not found: $path");

        File::read($path);
    }

    public function testWriteReturnsBytesWritten(): void
    {
        $path = $this->tempPath();

        $written = File::write($path, 'abc');

        self::assertSame(3, $written);
        self::assertSame('abc', file_get_contents($path));
    }

    public function testReadJSONDecodesAssociativeArray(): void
    {
        $path = $this->createTempFile('{"name":"php-core","count":2,"active":true}');

        $data = File::readJSON($path);

        self::assertSame([
            'name' => 'php-core',
            'count' => 2,
            'active' => true,
        ], $data);
    }

    public function testReadJSONThrowsWhenFileIsEmpty(): void
    {
        $path = $this->createTempFile('');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Empty file: $path");

        File::readJSON($path);
    }

    public function testReadJSONDecodesJsonZero(): void
    {
        $path = $this->createTempFile('0');

        self::assertSame(0, File::readJSON($path));
    }

    public function testReadJSONThrowsOnInvalidJson(): void
    {
        $path = $this->createTempFile('{not json}');

        $this->expectException(JsonException::class);

        File::readJSON($path);
    }

    public function testWriteJSONEncodesWithUnicodeAndSlashesUnescaped(): void
    {
        $path = $this->tempPath();
        $payload = ['message' => 'café', 'path' => '/api/v1'];

        File::writeJSON($path, $payload);

        self::assertSame(
            '{"message":"café","path":"/api/v1"}',
            file_get_contents($path)
        );
    }

    public function testWriteJSONRoundTrip(): void
    {
        $path = $this->tempPath();
        $payload = ['items' => [1, 2], 'meta' => ['ok' => true]];

        File::writeJSON($path, $payload);

        self::assertSame($payload, File::readJSON($path));
    }

    public function testWriteJSONPrettyPrint(): void
    {
        $path = $this->tempPath();
        $payload = ['message' => 'café', 'path' => '/api/v1'];

        File::writeJSON($path, $payload, pretty: true);

        $expected = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
        self::assertSame($expected, file_get_contents($path));
        self::assertSame($payload, File::readJSON($path));
    }

    public function testWriteThrowsWhenPathIsNotWritable(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot write file:');

        File::write(sys_get_temp_dir(), 'content');
    }

    public function testWriteJSONThrowsOnNonEncodableValue(): void
    {
        $path = $this->tempPath();
        $handle = fopen('php://memory', 'r');
        self::assertIsResource($handle);

        try {
            $this->expectException(JsonException::class);

            File::writeJSON($path, $handle);
        } finally {
            fclose($handle);
        }
    }

    public function testMakeDirCreatesNestedDirectory(): void
    {
        $base = $this->tempDir();
        $nested = $base . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b';

        File::makeDir($nested);

        self::assertDirectoryExists($nested);
    }

    public function testMakeDirIsNoOpWhenDirectoryAlreadyExists(): void
    {
        $dir = $this->tempDir();

        File::makeDir($dir);
        File::makeDir($dir);

        self::assertDirectoryExists($dir);
    }

    public function testMakeDirForFileCreatesParentDirectories(): void
    {
        $base = $this->tempDir();
        $file = $base . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'out.txt';

        File::makeDirForFile($file);

        self::assertDirectoryExists(dirname($file));
        self::assertFileDoesNotExist($file);
    }

    public function testMakeDirThrowsMkDirExceptionWhenParentIsAFile(): void
    {
        $parent = $this->tempPath();
        $dir = $parent . DIRECTORY_SEPARATOR . 'child';

        $this->expectException(MkDirException::class);
        $this->expectExceptionMessage('Cannot create the folder: ' . $dir);

        try {
            File::makeDir($dir);
        } catch (MkDirException $e) {
            self::assertSame($dir, $e->dir);
            throw $e;
        }
    }

    private function tempPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'phpcore_file_');
        self::assertNotFalse($path);
        $this->tempPath = $path;

        return $path;
    }

    private function createTempFile(string $content): string
    {
        $path = $this->tempPath();
        self::assertNotFalse(file_put_contents($path, $content));

        return $path;
    }

    private function tempDir(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpcore_dir_' . uniqid('', true);
        self::assertTrue(mkdir($dir));
        $this->tempDirs[] = $dir;

        return $dir;
    }

    private function removeDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $entries = scandir($dir);
        if ($entries === false) {
            return;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDirRecursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
