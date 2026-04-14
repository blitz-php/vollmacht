<?php

namespace BlitzPHP\Vollmacht\Responses;

use BlitzPHP\Vollmacht\Contracts\ApprovedDeviceAuthorizationResponse as ApprovedDeviceAuthorizationResponseContract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApprovedDeviceAuthorizationResponse implements ApprovedDeviceAuthorizationResponseContract
{
	/**
	 * {@inheritDoc}
	 */
	public function getResponse(): ResponseInterface
	{
		return redirect()
			->route('vollmacht.device')
			->with('status', 'authorization-approved');
	}

    /**
     * {@inheritDoc}
     */
    public function toResponse(ServerRequestInterface $request): ResponseInterface
	{
        return $this->getResponse();
    }
}
