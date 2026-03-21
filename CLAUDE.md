# CLAUDE.md — ufolep13volley

Application web de gestion des championnats de volleyball UFOLEP 13.

## Stack Technique

- **Backend** : PHP 8.1, MySQL
- **Frontend client** : Vue.js 3, Tailwind CSS, DaisyUI
- **Frontend admin** : Sencha/ExtJS (interface d'administration)
- **Tests** : PHPUnit (`unit_tests/`)
- **Reverse proxy local** : Caddy (Docker)
- **Déploiement** : Docker + GitHub Actions → OVH

## Structure du projet

```
classes/          # Toutes les classes PHP métier
  SqlManager.php  # Accès base de données
  Rest.php        # Classe de base pour les endpoints REST
  Generic.php     # Classe de base commune
  MatchMgr.php    # Gestion des matchs
  Players.php     # Gestion des joueurs
  Register.php    # Inscriptions
  Rank.php        # Classements
  ...
ajax/             # Endpoints AJAX (PHP)
cron/             # Tâches planifiées (daily, hourly, weekly)
js/               # JavaScript admin (ExtJS/Sencha)
  controller/     # Contrôleurs ExtJS
  model/          # Modèles ExtJS
  view/           # Vues ExtJS
  store/          # Stores ExtJS
admin/            # Interface admin Vue.js moderne (en cours de migration)
  components/     # Web components JS
unit_tests/       # Tests PHPUnit
templates/emails/ # Templates d'emails HTML
sql/              # Requêtes SQL utilitaires (lecture seule, pas de migrations)
images/           # Assets images
```

## Commandes essentielles

### Lancer l'application

**Mode développement local** (HTTP seulement, MySQL local, Mailpit) :
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
# App      : http://localhost
# Emails   : http://localhost:8025  (Mailpit)
```

**Mode home server / biggyben.freeboxos.fr** (HTTPS via Let's Encrypt, Caddyfile.docker) :
```bash
docker compose up -d --build
# App : https://biggyben.freeboxos.fr
```
> Le `docker-compose.dev.yml` n'est PAS chargé automatiquement — c'est voulu.
> `docker-compose.override.yml` serait chargé auto : ne pas utiliser ce nom.

### Tests PHP (dans le container dev)
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec php vendor/bin/phpunit unit_tests/
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec php vendor/bin/phpunit unit_tests/FinalsDrawTest.php
```

### Tests E2E Playwright
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile test run --rm playwright
```

### Installer les dépendances PHP
```bash
c:\php\php.exe composer.phar install
c:\php\php.exe composer.phar update
```

### Déployer en production
```bash
# Créer un tag pour déclencher le CI/CD
git tag v1.2.3
git push origin v1.2.3
```

## Configuration

- Copier `.env-template` en `.env` et remplir les valeurs
- Variables clés : `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_SERVER`, `MAIL_HOST`, etc.
- Pour Docker : utiliser `.env.docker`

## Architecture Backend

### Classe `SqlManager`
Point central d'accès à la base de données MySQL. Toutes les classes métier héritent de `Generic` qui instancie `SqlManager`.

### Classe `Rest`
Classe de base pour les endpoints REST auto-générés depuis la structure de table MySQL.

### Pattern des classes métier
```php
class MaClasse extends Generic {
    // hérite de $this->sql_manager pour les requêtes
}
```

### Endpoints AJAX
Les fichiers dans `ajax/` sont des points d'entrée HTTP. Ils instancient les classes et retournent du JSON.

## Tests unitaires

```php
// Hériter de UfolepTestCase pour les tests avec DB
class MonTest extends UfolepTestCase {
    // $this->sql          → instance SqlManager
    // connect_as_admin()  → simule session admin
    // connect_as_team_leader($id_equipe) → simule responsable équipe
}
```

## Frontend Vue.js (pages publiques)

- Vue.js 3 chargé via CDN ou bundler
- Tailwind CSS + DaisyUI pour les composants UI
- Fichiers `.js` à la racine ou dans `admin/` pour les nouvelles pages

## Frontend ExtJS (admin)

- Sencha/ExtJS pour l'interface d'administration historique
- Fichiers dans `js/` (controllers, models, views, stores)
- Point d'entrée : `admin.php` et `js/administration.js`

## GitHub

- Repository : https://github.com/benallemand/ufolep13volley
- Issues : https://github.com/benallemand/ufolep13volley/issues
- CI/CD : `.github/workflows/main.yml` (déclenchement par tag)

## Points d'attention

- **Scripts SQL de migration** : les stocker dans `ufolep13volley_python/sql/updates/{année}/` — PAS dans ce repo
- **Fichiers ignorés par git** : `players_pics/`, `teams_pics/`, `match_files/` (photos et feuilles de match)
- **Encodage** : toujours LF, jamais CRLF
- **Composer** : utiliser `composer.phar` local, pas un composer global
