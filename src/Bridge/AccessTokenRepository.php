<?php

namespace BlitzPHP\Vollmacht\Bridge;

use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Vollmacht\Vollmacht;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(protected EventManagerInterface $events)
	{
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, ?string $userIdentifier = null): AccessTokenEntityInterface
	{
        return new Vollmacht::$accessTokenEntity($userIdentifier, $scopes, $clientEntity);
    }

    /**
     * {@inheritDoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        Vollmacht::token()->forceFill([
            'id'         => $id = $accessTokenEntity->getIdentifier(),
            'user_id'    => $userId = $accessTokenEntity->getUserIdentifier(),
            'client_id'  => $clientId = $accessTokenEntity->getClient()->getIdentifier(),
            'scopes'     => $accessTokenEntity->getScopes(),
            'revoked'    => false,
            'expires_at' => $accessTokenEntity->getExpiryDateTime(),
        ])->save();

		$this->events->emit(
			'vollmacht:access_token.created',
			$id,
			['id' => $id, 'user_id' => $userId, 'client_id' => $clientId]
		);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken(string $tokenId): void
    {
        if (Vollmacht::token()->newQuery()->whereKey($tokenId)->update(['revoked' => true])) {
            $this->events->emit('vollmacht:access_token.revoked', $tokenId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return Vollmacht::token()->newQuery()->whereKey($tokenId)->where('revoked', false)->doesntExist();
    }
}
