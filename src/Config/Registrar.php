<?php

namespace BlitzPHP\Vollmacht\Config;

class Registrar
{
    /**
     * Enregistre les middlewares Schild.
     */
    public static function middlewares(): array
    {
        return [
            'aliases' => [

			],
        ];
    }

    /**
     * Enregistre les fichiers de configurations publiable
     */
    public static function config(): array
    {
        return ['vollmacht'];
    }

    /**
     * Routes d'authentification
     */
    public static function routes(): array
    {
        return [];
    }
}
