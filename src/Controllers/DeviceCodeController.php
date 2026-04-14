<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Http\Response;

class DeviceCodeController extends BaseController
{
    /**
     * Issue a device code for the client.
     */
    public function __invoke(): Response
    {
        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->respondToDeviceAuthorizationRequest($this->request, $this->response)
        ));
    }
}
