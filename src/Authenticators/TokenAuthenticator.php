<?php

namespace BlitzPHP\Vollmacht\Authenticators;

use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Exceptions\EncryptionException;
use BlitzPHP\Http\Request;
use BlitzPHP\Middlewares\EncryptCookies;
use BlitzPHP\Schild\Authentication\AuthenticatorInterface;
use BlitzPHP\Schild\Entities\User;
use BlitzPHP\Schild\Models\TokenLoginModel;
use BlitzPHP\Schild\Result;
use BlitzPHP\Session\Cookie\CookieValuePrefix;
use BlitzPHP\Traits\Macroable;
use BlitzPHP\Utilities\DateTime\Date;
use BlitzPHP\Vollmacht\AccessToken;
use BlitzPHP\Vollmacht\Contracts\OAuthenticatable;
use BlitzPHP\Vollmacht\Entities\Client;
use BlitzPHP\Vollmacht\Exceptions\AuthenticationException;
use BlitzPHP\Vollmacht\Models\UserModel;
use BlitzPHP\Vollmacht\Repositories\ClientRepository;
use BlitzPHP\Vollmacht\TransientToken;
use BlitzPHP\Vollmacht\Vollmacht;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ServerRequestInterface;

class TokenAuthenticator implements AuthenticatorInterface
{
	use Macroable;

    /**
     * Type d'identification spécial pour les logs.
     */
    public const ID_TYPE_OAUTH_TOKEN = 'oauth_token';

    /**
     * The currently authenticated user.
     */
    protected $user = null;

	/**
     * The currently authenticated client.
     */
    protected ?Client $client = null;

	protected TokenLoginModel $loginModel;

    /**
     * Create a new token guard instance.
	 *
	 * @param UserModel $provider The user provider implementation.
     */

    public function __construct(
        protected UserModel $provider,
        protected ResourceServer $server,
        protected ClientRepository $clients,
        protected EncrypterInterface $encrypter,
        protected Request $request,
    ) {
		$this->loginModel = model(TokenLoginModel::class);

    }

    /**
     * Tente d'authentifier un utilisateur/client avec les $credentials donnés.
     * Connecte l'utilisateur avec une vérification réussie.
     *
     * @param array{token?: string} $credentials Le token Bearer
     */
    public function attempt(array $credentials): Result
    {
		$request = $this->request;

        $config  = (object) config('vollmacht');

        $ipAddress = $request->ip();
        $userAgent = (string) $request->userAgent();

        $result = $this->check($credentials);

        if (! $result->isOK()) {
            if ($config->record_login_attempt >= RECORD_LOGIN_ATTEMPT_FAILURE) {
                // Enregistrer une tentative de connexion échouée
                $this->loginModel->recordLoginAttempt(
                    self::ID_TYPE_OAUTH_TOKEN,
                    'sha256:' . hash('sha256', $credentials['token'] ?? ''),
                    false,
                    $ipAddress,
                    $userAgent,
                );
            }

            return $result;
        }

		$user = $result->extraInfo();

		if ($user->isBanned()) {
			if (($config->record_login_attempt) >= RECORD_LOGIN_ATTEMPT_FAILURE) {
				$this->loginModel->recordLoginAttempt(
					self::ID_TYPE_OAUTH_TOKEN,
					'sha256:' . hash('sha256', $credentials['token'] ?? ''),
					false,
					$ipAddress,
					$userAgent,
					$user->id,
				);
			}

			$this->user = null;

			return new Result([
				'success' => false,
				'reason'  => $user->getBanMessage() ?? lang('Auth.bannedUser'),
			]);
		}

		$this->login($user);

		if ($config->record_login_attempt === RECORD_LOGIN_ATTEMPT_ALL) {
			$this->loginModel->recordLoginAttempt(
				self::ID_TYPE_OAUTH_TOKEN,
				'sha256:' . hash('sha256', $credentials['token']),
				true,
				$ipAddress,
				$userAgent,
				$this->user->id,
			);
		}

        service('event')->emit('vollmacht:login', $user);

        return $result;
    }

