<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Http\Response;
use BlitzPHP\Validation\Rule;
use BlitzPHP\Vollmacht\Entities\Token;
use BlitzPHP\Vollmacht\PersonalAccessTokenResult;
use BlitzPHP\Vollmacht\Repositories\TokenRepository;
use BlitzPHP\Vollmacht\Vollmacht;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class PersonalAccessTokenController extends BaseController
{
    /**
     * Create a controller instance.
     */
    public function __construct(protected TokenRepository $tokenRepository) 
	{
    }

    /**
     * Get all of the personal access tokens for the authenticated user.
     *
     * @return \BlitzPHP\Wolke\Collection<int, Token>
     */
    public function forUser()
    {
        return $this->tokenRepository->forUser($this->authenticator->getUser())
            ->filter(
                fn (Token $token): bool => ! $token->client->revoked && $token->client->hasGrantType('personal_access')
            )
            ->values();
    }

    /**
     * Create a new personal access token for the user.
     */
    public function store(): PersonalAccessTokenResult
    {
        $post = $this->validate([
            'name'   => ['required', 'max:255'],
            'scopes' => ['array', Rule::in(Vollmacht::scopeIds())],
        ])->toArray();

        return $this->authenticator->getUser()->createToken(
            $post['name'], $post['scopes'] ?: []
        );
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

        return (new Response())->withStatus(StatusCode::NO_CONTENT);
    }
}
