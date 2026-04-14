<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Http\Redirection;
use BlitzPHP\Vollmacht\Contracts\DeviceUserCodeViewResponse;

class DeviceUserCodeController extends BaseController
{
    /**
     * Show the form for entering the user code.
     */
    public function __invoke(DeviceUserCodeViewResponse $viewResponse): Redirection|DeviceUserCodeViewResponse {
        if ($userCode = $this->request->query('user_code')) {
            return redirect()->route('vollmacht.device.authorizations.authorize', [
                'user_code' => $userCode,
            ]);
        }

        return $viewResponse->withParameters([
            'request' => $this->request,
        ]);
    }
}
