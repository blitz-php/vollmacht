<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Http\Response;

class ApproveAuthorizationController extends BaseController
{
	/**
     * Approve the authorization request.
     */
    public function approve(): Response
    {
        $authRequest = $this->getAuthRequestFromSession();

        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, $this->response)
        ), $authRequest->getGrantTypeId() === 'implicit');
    }
}
