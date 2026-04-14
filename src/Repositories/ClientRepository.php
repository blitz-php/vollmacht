<?php

namespace BlitzPHP\Vollmacht\Repositories;

use BlitzPHP\Utilities\String\Text;
use BlitzPHP\Vollmacht\Contracts\OAuthenticatable;
use BlitzPHP\Vollmacht\Entities\Client;
use BlitzPHP\Vollmacht\Entities\Token;
use BlitzPHP\Vollmacht\Vollmacht;
use BlitzPHP\Wolke\Builder;
use RuntimeException;

class ClientRepository
{
    /**
     * Get a client by the given ID.
     */
    public function find(string|int $id): ?Client
    {
		return Vollmacht::client()->newQuery()->find($id);
    }

    /**
     * Get an active client by the given ID.
     */
    public function findActive(string|int $id): ?Client
    {
        $client = $this->find($id);

        return $client && ! $client->revoked ? $client : null;
    }

    /*
     * Get the latest active personal access client for the given user provider.
     *
     * @throws \RuntimeException
     */
    public function personalAccessClient(string $provider): Client
    {
        return Vollmacht::client()
            ->newQuery()
            ->where('revoked', false)
            ->where(function (Builder $query) use ($provider): void {
                $query->when($provider === config('auth.guards.api.provider'), function (Builder $query): void {
                    $query->orWhereNull('provider');
                })->orWhere('provider', $provider);
            })
            ->latest()
            ->get()
            ->first(fn (Client $client): bool => $client->hasGrantType('personal_access'))
            ?? throw new RuntimeException(
                "Personal access client not found for '$provider' user provider. Please create one."
            );
    }

    /**
     * Store a new client.
     *
     * @param  string[]  $grantTypes
     * @param  string[]  $redirectUris
     */
    protected function create(
        string $name,
        array $grantTypes,
        array $redirectUris = [],
        ?string $provider = null,
        bool $confidential = true,
        ?OAuthenticatable $user = null
    ): Client {
        $client = Vollmacht::client();
        $columns = $client->getConnection()->getColumnNames($client->getTable());

        $attributes = [
            'name' => $name,
            'secret' => $confidential ? Text::random(40) : null,
            'provider' => $provider,
            'revoked' => false,
            ...(in_array('redirect_uris', $columns) ? [
                'redirect_uris' => $redirectUris,
            ] : [
                'redirect' => implode(',', $redirectUris),
            ]),
            ...(in_array('grant_types', $columns) ? [
                'grant_types' => $grantTypes,
            ] : [
                'personal_access_client' => in_array('personal_access', $grantTypes),
                'password_client' => in_array('password', $grantTypes),
            ]),
        ];

        return match (true) {
            ! is_null($user) && in_array('user_id', $columns) => $user->clients()->forceCreate($attributes),
            ! is_null($user) => $user->oauthApps()->forceCreate($attributes),
            default => $client->newQuery()->forceCreate($attributes),
        };
    }

    /**
     * Store a new personal access token client.
     */
    public function createPersonalAccessGrantClient(string $name, ?string $provider = null): Client
    {
        return $this->create($name, ['personal_access'], [], $provider);
    }

    /**
     * Store a new password grant client.
     */
    public function createPasswordGrantClient(string $name, ?string $provider = null, bool $confidential = false): Client
    {
        return $this->create($name, ['password', 'refresh_token'], [], $provider, $confidential);
    }

    /**
     * Store a new client credentials grant client.
     */
    public function createClientCredentialsGrantClient(string $name): Client
    {
        return $this->create($name, ['client_credentials']);
    }

    /**
     * Store a new implicit grant client.
     *
     * @param  string[]  $redirectUris
     */
    public function createImplicitGrantClient(string $name, array $redirectUris): Client
    {
        return $this->create($name, ['implicit'], $redirectUris, null, false);
    }

    /**
     * Store a new device authorization grant client.
     */
    public function createDeviceAuthorizationGrantClient(
        string $name,
        bool $confidential = true,
        ?OAuthenticatable $user = null
    ): Client {
        return $this->create(
            $name, ['urn:ietf:params:oauth:grant-type:device_code', 'refresh_token'], [], null, $confidential, $user
        );
    }

    /**
     * Store a new authorization code grant client.
     *
     * @param  string[]  $redirectUris
     */
    public function createAuthorizationCodeGrantClient(
        string $name,
        array $redirectUris,
        bool $confidential = true,
        ?OAuthenticatable $user = null,
        bool $enableDeviceFlow = false
    ): Client {
        $grantTypes = ['authorization_code', 'refresh_token'];

        if ($enableDeviceFlow) {
            $grantTypes[] = 'urn:ietf:params:oauth:grant-type:device_code';
        }

        return $this->create($name, $grantTypes, $redirectUris, null, $confidential, $user);
    }

    /**
     * Update the given client.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string[]  $redirectUris
     */
    public function update(Client $client, string $name, array $redirectUris): bool
    {
        $columns = $client->getConnection()->getColumnNames($client->getTable());

        return $client->forceFill([
            'name' => $name,
            ...(in_array('redirect_uris', $columns) ? [
                'redirect_uris' => $redirectUris,
            ] : [
                'redirect' => implode(',', $redirectUris),
            ]),
        ])->save();
    }

    /**
     * Regenerate the client secret.
     */
    public function regenerateSecret(Client $client): bool
    {
        return $client->forceFill([
            'secret' => Text::random(40),
        ])->save();
    }

    /**
     * Revoke the given client and its tokens.
     *
     * @deprecated Will be removed in a future Laravel version.
     */
    public function delete(Client $client): void
    {
        $client->tokens()->with('refreshToken')->each(function (Token $token): void {
            $token->refreshToken?->revoke();
            $token->revoke();
        });

        $client->forceFill(['revoked' => true])->save();
    }
}
