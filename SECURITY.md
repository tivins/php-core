# Politique de sécurité

## Versions supportées

| Version | Supportée |
|---------|-----------|
| 1.5.x   | Oui       |
| &lt; 1.5  | Non       |

## Signaler une vulnérabilité

Merci de **ne pas** ouvrir d’issue publique pour un problème de sécurité.

Contactez les mainteneurs du dépôt en privé (e-mail ou canal interne Tivins) avec :

- description du problème et impact ;
- étapes pour reproduire ;
- version du paquet et de PHP concernées.

Nous nous efforçons de répondre sous **72 heures** ouvrées et de publier un correctif selon la gravité.

## Bonnes pratiques d’utilisation

### JWT (`Api\AccessToken`, `Api\Auth`)

- Définir `JWT_SECRET` avec **au moins 32 octets** aléatoires (voir `JwtSigningSecret`).
- Ne pas committer de secrets ; injecter les variables via l’hébergeur en production.
- Les jetons sont **stateless** : pas de révocation intégrée ; durée de vie fixe (1 h par défaut).
- HS256 uniquement ; pour multi-services ou OIDC, utiliser une solution dédiée (Symfony Security, bundle JWT, IdP).

### `.env` (`DotEnv`)

- Fichier `.env` pour le développement local ; en production, préférer les variables d’environnement du runtime.
- Le parseur ne gère pas les valeurs entre guillemets ni `export` — éviter les secrets complexes non échappés.

### HTTP sortant (`Request`)

- **Ne pas** construire l’URL à partir d’entrées utilisateur sans liste blanche (risque SSRF).
- Garder `verifySsl(true)` sauf besoin explicite en environnement de test.

### Fichiers (`Io\File`)

- Valider les chemins côté application (pas de traversal `../` non contrôlé).

## Dépendances

Exécuter régulièrement :

```bash
composer audit
```

La CI du dépôt lance cet audit à chaque push/PR.
