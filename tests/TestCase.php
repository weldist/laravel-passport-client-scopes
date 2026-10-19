<?php

declare(strict_types=1);

namespace Weldist\Laravel\Passport\ClientScopes\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Weldist\Laravel\Passport\ClientScopes\PassportClientScopesServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Passport::tokensCan([
            'venues:read' => 'Read venues',
            'venues:create' => 'Create venues',
            'tickets:read' => 'Read tickets',
        ]);
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PassportServiceProvider::class,
            PassportClientScopesServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('passport.private_key', $privateKey);
        $app['config']->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../vendor/laravel/passport/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * @param  string[]|null  $scopes
     */
    protected function createClient(?array $scopes): Client
    {
        $client = app(ClientRepository::class)->createClientCredentialsGrantClient('Service');
        $secret = $client->plainSecret;

        $client->forceFill(['scopes' => $scopes])->save();
        $client->plainSecret = $secret;

        return $client;
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestToken(Client $client, ?string $scope = null): array
    {
        return $this->post('/oauth/token', array_filter([
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'scope' => $scope,
        ], fn (?string $value) => $value !== null))->assertOk()->json();
    }

    /**
     * @return string[]
     */
    protected function tokenScopes(string $accessToken): array
    {
        $payload = explode('.', $accessToken)[1];

        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true)['scopes'];
    }
}
