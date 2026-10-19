<?php

declare(strict_types=1);

namespace Weldist\Laravel\Passport\ClientScopes;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\ScopeRepository as PassportScopeRepository;
use Laravel\Passport\Exceptions\MissingScopeException;
use Laravel\Passport\Passport;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PassportClientScopesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PassportScopeRepository::class, ScopeRepository::class);

        Passport::$authorizationServerResponseType ??= new BearerTokenResponse;
    }

    public function boot(): void
    {
        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'passport-client-scopes-migrations');

        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if ($handler instanceof Handler) {
                $handler->map(MissingScopeException::class, fn (MissingScopeException $e) => $this->insufficientScope($e));
            }
        });
    }

    protected function insufficientScope(MissingScopeException $e): HttpException
    {
        $scopes = implode(' ', $e->scopes());

        return new HttpException(403, "Insufficient scope: {$scopes}", $e, [
            'WWW-Authenticate' => "Bearer error=\"insufficient_scope\", scope=\"{$scopes}\"",
        ]);
    }
}
