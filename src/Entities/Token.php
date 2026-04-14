<?php

namespace BlitzPHP\Vollmacht\Entities;

use BlitzPHP\Schild\Entities\User;
use BlitzPHP\Vollmacht\Traits\ResolvesInheritedScopes;
use BlitzPHP\Vollmacht\Vollmacht;
use BlitzPHP\Wolke\Entity;
use BlitzPHP\Wolke\Relations\BelongsTo;
use BlitzPHP\Wolke\Relations\HasOne;

class Token extends Entity
{
    use ResolvesInheritedScopes;

    /**
     * {@inheritDoc}
     */
    protected string $table = 'oauth_access_tokens';

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
    protected array $guarded = false;

    /**
     * {@inheritDoc}
     */
    protected array $casts = [
        'scopes'     => 'array',
        'revoked'    => 'bool',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the client that the token belongs to.
     *
     * @return BelongsTo<\BlitzPHP\Vollmacht\Entities\Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Vollmacht::clientModel());
    }

    /**
     * Get the refresh token associated with the token.
     *
     * @return HasOne<\BlitzPHP\Vollmacht\Entities\RefreshToken, $this>
     */
    public function refreshToken(): HasOne
    {
        return $this->hasOne(Vollmacht::refreshTokenModel(), 'access_token_id');
    }

    /**
     * Get the user that the token belongs to.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        $provider = $this->client->provider ?: config('auth.guards.api.provider');


        $model = config('auth.providers.'.$provider.'.model');

        return $this->belongsTo($model, 'user_id');
    }

    /**
     * Determine if the token has a given scope.
     */
    public function can(string $scope): bool
    {
        if (empty($this->scopes)) {
            return false;
        }

        return in_array('*', $this->scopes)
            || $this->scopeExistsIn($scope, $this->scopes);
    }

    /**
     * Determine if the token is missing a given scope.
     */
    public function cant(string $scope): bool
    {
        return ! $this->can($scope);
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
