<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Vollmacht\Contracts\ApprovedDeviceAuthorizationResponse;

class ApproveDeviceAuthorizationController extends BaseController
{
    /**
     * Approve the device authorization request.
     */
    public function __invoke(ApprovedDeviceAuthorizationResponse $response): ApprovedDeviceAuthorizationResponse {
        $deviceCode = $this->getDeviceCodeFromSession();

        $this->withErrorHandling(fn () => $this->server->completeDeviceAuthorizationRequest(
            $deviceCode->getIdentifier(),
            $deviceCode->getUserIdentifier(),
            true
        ));

        return $response;
    }
}
