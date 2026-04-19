<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Http\Response;
use BlitzPHP\Vollmacht\Entities\Token;
use BlitzPHP\Vollmacht\Repositories\TokenRepository;

class AuthorizedAccessTokenController extends BaseController
{
	/**
     * Create a new controller instance.
     */
    public function __construct(protected TokenRepository $tokenRepository) 
	{
    }

    /**
     * Get all of the authorized tokens for the authenticated user.
     *
     * @return \BlitzPHP\Wolke\Collection<int, Token>
     */
    public function forUser()
    {
		return $this->tokenRepository->forUser($this->authenticator->getUser())
            ->reject(fn (Token $token): bool => $token->client->revoked || $token->client->firstParty())
            ->values();
    }

    /**
     * Delete the given token.
     */
    public function destroy(string $tokenId): Response
    {
        $token = $this->tokenRepository->findForUser($tokenId, $this->authenticator->getUser());

        if (is_null($token)) {
            return (new Response())->withStatus(StatusCode::NOT_FOUND);
        }

        $token->revoke();
        $token->refreshToken?->revoke();

        return (new Response())->withStatus(StatusCode::NO_CONTENT);
    }
}
