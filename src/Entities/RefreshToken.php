<?php

namespace BlitzPHP\Vollmacht\Entities;

use BlitzPHP\Vollmacht\Vollmacht;
use BlitzPHP\Wolke\Entity;
use BlitzPHP\Wolke\Relations\BelongsTo;

class RefreshToken extends Entity
{
    /**
	 * {@inheritDoc}
     */
    protected string $table = 'oauth_refresh_tokens';

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
     * Get the access token that the refresh token belongs to.
     *
     * @return BelongsTo<\BlitzPHP\Vollmacht\Entities\Token, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(Vollmacht::tokenModel());
    }

    /**
     * Revoke the token instance.
     */
    public function revoke(): bool
    {
        return $this->forceFill(['revoked' => true])->save();
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionName(): ?string
    {
        return $this->connection ?? config('vollmacht.connection');
    }
}
