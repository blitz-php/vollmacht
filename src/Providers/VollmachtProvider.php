<?php

namespace BlitzPHP\Vollmacht\Providers;

use BlitzPHP\Container\AbstractProvider;
use BlitzPHP\Contracts\Container\ContainerInterface;
use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Http\Request;
use BlitzPHP\Vollmacht\Authenticators\TokenAuthenticator;
use BlitzPHP\Vollmacht\Bridge\AccessTokenRepository;
use BlitzPHP\Vollmacht\Bridge\AuthCodeRepository;
use BlitzPHP\Vollmacht\Bridge\ClientRepository as BridgeClientRepository;
use BlitzPHP\Vollmacht\Bridge\DeviceCodeRepository;
use BlitzPHP\Vollmacht\Bridge\PersonalAccessBearerTokenResponse;
use BlitzPHP\Vollmacht\Bridge\PersonalAccessGrant;
use BlitzPHP\Vollmacht\Bridge\RefreshTokenRepository;
use BlitzPHP\Vollmacht\Bridge\ScopeRepository;
use BlitzPHP\Vollmacht\Bridge\UserRepository;
use BlitzPHP\Vollmacht\Contracts\ApprovedDeviceAuthorizationResponse as ApprovedDeviceAuthorizationResponseContract;
use BlitzPHP\Vollmacht\Contracts\DeniedDeviceAuthorizationResponse as DeniedDeviceAuthorizationResponseContract;
use BlitzPHP\Vollmacht\Controllers\AuthorizationController;
use BlitzPHP\Vollmacht\Controllers\DeviceAuthorizationController;
use BlitzPHP\Vollmacht\Factories\PersonalAccessTokenFactory;
use BlitzPHP\Vollmacht\Models\UserModel;
use BlitzPHP\Vollmacht\Repositories\ClientRepository;
use BlitzPHP\Vollmacht\Responses\ApprovedDeviceAuthorizationResponse;
use BlitzPHP\Vollmacht\Responses\DeniedDeviceAuthorizationResponse;
use BlitzPHP\Vollmacht\Vollmacht;
use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\DeviceCodeGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;

class VollmachtProvider extends AbstractProvider
{
	public static function definitions(): array
	{
		return [
			ApprovedDeviceAuthorizationResponseContract::class => fn(ContainerInterface $container) => $container->get(ApprovedDeviceAuthorizationResponse::class),
			DeniedDeviceAuthorizationResponseContract::class   => fn(ContainerInterface $container) => $container->get(DeniedDeviceAuthorizationResponse::class),

			AuthorizationController::class => function(ContainerInterface $container) {
				return new AuthorizationController(
					$container->get(AuthorizationServer::class),
					auth(config('vollmacht.guard'))->getAuthenticator(),
					$container->get(ClientRepository::class),
				);
			},
			DeviceAuthorizationController::class => function(ContainerInterface $container) {
				return new DeviceAuthorizationController(
					auth(config('vollmacht.guard'))->getAuthenticator(),
					$container->get(DeviceCodeRepository::class),
					$container->get(ClientRepository::class),
				);
			},
		];
	}

	public function register(): void
    {
		$this->registerAuthorizationServer();
        $this->registerResourceServer();
        $this->registerAuthenticator();
    }


	/**
     * Register the authorization server.
     */
    protected function registerAuthorizationServer(): void
    {
		$this->container->add(PersonalAccessTokenFactory::class, fn() => tap(
			$this->makeAuthorizationServer(new PersonalAccessBearerTokenResponse),
			function(AuthorizationServer $server): void {
				$server->enableGrantType(new PersonalAccessGrant(), Vollmacht::personalAccessTokensExpireIn());
			}
		));

		$this->container->add(AuthorizationServer::class, fn() => tap(
			$this->makeAuthorizationServer(),
			function(AuthorizationServer $server): void {
				$server->enableGrantType(
                    $this->makeAuthCodeGrant(), Vollmacht::tokensExpireIn()
                );

                $server->enableGrantType(
                    $this->makeRefreshTokenGrant(), Vollmacht::tokensExpireIn()
                );

                if (Vollmacht::$passwordGrantEnabled) {
                    $server->enableGrantType(
                        $this->makePasswordGrant(), Vollmacht::tokensExpireIn()
                    );
                }

                $server->enableGrantType(
                    new ClientCredentialsGrant, Vollmacht::clientCredentialsTokensExpireIn() ?? Vollmacht::tokensExpireIn()
                );

                if (Vollmacht::$implicitGrantEnabled) {
                    $server->enableGrantType(
                        $this->makeImplicitGrant(), Vollmacht::tokensExpireIn()
                    );
                }

                if (Vollmacht::$deviceCodeGrantEnabled/*  && Route::has('passport.device') */) {
                    $server->enableGrantType(
                        $this->makeDeviceCodeGrant(), Vollmacht::tokensExpireIn()
                    );
                }
			}
		));
    }

