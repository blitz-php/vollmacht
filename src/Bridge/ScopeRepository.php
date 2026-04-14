<?php

namespace BlitzPHP\Vollmacht\Bridge;

use BlitzPHP\Utilities\Iterable\Collection;
use BlitzPHP\Vollmacht\Entities\Client;
use BlitzPHP\Vollmacht\Repositories\ClientRepository;
use BlitzPHP\Vollmacht\Vollmacht;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * Create a new scope repository.
     */
    public function __construct(protected ClientRepository $clients)
	{
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        return Vollmacht::hasScope($identifier) ? new Scope($identifier) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        return collect($scopes)
            ->unless(in_array($grantType, ['password', 'personal_access', 'client_credentials']),
                fn (Collection $scopes): Collection => $scopes->reject(
                    fn (ScopeEntityInterface $scope): bool => $scope->getIdentifier() === '*'
                )
            )
            ->filter(fn (ScopeEntityInterface $scope): bool => Vollmacht::hasScope($scope->getIdentifier()))
            ->when($this->clients->findActive($clientEntity->getIdentifier()),
                fn (Collection $scopes, Client $client): Collection => $scopes->filter(
                    fn (ScopeEntityInterface $scope): bool => $client->hasScope($scope->getIdentifier())
                )
            )
            ->values()
            ->all();
    }
}
