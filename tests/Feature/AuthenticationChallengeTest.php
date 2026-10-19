<?php

declare(strict_types=1);

namespace Weldist\Laravel\Passport\ClientScopes\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Weldist\Laravel\Passport\ClientScopes\Tests\TestCase;

class AuthenticationChallengeTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::get('/venues', fn () => 'venues')->middleware(EnsureClientIsResourceOwner::using('venues:read'));
        Route::get('/me', fn () => 'me')->middleware('auth:api');
        Route::get('/dashboard', fn () => 'dashboard')->middleware('auth:web');
    }

    protected function disableAuthenticationChallenge($app): void
    {
        $app['config']->set('passport-client-scopes.authentication_challenge', false);
    }

    public function test_request_without_token_receives_bearer_challenge(): void
    {
        $this->getJson('/venues')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer');
    }

    public function test_request_with_invalid_token_receives_invalid_token_challenge(): void
    {
        $this->getJson('/venues', ['Authorization' => 'Bearer invalid'])
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer error="invalid_token"');
    }

    public function test_passport_guard_without_token_receives_bearer_challenge(): void
    {
        $this->getJson('/me')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer');
    }

    public function test_passport_guard_with_invalid_token_receives_invalid_token_challenge(): void
    {
        $this->getJson('/me', ['Authorization' => 'Bearer invalid'])
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer error="invalid_token"');
    }

    public function test_other_guards_receive_no_challenge(): void
    {
        $this->getJson('/dashboard')
            ->assertUnauthorized()
            ->assertHeaderMissing('WWW-Authenticate');
    }

    #[DefineEnvironment('disableAuthenticationChallenge')]
    public function test_challenge_can_be_disabled(): void
    {
        $this->getJson('/venues')
            ->assertUnauthorized()
            ->assertHeaderMissing('WWW-Authenticate');
    }
}
