# Changelog

Tous les changements notables de ce projet sont documentés dans ce fichier.

Le format s’inspire de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), et ce projet suit [Semantic Versioning](https://semver.org/lang/fr/).

## [1.2.0] - 2026-05-17

### Ajouté

- **`Tivins\PhpCore\CliArgv`** : analyse portable des arguments CLI (`$argv`) — drapeaux longs `--nom` / `--nom=valeur`, drapeaux courts `-x` / `-x=valeur`, normalisation des valeurs `getopt()` (tableaux, `false`), séparation `--` pour les opérandes, fabrique `CliArgv::fromGlobals()`.
- **`tests/CliArgvTest.php`** : couverture des chemins principaux.

## [1.1.1] - 2026-05-16

### Modifié

- **`Tivins\PhpCore\Request`** : si la constante `CURLSSLOPT_NATIVE_CA` existe (PHP / libcurl récents) et que la vérification TLS est activée, définition de `CURLOPT_SSL_OPTIONS` vers le magasin de certificats **natif du système** — ce qui évite souvent les échecs TLS sous Windows lorsque `curl.cainfo` n’est pas configuré. Une fusion explicite via `curlOptions()` remplace encore cette valeur si besoin.

## [1.1.0] - 2026-05-16

### Ajouté

- **`Tivins\PhpCore\Request`** : client HTTP fluent basé sur cURL (verbes courants, requête `query`, corps texte ou `jsonBody`, `bearerToken`, auth basique, timeout, redirections, vérification TLS, `userAgent`, fusion d’options `curl_*` avec `curlOptions()`).
- **`Tivins\PhpCore\Response`** : statut, en-têtes (noms en minuscules, valeurs répétables), corps, erreurs cURL (`hasCurlError`, `isSuccessful`), décodage JSON `decodeJson()`.
- Dépendance d’exécution **`php`**: `^8.2`.
- **`tests/RequestTest.php`** : fichier local `file://`, exemple réseau avec requêtes ignorées si hors ligne, tests valeur `Response`.

## [1.0.0] - 2026-05-16

### Contenu de la bibliothèque

- **Paquet Composer** `tivins/php-core`, licence MIT, autoload PSR-4 `Tivins\PhpCore\` vers `src/Tivins/PhpCore/`.
- **`Tivins\PhpCore\DotEnv`** : chargement minimal de variables d’environnement à partir d’un fichier (une paire `clé=valeur` par ligne) ou d’un tableau de lignes. Ignore les lignes vides et les commentaires (lignes commençant par `#`). Met à jour `putenv()` et `$_ENV`. Le premier `=` de la ligne sépare clé et valeur (les `=` suivants font partie de la valeur).

### Outils et tests

- **PHPUnit** (^13.1) en dépendance de développement, script Composer `composer test` → `phpunit`.
- **`phpunit.xml.dist`** : bootstrap `vendor/autoload.php`, suite de tests sur le répertoire `tests/`.
- **`tests/DotEnvTest.php`** : test du chargement sur une fixture `tests/.env.test` (fichier type application : URLs, sections commentées, valeur contenant `=`).
- Aucune dépendance d’exécution (`require`) pour la version 1.0.0 : le code livré ne s’appuie que sur PHP standard.