    /**
	 * {@inheritDoc}
	 *
	 * @param array{token?: string} $credentials Le token Bearer
     */
    public function check(array $credentials = []): Result
    {
        $token = $credentials['token'] ?? $this->request->bearerToken();
        $user = null;

		try {
			if ($token) {
            	$user = $this->authenticateViaBearerToken($token);
			}
			else if ($this->request->cookie(Vollmacht::cookie())) {
				$user = $this->authenticateViaCookie();
			}
		} catch (AuthenticationException $e) {
			return new Result(['success' => false, 'reason' => $e->getMessage()]);
		}

		if ($user === null) {
			return new Result(['success' => false, 'reason' => lang('Auth.invalidUser')]);
		}

		return new Result([
			'success'   => true,
			'extraInfo' => $user,
		]);
    }

    /**
     * {@inheritDoc}
     *
     * @return OAuthenticatable|null
     */
    public function getUser(): ?User
	{
        if ($this->user !== null) {
            return $this->user;
        }

        // Tenter l'authentification silencieuse
		if (null !== $token = $this->request->bearerToken()) {
			$result = $this->attempt(['token' => $token]);
            if ($result->isOK()) {
                $extraInfo = $result->extraInfo();
                if ($extraInfo instanceof User) {
                    return $extraInfo;
                }
            }
        }

        return null;
    }

	/**
     * {@inheritDoc}
     */
    public function login(User $user): void
    {
        $this->user = $user;
    }

    /**
     * {@inheritDoc}
     */
    public function logout(): void
    {
		$user   = $this->user;
        $client = $this->client;

        $this->user   = null;
        $this->client = null;

        if ($user) {
            service('event')->emit('vollmacht:logout', $user);
        } elseif ($client) {
            service('event')->emit('vollmacht:client.logout', $client);
        }
    }

	/**
     * {@inheritDoc}
     */
    public function loggedIn(): bool
    {
        if ($this->user !== null) {
            return true;
        }

        if ($this->client !== null) {
            return true;
        }

        if (null === $token = $this->request->bearerToken()) {
            return false;
        }

        return $this->attempt(['token' => $token])->isOK();
    }

	/**
	 * {@inheritDoc}
	 */
	public function loginById($userId): void
	{
		$user = $this->provider->retrieveById($userId);

        if ($user === null) {
            throw AuthenticationException::invalidUser();
        }

        // Pour les tokens, on ne peut pas "loginById" sans token
        // Cette méthode existe pour compatibilité avec l'interface
        $this->login($user);
	}

	 /**
     * Met à jour la dernière date active de l'utilisateur.
     */
    public function recordActiveDate(): void
    {
        if (! $this->user instanceof User) {
            throw new InvalidArgumentException(
                __METHOD__ . '() nécessite un utilisateur connecté avant d\'être appelée.',
            );
        }

        $this->user->last_active = Date::now();

        $this->provider->updateActiveDate($this->user);
    }

	/**
     * Get the user for the incoming request.
     */
    public function user(): ?OAuthenticatable
	{
		return $this->getUser();
	}

    /**
     * Get the client for the incoming request.
     */
    public function client(): ?Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if (null !== $token = $this->request->bearerToken()) {
            if (! $psr = $this->getPsrRequestViaBearerToken($token)) {
                return null;
            }

            return $this->client = $this->clients->findActive(
                $psr->getAttribute('oauth_client_id')
            );
        }

        if (null !== $this->request->cookie(Vollmacht::cookie()) && $token = $this->getTokenViaCookie()) {
            return $this->client = $this->clients->findActive($token['aud']);
        }

