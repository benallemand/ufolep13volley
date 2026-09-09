# CLAUDE.md — ufolep13volley

Application web de gestion des championnats de volleyball UFOLEP 13.

## Stack Technique

- **Backend** : PHP 8.1, MySQL
- **Frontend client** : Vue.js 3, Tailwind CSS, DaisyUI — bundlé via Vite (Node.js 20)
- **Frontend admin** : Vue.js 3 (`admin/index.html`, cible) + Sencha/ExtJS (`admin.php`, historique) — migration en cours, issue #265
- **Tests unitaires** : PHPUnit (`unit_tests/`)
- **Tests E2E** : Playwright (`e2e/`)
- **Reverse proxy local** : Caddy (Docker)
- **Déploiement** : Docker + GitHub Actions → OVH

## Structure du projet

```
classes/          # Toutes les classes PHP métier
  SqlManager.php  # Accès base de données
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
admin/            # Interface admin Vue.js (en cours de migration — issue #265)
  index.html      # Entrée Vite de la nouvelle admin
  components/
    layout/       # Shell, sidebar, routeur, garde admin
    grid/         # Grille et formulaire modal génériques
    screens/      # Un composant par écran migré
src/              # Sources Vite (CSS global)
  css/app.css     # Tailwind + DaisyUI + libs tierces
helpers/          # Helpers PHP
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
# Emails   : http://localhost/mailpit  (via Caddy, auth basique)
#            http://localhost:8025/mailpit/  (accès direct, sans mot de passe)
```

**Mode home server / biggyben.freeboxos.fr** (HTTPS via Let's Encrypt, Caddyfile.docker) :
```bash
docker compose up -d --build
# App    : https://biggyben.freeboxos.fr
# Emails : https://biggyben.freeboxos.fr/mailpit  (auth basique)
```
> Le `docker-compose.dev.yml` n'est PAS chargé automatiquement — c'est voulu.
> `docker-compose.override.yml` serait chargé auto : ne pas utiliser ce nom.
>
> En mode home server, le code est servi **depuis l'image** (pas de bind mount
> du working dir — le partage de fichiers Windows→VM wedgeait la VM Docker
> Desktop). Toute modif de code nécessite donc un `docker compose up -d --build`.
> Seuls `.env.docker` (monté comme `.env`) et les répertoires d'uploads
> (`players_pics`, `players_pics_low`, `teams_pics`, `match_files`) restent montés.
>
> **`.env.docker` étant monté fichier par fichier, une réécriture du fichier casse
> le bind mount** (nouvel inode) : le conteneur continue de lire l'ancienne version.
> Après modification, `docker compose ... up -d --force-recreate php`.

### Emails : tout part dans Mailpit

Le service `mailpit` est déclaré dans `docker-compose.yml`, donc présent dans les
**deux** modes, et `.env.docker` pointe dessus (`MAIL_HOST=mailpit`, port 1025).
Ni le dev local ni le home server n'envoient de vrai email. Seule la prod OVH
envoie réellement — elle utilise `.env.prod` (SMTP OVH), jamais `.env.docker`.

L'interface est servie par Caddy sous `/mailpit` (Mailpit tourne avec
`MP_WEBROOT=mailpit`, donc le préfixe n'est **pas** retiré par le proxy) et
protégée par une auth basique : la boîte contient des liens de réinitialisation
valides et des mots de passe générés en clair. Le port 8025 n'est publié que sur
`127.0.0.1`, jamais sur l'extérieur.

> **Piège** : `Emails.php` force **tous** les destinataires vers
> `benallemand@gmail.com` dès que `MAIL_USERNAME` vaut cette adresse. En pointant
> sur Mailpit on utilise donc un autre expéditeur, pour voir les vrais
> destinataires. Ne pas remettre l'ancien couple Gmail sans y penser.
>
> `.env.docker` n'est pas versionné : le changement doit être fait **sur chaque
> machine**, y compris sur biggyben (voir `.env-template`).

