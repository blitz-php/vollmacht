<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Vollmacht\Factories\ApiTokenCookieFactory;

class TransientTokenController extends BaseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected ApiTokenCookieFactory $cookieFactory) {
    }

    /**
     * Get a fresh transient token cookie for the authenticated user.
     */
    public function refresh(Request $request): Response
    {
        return (new Response())
			->withStringBody('Refreshed.')
			->withCookie($this->cookieFactory->make(
            $this->authenticator->getUser()->id, $request->session()->token()
        ));
    }
}