    /**
     * Create and configure an instance of the Auth Code grant.
     */
    protected function makeAuthCodeGrant(): AuthCodeGrant
    {
        return tap($this->buildAuthCodeGrant(), function (AuthCodeGrant $grant): void {
            $grant->setRefreshTokenTTL(Vollmacht::refreshTokensExpireIn());
        });
    }

    /**
     * Build the Auth Code grant instance.
     */
    protected function buildAuthCodeGrant(): AuthCodeGrant
    {
        return new AuthCodeGrant(
            $this->container->make(AuthCodeRepository::class),
            $this->container->make(RefreshTokenRepository::class),
            new DateInterval('PT10M')
        );
    }

    /**
     * Create and configure a Refresh Token grant instance.
     */
    protected function makeRefreshTokenGrant(): RefreshTokenGrant
    {
        return tap(new RefreshTokenGrant(
            $this->container->make(RefreshTokenRepository::class)
        ), function (RefreshTokenGrant $grant): void {
            $grant->setRefreshTokenTTL(Vollmacht::refreshTokensExpireIn());
        });
    }

    /**
     * Create and configure a Password grant instance.
     */
    protected function makePasswordGrant(): PasswordGrant
    {
        return tap(new PasswordGrant(
            $this->container->make(UserRepository::class),
            $this->container->make(RefreshTokenRepository::class)
        ), function (PasswordGrant $grant): void {
            $grant->setRefreshTokenTTL(Vollmacht::refreshTokensExpireIn());
        });
    }

    /**
     * Create and configure an instance of the Implicit grant.
     */
    protected function makeImplicitGrant(): ImplicitGrant
    {
        return new ImplicitGrant(Vollmacht::tokensExpireIn());
    }

    /**
     * Create and configure an instance of the Device Code grant.
     */
    protected function makeDeviceCodeGrant(): DeviceCodeGrant
    {
        return tap(new DeviceCodeGrant(
            $this->container->make(DeviceCodeRepository::class),
            $this->container->make(RefreshTokenRepository::class),
            new DateInterval('PT10M'),
            link_to('vollmacht.device'),
            5
        ), function (DeviceCodeGrant $grant) {
            $grant->setRefreshTokenTTL(Vollmacht::refreshTokensExpireIn());
            $grant->setIncludeVerificationUriComplete(true);
            $grant->setIntervalVisibility(true);
        });
    }

    /**
     * Make the authorization service instance.
     */
    protected function makeAuthorizationServer(?ResponseTypeInterface $responseType = null): AuthorizationServer
    {
        return tap(new AuthorizationServer(
            $this->container->make(BridgeClientRepository::class),
            $this->container->make(AccessTokenRepository::class),
            $this->container->make(ScopeRepository::class),
            $this->makeCryptKey('private'),
            Vollmacht::tokenEncryptionKey($this->container->get(EncrypterInterface::class)),
            $responseType ?? Vollmacht::$authorizationServerResponseType
        ), function (AuthorizationServer $server): void {
            $server->setDefaultScope(Vollmacht::$defaultScope);
            $server->revokeRefreshTokens(Vollmacht::$revokeRefreshTokenAfterUse);
        });
    }

    /**
     * Register the resource server.
     */
    protected function registerResourceServer(): void
    {
        $this->container->add(ResourceServer::class, fn ($container) => new ResourceServer(
            $container->make(AccessTokenRepository::class),
            $this->makeCryptKey('public')
        ));
    }

    /**
     * Create a CryptKey instance.
     */
    protected function makeCryptKey(string $type): CryptKey
    {
        $key = str_replace('\\n', "\n", config("vollmacht.{$type}_key") ?? '');

        if (! $key) {
            $key = 'file://' . Vollmacht::keyPath('oauth-'.$type.'.key');
        }

        return new CryptKey($key, null, Vollmacht::$validateKeyPermissions);
    }

    /**
     * Register the token guard.
     */
    protected function registerAuthenticator(): void
    {
		$this->container->add(TokenAuthenticator::class, fn() => new TokenAuthenticator(
			model(UserModel::class),
            $this->container->make(ResourceServer::class),
            $this->container->get(ClientRepository::class),
            $this->container->get(EncrypterInterface::class),
            $this->container->get(Request::class)
        ));
    }

    /**
     * Register the cookie deletion event handler.
     */
    protected function deleteCookieOnLogout(): void
    {
		service('event')->on('schild:logout', function() {
			if (cookie()->has(Vollmacht::cookie())) {
				cookie()->forget(Vollmacht::cookie());
			}
		});
    }
}
