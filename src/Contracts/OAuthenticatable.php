<?php

namespace BlitzPHP\Vollmacht\Contracts;

use BlitzPHP\Vollmacht\PersonalAccessTokenResult;
use BlitzPHP\Wolke\Relations\HasMany;
use BlitzPHP\Wolke\Relations\MorphMany;

interface OAuthenticatable extends Authenticatable
{
    /**
     * Get all the user's registered OAuth applications.
     *
     * @return MorphMany<\BlitzPHP\Vollmacht\Entities\Client, \BlitzPHP\Schild\Entities\User>
     */
    public function oauthApps(): MorphMany;

    /**
     * Get all the access tokens for the user.
     *
     * @return HasMany<\BlitzPHP\Vollmacht\Entities\Token, \BlitzPHP\Schild\Entities\User>
     */
    public function tokens(): HasMany;

    /**
     * Determine if the current API token has a given scope.
     */
    public function tokenCan(string $scope): bool;

    /**
     * Determine if the current API token is missing a given scope.
     */
    public function tokenCant(string $scope): bool;

    /**
     * Create a new personal access token for the user.
     *
     * @param  string[]  $scopes
     */
    public function createToken(string $name, array $scopes = []): PersonalAccessTokenResult;

    /**
     * Get the access token currently associated with the user.
     */
    public function currentAccessToken(): ?ScopeAuthorizable;

    /**
     * Set the current access token for the user.
     */
    public function withAccessToken(?ScopeAuthorizable $accessToken): static;

    /**
     * Get the user provider name.
     */
    public function getProviderName(): string;
}
