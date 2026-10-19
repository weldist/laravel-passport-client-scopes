# weldist/laravel-passport-client-scopes

[![Tests](https://github.com/weldist/laravel-passport-client-scopes/actions/workflows/tests.yml/badge.svg)](https://github.com/weldist/laravel-passport-client-scopes/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-blue)](https://www.php.net)
[![Laravel Passport](https://img.shields.io/badge/Passport-13.x-purple)](https://laravel.com/docs/passport)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE.md)

Per-client default scopes and standard `insufficient_scope` responses for [Laravel Passport](https://laravel.com/docs/passport) **client credentials** tokens. Grant a service its scopes once, on the server, and stop maintaining the same list in every client's `.env`.

> A [weld.ist](https://weld.ist) project.
>
> Unofficial. Not affiliated with Laravel.

## The Problem

Passport lets you restrict what a client may receive through the `oauth_clients.scopes` column, but that list is only a **ceiling**. A client credentials token carries exactly the scopes the client asks for:

```
POST /oauth/token  grant_type=client_credentials             → token with no scopes → every scoped route answers 403
POST /oauth/token  grant_type=client_credentials  scope=...  → token with the requested scopes
```

So every machine client has to keep a copy of its own permission list and send it on each token request. Every time the server grants a new scope, each client needs a config change and a redeploy. Asking for `*` doesn't help either: once the client's `scopes` column is set, `*` is not in it and gets filtered out.

Passport's token response doesn't include the `scope` field either, so a client can't tell which scopes it was actually issued.

When a scope is missing, Passport answers with a generic `403`. A client cannot tell it apart from any other authorization failure, so it cannot react, for example by fetching a fresh token.

## The Solution

This package welds the missing pieces onto Passport:

- **Default scopes per client.** A client credentials request without a `scope` parameter receives every scope granted to the client in `oauth_clients.scopes`. [RFC 6749 §3.3](https://www.rfc-editor.org/rfc/rfc6749#section-3.3) explicitly allows this pre-defined default, and it's how Auth0, GitHub App installation tokens, Microsoft Entra (`/.default`) and Keycloak behave.
- **Issued scopes in the token response.** The response includes the `scope` field, so the client knows what it received without inspecting the token.
- **`insufficient_scope` responses.** A token without a required scope receives the [RFC 6750 §3.1](https://www.rfc-editor.org/rfc/rfc6750#section-3.1) challenge, so clients can tell exactly which scope is missing:

```http
HTTP/1.1 403 Forbidden
WWW-Authenticate: Bearer error="insufficient_scope", scope="venues:create"

{"message": "Insufficient scope: venues:create"}
```

A client that does send `scope` still gets the narrower token it asked for. Other grant types are unaffected.

## Requirements

- PHP `^8.4`
- `laravel/passport ^13.0`

## Installation

```bash
composer require weldist/laravel-passport-client-scopes
```

The service provider is auto-discovered — no manual registration.

## Setup

Passport's default `oauth_clients` migration has no `scopes` column. If your table doesn't have one yet, publish and run this package's migration:

```bash
php artisan vendor:publish --tag=passport-client-scopes-migrations
php artisan migrate
```

Then grant scopes to a client:

```php
use Laravel\Passport\ClientRepository;

$client = app(ClientRepository::class)->createClientCredentialsGrantClient('Billing Service');

$client->forceFill(['scopes' => ['invoices:read', 'invoices:create']])->save();
```

The client now requests a token without any scope and receives both.

## How It Works

| `oauth_clients.scopes` | Token request without `scope` | Token request with `scope` |
|---|---|---|
| `null` | no scopes | requested scopes (Passport default: no restriction) |
| `[]` | no scopes | no scopes |
| `["a", "b"]` | `a b` | requested scopes that are in the list |

- Scopes in the column that aren't defined with `Passport::tokensCan()` are dropped.
- `null` never grants everything by default — an unconfigured client receives nothing until you fill its column.
- The token response always carries the issued scopes in its `scope` field, as [RFC 6749 §5.1](https://www.rfc-editor.org/rfc/rfc6749#section-5.1) requires whenever they differ from the requested ones. Clients should read their scopes from there and treat the access token itself as opaque ([RFC 9068 §6](https://www.rfc-editor.org/rfc/rfc9068#section-6)).

```json
{"scope": "invoices:read invoices:create", "token_type": "Bearer", "expires_in": 31536000, "access_token": "..."}
```

### Minimal override

- `ScopeRepository` extends Passport's `Bridge\ScopeRepository` and overrides only `finalizeScopes()`: when the grant is `client_credentials` and nothing was requested, it substitutes the client's granted scopes, then hands over to Passport's own filtering.
- `BearerTokenResponse` extends league's `BearerTokenResponse` and only adds the `scope` field. It is installed through `Passport::$authorizationServerResponseType` unless your application already set its own response type; in that case add the `scope` field there.
- `MissingScopeException` is mapped to an `HttpException` carrying the `WWW-Authenticate` header; the rest of your exception rendering stays as it is.

## Testing

```bash
# Build first (once per PHP version)
DOCKER_BUILDKIT=0 docker compose --profile php84 build

# Run tests
docker compose --profile php84 up --abort-on-container-exit --exit-code-from php84   # PHP 8.4
docker compose --profile php85 up --abort-on-container-exit --exit-code-from php85   # PHP 8.5

# Run a single test class or method
docker compose --profile php84 run --rm php84 vendor/bin/phpunit --filter DefaultScopesTest
```

CI runs PHP 8.4–8.5 via `.github/workflows/tests.yml`.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/license/MIT).
