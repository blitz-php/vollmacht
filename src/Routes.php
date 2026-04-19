<?php

namespace BlitzPHP\Vollmacht;

use BlitzPHP\Facades\Route;
use Closure;

class Routes
{
	public static function all()
	{
		static::publicly();
		static::web();

		if (Vollmacht::$registersJsonApiRoutes) {
			static::api();
		}

		if (Vollmacht::$deviceCodeGrantEnabled) {
			static::device();
		}
	}

	/**
	 * ROUTES PUBLIQUES (Pas d'authentification requise)
	 */
	public static function publicly(): void
	{
		static::registerRoute(function() {
			// POST /oauth/token - Échange de code contre token
			Route::name('vollmacht.token')
				->middleware([/* 'throttle' */])
				->post('/token', 'AccessTokenController::issueToken');
		});
	}

	/**
	 * ROUTES WEB (Nécessite une session utilisateur - auth Schild Session)
	 * 
	 * Ces routes correspondent à l'interface utilisateur d'autorisation OAuth.
	 * L'utilisateur doit être connecté via le web (session) pour voir ces pages.
	 */
	public static function web(): void
	{
		$webMiddlewares = config('vollmacht.middlewares.web', ['session']);

		static::registerRoute(['middleware' => $webMiddlewares], function() {
			// GET /oauth/authorize - Affiche l'écran d'autorisation
			Route::name('vollmacht.authorizations.authorize')
				->get('/authorize', 'AuthorizationController::authorize');

			// POST /oauth/authorize - L'utilisateur approuve
			Route::name('vollmacht.authorizations.approve')
				->post('/authorize', 'AuthorizationController::approve');

			// DELETE /oauth/authorize - L'utilisateur refuse
			Route::name('vollmacht.authorizations.deny')
				->delete('/authorize', 'AuthorizationController::deny');

			// POST /oauth/token/refresh - Rafraîchit le token de session
			Route::name('vollmacht.token.refresh')
				->post('/token/refresh', 'TransientTokenController::refresh');
		});

	}

	/**
	 * ROUTES API (Nécessite une authentification par token OAuth)
	 * 
	 * Ces routes sont protégées par Vollmacht (Bearer token).
	 * L'utilisateur/client doit fournir un token OAuth valide.
	 */
	public static function api(): void
	{
		['prefix' => $prefix, 'namespace' => $namespace] = static::routesConfig();
		$apiMiddlewares = array_merge(
			['api', 'auth.vollmacht'],  // Authentification OAuth requise
			config('vollmacht.middlewares.api', [])
		);

		Route::group(['prefix' => $prefix, 'namespace' => $namespace, 'middleware' => $apiMiddlewares], function() {
			// GET /oauth/clients - Liste les clients de l'utilisateur
			Route::name('vollmacht.clients.index')
				->get('/clients', 'ClientController::forUser');

			// POST /oauth/clients - Crée un nouveau client
			Route::name('vollmacht.clients.store')
				->post('/clients', 'ClientController::store');

			// PUT /oauth/clients/{client_id} - Met à jour un client
			Route::name('vollmacht.clients.update')
				->put('/clients/(:alphanum)', 'ClientController::update/$1');

			// DELETE /oauth/clients/{client_id} - Supprime un client
			Route::name('vollmacht.clients.destroy')
				->delete('/clients/(:alphanum)', 'ClientController::destroy/$1');

			// GET /oauth/personal-access-tokens - Liste les tokens personnels
			Route::name('vollmacht.personal.tokens.index')
				->get('/personal-access-tokens', 'PersonalAccessTokenController::forUser');
			
			// POST /oauth/personal-access-tokens - Crée un token personnel
			Route::name('vollmacht.personal.tokens.store')
				->post('/personal-access-tokens', 'PersonalAccessTokenController::store');
			
			// DELETE /oauth/personal-access-tokens/{token_id} - Révoque un token
			Route::name('vollmacht.personal.tokens.destroy')
				->delete('/personal-access-tokens/(:alphanum)', 'PersonalAccessTokenController::destroy/$1');
		});
		
		// GET /oauth/scopes - Liste les scopes disponibles
		Route::name('vollmacht.scopes.index')
			->get('/scopes', 'ScopeController::all');
	}

	/**
	 * ROUTE DEVICE (Interface utilisateur pour entrer le code device)
	 */
	public static function device(): void
	{
		static::registerRoute(function() {
			Route::name('vollmacht.device.code')
					->middleware([/* 'throttle' */])
					->post('/device/code', 'DeviceCodeController::process');

			// GET /oauth/device - Formulaire pour entrer le code utilisateur
			Route::name('vollmacht.device')
				->middleware(['web'])
				->get('/device', 'DeviceCodeController::form');		
				
			$webMiddlewares = config('vollmacht.middlewares.web', ['session']);
			Route::middleware($webMiddlewares)->group(function() {
				// GET /oauth/device/authorize - Affiche l'écran d'autorisation device
				Route::name('vollmacht.device.authorizations.authorize')
					->get('/device/authorize', 'DeviceAuthorizationController::authorize');
				
				// POST /oauth/device/authorize - L'utilisateur approuve le device
				Route::name('vollmacht.device.authorizations.approve')
					->post('/device/authorize', 'DeviceAuthorizationController::approve');

				// DELETE /oauth/device/authorize - L'utilisateur refuse le device
				Route::name('vollmacht.device.authorizations.deny')
					->delete('/device/authorize', 'DeviceAuthorizationController::deny');
			});
		});
	}

	protected static function registerRoute(array|Closure $options, ?Closure $callback = null): void
    {
        if (! is_array($options)) {
            $callback = $options;
            $options  = [];
        }

		['prefix' => $prefix, 'namespace' => $namespace] = static::routesConfig();
		$options = array_merge(compact('prefix', 'namespace'), $options);
		
		Route::group($options, $callback);
	}

	protected static function routesConfig(): array
	{
		$config = config('vollmacht.routes', []);

		$prefix    = $config['prefix'] ?? 'oauth';
		$namespace = $config['namespace'] ?? 'BlitzPHP\Vollmacht\Controllers';

		$config['prefix']    = $prefix;
		$config['namespace'] = $namespace;

		return $config;
	}
}
