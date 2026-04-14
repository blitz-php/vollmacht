<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Http\Response;

class DenyAuthorizationController extends BaseController
{
    /**
     * Deny the authorization request.
     */
    public function deny(): Response
    {
        $authRequest = $this->getAuthRequestFromSession();

        $authRequest->setAuthorizationApproved(false);

        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, $this->response)
        ), $authRequest->getGrantTypeId() === 'implicit');
    }
}
