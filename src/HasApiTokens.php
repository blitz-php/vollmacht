<?php

namespace BlitzPHP\Vollmacht;

use BlitzPHP\Vollmacht\Contracts\ScopeAuthorizable;
use BlitzPHP\Vollmacht\Factories\PersonalAccessTokenFactory;
use BlitzPHP\Wolke\Builder;
use BlitzPHP\Wolke\Relations\HasMany;
use BlitzPHP\Wolke\Relations\MorphMany;

/**
 * @phpstan-require-implements \BlitzPHP\Vollmacht\Contracts\OAuthenticatable
 */
trait HasApiTokens
{
    /**
     * The current access token for the authentication user.
     */
    protected ?ScopeAuthorizable $accessToken = null;

    /**
     * Get all of the user's registered OAuth clients.
     *
     * @deprecated Use oauthApps()
     *
     * @return HasMany<\BlitzPHP\Vollmacht\Entities\Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Vollmacht::clientModel(), 'user_id');
    }

    /**
     * Get all of the user's registered OAuth applications.
     *
     * @return MorphMany<\BlitzPHP\Vollmacht\Entities\Client, $this>
     */
    public function oauthApps(): MorphMany
    {
        return $this->morphMany(Vollmacht::clientModel(), 'owner');
    }

    /**
     * Get all of the access tokens for the user.
     *
     * @return HasMany<\BlitzPHP\Vollmacht\Entities\Token, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Vollmacht::tokenModel(), 'user_id', $this->getAuthIdentifierName())
            ->where(function (Builder $query): void {
                $query->whereHas('client', function (Builder $query): void {
                    $query->where(function (Builder $query): void {
                        $provider = $this->getProviderName();

                        $query->when($provider === config('vollmacht.provider'), function (Builder $query): void {
                            $query->orWhereNull('provider');
                        })->orWhere('provider', $provider);
                    });
                });
            });
    }

    /**
     * Get the access token currently associated with the user.
     */
    public function token(): ?ScopeAuthorizable
    {
        return $this->currentAccessToken();
    }

    /**
     * Get the access token currently associated with the user.
     */
    public function currentAccessToken(): ?ScopeAuthorizable
    {
        return $this->accessToken;
    }

    /**
     * Determine if the current API token has a given scope.
     */
    public function tokenCan(string $scope): bool
    {
        return $this->accessToken && $this->accessToken->can($scope);
    }

    /**
     * Determine if the current API token is missing a given scope.
     */
    public function tokenCant(string $scope): bool
    {
        return ! $this->tokenCan($scope);
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  string[]  $scopes
     */
    public function createToken(string $name, array $scopes = []): PersonalAccessTokenResult
    {
        return service(PersonalAccessTokenFactory::class)->make(
            $this->getAuthIdentifier(), $name, $scopes, $this->getProviderName()
        );
    }

    /**
     * Get the user provider name.
     */
    public function getProviderName(): string
    {
		return config('vollmacht.provider', 'vollmacht');
    }

    /**
     * Set the current access token for the user.
     */
    public function withAccessToken(?ScopeAuthorizable $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }


	
    /**
     * {@inheritDoc}
     */
    public function getAuthIdentifierName(): string
	{
		return 'id';
	}

    /**
     * {@inheritDoc}
     */
    public function getAuthIdentifier(): mixed
	{
		return $this->{$this->getAuthIdentifierName()} ?? $this->id;
	}

    /**
     * {@inheritDoc}
     */
    public function getAuthPasswordName(): string
	{
		return 'password';
	}

    /**
     * {@inheritDoc}
     */
    public function getAuthPassword(): string
	{
		return $this->password_hash;
	}

    /**
     * {@inheritDoc}
     */
    public function getRememberToken(): ?string
	{
		return null;
	}

    /**
     * {@inheritDoc}
     */
    public function setRememberToken(string $value): void
	{

	}

    /**
     * {@inheritDoc}
     */
    public function getRememberTokenName(): string
	{
		return config('auth.session.remember_cookie_name', 'remember');
	}
}
