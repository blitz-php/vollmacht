<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Http\Response;

class AccessTokenController extends BaseController
{
	/**
     * Issue an access token.
     */
    public function issueToken(): Response
    {
        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->respondToAccessTokenRequest($this->request, $this->response)
        ));
    }
}
