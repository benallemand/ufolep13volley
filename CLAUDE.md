# CLAUDE.md — ufolep13volley

Application web de gestion des championnats de volleyball UFOLEP 13.

## Stack Technique

- **Backend** : PHP 8.1, MySQL
- **Frontend client** : Vue.js 3, Tailwind CSS, DaisyUI — bundlé via Vite (Node.js 20)
- **Frontend admin** : Sencha/ExtJS (interface d'administration historique, CDN — non bundlé)
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
src/              # Sources Vite (CSS global)
  css/app.css     # Tailwind + DaisyUI + libs tierces
helpers/          # Helpers PHP
  vite.php        # vite_asset() : lit dist/.vite/manifest.json
dist/             # Bundle Vite (gitignored — produit par `npm run build`)
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
- `messages_setup.php` / `messages_teardown.php` — emails non lus pour responsable d'équipe (issue #221)

### Installer les dépendances PHP
```bash
c:\php\php.exe composer.phar install
c:\php\php.exe composer.phar update
```

### Frontend bundlé (Vite)

Le frontend Vue 3 est bundlé par Vite. Les pages PHP utilisent le helper `vite_asset()` (dans `helpers/vite.php`) pour émettre les balises `<script>` / `<link>` vers les fichiers hashés ; les pages HTML pures (home, my_page, admin/matches) sont des entrées HTML natives Vite et un `.htaccess` rewrite leurs URLs publiques vers `/dist/`.

**Installer les dépendances Node**
```bash
npm ci    # apres un git pull, pour respecter le lockfile
npm install   # pour ajouter/retirer un paquet
```

**Builder le bundle** (produit `dist/` minifié + hashé + `dist/.vite/manifest.json`)
```bash
npm run build
```

> **Important** : le `dist/` n'est PAS versionné (.gitignore). Il faut le rebuild après chaque modification du code frontend. En Docker, le `Dockerfile` build automatiquement le `dist/` via une étape multi-stage `node:20-alpine`. En CI (`.github/workflows/main.yml`), GitHub Actions build et rsync `dist/` vers OVH avant le `git pull` du code source.

**Entrées déclarées dans `vite.config.js`** :
- `src/css/app.css` — Tailwind + DaisyUI + libs tierces (FontAwesome, Notyf, Toastify)
- `live.js`, `match.js`, `survey.js`, `team_sheets.js` — entrées JS pour les pages .php (chargées via `vite_asset('live.js')` etc.)
- `pages/home.html`, `pages/my_page.html`, `admin/matches.html` — entrées HTML natives Vite

**Hors périmètre du bundle Vite** : `admin.php`, `register.php`, `reset_password.php`, `rank_for_cup.php` — restent sur les CDN ExtJS (interface d'administration historique).

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

- Vue.js 3 (Options API) bundlé par Vite — plus aucun CDN externe en prod
- Tailwind CSS + DaisyUI pour les composants UI
- Fichiers `.js` à la racine (`live.js`, `match.js`, ...) ou dans `pages/components/`
- Communication parent→enfant : props ; enfant→parent : `$emit()`
- Templates en string (`template: '...'`) dans les composants — `vite.config.js` alias `vue` → `vue/dist/vue.esm-bundler.js` pour inclure le compilateur de templates
- Composants async via `defineAsyncComponent(() => import(...))` (pas `() => import(...)` direct — pas supporté en Vue 3)
- `axios`, `Toastify`, `Notyf` exposés sur `window` par chaque entrée pour préserver l'usage en globaux dans les sous-composants
- `dist/` produit par `npm run build` (voir section "Commandes essentielles")

## Frontend ExtJS (admin)

- Sencha/ExtJS pour l'interface d'administration historique
- Fichiers dans `js/` (controllers, models, views, stores)
- Point d'entrée : `admin.php` et `js/administration.js`

## GitHub

- Repository : https://github.com/benallemand/ufolep13volley
- Issues : https://github.com/benallemand/ufolep13volley/issues
- CI/CD : `.github/workflows/main.yml` (déclenchement par tag)

## Bonnes Pratiques Apprises

### Identifiants de Match
- Les `id_match` peuvent être des chaînes (ex: `C_9_20260122_040`), pas seulement des entiers
- Utiliser `VARCHAR(20)` pour stocker les `code_match` en base
- Dans l'API PHP, utiliser `FILTER_SANITIZE_FULL_SPECIAL_CHARS` au lieu de `FILTER_VALIDATE_INT`
- Utiliser `get_match_by_code_match()` plutôt que `get_match()` pour les requêtes par code

### Boutons Conditionnels (Vue.js)
- Vérifier la date du jour : `new Date().toLocaleDateString('fr-FR')` pour comparer avec les dates au format `dd/mm/yyyy`
- Masquer les boutons quand l'action n'est plus pertinente (ex: match terminé)
- Utiliser `animate-pulse` de Tailwind pour attirer l'attention

### Autorisation Multi-Niveau
- Vérifier côté backend ET frontend
- Pattern : Admin OU responsable de l'équipe concernée
- Stocker `id_equipe` en session pour les vérifications

### Debug de Features en Temps Réel
- Créer un fichier `debug_{feature}.php` pour tester/modifier les données temporairement
- Permet de mettre à jour les dates pour simuler "aujourd'hui"

### Requêtes AJAX ExtJS
- Toujours spécifier `method: 'GET'` pour les lectures, `method: 'POST'` pour les écritures
- Par défaut ExtJS utilise POST, ce qui n'est pas toujours approprié

### Validation d'ID en PHP
- Utiliser `!empty($id) && is_numeric($id)` pour vérifier les IDs de base de données
- ExtJS génère des IDs temporaires comme `"extModel1124-23"` pour les nouveaux records
- `isset()` retourne true même pour `null`, préférer `!empty()`

### Tests Unitaires Sélectifs
- Certains tests sont destructifs (modification de données réelles)
- Préférer exécuter les tests spécifiques : `--filter "test_method1|test_method2"`
- Éviter `phpunit unit_tests/` si la base contient des données de production

### Routeur REST PHP et Paramètres Nommés
- Le routeur `rest/action.php` appelle les méthodes avec des **paramètres nommés** PHP 8+
- Les méthodes CRUD doivent accepter les paramètres comme arguments de fonction, pas via `$_POST`
- Exemple : `public function saveNews($id = null, $title = '', $text = ''): void`
- ExtJS envoie automatiquement `$dirtyFields` lors du submit — l'ajouter comme paramètre optionnel

### Frontend ExtJS — Patterns Admin
- **Grilles admin** : utiliser des fenêtres d'édition modales (pattern `window.Window`), pas le plugin `rowediting`
- Créer : `js/view/{entity}/AdminGrid.js` pour la grille, `js/view/{entity}/Edit.js` pour la fenêtre modale
- Ajouter les refs : `formPanelEdit{Entity}`, `windowEdit{Entity}`, `manage{Entity}Grid`

### GitHub CLI
- Pour créer une issue/PR via `gh`, rédiger le body dans un fichier `.md` temporaire puis utiliser `--body-file`
- Ne pas utiliser `--body` inline (problèmes d'échappement)
- Supprimer le fichier temporaire après usage

## Points d'attention

- **Scripts SQL de migration** : les stocker dans `ufolep13volley_python/sql/updates/{année}/` — PAS dans ce repo
- **Structure des tables** : schéma de référence dans `ufolep13volley_python/sql/ufolepvocbufolep.sql`
- **`matchs_view`** : vue SQL centrale utilisée par `MatchMgr::get_matches()` — fait un INNER JOIN sur `competitions`, donc tout match de test doit avoir une `code_competition` existante dans cette table
- **Fichiers ignorés par git** : `players_pics/`, `teams_pics/`, `match_files/` (photos et feuilles de match)
- **Encodage** : toujours LF, jamais CRLF
- **Composer** : utiliser `composer.phar` local, pas un composer global
