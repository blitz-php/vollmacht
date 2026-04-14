<?php

namespace BlitzPHP\Vollmacht\Bridge;

use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Vollmacht\Vollmacht;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
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
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        Vollmacht::refreshToken()->forceFill([
            'id' => $id = $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $accessTokenId = $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'revoked' => false,
            'expires_at' => $refreshTokenEntity->getExpiryDateTime(),
        ])->save();

		$this->events->emit(
			'vollmacht:refresh_token.created',
			$id,
			['id' => $id, 'access_token_id' => $accessTokenId],
		);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeRefreshToken(string $tokenId): void
    {
        Vollmacht::refreshToken()->newQuery()->whereKey($tokenId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        return Vollmacht::refreshToken()->newQuery()->whereKey($tokenId)->where('revoked', false)->doesntExist();
    }
}
