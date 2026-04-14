<?php

namespace BlitzPHP\Vollmacht\Factories;

use BlitzPHP\Http\ServerRequestFactory;
use BlitzPHP\Vollmacht\PersonalAccessTokenResult;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PersonalAccessTokenFactory
{
    /**
     * Create a new personal access token factory instance.
     */
    public function __construct(protected AuthorizationServer $server)
	{
    }

    /**
     * Create a new personal access token.
     *
     * @param  string[]  $scopes
     */
    public function make(string|int $userId, string $name, array $scopes, string $provider): PersonalAccessTokenResult
    {
        return new PersonalAccessTokenResult(
            $this->dispatchRequestToAuthorizationServer(
                $this->createRequest($userId, $name, $scopes, $provider)
            )
        );
    }

    /**
     * Create a request instance for the given client.
     *
     * @param  string[]  $scopes
     */
    protected function createRequest(string|int $userId, string $name, array $scopes, string $provider): ServerRequestInterface
    {
		$factory = new ServerRequestFactory();

		return $factory->createServerRequest('POST', config('app.baseUrl'))
			->withParsedBody([
				'grant_type' => 'personal_access',
				'provider' => $provider,
				'user_id' => $userId,
				'scope' => implode(' ', $scopes),
				'name' => $name,
			]);
    }

    /**
     * Dispatch the given request to the authorization server.
     *
     * @return array<string, mixed>
     */
    protected function dispatchRequestToAuthorizationServer(ServerRequestInterface $request): array
    {
        return json_decode($this->server->respondToAccessTokenRequest(
            $request, service(ResponseInterface::class)
        )->getBody()->__toString(), true);
    }
}
