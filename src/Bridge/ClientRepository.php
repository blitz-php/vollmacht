<?php

namespace BlitzPHP\Vollmacht\Bridge;

use BlitzPHP\Contracts\Security\HasherInterface;
use BlitzPHP\Vollmacht\Entities\Client as ClientEntity;
use BlitzPHP\Vollmacht\Repositories\ClientRepository as ClientModelRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(protected ClientModelRepository $clients, protected HasherInterface $hasher)
	{
    }

    /**
     * {@inheritdoc}
     */
    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $record = $this->clients->findActive($clientIdentifier);

        return $record ? $this->fromClientEntity($record) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $record = $this->clients->findActive($clientIdentifier);

        return $record && ! empty($clientSecret) && $this->hasher->check($clientSecret, $record->secret);
    }

    /**
     * Get the personal access client for the given provider.
     */
    public function getPersonalAccessClientEntity(string $provider): ?ClientEntityInterface
    {
        return $this->fromClientEntity(
            $this->clients->personalAccessClient($provider)
        );
    }

    /**
     * Create a new league client from the given client entity instance.
     */
    protected function fromClientEntity(ClientEntity $entity): ClientEntityInterface
    {
        return new Client(
            $entity->getKey(),
            $entity->name,
            $entity->redirect_uris,
            $entity->confidential(),
            $entity->provider,
            $entity->grant_types
        );
    }
}
