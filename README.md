# BlitzPHP Vollmacht

[![PHP Version](https://img.shields.io/badge/php-^8.2-blue)](https://php.net)
[![Latest Version](https://img.shields.io/github/v/release/blitz-php/vollmacht)](https://github.com/blitz-php/vollmacht/releases)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

**Vollmacht** est le serveur OAuth2 officiel pour l'écosystème [BlitzPHP](https://github.com/blitz-php/framework).

> *Vollmacht* signifie **"Procuration"** ou **"Mandat"** en allemand. Fidèle à son nom, cette bibliothèque permet à vos utilisateurs de délivrer une procuration sécurisée à des applications tierces pour accéder à leurs ressources, sans jamais exposer leurs identifiants.

## Fonctionnalités

- **Serveur OAuth2 complet** : Implémente les standards `authorization_code`, `client_credentials`, `password`, `refresh_token` et `implicit`.
- **Intégration native avec [Schild](https://github.com/blitz-php/schild)** : Utilise les utilisateurs, groupes et permissions de Schild. Aucune duplication.
- **Cli API First** : Générez des clients OAuth via l'interface en ligne de commande `klinge`.
- **Scopes Dynamiques** : Définissez et vérifiez des scopes pour granulariser les accès.
- **Middleware Prêt à l'emploi** : Protégez vos routes API avec `vollmacht:auth`.
- **JWT ou Base de données** : Choisissez entre des tokens opaques (stockés en DB) ou des JWT signés.

## Installation

Via Composer :

```bash
composer require blitz-php/vollmacht
```

Puis, publiez la configuration et les migrations :

```bash
php klinge vollmacht:publish
php klinge migrate
```

Générez les clés de chiffrement nécessaires au serveur OAuth :

```bash
php klinge vollmacht:keys
```

## Démarrage Rapide

### 1. Ajoutez le Trait à votre Modèle Utilisateur

Pour que vos utilisateurs puissent créer des tokens personnels, ajoutez le trait `HasApiTokens` à votre `User` entity :

```php
<?php

namespace App\Entities;

use BlitzPHP\Schild\Entities\User as SchildUser;
use BlitzPHP\Vollmacht\Traits\HasApiTokens;

class User extends SchildUser
{
    use HasApiTokens;
}
```

### 2. Protégez une Route API

Dans votre fichier `app/Config/Routes.php` :

```php
$router->group('/api', ['middleware' => 'vollmacht:auth'], function($router) {
    $router->get('user', 'Api\UserController::profile');
});
```

### 3. Créez un Client OAuth

```bash
php klinge vollmacht:client --name="Mon Application Mobile" --redirect_uri="myapp://callback"
```

## Tests

```bash
composer test
```

## Contribution

Les contributions sont les bienvenues ! Veuillez consulter le [Guide de Contribution](CONTRIBUTING.md) et vous assurer que vos modifications passent les tests et l'analyse statique.

```bash
composer ci
```

## Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## Crédits

- **Auteur** : Dimitri Sitchet Tomkeu
- **Inspiration** : [Laravel Passport](https://github.com/laravel/passport) & [league/oauth2-server](https://github.com/thephpleague/oauth2-server)

---

*"Donner procuration, c'est faire confiance. Vollmacht rend cette confiance sécurisée."*
```
