<?php

namespace BlitzPHP\Vollmacht\Models;

use BlitzPHP\Schild\Models\UserModel as SchildUserModel;
use BlitzPHP\Schild\Entities\User;
use BlitzPHP\Vollmacht\Contracts\OAuthenticatable;

class UserModel extends SchildUserModel
{
	public function __construct()
	{
		parent::__construct();

		$this->returnType = config('vollmacht.user_entity', User::class);
	}

	/**
     * Retrieve a user by their unique identifier.
	 *
	 * @return User|null
     */
    public function retrieveById(int|string $identifier)
    {
		$this->findById($identifier, true);
    }

	/**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @return OAuthenticatable|null
     */
    public function retrieveByToken(int|string $identifier, string $token)
    {
		$user = $this->retrieveById($identifier);

		return $user && $user->getRememberToken() && hash_equals($user->getRememberToken(), $token)
            ? $user
            : null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     */
    public function updateRememberToken(OAuthenticatable $user, string $token): void
    {
		// $this->provider->updateRememberToken($user, $token);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @return OAuthenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
		return $this->findByCredentials($credentials);
    }

    /**
     * Validate a user against the given credentials.
     */
    public function validateCredentials(User $user, array $credentials): bool
    {
		if (is_null($plain = $credentials['password'])) {
            return false;
        }

        if (is_null($hashed = $user->getPasswordHash())) {
            return false;
        }

        return service('passwords')->check($plain, $hashed);
    }

    /**
     * Rehash the user's password if required and supported.
     */
    public function rehashPasswordIfRequired(User $user, array $credentials, bool $force = false): void
    {
		$passwords = service('passwords');

		if (! $passwords->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

		$user->setPassword('')
			->setPasswordHash($passwords->hash($credentials['password']))
			->saveEmailIdentity();
	}

    /**
     * Get the name of the user provider.
     */
    public function getProviderName(): string
    {
        return static::class;
    }
}
