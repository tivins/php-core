# Changelog

Tous les changements notables de ce projet sont documentés dans ce fichier.

Le format s’inspire de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), et ce projet suit [Semantic Versioning](https://semver.org/lang/fr/).

## [2.0.1] - 2026-06-21

### Added

- **`Ansi256`** : afficher du texte dans un terminal en utilisant les 256 couleurs

## [2.0.0] - 2026-05-29

### Sécurité

- **`DotEnv`** : les variables d'environnement déjà définies ne sont **plus écrasées par défaut** (`overwrite = false`). Un fichier `.env` ne peut donc plus clobber `PATH`, `LD_PRELOAD` ou toute autre variable héritée du système / du processus parent (vecteur d'injection vers les sous-processus). Les noms de clés invalides (caractères hors `[A-Za-z_][A-Za-z0-9_]*`) sont désormais ignorés.
- **`Request`** : les garde-fous de sécurité (`CURLOPT_PROTOCOLS`, `CURLOPT_REDIR_PROTOCOLS`, `CURLOPT_SSL_VERIFYPEER/HOST`) sont ré-appliqués **après** la fusion de `curlOptions()` : un appelant ne peut plus désactiver silencieusement l'anti-SSRF ou la vérification TLS via des options cURL brutes. Utilisez `allowedProtocols()` / `verifySsl()`.
- **`Request`** : anti-fuite d'identifiants sur redirection. `followRedirects` passe en mode automatique (`null`) : les redirections ne sont **pas** suivies lorsqu'un en-tête `Authorization` (`bearerToken()`) ou une auth basique sont présents, afin de ne pas divulguer le jeton à une cible cross-origin (cURL réémet les en-têtes personnalisés sur redirection). Ajout de `CURLOPT_UNRESTRICTED_AUTH = false`.
- **`Request::header()`** : rejette désormais les noms vides et tout caractère CR/LF dans le nom ou la valeur (`ValueError`), prévenant l'injection d'en-têtes (CRLF).

### Ajouté

- **`Api\Router`** : contraintes par paramètre `{name:contrainte}` (sous-expression regex de confiance), p. ex. `{id:\d+}` ou `{code:\d{4}}`, pour restreindre le matching (les accolades de quantifieur sont gérées).
- **`Api\Router`** : un nom de paramètre dupliqué dans un motif lève désormais `InvalidArgumentException` à l'ajout (auparavant : route qui ne matchait jamais, en silence).
- **`DotEnv::loadFile/loadLines/tryLoadFile`** : paramètre `bool $overwrite = false`.
- **Tests** : couverture des nouveaux comportements (non-écrasement, quotes/commentaires, clés invalides, inviolabilité des options cURL, redirections + bearer, CRLF, contraintes de route, noms dupliqués).

### Modifié

- **`DotEnv`** : parsing des valeurs amélioré — trim, retrait des guillemets simples/doubles entourants (séquences `\n \r \t \" \\` interprétées en guillemets doubles), retrait des commentaires en fin de ligne (` # ...`) pour les valeurs non quotées, tolérance du préfixe `export `.
- **`Request::followRedirects()`** : accepte `?bool` (`null` = mode automatique).

### Note de mise à niveau

- **`DotEnv`** (rupture) : si vous comptiez sur l'écrasement des variables existantes, appelez explicitement `DotEnv::loadFile($path, overwrite: true)` (ou `loadLines($lines, true)`). Le parsing trim désormais les valeurs et retire les guillemets entourants : adaptez les fixtures qui dépendaient des espaces / guillemets bruts.
- **`Request`** (rupture potentielle) : avec un `bearerToken()`/auth basique, les redirections ne sont plus suivies par défaut. Pour rétablir l'ancien comportement (au risque de divulguer le jeton), appelez `->followRedirects(true)` explicitement. `header()` lève désormais sur CR/LF.

## [1.6.0] - 2026-05-29

### Sécurité

- **`Api\Router`** : les segments littéraux des motifs sont désormais échappés (`preg_quote`). Auparavant, des caractères comme `.`, `+` ou `#` étaient interprétés comme métacaractères regex, ce qui pouvait élargir le matching (`/v1/api` matchait `/v1xapi`) ou casser le délimiteur — risque de routage involontaire.
- **`Request`** : nouvelle liste blanche de protocoles cURL, restreinte par défaut à `http`/`https` (`CURLOPT_PROTOCOLS` et `CURLOPT_REDIR_PROTOCOLS`). Bloque `file://`, `gopher://`, `dict://`, etc., y compris via redirection (durcissement anti-SSRF).
- **`Api\AccessToken`** : `issue()` refuse un identifiant `<= 0` (`InvalidArgumentException`) et `verify()` rejette un jeton dont le `sub` n'est pas strictement positif.

