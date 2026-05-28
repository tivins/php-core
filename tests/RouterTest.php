<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tivins\PhpCore\Api\Router;

final class RouterTest extends TestCase
{
    public function testMatchesStaticRouteAndCapturesParams(): void
    {
        $captured = null;
        $router = new Router();
        $router->add('GET', '/users/{id}', function (array $params) use (&$captured): void {
            $captured = $params;
        });

        self::assertTrue($router->dispatch('GET', '/users/42'));
        self::assertSame(['id' => '42'], $captured);
    }

    public function testMethodIsCaseInsensitiveButPathIsExact(): void
    {
        $router = new Router();
        $router->add('post', '/things', static fn (array $p) => null);

        self::assertTrue($router->dispatch('POST', '/things'));
        self::assertFalse($router->dispatch('GET', '/things'));
    }

    public function testLiteralDotIsNotTreatedAsWildcard(): void
    {
        $hits = 0;
        $router = new Router();
        $router->add('GET', '/v1/api', function (array $p) use (&$hits): void {
            $hits++;
        });

        // Sans échappement, '.' matcherait n'importe quel caractère ('/v1xapi').
        self::assertFalse($router->dispatch('GET', '/v1xapi'));
        self::assertTrue($router->dispatch('GET', '/v1/api'));
        self::assertSame(1, $hits);
    }

    public function testRegexMetacharactersInRouteDoNotBreakDispatch(): void
    {
        $router = new Router();
        $router->add('GET', '/files/a+b#c', static fn (array $p) => null);

        // Le '#' ne doit pas casser le délimiteur, le '+' ne doit pas être un quantifieur.
        self::assertTrue($router->dispatch('GET', '/files/a+b#c'));
        self::assertFalse($router->dispatch('GET', '/files/aaab#c'));
    }

    public function testUnmatchedRouteReturnsFalse(): void
    {
        $router = new Router();
        $router->add('GET', '/known', static fn (array $p) => null);

        self::assertFalse($router->dispatch('GET', '/unknown'));
    }

    public function testParameterConstraintRestrictsMatching(): void
    {
        $captured = null;
        $router = new Router();
        $router->add('GET', '/users/{id:\d+}', function (array $params) use (&$captured): void {
            $captured = $params;
        });

        self::assertTrue($router->dispatch('GET', '/users/42'));
        self::assertSame(['id' => '42'], $captured);
        self::assertFalse($router->dispatch('GET', '/users/abc'));
        self::assertFalse($router->dispatch('GET', '/users/..'));
    }

    public function testParameterConstraintSupportsBraceQuantifiers(): void
    {
        $router = new Router();
        $router->add('GET', '/code/{value:\d{4}}', static fn (array $p) => null);

        self::assertTrue($router->dispatch('GET', '/code/1234'));
        self::assertFalse($router->dispatch('GET', '/code/12'));
        self::assertFalse($router->dispatch('GET', '/code/12345'));
    }

    public function testDuplicateParameterNamesThrow(): void
    {
        $router = new Router();

        $this->expectException(InvalidArgumentException::class);
        $router->add('GET', '/a/{id}/b/{id}', static fn (array $p) => null);
    }

    public function testUnclosedBraceIsTreatedAsLiteral(): void
    {
        $router = new Router();
        $router->add('GET', '/weird/{notclosed', static fn (array $p) => null);

        self::assertTrue($router->dispatch('GET', '/weird/{notclosed'));
        self::assertFalse($router->dispatch('GET', '/weird/x'));
    }
}
