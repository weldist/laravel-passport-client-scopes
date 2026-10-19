<?php

declare(strict_types=1);

namespace Weldist\Laravel\Passport\ClientScopes;

use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\ScopeRepository as PassportScopeRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class ScopeRepository extends PassportScopeRepository
{
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        if ($grantType === 'client_credentials' && $scopes === []) {
            $scopes = $this->grantedScopes($clientEntity);
        }

        return parent::finalizeScopes($scopes, $grantType, $clientEntity, $userIdentifier, $authCodeId);
    }

    /**
     * @return Scope[]
     */
    protected function grantedScopes(ClientEntityInterface $clientEntity): array
    {
        $client = $this->clients->findActive($clientEntity->getIdentifier());

        return collect($client?->scopes ?? [])
            ->map(fn (string $scope) => $this->getScopeEntityByIdentifier($scope))
            ->filter()
            ->values()
            ->all();
    }
}
