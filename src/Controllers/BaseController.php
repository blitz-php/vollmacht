<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Controllers\BaseController as CoreController;
use BlitzPHP\Http\Response;
use BlitzPHP\Vollmacht\Exceptions\InvalidAuthTokenException;
use BlitzPHP\Vollmacht\Exceptions\OAuthServerException;
use Closure;
use Exception;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ResponseInterface;

class BaseController extends CoreController
{
	/**
     * Create a new controller instance.
     */
    public function __construct(protected AuthorizationServer $server)
	{
    }

    /**
     * Convert a PSR7 response to a Illuminate Response.
     */
    protected function convertResponse(ResponseInterface $psrResponse): Response
    {
		return (new Response())
			->withBody($psrResponse->getBody())
			->withStatus($psrResponse->getStatusCode(), $psrResponse->getReasonPhrase())
			->withHeaders($psrResponse->getHeaders())
			->withProtocolVersion($psrResponse->getProtocolVersion());
    }

	/**
     * Get the authorization request from the session.
     *
     * @throws InvalidAuthTokenException
     * @throws Exception
     */
    protected function getAuthRequestFromSession(): AuthorizationRequestInterface
    {
		$request = $this->request;

        if ($request->isNotFilled('auth_token') ||
            $request->session()->pull('authToken') !== $request->input('auth_token')) {
            $request->session()->remove(['authToken', 'authRequest']);

            throw InvalidAuthTokenException::different();
        }

        $authRequest = $request->session()->pull('authRequest')
            ?? throw new Exception('Authorization request was not present in the session.');

        return unserialize($authRequest, ['allowed_classes' => [
            \League\OAuth2\Server\RequestTypes\AuthorizationRequest::class,
            \BlitzPHP\Vollmacht\Bridge\Client::class,
            \BlitzPHP\Vollmacht\Bridge\Scope::class,
            \BlitzPHP\Vollmacht\Bridge\User::class,
        ]]);
    }

	/**
     * Get the device code from the session.
     *
     * @throws InvalidAuthTokenException
     * @throws Exception
     */
    protected function getDeviceCodeFromSession(): DeviceCodeEntityInterface
    {
		$request = $this->request;

        if ($request->isNotFilled('auth_token') ||
            $request->session()->pull('authToken') !== $request->input('auth_token')) {
            $request->session()->remove(['authToken', 'deviceCode']);

            throw InvalidAuthTokenException::different();
        }

        $deviceCode = $request->session()->pull('deviceCode')
            ?? throw new Exception('Device code was not present in the session.');

        return unserialize($deviceCode, ['allowed_classes' => [
            \BlitzPHP\Vollmacht\Bridge\DeviceCode::class,
            \BlitzPHP\Vollmacht\Bridge\Client::class,
            \BlitzPHP\Vollmacht\Bridge\Scope::class,
            \DateTimeImmutable::class,
        ]]);
    }

	/**
     * Perform the given callback with exception handling.
     *
     * @template TResult
     *
     * @param  (\Closure(): TResult)  $callback
     * @return TResult
     *
     * @throws OAuthServerException
     */
    protected function withErrorHandling(Closure $callback, bool $useFragment = false)
    {
        try {
            return $callback();
        } catch (LeagueException $e) {
            throw new OAuthServerException($e, $useFragment);
        }
    }
}
