<?php

declare(strict_types=1);

namespace Weldist\Laravel\Passport\ClientScopes\Tests\Feature;

use Laravel\Passport\Bridge\ClientRepository;
use Laravel\Passport\Bridge\ScopeRepository;
use Weldist\Laravel\Passport\ClientScopes\ScopeRepository as ClientScopesScopeRepository;
use Weldist\Laravel\Passport\ClientScopes\Tests\TestCase;

class DefaultScopesTest extends TestCase
{
    public function test_scope_repository_is_replaced(): void
    {
        $this->assertInstanceOf(ClientScopesScopeRepository::class, app(ScopeRepository::class));
    }

    public function test_token_without_scope_receives_every_scope_granted_to_the_client(): void
    {
        $client = $this->createClient(['venues:read', 'venues:create']);

        $token = $this->requestToken($client);

        $this->assertSame(['venues:read', 'venues:create'], $this->grantedScopes($token));
    }

    public function test_client_with_null_scopes_receives_no_scope(): void
    {
        $client = $this->createClient(null);

        $token = $this->requestToken($client);

        $this->assertSame([], $this->grantedScopes($token));
    }

    public function test_client_with_empty_scopes_receives_no_scope(): void
    {
        $client = $this->createClient([]);

        $token = $this->requestToken($client);

        $this->assertSame([], $this->grantedScopes($token));
    }

    public function test_undefined_scopes_granted_to_the_client_are_dropped(): void
    {
        $client = $this->createClient(['venues:read', 'venues:delete']);

        $token = $this->requestToken($client);

        $this->assertSame(['venues:read'], $this->grantedScopes($token));
    }

    public function test_requested_scopes_narrow_the_token(): void
    {
        $client = $this->createClient(['venues:read', 'venues:create']);

        $token = $this->requestToken($client, 'venues:read');

        $this->assertSame(['venues:read'], $this->grantedScopes($token));
    }

    public function test_requested_scopes_not_granted_to_the_client_are_dropped(): void
    {
        $client = $this->createClient(['venues:read']);

        $token = $this->requestToken($client, 'venues:read tickets:read');

        $this->assertSame(['venues:read'], $this->grantedScopes($token));
    }

    public function test_token_response_scope_matches_the_issued_access_token(): void
    {
        $client = $this->createClient(['venues:read', 'venues:create']);

        $token = $this->requestToken($client, 'venues:create');

        $this->assertSame($this->accessTokenClaimScopes($token['access_token']), $this->grantedScopes($token));
    }

    public function test_other_grants_do_not_receive_default_scopes(): void
    {
        $client = $this->createClient(['venues:read']);
        $clientEntity = app(ClientRepository::class)->getClientEntity($client->getKey());

        $scopes = app(ScopeRepository::class)->finalizeScopes([], 'password', $clientEntity);

        $this->assertSame([], $scopes);
    }
}
