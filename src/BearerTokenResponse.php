<?php

declare(strict_types=1);

namespace Weldist\Laravel\Passport\ClientScopes;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse as LeagueBearerTokenResponse;

class BearerTokenResponse extends LeagueBearerTokenResponse
{
    /**
     * @return array{scope: string}
     */
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        return [
            'scope' => implode(' ', array_map(
                fn (ScopeEntityInterface $scope) => $scope->getIdentifier(),
                $accessToken->getScopes(),
            )),
        ];
    }
}
