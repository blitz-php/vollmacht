<?php

namespace BlitzPHP\Vollmacht\Middlewares;

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Middlewares\BaseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TokenAuth extends BaseMiddleware implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
		$authenticator = auth(parametre('vollmacht.authenticator') ?? 'vollmacht')->getAuthenticator();

		$token = $request->getHeaderLine(parametre('vollmacht.authenticator_header.tokens') ?? 'Authorization');

        $result = $authenticator->attempt(['token' => $token]);

        if (! $result->isOK()) {
            return service('response')->json(['error' => $result->reason()], StatusCode::INVALID_TOKEN);
        }

        if (parametre('auth.record_active_date')) {
            $authenticator->recordActiveDate();
        }

        return $handler->handle($request);
    }
}
