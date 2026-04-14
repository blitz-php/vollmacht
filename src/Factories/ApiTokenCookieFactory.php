<?php

namespace BlitzPHP\Vollmacht\Factories;

use BlitzPHP\Config\Config;
use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Session\Cookie\Cookie;
use BlitzPHP\Utilities\DateTime\Date;
use BlitzPHP\Vollmacht\Vollmacht;
use DateTimeInterface;
use Firebase\JWT\JWT;

class ApiTokenCookieFactory
{
    /**
     * Create an API token cookie factory instance.
     */
    public function __construct(protected Config $config, protected EncrypterInterface $encrypter)
	{
    }

    /**
     * Create a new API token cookie.
     */
    public function make(string|int $userId, string $csrfToken): Cookie
    {
        $config = $this->config->get('cookie');

		$expiration = $config['expires'];
		if (! $expiration instanceof DateTimeInterface) {
			$expiration = Date::now()->addMinutes((int) $expiration);
		}

        return new Cookie(
            Vollmacht::cookie(),
            $this->createToken($userId, $csrfToken, $expiration->getTimestamp()),
            $expiration,
            $config['path'],
            $config['domain'],
            $config['secure'],
            true,
            $config['samesite'] ?? null,
        );
    }

    /**
     * Create a new JWT token for the given user ID and CSRF token.
     */
    protected function createToken(string|int $userId, string $csrfToken, int $expiration): string
    {
		$payload = [
            'sub'  => $userId,
            'csrf' => $csrfToken,
            'exp'  => $expiration,
        ];

        return JWT::encode(
			$payload,
		Vollmacht::tokenEncryptionKey($this->encrypter),
		config('vollmacht.algorithm', 'HS256')
		);
    }
}
