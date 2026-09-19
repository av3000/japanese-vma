<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnsureHorizonBasicAuth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Issue #249: credentials must come from config so they survive config:cache in production.
 */
class EnsureHorizonBasicAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The middleware short-circuits in `local`; the test env is `testing`.
        config(['horizon.basic_auth' => ['username' => 'ops', 'password' => 'secret']]);
    }

    public function test_reads_credentials_from_config_and_lets_a_matching_request_through(): void
    {
        $request = Request::create('/horizon', 'GET', server: ['PHP_AUTH_USER' => 'ops', 'PHP_AUTH_PW' => 'secret']);

        $response = (new EnsureHorizonBasicAuth)->handle($request, fn () => new Response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertTrue($request->attributes->get(EnsureHorizonBasicAuth::REQUEST_ATTRIBUTE));
    }

    public function test_rejects_wrong_credentials_with_a_basic_challenge(): void
    {
        $request = Request::create('/horizon', 'GET', server: ['PHP_AUTH_USER' => 'ops', 'PHP_AUTH_PW' => 'nope']);

        $response = (new EnsureHorizonBasicAuth)->handle($request, fn () => new Response('ok'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Basic realm="Horizon"', $response->headers->get('WWW-Authenticate'));
    }

    public function test_aborts_when_config_has_no_credentials(): void
    {
        config(['horizon.basic_auth' => ['username' => '', 'password' => '']]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Horizon basic auth credentials are not configured.');

        (new EnsureHorizonBasicAuth)->handle(Request::create('/horizon'), fn () => new Response('ok'));
    }
}