        return null;
    }

    /**
     * Authenticate the incoming request via the Bearer token.
     */
    protected function authenticateViaBearerToken(string $token): ?OAuthenticatable
    {
        if (null === $psr = $this->getPsrRequestViaBearerToken($token)) {
			throw new AuthenticationException(lang('Auth.noToken', ['Authorization: Bearer']));
        }

        $client = $this->clients->findActive($psr->getAttribute('oauth_client_id'));

        if (! $client ||
            ($client->provider &&
             $client->provider !== $this->provider->getProviderName())) {
			throw new AuthenticationException(lang('Auth.invalidClient'));
        }

        $this->setClient($client);

        $oauthUserId = $psr->getAttribute('oauth_user_id');

        if (empty($oauthUserId) || ($oauthUserId === $psr->getAttribute('oauth_client_id') && $client->hasGrantType('client_credentials'))) {
            return null;
        }

        // If the access token is valid we will retrieve the user according to the user ID
        // associated with the token. We will use the provider implementation which may
        // be used to retrieve users from Eloquent. Next, we'll be ready to continue.
        try {
            $user = $this->provider->retrieveById($oauthUserId);
        } catch (Exception) {
            return null;
        }

        // Next, we will assign a token instance to this user which the developers may use
        // to determine if the token has a given scope, etc. This will be useful during
        // authorization such as within the developer's Laravel model policy classes.
        return $user?->withAccessToken(AccessToken::fromPsrRequest($psr));
    }

    /**
     * Authenticate and get the incoming PSR-7 request via the Bearer token.
     */
    protected function getPsrRequestViaBearerToken(string $token): ?ServerRequestInterface
    {
        try {
            return $this->server->validateAuthenticatedRequest(
				$this->request->withHeader('Authorization', 'Bearer ' . $token)
			);
        } catch (OAuthServerException $e) {
            $this->request = $this->request->withoutHeader('Authorization');

            return null;
        }
    }

    /**
     * Authenticate the incoming request via the token cookie.
     */
    protected function authenticateViaCookie(): ?OAuthenticatable
    {
        if (! $token = $this->getTokenViaCookie()) {
            return null;
        }

        // If this user exists, we will return this user and attach a "transient" token to
        // the user model. The transient token assumes it has all scopes since the user
        // is physically logged into the application via the application's interface.
        try {
            $user = $this->provider->retrieveById($token['sub']);
        } catch (Exception) {
            return null;
        }

        return $user?->withAccessToken(new TransientToken);
    }

    /**
     * Get the token cookie via the incoming request.
     *
     * @return array<string, mixed>|null
     */
    protected function getTokenViaCookie(): ?array
    {
        // If we need to retrieve the token from the cookie, it'll be encrypted so we must
        // first decrypt the cookie and then attempt to find the token value within the
        // database. If we can't decrypt the value we'll bail out with a null return.
        try {
            $token = $this->decodeJwtTokenCookie();
        } catch (Exception) {
            return null;
        }

        // Token's expiration time is checked using the "exp" claim during decoding, but
        // legacy tokens may have an "expiry" claim instead of the standard "exp". So
        // we must manually check token's expiry, if the "expiry" claim is present.
        if (isset($token['expiry']) && time() >= $token['expiry']) {
            return null;
        }

        // We will compare the CSRF token in the decoded API token against the CSRF header
        // sent with the request. If they don't match then this request isn't sent from
        // a valid source and we won't authenticate the request for further handling.
        if (! Vollmacht::$ignoreCsrfToken && ! $this->validCsrf($token)) {
            return null;
        }

        return $token;
    }

    /**
     * Decode and decrypt the JWT token cookie.
     *
     * @return array<string, mixed>
     */
    protected function decodeJwtTokenCookie(): array
    {
        $jwt = $this->request->cookie(Vollmacht::cookie());

        return (array) JWT::decode(
            Vollmacht::$decryptsCookies
                ? CookieValuePrefix::remove($this->encrypter->decrypt($jwt, Vollmacht::$unserializesCookies))
                : $jwt,
            new Key(Vollmacht::tokenEncryptionKey($this->encrypter), 'HS256')
        );
    }

    /**
     * Determine if the CSRF / header are valid and match.
     *
     * @param  array<string, mixed>  $token
     */
    protected function validCsrf(array $token): bool
    {
        $requestToken = $this->getTokenFromRequest();

        return isset($token['csrf']) &&
               is_string($requestToken) &&
               hash_equals($token['csrf'], $requestToken);
    }

    /**
     * Get the CSRF token from the request.
     */
    protected function getTokenFromRequest(): ?string
    {
        $token = $this->request->header('X-CSRF-TOKEN');

        if (! $token && $header = $this->request->header('X-XSRF-TOKEN')) {
            try {
                $token = CookieValuePrefix::remove($this->encrypter->decrypt($header, static::serialized()));
            } catch (EncryptionException) {
                $token = null;
            }
        }

        return $token;
    }

    /**
     * Set the current request instance.
     */
    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Determine if the cookie contents should be serialized.
     */
    public static function serialized(): bool
    {
        return EncryptCookies::serialized('XSRF-TOKEN');
    }

    /**
     * Set the client for the current request.
     */
    public function setClient(Client $client): static
    {
        $this->client = $client;

        return $this;
    }
}
