# tivins/php-core

Bibliothèque PHP légère (utilitaires CLI, HTTP sortant, fichiers, `.env`, mini-API JSON + JWT).

- **PHP** : `^8.2`
- **Licence** : MIT
- **Dépendance runtime** : [`firebase/php-jwt`](https://github.com/firebase/php-jwt) `^7.0`

## Installation

```bash
composer require tivins/php-core
```

## Modules

| Namespace | Rôle |
|-----------|------|
| `Tivins\PhpCore\DotEnv` | Chargement minimal d’un fichier `.env` |
| `Tivins\PhpCore\Request` / `Response` | Client HTTP sortant (cURL) |
| `Tivins\PhpCore\CliArgv` | Analyse portable de `$argv` (Windows-friendly) |
| `Tivins\PhpCore\Io\File` | Lecture/écriture texte et JSON |
| `Tivins\PhpCore\Tty` | Détection du contexte CLI |
| `Tivins\PhpCore\Api\*` | Routeur regex, réponses JSON, JWT HS256 |

Voir le [CHANGELOG](CHANGELOG.md) pour l’historique des versions.

## Exemples

### Variables d’environnement

```php
use Tivins\PhpCore\DotEnv;

DotEnv::loadFile(__DIR__ . '/.env');
// ou, sans erreur si le fichier est absent :
DotEnv::tryLoadFile(__DIR__ . '/.env');
```

Le parseur est volontairement simple : une paire `clé=valeur` par ligne, commentaires `#`, premier `=` séparateur. Pas de guillemets ni d’expansion de variables (contrairement à Symfony Dotenv).

### Requête HTTP sortante

```php
use Tivins\PhpCore\Request;

$response = Request::get('https://api.example.com/status')
    ->timeout(10)
    ->send();

if ($response->isSuccessful()) {
    $data = $response->decodeJson();
}
```

**Important** : n’utilisez pas `Request` avec une URL fournie par un utilisateur sans liste blanche — risque de SSRF.

### JWT (API)

Définir `JWT_SECRET` (≥ 32 octets) dans l’environnement :

```php
use Tivins\PhpCore\Api\AccessToken;
use Tivins\PhpCore\Api\Auth;

$token = AccessToken::issue($userId);
$userId = AccessToken::verify($token);

// ou, dans un endpoint :
$userId = Auth::requireUserId(); // 401 JSON si absent / invalide
```

### CLI

```php
use Tivins\PhpCore\CliArgv;

$argv = CliArgv::fromGlobals();
$verbose = $argv->hasLongFlag('verbose');
$config = $argv->longFlagValue('config');
```

## Développement

```bash
composer install
composer test
composer audit
```

La CI (GitHub Actions) exécute les tests et `composer audit` sur PHP 8.2–8.4.

## Sécurité

Consulter [SECURITY.md](SECURITY.md) pour signaler une vulnérabilité et les bonnes pratiques (JWT, `.env`, HTTP sortant).

## Symfony : quand migrer ?

Ce paquet vise les **scripts et micro-services** avec peu de dépendances. Pour une application web complète (routing, sécurité, validation, DI), préférez les composants Symfony (`http-client`, `dotenv`, `security`, etc.) ou un framework complet.

Garder `php-core` reste pertinent pour homogénéiser des utilitaires transverses sans importer Symfony.
