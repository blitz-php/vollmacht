<?php

namespace BlitzPHP\Vollmacht\Entities;

use BlitzPHP\Vollmacht\Vollmacht;
use BlitzPHP\Wolke\Entity;
use BlitzPHP\Wolke\Relations\BelongsTo;

class AuthCode extends Entity
{
    /**
	 * {@inheritDoc}
     */
    protected string $table = 'oauth_auth_codes';

    /**
	 * {@inheritDoc}
     */
    public bool $incrementing = false;

    /**
     * {@inheritDoc}
     */
    protected array $guarded = [];

    /**
     * {@inheritDoc}
     */
    protected array $casts = [
        'revoked'    => 'bool',
        'expires_at' => 'datetime',
    ];

    /**
	 * {@inheritDoc}
     */
    public array|bool $timestamps = false;

    /**
	 * {@inheritDoc}
     */
    protected string $keyType = 'string';

    /**
     * Get the client that owns the authentication code.
     *
     * @deprecated Will be removed in a future Vollmacht version.
     *
     * @return BelongsTo<\BlitzPHP\Vollmacht\Entities\Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Vollmacht::clientModel());
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionName(): ?string
    {
        return $this->connection ?? config('vollmacht.connection');
    }
}
