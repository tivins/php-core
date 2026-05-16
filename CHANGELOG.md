# Changelog

Tous les changements notables de ce projet sont documentés dans ce fichier.

Le format s’inspire de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), et ce projet suit [Semantic Versioning](https://semver.org/lang/fr/).

## [1.0.0] - 2026-05-16

### Contenu de la bibliothèque

- **Paquet Composer** `tivins/php-core`, licence MIT, autoload PSR-4 `Tivins\PhpCore\` vers `src/Tivins/PhpCore/`.
- **`Tivins\PhpCore\DotEnv`** : chargement minimal de variables d’environnement à partir d’un fichier (une paire `clé=valeur` par ligne) ou d’un tableau de lignes. Ignore les lignes vides et les commentaires (lignes commençant par `#`). Met à jour `putenv()` et `$_ENV`. Le premier `=` de la ligne sépare clé et valeur (les `=` suivants font partie de la valeur).

### Outils et tests

- **PHPUnit** (^13.1) en dépendance de développement, script Composer `composer test` → `phpunit`.
- **`phpunit.xml.dist`** : bootstrap `vendor/autoload.php`, suite de tests sur le répertoire `tests/`.
- **`tests/DotEnvTest.php`** : test du chargement sur une fixture `tests/.env.test` (fichier type application : URLs, sections commentées, valeur contenant `=`).
- Aucune dépendance d’exécution (`require`) pour la version 1.0.0 : le code livré ne s’appuie que sur PHP standard.
