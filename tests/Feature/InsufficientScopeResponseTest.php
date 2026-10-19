<?php

declare(strict_types=1);

namespace Weldist\Laravel\Passport\ClientScopes\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;
use Weldist\Laravel\Passport\ClientScopes\Tests\TestCase;

class InsufficientScopeResponseTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::get('/venues', fn () => 'venues')->middleware(EnsureClientIsResourceOwner::using('venues:read'));
        Route::post('/venues', fn () => 'created')->middleware(EnsureClientIsResourceOwner::using('venues:create'));
    }

    public function test_token_with_the_required_scope_passes(): void
    {
        $client = $this->createClient(['venues:read']);
        $token = $this->requestToken($client);

        $this->getJson('/venues', ['Authorization' => "Bearer {$token['access_token']}"])
            ->assertOk();
    }

    public function test_token_without_the_required_scope_receives_insufficient_scope(): void
    {
        $client = $this->createClient(['venues:read']);
        $token = $this->requestToken($client);

        $this->postJson('/venues', [], ['Authorization' => "Bearer {$token['access_token']}"])
            ->assertForbidden()
            ->assertHeader('WWW-Authenticate', 'Bearer error="insufficient_scope", scope="venues:create"')
            ->assertJsonPath('message', 'Insufficient scope: venues:create');
    }
}
