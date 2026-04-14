<?php

return [
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
	 * Clé privée (ou chemin absolu) pour signer les tokens JWT
	 *
	 * @var string
	 */
	'private_key' => env('vollmacht.private_key', storage_path('oauth-private.key')),

	/**
	 * Chemin de la clé publique (ou chemin absolu) pour vérifier les tokens JWT
	 *
	 * @var string
	 */
	'public_key' => env('vollmacht.public_key', storage_path('oauth-public.key')),

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
	'access_token_lifetime' => HOUR,

	/**
	 * Durée de vie des refresh tokens (en seconde)
	 *
	 * @var int
	 */
	'refresh_token_lifetime' => 30 * DAY,

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
     * Entite User à utiliser
	 *
	 * @var class-string<\BlitzPHP\Schild\Entities\User>
     */
	'user_model' => \BlitzPHP\Schild\Entities\User::class,

	/**
     * Indique si les routes vollmacht doivent etre charger
     */
	'routes' => true,
];
