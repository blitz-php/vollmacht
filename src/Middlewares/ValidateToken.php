<?php

namespace BlitzPHP\Vollmacht\Middlewares;

use BlitzPHP\Middlewares\BaseMiddleware;
use BlitzPHP\Vollmacht\AccessToken;
use BlitzPHP\Vollmacht\Contracts\ScopeAuthorizable;
use BlitzPHP\Vollmacht\Exceptions\AuthenticationException;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class ValidateToken extends BaseMiddleware implements MiddlewareInterface
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(protected ResourceServer $server)
	{
    }

    /**
     * Specify the parameters for the middleware.
     *
     * @param  string[]|string  $param
     */
    public static function using(array|string $param, string ...$params): string
    {
        if (is_array($param)) {
            return static::class.':'.implode(',', $param);
        }

        return static::class.':'.implode(',', [$param, ...$params]);
    }

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$token = $this->validateToken($request);

        $this->validate($token, ...$this->arguments);

        return $handler->handle($request);
	}

    /**
     * Validate and get the request's access token.
     *
     * @throws AuthenticationException
     */
    protected function validateToken(ServerRequestInterface $request): ScopeAuthorizable
    {
		/** @var \BlitzPHP\Vollmacht\Contracts\OAuthenticatable|null */
		$user = auth()->user();

		if ($user?->currentAccessToken()) {
            return $user->currentAccessToken();
        }

        try {
            $request = $this->server->validateAuthenticatedRequest($request);
        } catch (OAuthServerException) {
            throw new AuthenticationException;
        }

        return AccessToken::fromPsrRequest($request);
    }

    /**
     * Validate the given access token.
     */
    abstract protected function validate(ScopeAuthorizable $token, string ...$params): void;
}
