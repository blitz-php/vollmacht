<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Vollmacht\Vollmacht;
use BlitzPHP\Wolke\Collection;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class ScopeController
{
    /**
     * Get all of the available scopes for the application.
     *
     * @return Collection<int, \BlitzPHP\Vollmacht\Scope>
     */
    public function all(): Collection
    {
        return Vollmacht::scopes();
    }
}
