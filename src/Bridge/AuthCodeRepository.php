<?php

namespace BlitzPHP\Vollmacht\Bridge;

use BlitzPHP\Vollmacht\Vollmacht;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCode;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        Vollmacht::authCode()->forceFill([
            'id'         => $authCodeEntity->getIdentifier(),
            'user_id'    => $authCodeEntity->getUserIdentifier(),
            'client_id'  => $authCodeEntity->getClient()->getIdentifier(),
            'scopes'     => json_encode($authCodeEntity->getScopes()),
            'revoked'    => false,
            'expires_at' => $authCodeEntity->getExpiryDateTime(),
        ])->save();
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAuthCode(string $codeId): void
    {
        Vollmacht::authCode()->newQuery()->whereKey($codeId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthCodeRevoked(string $codeId): bool
    {
        return Vollmacht::authCode()->newQuery()->whereKey($codeId)->where('revoked', false)->doesntExist();
    }
}