### Ajouté

- **`Request::allowedProtocols(int $protocols)`** : configure le masque `CURLPROTO_*` autorisé (à n'élargir que pour des URL de confiance).
- **`tests/RouterTest.php`** : couverture du matching, des paramètres `{name}` et de l'échappement des métacaractères.
- **`RequestTest`** : vérifie que `file://` est bloqué par défaut et accessible uniquement via `allowedProtocols(CURLPROTO_FILE)`.
- **`AccessTokenTest`** / **`DotEnvTest`** : cas `sub <= 0` et lignes `.env` malformées.

### Modifié

- **`DotEnv::loadLines`** : ignore les lignes sans `=` (plus de warning « Undefined array key ») et trim la clé ; les clés vides sont ignorées.
- **`scripts/new_release.php`** : vérifie les codes de sortie de `git tag` et `git push` et échoue explicitement.

### Note de mise à niveau

- Si vous utilisiez `Request` avec des URL `file://` (ou un autre protocole non HTTP), ajoutez désormais `->allowedProtocols(CURLPROTO_FILE)` (ou le masque adapté).

## [1.5.2] - 2026-05-20

### Corrigé

- **CI** : PHPUnit repassé en `^11.5` (compatible PHP 8.2–8.4) ; PHPUnit 13 exige PHP ≥ 8.4.1 et faisait échouer la matrice 8.2/8.3.

## [1.5.1] - 2026-05-20

### Ajouté

- **`README.md`** : installation, modules, exemples, limites (SSRF, JWT, Dotenv).
- **`SECURITY.md`** : signalement de vulnérabilités et bonnes pratiques.
- **CI GitHub Actions** : `composer test` et `composer audit` sur PHP 8.2–8.4.

## [1.5.0] - 2026-05-20

### Ajouté

- **`Tivins\PhpCore\Tty`** : détection du contexte CLI (`isCLI`, `ensureIsCLI`).
- **`Tivins\PhpCore\Exception\MkDirException`** : erreur dédiée à la création de répertoires (propriété `dir`).
- **`Tivins\PhpCore\Io\File::makeDir`** et **`makeDirForFile`** : création récursive de dossiers (idempotente si le dossier existe déjà).
- **`tests/TtyTest.php`** : comportement sous SAPI `cli`.
- **`tests/FileTest.php`** : couverture de `makeDir` / `makeDirForFile` et de `MkDirException`.

### Corrigé

- **`Tivins\PhpCore\Tty::isCLI`** : retournait l’inverse du SAPI CLI.

## [1.4.2] - 2026-05-18

fix tag

## [1.4.1] - 2026-05-18

### Ajouté

- **`Tivins\PhpCore\Io\File::writeJSON`** : paramètre `$pretty` (défaut `false`) pour activer `JSON_PRETTY_PRINT`.

## [1.4.0] - 2026-05-18

### Ajouté

- **`Tivins\PhpCore\Io\File`** : lecture et écriture de fichiers texte (`read`, `write`) et JSON (`readJSON`, `writeJSON`) avec `JSON_THROW_ON_ERROR` et options Unicode / slashs non échappés.
- **`tests/FileTest.php`** : couverture des chemins principaux (lecture/écriture, JSON valide ou invalide, fichier vide).

### Modifié

- **`Tivins\PhpCore\Io\File`** : erreurs I/O explicites (`File not found`, `Cannot read file`, `Empty file`, `Cannot write file`) au lieu de `TypeError` ; JSON `0` accepté par `readJSON` ; écriture avec verrou `LOCK_EX`.

## [1.3.0] - 2026-05-17

### Ajouté

- **`Tivins\PhpCore\DotEnv::tryLoadFile`** : appelle `loadFile` et ignore les `Exception` (notamment fichier introuvable).

## [1.2.1] - 2026-05-17

### Ajouté

- **`Tivins\PhpCore\Api\JwtSigningSecret`** : clé HS256 lue depuis la variable d’environnement `JWT_SECRET` (minimum 32 octets).
- **`tests/AccessTokenTest.php`** : émission / vérification JWT et garde-fous sur la clé.

### Modifié

- **`Tivins\PhpCore\Api\AccessToken`** : algorithme centralisé, absence de `sub` après décodage traitée comme invalide, documentation des erreurs de configuration.
- **`Tivins\PhpCore\Api\Auth`**, **`JsonResponder`**, **`Router`** : même ordre de déclaration `strict_types` que le reste du paquet.

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
