<?php

namespace BlitzPHP\Vollmacht\Middlewares;

use BlitzPHP\Vollmacht\AccessToken;
use BlitzPHP\Vollmacht\Contracts\ScopeAuthorizable;
use BlitzPHP\Vollmacht\Exceptions\AuthenticationException;
use BlitzPHP\Vollmacht\Exceptions\MissingScopeException;

class EnsureClientIsResourceOwner extends ValidateToken
{
    /**
     * Determine if the token's client is the resource owner and has all the given scopes.
     *
     * @throws AuthenticationException|MissingScopeException
     */
    protected function validate(ScopeAuthorizable $token, string ...$params): void
    {
        if (
            $token instanceof AccessToken
            && ! is_null($token->oauth_user_id)
            && $token->oauth_user_id !== $token->oauth_client_id
        ) {
            throw new AuthenticationException;
        }

        foreach ($params as $scope) {
            if ($token->cant($scope)) {
                throw new MissingScopeException($scope);
            }
        }
    }
}
