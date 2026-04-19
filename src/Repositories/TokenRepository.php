<?php

namespace BlitzPHP\Vollmacht\Repositories;

use BlitzPHP\Schild\Entities\User;
use BlitzPHP\Utilities\DateTime\Date;
use BlitzPHP\Vollmacht\Entities\Token;
use BlitzPHP\Wolke\Collection;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class TokenRepository
{
    /**
     * Get a token by the given user ID and token ID.
     *
     * @deprecated Use $user->tokens()->find()
     *
	 * @param \BlitzPHP\Vollmacht\Contracts\OAuthenticatable $user
     */
    public function findForUser(string $id, User $user): ?Token
    {
        return $user->tokens()
            ->with('client')
            ->where('revoked', false)
            ->where('expires_at', '>', Date::now())
            ->find($id);
    }

    /**
     * Get the token instances for the given user ID.
     *
     * @deprecated Use $user->tokens()
     *
	 * @param \BlitzPHP\Vollmacht\Contracts\OAuthenticatable $user
	 * 
     * @return Collection<int, Token>
     */
    public function forUser(User $user): Collection
    {
        return $user->tokens()
            ->with('client')
            ->where('revoked', false)
            ->where('expires_at', '>', Date::now())
            ->get();
    }
}
