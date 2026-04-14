<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccessTokenController extends BaseController
{
	/**
     * Issue an access token.
     */
    public function issueToken(ServerRequestInterface $psrRequest, ResponseInterface $psrResponse): Response
    {
        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->respondToAccessTokenRequest($psrRequest, $psrResponse)
        ));
    }
}
