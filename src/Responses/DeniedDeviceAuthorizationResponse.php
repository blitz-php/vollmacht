<?php

namespace BlitzPHP\Vollmacht\Responses;

use BlitzPHP\Vollmacht\Contracts\DeniedDeviceAuthorizationResponse as DeniedDeviceAuthorizationResponseContract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DeniedDeviceAuthorizationResponse implements DeniedDeviceAuthorizationResponseContract
{
    /**
	 * {@inheritDoc}
	 */
	public function getResponse(): ResponseInterface
	{
		return redirect()
			->route('vollmacht.device')
			->with('status', 'authorization-denied');
	}

    /**
     * {@inheritDoc}
     */
    public function toResponse(ServerRequestInterface $request): ResponseInterface
	{
        return $this->getResponse();
    }
}
