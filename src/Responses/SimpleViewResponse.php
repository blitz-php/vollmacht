<?php

namespace BlitzPHP\Vollmacht\Responses;

use BlitzPHP\Contracts\Http\ResponsableInterface;
use BlitzPHP\Vollmacht\Contracts\AuthorizationViewResponse;
use BlitzPHP\Vollmacht\Contracts\DeviceAuthorizationViewResponse;
use BlitzPHP\Vollmacht\Contracts\DeviceUserCodeViewResponse;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SimpleViewResponse implements AuthorizationViewResponse, DeviceAuthorizationViewResponse, DeviceUserCodeViewResponse
{
    /**
     * An array of arguments that may be passed to the view response and used in the view.
     *
     * @var array<string, mixed>
     */
    protected array $parameters = [];

    /**
     * Create a new response instance.
     *
     * @param  (\Closure(array<string, mixed>): (ResponseInterface))|string  $view
     */
    public function __construct(protected Closure|string $view)
	{
    }

    /**
     * Add parameters to response.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function withParameters(array $parameters = []): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
	 * {@inheritDoc}
	 */
	public function getResponse(): ResponseInterface
	{
		return $this->toResponse(service('request'));
	}

    /**
     * {@inheritDoc}
     */
    public function toResponse(ServerRequestInterface $request): ResponseInterface
	{
        if (! is_callable($this->view) || is_string($this->view)) {
			return service('response')->view($this->view, $this->parameters);
        }

        $response = call_user_func($this->view, $this->parameters);

        if ($response instanceof ResponsableInterface) {
            return $response->toResponse($request);
        }

        return $response;
    }
}
