<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        http_response_code(200);
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        parent::tearDown();
    }

    public function testDispatchPassesNamedRouteParameter(): void
    {
        $router = new Router();
        $captured = null;
        $router->get('/posts/{id}', static function (array $params) use (&$captured): void {
            $captured = $params;
        });

        $router->dispatch('GET', '/posts/42');

        self::assertSame(['id' => '42'], $captured);
    }

    public function testDispatchIgnoresQueryStringWhenMatchingRoute(): void
    {
        $router = new Router();
        $captured = null;
        $router->get('/posts/{id}', static function (array $params) use (&$captured): void {
            $captured = $params;
        });

        $router->dispatch('GET', '/posts/42?page=2');

        self::assertSame(['id' => '42'], $captured);
    }

    public function testDispatchMatchesLowercaseMethodInput(): void
    {
        $router = new Router();
        $called = false;
        $router->get('/posts', static function () use (&$called): void {
            $called = true;
        });

        $router->dispatch('get', '/posts');

        self::assertTrue($called);
    }

    public function testDispatchHandlesBasePathAndIndexPhpPrefix(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/cgi-bin/bbs-v2/public/index.php';

        $router = new Router();
        $called = false;
        $router->get('/posts', static function () use (&$called): void {
            $called = true;
        });

        $router->dispatch('GET', '/cgi-bin/bbs-v2/public/index.php/posts?x=1');

        self::assertTrue($called);
    }

    public function testDispatchReturns404ForMissingMethodRoutes(): void
    {
        $router = new Router();
        $router->get('/posts', static function (): void {
        });

        ob_start();
        $router->dispatch('PUT', '/posts');
        $output = (string) ob_get_clean();

        self::assertSame(404, http_response_code());
        self::assertSame('404 Not Found', $output);
    }

    public function testDispatchReturns404ForUnknownPathOnRegisteredMethod(): void
    {
        $router = new Router();
        $router->get('/posts', static function (): void {
        });

        ob_start();
        $router->dispatch('GET', '/unknown');
        $output = (string) ob_get_clean();

        self::assertSame(404, http_response_code());
        self::assertSame('404 Not Found', $output);
    }
}
