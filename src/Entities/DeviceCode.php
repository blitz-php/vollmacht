<?php

namespace BlitzPHP\Vollmacht\Entities;

use BlitzPHP\Wolke\Entity;

class DeviceCode extends Entity
{
    /**
     * {@inheritDoc}
     */
    protected string $table = 'oauth_device_codes';

    /**
	 * {@inheritDoc}
     */
    protected string $keyType = 'string';

    /**
	 * {@inheritDoc}
     */
    public bool $incrementing = false;

    /**
	 * {@inheritDoc}
     */
    public array|bool $timestamps = false;

    /**
     * {@inheritDoc}
     */
    protected array $guarded = [];

    /**
     * {@inheritDoc}
     */
    protected array $casts = [
        'scopes'           => 'array',
        'revoked'          => 'bool',
        'user_approved_at' => 'datetime',
        'last_polled_at'   => 'datetime',
        'expires_at'       => 'datetime',
    ];

    /**
     * {@inheritDoc}
     */
    public function getConnectionName(): ?string
    {
        return $this->connection ?? config('vollmacht.connection');
    }
}