### Tests PHP (dans le container dev)
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec php vendor/bin/phpunit unit_tests/
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec php vendor/bin/phpunit unit_tests/FinalsDrawTest.php
```

**En CI** (`.github/workflows/tests.yml`) : la suite tourne sur chaque PR contre
une base MySQL jetable, montée de zéro à partir de `.github/ci/` :

| Fichier | Rôle |
|---------|------|
| `schema.sql` | Schéma seul (`--no-data`), clauses `DEFINER` retirées |
| `seed.sql` | Jeu de référence **fictif** (compétitions, 3 clubs, 5 équipes, 60 joueurs, comptes) |
| `ci.env` | `.env` du job — valeurs bidon hors base, `MAIL_FUNCTION=mail` |
| `dump-schema.ps1` | Régénère `schema.sql` depuis une base de référence |

> **Le `schema.sql` doit être régénéré après chaque migration** appliquée à la
> base (`pwsh .github/ci/dump-schema.ps1`), sinon la CI teste une structure qui
> a dérivé de la prod. C'est le seul coût d'entretien de ce job.

Deux réglages MySQL sont appliqués à chaud par le workflow car les service
containers GitHub n'acceptent pas d'arguments serveur : `sql_mode` sans
`ONLY_FULL_GROUP_BY` (aligné sur dev/prod) et `log_bin_trust_function_creators`
(la fonction stockée `SPLIT_STRING` n'est pas déclarée `DETERMINISTIC`).

Contrairement à une exécution sur la base de dev, **les tests destructifs sont
sans danger en CI** puisque la base est vierge à chaque run.

### Tests E2E Playwright

**Boucle de développement** (recommandé) — le code est servi par le bind mount,
donc aucun `build` d'image entre deux itérations :

```bash
npm run e2e:up       # démarre la stack en mode test (APP_ENV=test)
npm run e2e:check    # préflight : refuse de lancer si la stack n'est pas prête
npm run e2e          # campagne complète  (~3 min 30, 54 tests)
npm run e2e:only -- tests/issue_268.spec.js   # une seule spec  (~25 s)
```

**Mode image** (ce que fait la CI, et le seul qui teste l'image telle qu'elle sera
déployée) — impose un `build` avant chaque run :

```bash
docker compose -f docker-compose.yml -f docker-compose.e2e.yml build php
docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm playwright
```

**Trois pièges qui coûtent cher :**

1. **Toujours passer le MÊME jeu de `-f` au `up` et au `run`.** Avec un jeu
   différent, Compose voit la définition de `php` changer et le recrée en cours de
   route : il perd `APP_ENV=test` et *tous* les helpers répondent 403, ce qui fait
   échouer une quinzaine de tests pour une raison qui n'a rien à voir.
2. **Ne rien lancer pendant une campagne.** Un `build`, un `up` ou une suite
   PHPUnit en parallèle recrée ou affame le conteneur `php` : la campagne meurt, ou
   `finals.spec.js` déborde son timeout de 10 s sur le spinner (la page enchaîne
   plusieurs `matchmgr/getMatches`, ~1,1 s pièce machine au repos).
3. **En mode image, penser au `build`.** Sinon on teste l'image précédente et on
   croit avoir validé un correctif qui n'y est pas.

Si un `up` dépasse la minute, la VM Docker Desktop est probablement coincée sur le
partage de fichiers Windows (le conteneur reste en `Created`) : redémarrer Docker
Desktop depuis la barre des tâches.

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
- `today_matches_setup.php` / `today_matches_teardown.php` — match programmé aujourd'hui + une nouvelle, pour l'encart "Matchs du jour" et les nouvelles repliables de la home (issue #230). Le setup recopie les FK d'un match déjà visible dans `matchs_view` (échantillonnage **depuis la vue**, pas la table `matches`, pour éviter les matchs orphelins).
- `admin_session.php` — ouvre une session administrateur, sans autre effet de bord (issue #265). À préférer à `messages_setup.php` pour les specs d'administration.
- `calendar_events_setup.php` / `calendar_events_teardown.php` — trois événements de calendrier dans la saison en cours, pour le calendrier de la home alimenté en base (issue #253). Les dates sont posées en novembre de l'année d'ouverture de saison, donc toujours dans les mois affichés ; le test déplie les mois passés pour ne pas dépendre du jour d'exécution.

### Installer les dépendances PHP
```bash
c:\php\php.exe composer.phar install
c:\php\php.exe composer.phar update
```

### Frontend bundlé (Vite)

Le frontend Vue 3 est bundlé par Vite. **Toutes les pages publiques sont des entrées HTML natives Vite** (plus aucune page servie par PHP — issue #228) : Vite bundle les `<script type="module">` / `<link>` qu'il y trouve et produit un `.html` hashé dans `dist/`, qu'un `.htaccess` rewrite expose à l'URL publique (ex. `/match.html` → `/dist/match.html`). Le PHP ne répond plus qu'à l'API REST (`/rest/`) et aux endpoints AJAX/JSON (`session_user.php`, `ajax/…`). Le contrôle d'accès des pages de gestion de match (live/match/survey/team_sheets) est fait côté client via `pages/components/auth/guard.js` (appels REST + redirection login).

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

**Entrées déclarées dans `vite.config.js`** (toutes des entrées HTML natives) :
- `src/css/app.css` — Tailwind + DaisyUI + libs tierces (FontAwesome, Notyf, Toastify)
- `live.html`, `match.html`, `survey.html`, `team_sheets.html` — pages de gestion de match (chacune charge son `.js` racine : `live.js`, etc.)
- `pages/home.html`, `pages/my_page.html`, `admin/matches.html` — pages publiques / dashboard responsable
- `admin/index.html` — administration Vue (issue #265), servie aussi sur /admin/

**Hors périmètre du bundle Vite** : `admin.php` seul — dernière page sur les CDN
ExtJS (interface d'administration historique, migration suivie par l'issue #265).
`register.php` (#249), `reset_password.php` et `rank_for_cup.php` (#266) ne sont plus
que des redirections vers les routes Vue correspondantes ; leurs URLs historiques
sont conservées parce qu'elles circulent en lien externe et en favori.

### Versionner et déployer

**Le tag est automatique.** À chaque merge sur `master`, `.github/workflows/auto-tag.yml`
rejoue la suite PHPUnit, calcule la version suivante depuis les conventional
commits et pose le tag + une Release GitHub contenant les notes de version.

Règles de bump (`.github/ci/next-release.sh`) — le semver strict ne bumpe que
sur `feat`/`fix`, ce qui laisserait les merges dependabot sans version :

| Commit | Bump |
|--------|------|
| `BREAKING CHANGE` en corps, ou type suivi de `!` | majeur |
| `feat` | mineur |
| tout le reste (`fix`, `chore(deps)`, `ci`…) | patch |

Prévisualiser sans rien pousser :
```bash
bash .github/ci/next-release.sh --dry-run
```

**Le déploiement reste manuel.** Le tag est poussé avec le `GITHUB_TOKEN`, et
GitHub ne redéclenche aucun workflow sur un push fait avec ce jeton : `main.yml`
ne part donc pas tout seul. Pour mettre en prod : onglet Actions → *CI/CD
Workflow* → **Run workflow** → choisir le tag comme ref.

> Deux conséquences de la protection de `master` (PR + 1 review) :
> `github-actions[bot]` ne peut pas y pousser de commit, d'où les notes de
> version en Release plutôt qu'un `CHANGELOG.md` versionné.
> Les 48 tags horodatés historiques (`YYYYMMDDHHMM`) cohabitent sans souci :
> le script ne considère que les tags commençant par `v`.

Poser un tag à la main reste possible :
```bash
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

