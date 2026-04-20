<?php

namespace BlitzPHP\Vollmacht\Config;

use BlitzPHP\Vollmacht\Authenticators\TokenAuthenticator;
use BlitzPHP\Vollmacht\Middlewares\TokenAuth;

class Registrar
{
    /**
     * Enregistre les fichiers de configurations publiable
     */
    public static function config(): array
    {
        return ['vollmacht'];
    }

    /**
     * Ajout de l'authentificateur Vollmacht aux authentificateurs Schild
     */
    public static function auth(): array
    {
        return [
			'authenticators' => [
				'vollmacht' => TokenAuthenticator::class,
			],
			'guards' => [
				TokenAuth::class,
			]
		];
    }
}
