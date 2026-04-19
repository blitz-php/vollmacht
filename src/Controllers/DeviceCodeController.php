<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Http\Redirection;
use BlitzPHP\Http\Response;
use BlitzPHP\View\View;

class DeviceCodeController extends BaseController
{
	/**
     * Show the form for entering the user code.
     */
    public function form(): Redirection|View 
	{
        if ($userCode = $this->request->query('user_code')) {
            return redirect()->route('vollmacht.device.authorizations.authorize', [
                'user_code' => $userCode,
            ]);
        }

		return view(parametre('vollmacht.views.device-user-code'))->with([
            'request'=> $this->request
		]);
    }

    /**
     * Issue a device code for the client.
     */
    public function process(): Response
    {
        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->respondToDeviceAuthorizationRequest($this->request, $this->response)
        ));
    }
}