### Pattern des classes métier
```php
class MaClasse extends Generic {
    // hérite de $this->sql_manager pour les requêtes
}
```

### Endpoints AJAX
Les fichiers dans `ajax/` sont des points d'entrée HTTP. Ils instancient les classes et retournent du JSON.

### Autorisation des endpoints REST — refus par défaut (issues #268, #270)

`rest/action.php` dispatche **n'importe quelle méthode publique** des classes
routées : plus de 400 points d'entrée, bien au-delà de ce que les frontends
appellent. `rest/access.php` liste donc les actions **autorisées** et leur niveau,
et tout ce qui n'y figure pas est refusé en 403 — qu'il existe ou non.

| Niveau | Règle |
|--------|-------|
| `public` | aucune connexion requise |
| `user` | connexion requise ; les contrôles fins (responsable d'équipe, de club, propriété de l'objet) restent dans les méthodes, qui les font déjà |
| `admin` | réservé aux administrateurs |

Principe : les lectures sont publiques, sauf celles qui exposent des données
personnelles ou scopées à la session (`getMy*`, activité, emails d'équipe) ; les
écritures exigent une connexion ; les actions d'administration exigent le rôle.

> **Pourquoi le refus par défaut.** La première version (#268) listait les actions
> d'administration, déduites de ce que les frontends appellent. Elle laissait donc
> ouvertes les ~99 méthodes publiques qu'aucun frontend n'appelle — dont
> `sqlmanager/execute`, qui exécutait du **SQL arbitraire sans authentification**.
> Une liste bâtie sur l'usage observé ne protège pas d'une surface non observée.

**Pour ajouter un endpoint** : l'inscrire dans `rest/access.php`, sinon il répondra
403. Et vérifier qu'il n'est pas appelé via une URL construite dynamiquement —
`pages/components/panel/Players.js` fait `` `/rest/action.php/player/${action}` ``,
ce qu'un grep sur les littéraux ne voit pas.

Listes d'ids reçues du client : passer par `Generic::parse_id_list()` puis lier les
valeurs. Ne jamais concaténer (suivi dans #270).

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

## Frontend admin

Deux interfaces cohabitent le temps de la migration (issue #265).

### Vue 3 — la cible (`admin/index.html`)

Entrée Vite, routeur à hash, garde `requireRoles(['admin'])`. Le socle vit dans
`admin/components/` :

| | |
|---|---|
| `layout/AdminLayout.js` | shell, routeur, garde ; `MENU` liste les écrans migrés |
| `layout/AdminSidebar.js` | navigation repliable (utilisable sur mobile) |
| `grid/AdminGrid.js` | grille générique : recherche multi-termes, tri, pagination, sélection, suppression en masse, export CSV |
| `grid/AdminEditModal.js` | formulaire modal générique |
| `screens/` | un composant par écran |

**Ajouter un écran** revient à déclarer ses colonnes, ses champs et ses URLs, puis
à l'inscrire dans `routes` et `MENU` (`AdminLayout.js` — `MENU` est une liste de
groupes `{label, items}` depuis le lot 3, la barre latérale les rend en sections).
Voir `screens/Gymnasiums.js`, qui remplace ~180 lignes d'ExtJS par une trentaine.
Les actions hors CRUD (réinitialiser un mot de passe, nommer un responsable…)
passent par le slot `actions` de la grille, les filtres par le slot `filters` et
la prop `rowFilter`.

> **L'identifiant est toujours envoyé au save**, vide à la création : plusieurs
> méthodes PHP le déclarent en paramètre **obligatoire** (`Club::saveClub($id, …)`),
> et c'est ce que faisait le champ caché `id` des formulaires ExtJS.

> **Un paramètre PHP obligatoire que le formulaire n'envoie pas fait échouer le
> save en 500.** Le routeur appelle la méthode avec des arguments nommés : un
> `$id_journee` déclaré sans valeur par défaut et absent du formulaire lève une
> `ArgumentCountError`. Vérifier la signature PHP en écrivant l'écran, et ne
> déclarer que des champs qui existent en base — `heure_reception` vient d'une
> jointure de `matchs_view`, l'écrire aurait produit un « Unknown column ».

> **Champ date** : `type: 'date'` rend le sélecteur natif du navigateur, mais
> l'API parle en `jj/mm/aaaa` (`STR_TO_DATE(?, '%d/%m/%Y')`) et l'`<input
> type="date">` en `aaaa-mm-jj`. `AdminEditModal` convertit dans les deux sens,
> les écrans n'ont rien à faire. Exception : une colonne stockée en **texte**
> libre (`dates_limite.date_limite`) reste un champ texte avec un `placeholder`.

**Validation** : la recette manuelle vit dans `admin/RECETTE.md` — cas transverses,
cas génériques de la grille, et cas par écran. Les écrans d'administration sont
volontairement peu couverts en Playwright : `player/getPlayers` renvoie 2,5 Mo en
~8 s, et une campagne complète passait de 20 s à plus de 10 minutes. Seuls la
garde d'accès et le comportement de la grille sont automatisés.

Conventions backend reprises telles quelles : lecture en GET, écriture en POST
avec `id` vide pour un INSERT, suppression en POST avec `ids` joints par des
virgules. Chaque endpoint doit être déclaré `admin` dans `rest/access.php`.

### Sencha/ExtJS — l'historique (`admin.php`)

- Fichiers dans `js/` (controllers, models, views, stores)
- **Point d'entrée unique : `admin.php` et `js/administration.js`** — depuis #266,
  plus aucune page publique ne charge ExtJS. Supprimer `admin.php` suffira donc à
  sortir ExtJS du projet, une fois tous les écrans migrés.

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

### Rôles utilisateurs (issue #245)
- Les rôles sont **dérivés et cumulables** — pas de table de profils :
  admin → `comptes_acces.is_admin` ; responsable d'équipe → ligne `users_teams` ;
  responsable de club → ligne `users_clubs`
- Flags posés en session au login : `is_admin`, `is_team_leader`, `is_club_leader`
  (+ `id_equipe`, `id_club`) — prédicats `UserManager::isAdmin()/isTeamLeader()/isClubLeader()`
- Côté frontend : `session_user.php` / `getCurrentUserDetails` exposent ces flags ;
  garde des pages match via `requireRoles(['admin', 'team_leader'])` (`pages/components/auth/guard.js`)
- « Agir en tant que » : sauvegarde/restauration des flags via `original_admin_*` en session

### Multi-club et équipe courante
- Un compte peut être rattaché à **plusieurs clubs** : la session porte
  `club_ids` (tous les clubs, `users_clubs`) **et** `id_club` (le club **courant**,
  le premier par ordre alphabétique au login). `UserManager::switchCurrentUserClub()`
  fait varier le club courant, `Club::getMyClubs()` alimente le sélecteur.
- `Club::getMyClubId()` reste le point unique de cadrage des écrans club
  (inscriptions, fermetures gymnases, indispos équipes, comptes responsables,
  matchs du club) : ils suivent tous le club courant. `Club::getMyClubIds()` sert
  aux autorisations (tous clubs) et `Club::assertManagesTeam()` accepte une équipe
  de n'importe lequel des clubs gérés.
- `id_equipe` / `is_team_leader` reflètent l'équipe **courante** :
  `UserManager::switchCurrentUserTeam()` accepte les équipes du compte
  (`users_teams`) **et** toute équipe d'un club géré, y compris **sans compte
  responsable rattaché** ; `getMyManageableTeams()` alimente le sélecteur d'équipe.
  Sélectionner une équipe d'un autre club bascule aussi le club courant.
- « Agir en tant que » un responsable d'équipe du club
  (`switch_to_club_team_leader`) reste disponible depuis l'écran « comptes
  responsables », mais n'est plus le chemin normal pour gérer une équipe.

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
