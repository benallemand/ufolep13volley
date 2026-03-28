# CLAUDE.md — ufolep13volley

Application web de gestion des championnats de volleyball UFOLEP 13.

## Stack Technique

- **Backend** : PHP 8.1, MySQL
- **Frontend client** : Vue.js 2, Tailwind CSS, DaisyUI
- **Frontend admin** : Sencha/ExtJS (interface d'administration)
- **Tests unitaires** : PHPUnit (`unit_tests/`)
- **Tests E2E** : Playwright (`e2e/`)
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
e2e/              # Tests E2E Playwright
  tests/          # Specs (.spec.js), un fichier par ticket/feature
  helpers/        # Helpers PHP (setup/teardown DB pour les tests)
  playwright.config.js
  global-setup.js # Setup global (données SQL optionnelles)
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
docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm playwright
```

**Architecture des tests E2E :**
- Chaque spec est dans `e2e/tests/` et correspond à un ticket GitHub (ex. `live_score.spec.js` → issue #217)
- Les données de test sont créées/nettoyées via des helpers PHP (`e2e/helpers/`) appelés en `beforeAll`/`afterAll`
- Les helpers PHP vérifient `APP_ENV=test` avant d'agir — ne jamais déployer en production
- Les screenshots de preuve sont rangés dans `e2e/test-results/issue-{N}/`
- `workers: 1` dans `playwright.config.js` : les tests partagent une DB, pas de parallélisme

**Pattern setup/teardown :**
```js
test.beforeAll(async ({ request }) => {
    const res = await request.get('/e2e/helpers/mon_setup.php');
    expect(res.status()).toBe(200);
});
test.afterAll(async ({ request }) => {
    await request.get('/e2e/helpers/mon_teardown.php');
});
```

**Helpers PHP existants :**
- `test_setup.php` / `test_teardown.php` — match live score (issue #217)
- `test_verify.php` — vérifie les scores en base après `save_to_match`
- `finals_setup.php` / `finals_teardown.php` — matchs 1/8 finale KF/CF (issue #215)

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

- Vue.js 2 chargé via CDN
- Tailwind CSS + DaisyUI pour les composants UI
- Fichiers `.js` à la racine ou dans `pages/components/` pour les composants
- Communication parent→enfant : props ; enfant→parent : `$emit()`

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
- **Structure des tables** : schéma de référence dans `ufolep13volley_python/sql/ufolepvocbufolep.sql`
- **`matchs_view`** : vue SQL centrale utilisée par `MatchMgr::get_matches()` — fait un INNER JOIN sur `competitions`, donc tout match de test doit avoir une `code_competition` existante dans cette table
- **Fichiers ignorés par git** : `players_pics/`, `teams_pics/`, `match_files/` (photos et feuilles de match)
- **Encodage** : toujours LF, jamais CRLF
- **Composer** : utiliser `composer.phar` local, pas un composer global
