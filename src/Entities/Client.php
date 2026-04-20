<?php

namespace BlitzPHP\Vollmacht\Entities;

use BlitzPHP\Schild\Entities\User;
use BlitzPHP\Vollmacht\Traits\ResolvesInheritedScopes;
use BlitzPHP\Vollmacht\Vollmacht;
use BlitzPHP\Wolke\Casts\Attribute;
use BlitzPHP\Wolke\Concerns\HasUuids;
use BlitzPHP\Wolke\Entity;
use BlitzPHP\Wolke\Relations\HasMany;
use BlitzPHP\Wolke\Relations\MorphTo;

class Client extends Entity
{
	use HasUuids;
    use ResolvesInheritedScopes;

    /**
     * {@inheritDoc}
     */
    protected string $table = 'oauth_clients';

    /**
     * {@inheritDoc}
     */
    protected array $guarded = [];

    /**
     * {@inheritDoc}
     */
    protected array $hidden = ['secret'];

    /**
     * {@inheritDoc}
     */
    protected array $casts = [
        'grant_types'            => 'array',
        'scopes'                 => 'array',
        'redirect_uris'          => 'array',
        'personal_access_client' => 'bool',
        'password_client'        => 'bool',
        'revoked'                => 'bool',
    ];

    /**
     * The temporary plain-text client secret.
     *
     * This is only available during the request that created the client.
     */
    public ?string $plainSecret = null;

    /**
     * Initialize the trait.
     */
    public function initializeHasUniqueStringIds(): void
    {
        $this->usesUniqueIds = Vollmacht::$clientUuids;
    }

    /**
     * Get the owner of the registered client.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }

    /**
     * Get all of the authentication codes for the client.
     *
     * @deprecated Will be removed in a future Laravel version.
     */
    public function authCodes(): HasMany
    {
        return $this->hasMany(Vollmacht::authCodeModel(), 'client_id');
    }

    /**
     * Get all of the tokens that belong to the client.
     *
     * @return HasMany<\BlitzPHP\Vollmacht\Entities\Token, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Vollmacht::tokenModel(), 'client_id');
    }

    /**
     * Interact with the client's secret.
     */
    protected function secret(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): ?string {
                $this->plainSecret = $value;

                return $this->castAttributeAsHashedString('secret', $value);
            },
        );
    }

    /**
     * Interact with the client's plain secret.
     */
    protected function plainSecret(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->plainSecret
        );
    }

    /**
     * Interact with the client's redirect URIs.
     */
    protected function redirectUris(): Attribute
    {
        return Attribute::make(
			// Il peut arriver que la valeur soit caster en amont (récuperation via wolke) ou pas (récuperation via le query builder simple)
			// dans le cas où  nous avons déjà un tableau, on le renvoi simplement
            get: fn ($value, array $attributes): array => match (true) {
				is_array($value) => $value,
                ! empty($value) => $this->fromJson($value),
                ! empty($attributes['redirect']) => explode(',', $attributes['redirect']),
                default => [],
            },
        );
    }

    /**
     * Interact with the client's grant types.
     */
    protected function grantTypes(): Attribute
    {
		return Attribute::make(
			get: function($value): array {
				if (!empty($value)) {
					// Il peut arriver que la valeur soit caster en amont (récuperation via wolke) ou pas (récuperation via le query builder simple)
					// dans le cas où  nous avons déjà un tableau, on le renvoi simplement
					return is_array($value) ? $value : $this->fromJson($value);
				}

				return array_keys(array_filter([
					'authorization_code' => ! empty($this->redirect_uris),
					'client_credentials' => $this->confidential() && $this->firstParty(),
					'implicit' => ! empty($this->redirect_uris),
					'password' => $this->password_client,
					'personal_access' => $this->personal_access_client && $this->confidential(),
					'refresh_token' => true,
					'urn:ietf:params:oauth:grant-type:device_code' => true,
				]));
			}
		);
    }

    /**
     * Determine if the client is a "first party" client.
     */
    public function firstParty(): bool
    {
        if (array_key_exists('user_id', $this->attributes)) {
            return empty($this->user_id);
        }

        return empty($this->owner_id);
    }

    /**
     * Determine if the client should skip the authorization prompt.
     *
     * @param  \BlitzPHP\Vollmacht\Scope[]  $scopes
     */
	public function skipsAuthorization(User $user, array $scopes): bool
    {
        return false;
    }

    /**
     * Determine if the client has the given grant type.
     */
    public function hasGrantType(string $grantType): bool
    {
        return in_array($grantType, $this->grant_types);
    }

    /**
     * Determine whether the client has the given scope.
     */
    public function hasScope(string $scope): bool
    {
        return ! isset($this->attributes['scopes']) || $this->scopeExistsIn($scope, $this->scopes);
    }

    /**
     * Determine if the client is a confidential client.
     */
    public function confidential(): bool
    {
        return ! empty($this->getAttributes()['secret'] ?? null);
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionName(): ?string
    {
        return $this->connection ?? config('vollmacht.connection');
    }
}
