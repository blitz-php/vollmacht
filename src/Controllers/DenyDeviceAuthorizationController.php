<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Vollmacht\Contracts\DeniedDeviceAuthorizationResponse;

class DenyDeviceAuthorizationController extends BaseController
{
    /**
     * Deny the device authorization request.
     */
    public function __invoke(DeniedDeviceAuthorizationResponse $response): DeniedDeviceAuthorizationResponse
	{
        $deviceCode = $this->getDeviceCodeFromSession();

        $this->withErrorHandling(fn () => $this->server->completeDeviceAuthorizationRequest(
            $deviceCode->getIdentifier(),
            $deviceCode->getUserIdentifier(),
            false
        ));

        return $response;
    }
}
