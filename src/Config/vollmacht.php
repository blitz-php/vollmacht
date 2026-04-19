<?php

return [
	/**
     * --------------------------------------------------------------------
     * Fichiers de vues
     * --------------------------------------------------------------------
     */
    'views' => [
        'authorization'        => '\BlitzPHP\Vollmacht\Views\authorize',
        'device-authorization' => '\BlitzPHP\Vollmacht\Views\device-authorize',
        'device-user-code'     => '\BlitzPHP\Vollmacht\Views\device-user-code',
    ],

	/**
     * -------------------------------------------------- -------------------
     * Enregistrer les tentatives de connexion pour l'authentification par jeton et l'authentification HMAC
     * ------------------------------------------------- -------------------
     * Spécifiez quelles tentatives de connexion sont enregistrées dans la base de données.
     *
     * Les valeurs valides sont :
     * - RECORD_LOGIN_ATTEMPT_NONE
     * - RECORD_LOGIN_ATTEMPT_FAILURE
     * - RECORD_LOGIN_ATTEMPT_ALL
     */
    'record_login_attempt' => RECORD_LOGIN_ATTEMPT_FAILURE,

    /**
	 * Clé privée pour signer les tokens JWT
	 *
	 * @var string
	 */
	'private_key' => env('vollmacht.private_key'),

	/**
	 * Chemin de la clé publique (ou chemin absolu) pour vérifier les tokens JWT
	 *
	 * @var string
	 */
	'public_key' => env('vollmacht.public_key'),

	/**
	 * Chemin de stockage des fichiers de clés privées/publiques
	 */
	'keys_path' => STORAGE_PATH,

	/**
     * Algorithme de signature JWT
	 *
	 * @var string
     */
	'algorithm' => env('vollmacht.algorithm', 'HS256'),

	/**
	 * Durée de vie des access tokens (en seconde)
	 *
	 * @var int
	 */
	'access_token_lifetime' => YEAR,

	/**
	 * Durée de vie des refresh tokens (en seconde)
	 *
	 * @var int
	 */
	'refresh_token_lifetime' => YEAR,

	/**
	 * Durée de vie des auth codes (en seconde)
	 *
	 * @var int
	 */
	'auth_codes_lifetime' => 10 * MINUTE,

	/**
	 * Durée de vie des personal access tokens (en seconde)
	 *
	 * @var int
	 */
	'personal_access_token_lifetime' => YEAR,

	/**
     * Scopes disponibles
	 *
	 * @var array<string, string>
     */
    'scopes' => [],

	/**
	 * Liste des scopes par défaut si aucun n'est spécifié
	 *
	 * @var list<string>
	 */
	'default_scopes' => [],

	/**
     * Middleware pour les routes API
	 *
	 * @var array{api: list<string>, web: list<string>}
     */
	'middlewares' => [
        'api' => [],
        'web' => ['session'],
    ],

	/**
	 * @var bool
     */
	'device_code_grant_enabled' => true,
	
	/**
	 * @var bool
     */
	'registers_json_api_routes' => true,

	/**
     * Indique si les routes vollmacht doivent etre charger
     */
	'routes' => [
		'enable' => true,
		'prefix' => 'oauth',
		'namespace' => 'BlitzPHP\Vollmacht\Controllers',
	],
];
