<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Schild\Authentication\AuthenticatorInterface;
use BlitzPHP\Schild\Entities\User as UserEntity;
use BlitzPHP\Utilities\DateTime\Date;
use BlitzPHP\Utilities\String\Text;
use BlitzPHP\Vollmacht\Bridge\User;
use BlitzPHP\Vollmacht\Contracts\AuthorizationViewResponse;
use BlitzPHP\Vollmacht\Entities\Client;
use BlitzPHP\Vollmacht\Exceptions\AuthenticationException;
use BlitzPHP\Vollmacht\Exceptions\OAuthServerException;
use BlitzPHP\Vollmacht\Repositories\ClientRepository;
use BlitzPHP\Vollmacht\Scope;
use BlitzPHP\Vollmacht\Vollmacht;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthorizationController extends BaseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AuthorizationServer $server,
        protected AuthenticatorInterface $authenticator,
        protected ClientRepository $clients,
    ) {
    }

    /**
     * Authorize a client to access the user's account.
     */
    public function authorize(
        ResponseInterface $psrResponse,
        AuthorizationViewResponse $viewResponse
    ): Response|AuthorizationViewResponse {
		$request = $this->request;

        $authRequest = $this->withErrorHandling(
            fn (): AuthorizationRequestInterface => $this->server->validateAuthorizationRequest($request),
            ($request->getQueryParams()['response_type'] ?? null) === 'token'
        );

        $prompt = $request->str('prompt')->explode(' ')->map(trim(...))->filter()->values();

        // If the prompt parameter includes "none", all other prompt values will be ignored
        // An error will be returned if the end-user is not already authenticated or the
        // OAuth client does not have pre-configured consent for the requested scopes.
        if ($prompt->contains('none')) {
            $prompt = collect(['none']);
        }

        if (! $this->authenticator->loggedIn()) {
            $prompt->contains('none')
                ? throw OAuthServerException::loginRequired($authRequest)
                : $this->promptForLogin($request);
        }

        if ($prompt->contains('login') &&
            ! $request->session()->get('promptedForLogin', false)) {
            $this->authenticator->logout();
			$request->session()->flush();
			$request->session()->regenerate(true);

            $this->promptForLogin($request);
        }

        $request->session()->remove('promptedForLogin');

		$user = $this->authenticator->getUser();
        $authRequest->setUser(new User($user->id));

        $scopes = $this->parseScopes($authRequest);
        $client = $this->clients->find($authRequest->getClient()->getIdentifier());

        if ($prompt->doesntContain('consent') &&
            ($client->skipsAuthorization($user, $scopes) || $this->hasGrantedScopes($user, $client, $scopes))) {
            return $this->approveRequest($authRequest, $psrResponse);
        }

        if ($prompt->contains('none')) {
            throw OAuthServerException::consentRequired($authRequest);
        }

        $request->session()->put('authToken', $authToken = Text::random());
        $request->session()->put('authRequest', serialize($authRequest));

        return $viewResponse->withParameters([
            'client'    => $client,
            'user'      => $user,
            'scopes'    => $scopes,
            'request'   => $request,
            'authToken' => $authToken,
        ]);
    }

    /**
     * Transform the authorization request's scopes into Scope instances.
     *
     * @return Scope[]
     */
    protected function parseScopes(AuthorizationRequestInterface $authRequest): array
    {
        return Vollmacht::scopesFor(
            collect($authRequest->getScopes())->map(
                fn (ScopeEntityInterface $scope): string => $scope->getIdentifier()
            )->unique()->all()
        );
    }

    /**
     * Determine if the given user has already granted the client access to the scopes.
     *
     * @param Scope[]  $scopes
     */
    protected function hasGrantedScopes(UserEntity $user, Client $client, array $scopes): bool
    {
        $activeTokens = $client->tokens()->where([
            ['user_id', '=', $user->id],
            ['revoked', '=', false],
            ['expires_at', '>', Date::now()],
        ]);

        // If no specific scope is requested, we'll simply check whether the given
        // user has any active tokens that grant access to the specified client
        // In this case, comparing the granted scopes is no longer necessary.
        if (empty($scopes)) {
            return $activeTokens->exists();
        }

        // Otherwise, we list all previously granted scopes from the active tokens
        // of the given user that authorize access to the specified client, and
        // check whether the newly requested scopes are included in that set.
        return collect($scopes)->pluck('id')->diff(
            $activeTokens->pluck('scopes')->flatten()
        )->isEmpty();
    }

    /**
     * Approve the authorization request.
     */
    protected function approveRequest(AuthorizationRequestInterface $authRequest, ResponseInterface $psrResponse): Response
    {
        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, $psrResponse)
        ), $authRequest->getGrantTypeId() === 'implicit');
    }

    /**
     * Prompt the user to login by throwing an AuthenticationException.
     *
     * @throws AuthenticationException
     */
    protected function promptForLogin(Request $request): never
    {
        $request->session()->put('promptedForLogin', true);

        throw new AuthenticationException();
    }
}
