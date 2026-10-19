<?php

declare(strict_types=1);

namespace Weldist\Laravel\Passport\ClientScopes;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\AuthenticationException as PassportAuthenticationException;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationChallenge
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $response = $next($request);

        if ($response->getStatusCode() === 401
            && ! $response->headers->has('WWW-Authenticate')
            && $this->isBearerFailure($response->exception ?? null)) {
            $response->headers->set('WWW-Authenticate', $token ? 'Bearer error="invalid_token"' : 'Bearer');
        }

        return $response;
    }

    protected function isBearerFailure(mixed $exception): bool
    {
        if ($exception instanceof PassportAuthenticationException) {
            return true;
        }

        if (! $exception instanceof AuthenticationException) {
            return false;
        }

        return collect($exception->guards() ?: [config('auth.defaults.guard')])
            ->contains(fn (?string $guard) => config("auth.guards.{$guard}.driver") === 'passport');
    }
}
