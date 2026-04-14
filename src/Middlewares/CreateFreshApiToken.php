<?php

namespace BlitzPHP\Vollmacht\Middlewares;

use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Middlewares\BaseMiddleware;
use BlitzPHP\Vollmacht\Factories\ApiTokenCookieFactory;
use BlitzPHP\Vollmacht\Vollmacht;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CreateFreshApiToken extends BaseMiddleware implements MiddlewareInterface
{
    /**
     * The authentication guard.
     */
    protected ?string $guard = null;

    /**
     * Create a new middleware instance.
     */
    public function __construct(protected ApiTokenCookieFactory $cookieFactory)
	{
    }

    /**
     * Specify the guard for the middleware.
     */
    public static function using(?string $guard = null): string
    {
        $guard = is_null($guard) ? '' : ':'.$guard;

        return static::class.$guard;
    }

	/**
	 * @param Request $request
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$this->guard = $this->arguments[0] ?? null;

		/** @var Response */
		$response = $handler->handle($request);

        if ($this->shouldReceiveFreshToken($request, $response)) {
            $response = $response->withCookie($this->cookieFactory->make(
				auth($this->guard)->id(), session()->token()
            ));
        }

        return $response;
    }

    /**
     * Determine if the given request should receive a fresh token.
     */
    protected function shouldReceiveFreshToken(Request $request, Response $response): bool
    {
        return $this->requestShouldReceiveFreshToken($request) &&
               $this->responseShouldReceiveFreshToken($response);
    }

    /**
     * Determine if the request should receive a fresh token.
     */
    protected function requestShouldReceiveFreshToken(Request $request): bool
    {
        return $request->isMethod('GET') && auth($this->guard)->user();
    }

    /**
     * Determine if the response should receive a fresh token.
     */
    protected function responseShouldReceiveFreshToken(Response $response): bool
    {
        return !$this->alreadyContainsToken($response);
    }

    /**
     * Determine if the given response already contains an API token.
     *
     * This avoids us overwriting a just "refreshed" token.
     */
    protected function alreadyContainsToken(Response $response): bool
    {
        foreach ($response->getCookies() as $cookieName => $cookie) {
            if ($cookieName === Vollmacht::cookie()) {
                return true;
            }
        }

        return false;
    }
}
