<?php

namespace BlitzPHP\Vollmacht\Contracts;

use BlitzPHP\Contracts\Http\ResponsableInterface;

interface AuthorizationViewResponse extends ResponsableInterface
{
    /**
     * Specify the parameters that should be passed to the view.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function withParameters(array $parameters = []): static;
}
