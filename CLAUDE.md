# CLAUDE.md — ufolep13volley

Application web de gestion des championnats de volleyball UFOLEP 13.

## Stack Technique

- **Backend** : PHP 8.3, MySQL
- **Frontend client** : Vue.js 3, Tailwind CSS, DaisyUI — bundlé via Vite (Node.js 20)
- **Frontend admin** : Vue.js 3 (`admin/index.html`, servie sur `/admin/`) — ExtJS supprimé par le lot 6 de l'issue #265
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
admin/            # Interface admin Vue.js (issue #265)
  index.html      # Entrée Vite de l'administration
  components/
    layout/       # Shell, sidebar, routeur, garde admin
    grid/         # Grille et formulaire modal génériques
    screens/      # Un composant par écran
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
- `calendar_events_setup.php` / `calendar_events_teardown.php` — trois événements de calendrier dans la saison en cours, pour le calendrier de la home alimenté en base (issue #253). Les dates sont posées en novembre de l'année d'ouverture de saison, donc toujours dans les dix mois affichés. Depuis #290 la spec cible la timeline : un ponctuel s'assertionne sur l'attribut `title` de son losange, plus sur du texte.

### Campagne E2E nocturne (issue #316)

`.github/workflows/e2e-nightly.yml` rejoue la campagne toutes les nuits sur
`master`, contre le **même jeu fictif que PHPUnit** (`.github/ci/schema.sql` +
`seed.sql`) et non contre la base de dev. Toute la stack tient dans
`docker-compose.e2e-ci.yml`, reproductible en local :

```bash
docker compose -p ufolep-e2e-ci -f docker-compose.e2e-ci.yml build php
docker compose -p ufolep-e2e-ci -f docker-compose.e2e-ci.yml up -d
docker compose -p ufolep-e2e-ci -f docker-compose.e2e-ci.yml --profile test run --rm playwright
docker compose -p ufolep-e2e-ci -f docker-compose.e2e-ci.yml down -v
```

> **Le `-p` n'est pas décoratif** : sans lui Compose prend le nom du répertoire,
> donc le même projet que le mode dev ou home server, et recrée leur service
> `php` avec cette définition-ci. Aucun port n'est publié ici : les deux stacks
> cohabitent.
>
> **Le `down -v` non plus** : MySQL ne rejoue ses scripts d'initialisation que
> sur un répertoire de données vide. Sans lui, une modification de `seed.sql`
> reste sans effet et on teste l'ancien jeu.

> **Le jeu fictif ne peuple que les trois championnats.** Les compétitions de
> coupe existent mais n'ont ni équipe ni classement, donc ni tirage de phases
> finales ni poules. Les neuf tests qui en dépendent (`finals.spec.js`,
> `issue_266.spec.js`, `issue_218.spec.js`) **se sautent d'eux-mêmes** — sur un
> signal lu dans la donnée (`finals_setup.php` ne rend aucun match,
> `rank/sort_cup_rank` répond vide, `rank/getDivisions` n'a aucune poule de
> coupe), jamais sur un « si CI ». Ils se rallument seuls le jour où le seed
> portera les coupes ; contre la base de dev, ils tournent tous.

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

**Plus aucune page hors du bundle Vite.** `admin.php` (#265 lot 6),
`register.php` (#249), `reset_password.php` et `rank_for_cup.php` (#266) ne sont
plus que des redirections vers les routes Vue correspondantes ; leurs URLs
historiques sont conservées parce qu'elles circulent en lien externe et en
favori. Il ne reste **aucune dépendance à un CDN tiers** en production.

### Mesure d'audience (Matomo auto-hébergé)

Le traceur est **injecté au build** par le plugin `matomoTracking()` de
`vite.config.js`, pas recopié dans les pages : les entrées HTML n'ont aucun
partiel commun, et six copies d'un snippet dériveraient à la première
modification. Les constantes (`MATOMO_URL`, `MATOMO_SITE_ID`, `MATOMO_HOSTS`)
sont en tête de fichier.

- **L'administration n'est pas mesurée** : les pages sous `admin/` sont exclues
  par le plugin. L'usage interne n'a rien à faire dans l'audience publique.
- **Rien n'est envoyé hors production** : le dev local et biggyben servent le
  même bundle, un test sur `location.hostname` les écarte. Pour vérifier qu'une
  page est bien instrumentée, `grep stats.ufolep13volley.org dist/**/*.html`.
- **Le suivi est servi depuis `stat.js` / `stat.php`**, copies de `matomo.js` /
  `matomo.php` faites côté serveur. Les noms d'origine sont dans toutes les
  listes de filtrage et coûtent 20 à 30 % des mesures. Ce sont des **copies**,
  pas des renommages — Matomo continue d'utiliser ses propres fichiers, et ses
  mises à jour ne cassent pas. **À recopier après chaque montée de version de
  Matomo**, sinon le traceur servi se fige.

Matomo vit hors du dépôt, dans `~/stats` sur l'hébergement OVH — pas dans
`~/www`, qui est la copie de travail git : un `git clean` l'effacerait. Son
archivage est un cron horaire (`~/stats/cron-archive.sh`), l'archivage à la
volée depuis le navigateur devant rester désactivé sur mutualisé.

`disableCookies` côté page, plus l'anonymisation d'IP côté Matomo, placent la
mesure dans les conditions d'exemption de consentement de la CNIL : **pas de
bandeau cookies** à afficher.

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

**La version PHP de la prod est dans le dépôt** (`.ovhconfig` à la racine, donc
à la racine web puisque `main.yml` fait un `git pull` dans `www/`) :

```
http.firewall=none
container.image=stable64
environment=production
app.engine=php
app.engine.version=8.3
```

Sans ce fichier, OVH applique la « version PHP globale » de l'hébergement, un
réglage qui vit dans le manager et peut bouger sans qu'on le voie passer.
C'est lui qui fait tourner `www/` en 8.3 alors que le réglage global du compte
est resté en 8.1 — le prompt SSH affiche la version effective du répertoire
courant, ce qui permet de vérifier d'un coup d'œil quelle version s'applique.

**Le contenu ci-dessus est celui qui existait déjà sur le serveur**, recopié à
l'identique : le versionner ne devait rien changer au comportement de la prod.
`http.firewall=none` désactive le pare-feu applicatif HTTP d'OVH — pas un
défaut, un choix, qu'on peut désormais discuter en PR au lieu de le découvrir
en SSH.

> Ce fichier n'a d'effet qu'**au déploiement suivant**, qui est manuel (ci-dessus).
>
> Il a longtemps vécu sur le serveur sans être versionné, d'où le garde-fou de
> `main.yml` qui le neutralise avant le `git pull` (voir le commentaire de
> l'étape *Deploy source code*).

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

### File d'emails : cron horaire ou envoi immédiat (issue #305)

Tout email passe d'abord par `Emails::insert_email()`, qui pose une ligne en
`sending_status = 'TO_DO'` dans la table `emails`. Deux sorties ensuite :

| Sortie | Méthode | Pour quoi |
|---|---|---|
| Cron horaire | `cron/hourly.php` → `send_pending_emails()` | envois groupés : récap d'activité, matchs non saisis, prochains matchs, licences manquantes, rappels de signature, inscriptions non payées |
| Immédiat | `send_email_now($id)` | emails qui répondent à une action que l'utilisateur vient de faire : identifiants de connexion, réinitialisation de mot de passe, prise en compte d'inscription, workflow de report de match, modification de date, fiches et feuilles à signer |

> **Pour un envoi immédiat, appeler `send_email_now($id)`, jamais
> `send_pending_emails()`.** La seconde vide **toute** la file (jusqu'à 50 mails,
> dont les groupés qu'on voulait justement laisser au cron) de façon synchrone
> dans la requête HTTP, et écrit sur la sortie standard en cas d'échec, ce qui
> pollue le JSON des réponses AJAX.

`send_email_now()` ne remonte jamais d'exception : une création de compte ne doit
pas échouer parce que le SMTP est indisponible. La ligne reste alors en `ERROR`,
que l'admin rejoue depuis l'écran Emails (« Relancer les emails en erreur »).

Un appel dans une **boucle** sur N enregistrements garde la mise en file : c'est
le sens du paramètre `$send_now = false` de `sendMailNewUser()` et de
`UserManager::create_or_update_leader_account()`, passé par
`Register::set_up_season()` et `Register::create_teams_and_accounts()`. N envois
SMTP synchrones dépasseraient `max_execution_time` sur le mutualisé OVH.

### Cookie de session et corps d'emails (issue #292)

`bootstrap.php` pose `HttpOnly`, `Secure` et `SameSite` sur le cookie de session.
**Tout nouveau point d'entrée PHP susceptible d'ouvrir une session doit
l'inclure en première ligne** — avant le premier `session_start()`, sinon le
cookie est déjà parti. `SessionCookieTest` fait échouer la suite sinon.

> **Pourquoi en PHP et pas en configuration.** Le `php.ini` de la prod est géré
> par OVH, et les deux contournements ne couvrent chacun qu'un SAPI :
> `.user.ini` n'est lu qu'en PHP-FPM/CGI, `.htaccess php_flag` qu'en mod_php (et
> provoque une 500 en FPM). Notre image est `php:8.3-apache` (mod_php), OVH est
> en FPM : on validerait sur biggyben autre chose que la prod.

> **`Secure` ne se déduit pas de `$_SERVER['HTTPS']`.** Caddy fait
> `reverse_proxy php:80` : PHP reçoit du HTTP en clair et `HTTPS` vaut `NULL`
> même quand le visiteur est en HTTPS. `ufolep_is_https()` regarde aussi
> `X-Forwarded-Proto` et le port. Forcer `secure = true` casserait les sessions
> en dev sur `http://localhost`.

> **Un corps d'email ne se rend jamais en `v-html`.** Il est construit par
> `str_replace` à partir de noms d'équipe et de joueur saisis par des
> responsables : c'était un XSS stocké. Les deux écrans qui l'affichent
> (`TeamLeaderMessages.js`, `admin/screens/Emails.js`) passent par un
> `<iframe sandbox="">`.

> **Les valeurs substituées dans un gabarit d'email sont échappées** par
> `Emails::escapeHtml()`. Exception : `escapeHtmlList()` pour `teams_list`, dont
> le `GROUP_CONCAT(... SEPARATOR '<br/>')` porte du HTML **voulu** — l'échapper
> en bloc afficherait « &lt;br/&gt; » dans le mail.

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

Les gardes client (`pages/components/auth/guard.js`) ne protègent rien côté API :
chaque méthode `user` qui prend un `id_match` doit vérifier elle-même que le match
concerne l'équipe de la session (`isUserTeamInMatch`, admin exempté). Le sondage
(`get_survey`/`save_survey`) et la recherche de renforts ne le faisaient pas, et
`get_survey` sans `id_match`, public, livrait tous les sondages (#351).

Même règle pour une équipe ou un joueur désignés par le client (`id_team`,
`id_equipe`, `id`) : `UserManager::assertCanManageTeam()` (admin, équipe du
compte ou de la session, équipe d'un club géré) et `assertCanManagePlayer()`
(joueur d'une de ces équipes ou de leurs clubs). Sans eux, un responsable
modifiait n'importe quel joueur — puis l'ajoutait à son équipe —, les rôles et
créneaux d'une autre équipe, et `save_match` sans `id_match` créait un match (#356).

> **Pourquoi le refus par défaut.** La première version (#268) listait les actions
> d'administration, déduites de ce que les frontends appellent. Elle laissait donc
> ouvertes les ~99 méthodes publiques qu'aucun frontend n'appelle — dont
> `sqlmanager/execute`, qui exécutait du **SQL arbitraire sans authentification**.
> Une liste bâtie sur l'usage observé ne protège pas d'une surface non observée.

**Pour ajouter un endpoint** : l'inscrire dans `rest/access.php`, sinon il répondra
403. Et vérifier qu'il n'est pas appelé via une URL construite dynamiquement —
`pages/components/panel/Players.js` fait `` `/rest/action.php/player/${action}` ``,
ce qu'un grep sur les littéraux ne voit pas. Même chose pour
`utils/reportUtils.js` (`matchmgr/${actionName}`) : le répertoire `utils/`
n'était pas parcouru par `AdminRestAuthzTest`, et le workflow de report a
répondu 403 aux responsables sans qu'aucun test le voie. Ses quatre actions
passent par `MatchMgr::assert_report_action_allowed()` : équipe de la session
dans le match, demande de l'adversaire pour accepter/refuser, date donnée par
l'équipe qui a accepté, rien une fois le score saisi ; la commission peut
seulement refuser.

Listes d'ids reçues du client : passer par `Generic::parse_id_list()` puis lier les
valeurs. Ne jamais concaténer (suivi dans #270).

**Arguments SQL bruts** : le routeur passe chaque paramètre de la requête en
argument nommé, donc `Generic::get($query)` et consorts (`getCompetitions`,
`getTeams`, `get_emails($where)`…) devenaient une clause WHERE libre —
sans connexion sur `club/get` (#354). `rest/raw_sql_guard.php` refuse (400) tout
paramètre nommé `query`, `where`, `bindings`, `order`, `order_by` ou `sql` qui
correspond à un argument de la méthode, et toute clé numérique (argument
positionnel). Une méthode où `query` est un **terme de recherche** lié ou échappé
se déclare dans `SEARCH_TERM_PARAMETERS`. `RawSqlGuardTest` parcourt toutes les
actions de `rest/access.php`.

**Helpers internes** (`getTeamName`, `getIdClubFromIdTeam`, `getPlayersIdClub`,
`getUserLogin`…) : ils lient leurs paramètres **eux-mêmes**, via
`Generic::parse_id()` — un appelant qui leur passe une valeur du client ne doit
pas avoir à y penser (#355). `parse_id` refuse `12 OR 1=1`, qu'un `(int)` seul
ramènerait silencieusement à 12.

**Erreurs renvoyées au client** : jamais de message MySQL brut. Sans
`mysqli_report()` explicite, PHP 8 lève `mysqli_sql_exception` (les `=== FALSE`
de `SqlManager` ne servent donc pas) ; `rest/error_response.php` la traduit :
doublon (1062) et intégrité (1451/1452) en 409 reformulés, le reste en 500
générique avec une référence retrouvable dans le log serveur. Un code
d'exception hors 200-599 (un errno) devient 500.

**Chemins de fichiers reçus du client** : jamais de `readfile`/`file_get_contents`
direct. `photo/get_photo`, public, servait tout fichier du site — `.env` et code
source compris (#352). Résoudre par `realpath`, exiger un répertoire autorisé et une
extension attendue : voir `Photo::resolve_servable_path()`.

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

**Recette** : `pages/RECETTE.md` (pendant de `admin/RECETTE.md`, qui ne couvre que
l'administration).

### Calendriers (issue #290)

Deux composants, deux formes de données — ne pas les confondre :

| Composant | Pour quoi | Techno |
|---|---|---|
| `calendar/SeasonTimeline.js` | agenda de la commission | maison, aucune dépendance |
| `calendar/MatchCalendar.js` | matchs du responsable | FullCalendar 6 (plugins MIT) |

`calendar/calendarData.js` normalise les deux sources vers une forme commune
(`startDate`/`endDate` en ISO, `time` en `hh:mm`) et porte `currentSeason()`,
**qui doit rester alignée sur `CalendarEvents::getCurrentSeason()`** — de janvier
à juin, la saison est celle ouverte en septembre précédent.

> **Timeline pour l'agenda, calendrier pour les matchs.** L'agenda est fait de
> périodes longues : regroupées par libellé, elles donnent le rythme de la
> saison (« Championnats » ×3 sur une ligne). Les matchs sont nombreux et
> ponctuels : sur une timeline ils s'empilent en un amas illisible. L'inverse
> est vrai aussi — une période de sept semaines remplit chaque case d'une
> grille mensuelle de « +2 en plus ». Le comparatif est dans #290.

> **Ne pas ajouter `@fullcalendar/resource-timeline`** : ce plugin est
> **Premium**, pas MIT (tri-licencié commercial / CC non-commercial / GPLv3), et
> `composer.json` déclare le dépôt `proprietary`. Écarté pour cette raison, tout
> comme `vis-timeline`, qui traîne moment.js et sept dépendances de pairs.

> **Chacun ne montre que ce qui le concerne** : l'agenda n'est pas superposé au
> calendrier des matchs — il est déjà affiché juste en dessous par la timeline.
> Si l'on devait un jour y remettre des périodes, se souvenir que FullCalendar
> attend une **fin exclusive** (une période du 3 au 10 se déclare jusqu'au 11)
> et qu'une période longue doit passer en `display: 'background'`, sinon chaque
> case se remplit de « +2 en plus ».

> **Week-ends** : les matchs se jouent tous du lundi au vendredi, donc les
> masquer gagne deux colonnes. Mais **jamais en dur** — c'est ce que faisait
> `AnnualCalendar.js` : un rendez-vous posé un samedi devenait invisible. Le
> masquage est conditionné à l'absence d'événement d'un seul jour un week-end
> (les périodes ne comptent pas, elles restent lisibles en semaine).

> **`heure_reception` est nullable** : sans heure, le match est une « journée
> entière ». Le placer à 00:00 afficherait un horaire faux.

> **`23:59` signifie « fin de journée »** dans `calendar_events`, comme minuit
> déjà neutralisé côté SQL. À traiter en journée entière, pas en rendez-vous.

> **`getMyClubMatches` renvoie aussi les matchs de l'équipe** du responsable :
> `mergeMatchSources()` dédoublonne sur `id_match`, sinon chaque match de
> l'équipe apparaît deux fois, dans deux couleurs.

> **« Aujourd'hui » peut être hors saison** (juillet, août) : une vue mois
> ouverte sur la date du jour tomberait sur un mois vide. Se replier sur
> septembre.

> **FullCalendar est chargé en `defineAsyncComponent`** : 210 Ko (64 Ko gzip)
> confinés au chunk `MatchCalendar`. La home ne charge que la timeline (5 Ko).
> Rester en **6.x** : les plugins de vues de la v7 ne sont qu'en RC.

## Frontend admin

### Vue 3 (`admin/index.html`, servie sur `/admin/`)

Entrée Vite, routeur à hash, garde `requireRoles(['admin'])`. **La page d'accueil
est le tableau de bord des indicateurs** (issue #313) : on atterrissait jusque-là
sur la grille des utilisateurs, par défaut de repli et non par choix. Le socle vit
dans `admin/components/` :

| | |
|---|---|
| `layout/AdminLayout.js` | shell, routeur, garde ; `MENU` liste les écrans migrés |
| `layout/AdminSidebar.js` | navigation repliable (utilisable sur mobile) |
| `grid/AdminGrid.js` | grille générique : recherche multi-termes, tri, pagination, sélection, suppression en masse, export CSV |
| `grid/AdminDetailDrawer.js` | tiroir de détail ouvert au clic sur une ligne (issue #308) |
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
> `ArgumentCountError`. Ne déclarer que des champs qui existent en base —
> `heure_reception` vient d'une jointure de `matchs_view`, l'écrire aurait
> produit un « Unknown column ».
>
> **Un champ posté que la méthode ne déclare PAS échoue aussi**, et c'est
> l'autre moitié du piège : `Error : Unknown named parameter $x`, donc 500
> également. Le routeur en arguments nommés échoue donc dans les deux sens.
>
> C'est ce second mode qui a mis **trois écrans en 500** (#299) : `AdminGrid`
> ne transmettait pas son `id-field` à `AdminEditModal`, qui postait donc `id`
> là où la méthode attend `id_date`, `id_match` ou `id_equipe`. **Toute prop de
> la grille qui décrit ce que le formulaire envoie doit lui être transmise.**

> **Une méthode variadique ne plante pas — elle fait pire.**
> `Generic::save_with_args(...$args)` collecte tout argument nommé inconnu :
> aucune erreur, mais `Generic::save()` décide insertion ou mise à jour sur
> `$inputs[$this->id_name]`. Un identifiant mal nommé y produit donc un
> **doublon silencieux** à chaque édition, pas un 500.

> **`AdminScreensTest` vérifie ça mécaniquement** (issues #288, #299) : il lit
> les écrans, en extrait les champs postés et les compare aux signatures PHP par
> réflexion, **dans les deux sens**. Il vérifie en plus le câblage
> `AdminGrid` → `AdminEditModal`, parce que la comparaison écran/PHP modélise
> l'intention et non ce qui part réellement — elle est restée verte pendant que
> trois écrans étaient en 500. Le défaut est passé quatre fois avant d'être
> outillé : `saveMatch`, `savePlayer`, les cases à cocher, puis l'identifiant.
> Donner une valeur par défaut à **tous** les paramètres d'une méthode de save
> reste la règle.
>
> Les champs `type: 'file'` sont exclus de ces contrôles : `FormData` les range
> dans `$_FILES`, ils ne deviennent jamais des arguments nommés.

> **Champ fichier** : `type: 'file'` envoie l'objet `File` dans le même
> `FormData` que le reste, donc en multipart, et remplit `$_FILES` côté PHP.
> `Players::save()` appelle `savePhoto()` en fin de course : la photo part avec
> le formulaire, sans second appel. Rien de choisi = clé absente, pour ne pas
> écraser l'existant.

> **Tiroir de détail** (issue #308) : la prop `detail` de `AdminGrid` ouvre
> `grid/AdminDetailDrawer.js` au clic sur une ligne. `:detail="true"` suffit — les
> sections sont alors déduites des colonnes ; un objet
> `{ title, subtitle?, badge?, image?, sections }` permet de montrer les champs que
> la grille n'affiche pas (voir `screens/Players.js`).
>
> **Le clic sur la ligne change de sens là où un tiroir est déclaré** : il l'ouvre
> au lieu de cocher la case, et la sélection passe alors par la case elle-même —
> sinon consulter une ligne l'aurait sélectionnée, et la barre d'outils aurait agi
> sur des lignes qu'on n'a fait que regarder. Les écrans sans `detail` gardent le
> comportement historique.
>
> Le tiroir est ancré sur la **zone de tableau**, pas sur l'écran entier : ancré
> plus haut, il recouvrirait `Export`, le bouton de rafraîchissement et les actions
> de l'écran, qui sont alignés à droite. Il flotte par-dessus la table plutôt que de
> la pousser — rétrécir une grille de onze colonnes la ferait défiler
> horizontalement. Sous `lg`, il devient une feuille ancrée en bas.
>
> Il se **referme quand la ligne sort du filtre**, comme la sélection se vide : un
> tiroir ouvert sur une ligne invisible est un mensonge.

> **Indicateurs actionnables** (issue #312) : un indicateur qui déclare un écran
> cible et une colonne d'identifiant gagne un bouton « Corriger ces N ligne(s) »
> dans son détail, qui ouvre l'écran **filtré sur ces seules lignes**.
>
> ```php
> new Indicator("Joueurs en attente de validation", $sql, 'alert',
>               'players', 'indicator_id');
> ```
>
> La requête doit sélectionner l'identifiant sous le nom convenu ;
> `Indicator::getResult()` l'extrait dans `ids`, **le dédoublonne** (une jointure
> sur `joueur_equipe` ramène le même joueur autant de fois qu'il a d'équipes) et
> le **retire du détail affiché** — une colonne d'identifiants bruts n'apprend
> rien dans le tableau.
>
> Côté grille, `AdminGrid` lit `?ids=` dans la route : **les 29 écrans en
> profitent sans rien déclarer**, l'identifiant comparé étant celui de `id-field`.
> Le filtre vit dans l'URL, donc il survit à un rechargement et se partage. Un
> bandeau l'annonce, avec un bouton pour tout revoir.
>
> **Toute alerte a son « Corriger » (#409)**, et `IndicatorCategoriesTest`
> le vérifie : écran cible routé dans l'administration, et requête qui
> sélectionne `indicator_id`. Matchs (`matches`) pour les alertes de saison,
> Inscriptions (`registrations`) pour celles d'inscription, Équipes, Joueurs,
> Clubs, Emails pour les autres. Les indicateurs d'information restent de
> simples constats.
>
> Une ligne peut désigner **plusieurs** lignes à corriger : `indicator_id` en
> liste séparée par des virgules (`GROUP_CONCAT`), découpée par
> `Indicator::getResult()`. C'est le cas des deux matchs d'une même réception,
> ou des demandes d'un club.
>
> **Ouverte avec `?ids=`, une grille ignore le filtre propre à l'écran**
> (`row-filter` : préréglage des matchs, statut des inscriptions). Sinon une
> alerte sur des matchs archivés ouvrirait un écran vide.

> **Fenêtre de sélection** : `grid/AdminPickerModal.js` couvre les actions
> « choisir dans une liste puis confirmer » — associer des joueurs à un club ou
> à une équipe, nommer un responsable, rattacher un compte à des équipes. En
> mode `multiple`, **passer les valeurs déjà liées dans `selected`** : sans
> elles, enregistrer détache tout.

> **Filtrer vide la sélection** : sinon un bouton d'action s'applique à une
> ligne devenue invisible. La pagination, elle, la conserve — la suppression en
> masse sur plusieurs pages est un usage légitime.

> **Colonne image** : `image: true` rend une vignette ronde chargée en différé,
> à partir du chemin contenu dans la colonne. `alt: (row) => …` fournit le texte
> alternatif. Troisième forme de cellule, aux côtés de `links` et `badge`
> (issue #295).

> **Tri des dates** : le comparateur reconnaît `jj/mm/aaaa[ hh:mm[:ss]]` et trie
> **chronologiquement**. Aucune colonne à annoter : la détection porte sur les
> valeurs, et ne s'applique que si les **deux** valeurs comparées sont des dates
> françaises. Avant l'issue #296, ces colonnes se triaient comme du texte —
> `02/12/2025` avant `15/11/2025` — sur 18 colonnes réparties dans 13 écrans.
> Les colonnes déjà en ISO se trient correctement en texte et ne passent pas par
> là.

> **`path_photo_low` n'existe pas toujours.** La vignette est **déduite** du
> chemin plein par un `REPLACE` dans `players_view` ; seules les photos
> téléversées depuis l'application passent par `generateLowPhoto()`, les autres
> n'ont pas de vignette sur le disque. `adjust_photo_path_from_results()` se
> rabat donc sur la photo pleine. Le défaut est ancien mais est resté invisible
> jusqu'à ce que la grille des joueurs affiche la vignette (#295) : la console
> s'est alors emplie de 404. Le surcoût du repli est négligeable — ces photos de
> licence pèsent 12 Ko en moyenne, 24 Ko au maximum.

> **Coût des photos de joueurs** : `players_view` renvoie déjà `path_photo` et
> `path_photo_low` ; les afficher ne coûte **rien** en données transférées.
> C'est la **vérification d'existence** côté PHP qui coûtait cher —
> `Players::adjust_photo_path_from_results()` faisait un `file_exists()` par
> joueur, soit 3 651 accès disque pour un appel à `getPlayers` (3,98 s sur 4,46,
> la requête SQL n'en prenant que 0,87). Elle indexe désormais chaque répertoire
> une fois. **Ne pas revenir à un accès par ligne** : `PlayerPhotoPathTest` le
> vérifie sur la source, car un tel retour ne casserait aucun test de
> comportement.

> **Champ date** : `type: 'date'` rend le sélecteur natif du navigateur, mais
> l'API parle en `jj/mm/aaaa` (`STR_TO_DATE(?, '%d/%m/%Y')`) et l'`<input
> type="date">` en `aaaa-mm-jj`. `AdminEditModal` convertit dans les deux sens,
> les écrans n'ont rien à faire. Deux variantes : `dateFormat: 'iso'` pour une
> colonne déjà en ISO (`news.news_date`), et `type: 'datetime'` pour un
> `aaaa-mm-jj hh:mm:ss` (`calendar_events`). Exception : une colonne stockée en
> **texte** libre (`dates_limite.date_limite`) reste un champ texte avec un
> `placeholder`.

> **Case à cocher** : le formulaire poste `1` ou `0`, et `Generic::to_flag()`
> les normalise côté PHP. Ne **pas** réintroduire de comparaison stricte du
> genre `$value === 'on' || $value === 1` : c'est ce qui faisait qu'une case
> cochée dans l'admin Vue était systématiquement enregistrée à 0 — l'ancien
> formulaire ExtJS postait `on`, le nouveau poste `1` (#265, lot 4).

> **Champ caché** : `hidden: true` ne rend rien mais reprend la valeur du record
> et l'envoie. Indispensable pour les endpoints qui déclarent des paramètres
> obligatoires que l'écran ne montre pas — `Register::register()` en compte
> quinze.

> **Suppression unitaire** : `delete-mode="id"` sur la grille quand l'endpoint
> ne prend qu'un identifiant (`News::deleteNews($id)`) au lieu d'une liste
> `ids`. La grille enchaîne alors un appel par ligne sélectionnée.

> **Écran volumineux** : le routeur accepte `_start`/`_end` et découpe côté
> serveur. `emails/get` renvoie 11 Mo sans borne (6 000 lignes portant chacune
> un corps HTML) et `activity/getActivity` 2,5 Mo (9 700 lignes) : ces écrans
> demandent `?_start=0&_end=499` et proposent d'élargir. La grille recharge
> quand `fetchUrl` change. Ne marche que si la requête est déjà triée du plus
> récent au plus ancien — le découpage est fait **après** l'`ORDER BY`.

> **Tout n'est pas une grille.** `screens/Indicators.js` est un tableau de bord
> en tuiles : `ajax/indicators.php?mode=list` rend les 44 libellés et les
> sections, puis un `mode=detail&id=N` par indicateur exécute sa requête, six en
> vol. Une tuile à zéro n'est pas affichée. C'est le modèle à suivre pour un
> écran qui n'est pas du CRUD : un composant à part, pas une contorsion de
> `AdminGrid`.
>
> **Chaque indicateur déclare sa section** (issue #396), en argument nommé :
> `new Indicator(..., category: Indicator::CALENDAR)`. `Indicator::CATEGORIES`
> en donne l'ordre et les libellés, du calendrier de la saison : Inscriptions,
> Préparation du calendrier, Équipes et clubs, Joueurs, Saison en cours,
> Statistiques. Une section inconnue lève une exception, et
> `IndicatorCategoriesTest` exige une section explicite pour chaque indicateur :
> la valeur par défaut rangerait en silence une alerte dans les statistiques.
> Seuls `mode=list` et `mode=detail` existent ; l'ancien export CSV de
> « Evènements » et le calcul de tout d'un coup n'avaient plus d'appelant.
>
> Le détail d'une tuile se trie, se filtre par colonne et se cherche (issue #340),
> avec les conventions des grilles : recherche multi-termes séparés par des
> virgules (un terme suffit), filtres de colonne cumulés, export CSV de **ce qui
> est affiché**. La comparaison des cellules — nombres, dates françaises, texte —
> vit dans `grid/compareCells.js`, **partagée avec `AdminGrid`** : un correctif de
> tri (comme #296) doit y être fait une fois pour les deux.

**Validation** : la recette manuelle vit dans `admin/RECETTE.md` — cas transverses,
cas génériques de la grille, et cas par écran. Les écrans d'administration sont
volontairement peu couverts en Playwright : `player/getPlayers` renvoie 2,5 Mo en
~8 s, et une campagne complète passait de 20 s à plus de 10 minutes. Seuls la
garde d'accès et le comportement de la grille sont automatisés.

Conventions backend reprises telles quelles : lecture en GET, écriture en POST
avec `id` vide pour un INSERT, suppression en POST avec `ids` joints par des
virgules. Chaque endpoint doit être déclaré `admin` dans `rest/access.php`.

### Sencha/ExtJS — supprimé

L'arbre `js/` (150 fichiers, 18 contrôleurs, 68 vues, 40 stores) et `admin.php`
ont été supprimés au **lot 6 de #265**, après migration des 30 entrées de menu.
`admin.php` subsiste comme simple redirection vers `/admin/`.

Deux écrans n'étaient pas de simples grilles CRUD et ont demandé un composant
propre : `screens/Indicators.js` (tableau de bord en tuiles) et
`screens/Divisions.js` (réorganisation par glisser-déposer, qui ferme #189).

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

### Effectif figé en Coupe Khoury Hanna (issue #32)

Dès qu'une équipe **`kh`** a signé la fiche équipe d'un de ses matchs (`kh` en
poules, `kf` en finales, qui réutilise les mêmes `id_equipe`), plus aucun joueur
ne peut lui être ajouté : l'objet est d'empêcher qu'elle se renforce en cours de
compétition.

- `Team::getSquadLock($id)` dit si l'effectif est figé, et depuis quel match ;
- `Players::assertSquadIsOpen()` applique le refus, en **403** (refus métier,
  pas panne) ;
- l'écran effectif du responsable masque les actions d'ajout via
  `team/getMySquadLock` — confort d'affichage, le refus serveur reste la
  garantie.

> **`joueur_equipe` ne doit être alimentée que par `addPlayerToTeam`.** C'est le
> seul endroit qui porte le contrôle. `add_to_team` en portait une copie de
> l'INSERT, ce qui offrait un chemin d'ajout hors verrou ; elle y a été ramenée,
> et `SquadLockTest` échoue si un second INSERT réapparaît.

> **L'administrateur n'est pas bloqué** : la commission doit pouvoir corriger
> une saisie ou accorder une dérogation. Son ajout est journalisé
> distinctement — `Ajout DEROGATOIRE de … (effectif fige depuis le match …)` —
> et donc repérable depuis l'écran Activité.

> **La règle ne vaut QUE pour la Khoury Hanna**, seule compétition à avoir ses
> propres inscriptions d'équipe (62 équipes en `code_competition = 'kh'`). Les
> autres coupes réutilisent les équipes de championnat : y étendre le verrou
> gèlerait tout le monde.

> L'indicateur « Joueurs inscrits hors délai en Coupe Khoury Hanna » (#233) a
> été **supprimé** avec ce lot : il reconstituait a posteriori, par recoupement
> de chaînes du journal d'activité, ce que le verrou empêche à la source.

### Pénalité automatique : feuille de match non signée à 48 h (issue #345)

`cron/hourly.php` appelle `MatchPenalty::apply_unsigned_sheet_penalties()` avant
d'envoyer la file d'emails. Match de championnat confirmé, non certifié, joué à
partir de `MatchPenalty::UNSIGNED_SHEET_SINCE` (2026-10-01, pas de rétroactivité),
dont la feuille n'est pas signée **des deux côtés** 48 h après l'horaire (date +
heure du créneau, sinon 23:59 — `sql/unsigned_match_sheets_48h.sql`) : -1 point à
**chacune des deux équipes**, même celle qui a signé (décision de la commission).

Chaque pénalité est tracée dans `match_penalties` (match, équipe, motif, date) ;
la clé unique rend l'application idempotente (`INSERT IGNORE`, puis
`classements.penalite + 1` seulement si la ligne est nouvelle). La pénalité reste
si la feuille est signée ensuite. L'admin l'annule par le « -1 » du classement ;
la ligne reste comme historique et empêche une nouvelle application. Indicateur
« Pénalités automatiques » ; email aux deux équipes par la file d'emails.

### Membre non jouant d'une équipe (issue #325)

`joueur_equipe.est_jouant` (BIT, défaut `b'1'`) dit si l'appartenance est
jouante. Le cas type : un joueur du championnat masculin qui est aussi
**responsable d'une équipe féminine**. Il doit être rattaché à l'équipe pour la
piloter, mais il n'en est pas un membre jouant.

> **Le drapeau porte sur l'appartenance, pas sur la personne.** Un flag sur
> `joueurs` ne saurait pas dire « jouant en masculin, non jouant en féminin ».
> Le **référent de club qui n'est pas joueur** est un autre besoin, et il ne
> demande aucune colonne : une ligne `joueurs` avec `id_club` et **aucune**
> ligne `joueur_equipe` suffit — tous les contrôles « joueur » partent de
> `joueur_equipe` et lui sont donc déjà aveugles (voir #326).

Ce qui **filtre** sur `est_jouant + 0 > 0` :

| Où | Pourquoi |
|----|----------|
| `sql/no_licence.sql`, `sql/not_valid_players.sql` | un responsable non jouant n'a pas besoin de licence à ce titre |
| `sql/teams_incomplete.sql` (jointures `j_masc` / `j_fem` **seules**) | sans ça, la règle `garcons > 0` déclarait une équipe féminine incomplète à vie |
| `sql/players_in_many_teams.sql` | une appartenance non jouante ne fait pas jouer plus |
| `MatchMgr::getNotMatchPlayers`, `Players::getPlayers($query, $id_match)` | **la garantie fonctionnelle** : sinon on peut coucher un non-licencié sur une feuille de match |
| `Players::getPlayersPdf` (fiche d'équipe), `getLivePlayersFromTeam` | la fiche liste les licenciés présentables |

Ce qui ne filtre **surtout pas** : toutes les résolutions `is_leader` /
`is_vice_leader` — `Team.php`, `teams_view`, `matchs_view`,
`sql/team_recaps.sql`, `sql/no_leader_team.sql`,
`sql/team_leaders_without_email.sql`. Un responsable non jouant reste le
responsable à qui l'on écrit ; c'est toute la raison d'être du drapeau.
`sql/no_photo.sql` et `match_players_count_view` partent de `match_player`, donc
d'une feuille de match : déjà protégés.

- **Un capitaine joue** : `set_captain` refuse un membre non jouant, et
  `set_playing` refuse de rendre non jouant le capitaine en poste.
- **Le réglage** passe par `player/set_playing` (bouton « joue dans l'équipe »
  de l'écran effectif du responsable) et par la case « Ne joue pas dans cette
  équipe » du sélecteur « Nommer responsable » de l'écran Équipes.
- `set_leader($ids, $id_team, $est_jouant = null)` : **`null` veut dire « ne
  touche pas »**. Les appels qui ne s'en préoccupent pas ne doivent pas
  rebasculer en jouant un membre déclaré non jouant.
- `players_view.non_playing_teams_list` liste les équipes où la personne figure
  sans y jouer. `active_teams_list`, `inactive_teams_list` et `teams_list` ne
  changent pas — une appartenance reste une appartenance, et le filtre
  « engagé » comme le `%teams_list%` des emails gardent leur sens.

### Encart d'alertes du tableau de bord (issue #346)

`alerts/getAlerts` (`classes/Alerts.php`) couvre l'équipe courante de la session
**et**, pour un responsable de club, les équipes engagées (avec classement) de
ses clubs ; rien pour un admin sans équipe ni club (#419). Chaque alerte porte `team`, `issue`, `criticity`
(error / warning / info), `expected_action` (code d'aide, décliné en texte par
`TeamLeaderAlerts.js`) et `link` (l'écran où corriger). Trois familles retenues :
actions de match en attente (`MatchMgr::getMyPendingMatchActions`, #240) et
pénalités automatiques des 60 derniers jours (#345) ; joueurs sans photo (#343),
sans licence, licence non validée ; effectif (joueurs **jouants**, #325), mixité,
rôles, créneau, contacts. Toutes les requêtes sont liées ; les rares fragments
interpolés (nom de colonne, condition) sont des constantes du code.
L'encart est en tête du tableau de bord, pour tous les responsables ; les toasts
de #240 restent.

### Live scoring : deux écrans, deux publics (issue #332)

`live.html` sert **deux usages qu'il ne faut pas confondre** :

- la **page publique**, consultée par les spectateurs : score, sets, détails du
  match. Elle n'affiche **pas** les compositions et n'en a pas besoin ;
- le **mode arbitre** (`?mode=scorer`), utilisé debout, sur un téléphone, par un
  responsable d'équipe ou un administrateur.

Dès que le live est démarré, le mode arbitre passe en **plein écran** (`fixed
inset-0`, computed `scorerFullScreen`) et remplace la page ordinaire :

| Composant | Rôle |
|---|---|
| `ScorerBoard.js` | les deux moitiés d'écran — **le terrain est le bouton**, `+1` en touchant la moitié qui marque |
| `ScorerControls.js` | ligne de service, barre au pouce, et les trois feuilles (positions, temps morts, plus) |
| `ScorerCourt.js` | un terrain 4-3-2 / 5-6-1 avec vignettes, partagé par la feuille et la composition |
| `ScorerLineup.js` | composition du set |
| `ScoreBoard.js` | **page publique uniquement**, lecture seule depuis ce lot |

> **Rien n'occupe l'écran en permanence si l'arbitre n'en a pas besoin à chaque
> échange.** Les positions tenaient plus d'un écran de téléphone en 12 `select`
> dépliés en continu ; il n'en reste qu'une ligne — qui sert — et le terrain
> complet s'ouvre à la demande.

> **`lineups` contient des IDENTIFIANTS de joueur**, plus des noms : c'est ce
> qui permet d'afficher la vignette. `keepPlayerIds()` écarte les brouillons
> `localStorage` antérieurs, qui portaient des noms complets.

> **Annuler défait la rotation.** `handleServiceAndRotation()` fait tourner
> l'équipe qui reprend le service ; l'ancien `-1` ne rendait que le point, donc
> une reprise de service annulée laissait la rotation fausse jusqu'à la fin du
> set, sans que rien ne le signale. `pointHistory` empile l'état **avant** chaque
> point — score, service, positions — et `undoLastPoint()` restitue les trois.
> La pile est **vidée à chaque fin de set** : on n'annule pas au travers.

> **Les vignettes ne sont servies qu'au scoreur.**
> `ajax/live_score.php?what=rosters` → `LiveScore::getScorerRosters()`, derrière
> le `canModifyLiveScore()` qui garde déjà les POST. On n'a **pas** élargi
> `player/getLivePlayersFromTeam` : il est en niveau `user`, donc tout compte
> connecté pourrait alors lister les photos de n'importe quelle équipe — il
> reste sans PII (issue #228). Les membres **non jouants** (#325) en sont exclus.

### Import des licences liguasso (issues #394, #404)

Liguasso ne produit qu'**une licence par PDF**. `form/LicenceImportModal.js`,
partagé par l'effectif du responsable et l'écran Joueurs de l'admin, prend les
fichiers en lot (glisser-déposer, ou sélection multiple, qui marche aussi sur
téléphone). Il les envoie **un par requête, trois en parallèle**. Un envoi
unique buterait sur `max_file_uploads` (20 par défaut), `post_max_size` et
`max_execution_time`, réglages du mutualisé OVH, et une erreur emporterait
tout le lot. Les fichiers en erreur se relancent seuls.

`Players::update_from_licence_file` traite un fichier et rend
`{message, report}`, une ligne par licence : `created`, `updated`, ou
`rejected` avec son motif, plus `photo`. Une licence écartée n'arrête pas la
suite du fichier. Un fichier sans licence reconnue répond 422, sans le
message technique du lecteur PDF.

> **Le routeur ne renvoie le résultat d'une écriture que sur demande** : une
> méthode POST qui rend un tableau avec une clé `report` voit
> `message` + `report` transmis au client (`rest/action.php`). Les autres
> valeurs de retour (identifiant créé…) restent internes. Ne pas élargir à
> tout tableau : on exposerait des données par mégarde.

`search_player_and_save_from_licence` traite une licence à la fois.

- **Club de la licence = numéro d'affiliation** imprimé (`licence_club` →
  `clubs.affiliation_number`), jamais le nom, qui diffère souvent de la base.
  Un responsable n'importe que les licences de ses clubs (ceux du compte, et
  celui de l'équipe courante). Si l'un d'eux n'a pas de numéro en base, une
  licence d'un club inconnu lui est attribuée. Seul l'admin crée un club
  inconnu.
- **Un joueur existant prend le club de sa licence** : c'est un changement de
  club, tracé dans l'Activité.
- **Recherche** : d'abord par département + licence, puis par nom. Le nom
  seul ne met à jour qu'un joueur unique, sans licence ou du club de la
  licence. On n'écrase pas la licence d'un homonyme.
- **Nom composé** : `Players::split_licence_name` prend les mots en
  majuscules en tête pour le nom.
- **`num_licence` normalisé dans `Players::save()`** : sans espace, sans
  préfixe `0?\d{2,3}_` (`013_DY10000187` imprimé sur la licence). Le
  département a sa colonne. Reprise de l'existant : migration `025`.
- La photo n'est pas exigée à l'import : seulement sur la feuille de match
  (#343).

### Fusion de deux fiches joueur (issue #409)

`Players::mergePlayers($id_keep, $id_remove)` (admin), depuis l'écran Joueurs :
sélectionner deux fiches, puis « Fusionner… » (`PlayerMergeModal.js`). C'est la
correction des « Licences dupliquées » et des doublons de saisie. Le plus
souvent, une fiche créée à la main face à la vraie.

En une transaction (`mysqli_begin_transaction` sur la connexion unique de
`Database`) :
- les appartenances aux équipes sont reportées, sans doublon, en cumulant les
  rôles ;
- les feuilles de match sont reportées, sans doublon ;
- les champs vides de la fiche gardée sont complétés ;
- le compte est reporté, après avoir été libéré (`id_compte` est UNIQUE) ;
- l'autre fiche est supprimée, et la fusion journalisée.

Le report des équipes passe par un `UPDATE`, pas par `addPlayerToTeam` : c'est
une correction d'administrateur, qui n'ajoute personne (le verrou de #32 ne la
concerne pas, et `SquadLockTest` ne surveille que les INSERT).

### Pas de photo, pas de match (issue #343)

`MatchMgr::manage_match_players` refuse (409, joueurs nommés) tout présent ou
renfort **sans photo enregistrée**, avant d'effacer quoi que ce soit : un refus
laisse la fiche intacte. L'admin est exempté pour corriger une fiche.

« Sans photo » veut dire **sans chemin en base** (`Players::has_photo_path`), pas
« fichier absent du disque » : la CI n'a aucun fichier photo et la base de dev
n'en a qu'une petite partie, un contrôle sur le fichier y bloquerait tout le
monde. `adjust_photo_path_from_results` expose `has_photo` (0/1) avant de
substituer l'image de repli, qui reste un simple affichage. `team_sheets.html`
grise le bouton d'ajout d'un joueur sans photo (sauf pour l'admin).

### Renforts : éligibilité en championnat (issue #349)

En championnat (`MatchMgr::REINFORCEMENT_RULE_COMPETITIONS` = `m`, `f`, `mo` ;
les coupes n'ont pas de niveau de division), un renfort doit venir d'un autre
championnat, ou d'une division **strictement inférieure** (numéro plus grand)
du même championnat — `reinforcement_ineligibility()`. Seules comptent ses
appartenances **jouantes** (#325). Un renfort, c'est un présent membre d'aucune
des deux équipes (`reinforcements_among()`). `getReinforcementPlayers` renvoie
quand même les inéligibles, avec `reinforcement_blocked` (le motif) pour que
l'écran explique ; `manage_match_players` les refuse (409), admin exempté.

### Renforts : compléter l'équipe, mixité, demi-saison (issue #348)

`match_player.id_team_reinforced` dit quelle équipe un renfort renforce (NULL
pour un joueur de l'équipe et pour les renforts saisis avant #348). La fiche
envoie `reinforcements[id_joueur] = id_equipe` ; un responsable ne renforce
que son équipe. En championnat, `assert_reinforcement_rules()` impose : un
renfort par équipe ; seulement si l'équipe compte moins de 6 (masculin) ou 4
(féminin, mixte) joueurs jouants présents ; renfort féminin en féminin, et en
mixte le sexe absent de l'équipe ; une fois par demi-saison (juillet-décembre,
janvier-juin), les renforts d'avant #348 comptant aussi. Admin exempté, coupes
non concernées.

`match_players_count_view` compte les renforts **par équipe** et par sexe : en
mixte, une renforte satisfait la mixité de son équipe (sinon celle-ci ne
pourrait jamais signer). Un renfort sans équipe compte pour les deux, comme avant.

### Le référent d'un club, c'est son compte (issue #326)

Depuis que la création des comptes d'équipe est déléguée au compte rattaché à un
club, le référent d'un club **est** la ligne `users_clubs` → `comptes_acces` :
seule table où l'email est à la fois obligatoire et **unique** (`uq_email`), et
seule qui porte le rôle (#245).

Les cinq colonnes `clubs.*_responsable` ont été **retirées par #327**. Elles
n'étaient pas décoratives : contact de dernier recours quand une équipe n'a pas
de responsable, et **adresse d'expédition** dans cinq requêtes
(`team_recaps.sql`, `teams_incomplete.sql`, `teams_with_missing_licences.sql`,
`register_invoices.sql`, `register_not_paid.sql`), toutes passées au compte du
club. La table `clubs` ne porte plus que `id`, `nom`, `affiliation_number`.

- `Club::createClubAccount($id_club, $email)` (admin) crée le compte ou rattache
  un compte existant, et envoie les identifiants **immédiatement** — la création
  se fait à l'unité, en face de quelqu'un qui attend (#305).
- `UserManager::create_or_update_club_account()` est le pendant club de
  `create_or_update_leader_account()` ; les deux passent par `ensure_account()`,
  pour qu'un référent déjà responsable d'équipe ne se voie pas créer un second
  compte.
- `Club::getAccountCandidates()` propose les adresses déjà connues (coordonnées
  du club, personnes du club) : le rattrapage se fait sans ressaisie.
- **Reprise manuelle du lien compte ↔ personne (#331)** : quand l'automatisme
  refuse de trancher (plusieurs personnes portent l'email, ou aucune), l'admin
  choisit dans l'écran Utilisateurs, « Personne rattachée… » :
  `UserManager::linkAccountToPerson($user_id, $id_player)` (vide = détacher)
  détache la personne précédente du compte et retire la personne choisie de son
  ancien compte — jamais deux liens. `getPersonCandidates()` propose toutes les
  personnes, celles des clubs, équipes et email du compte en tête. La colonne
  « Personne » des comptes (`get_users.sql`) montre ce qui manque.
- L'indicateur **« Clubs engagés sans compte de club »** est actionnable (#312) :
  sa tuile ouvre l'écran Clubs filtré, où l'action corrige. La grille porte les
  colonnes `Compte(s)` et `Référent(s)`, servies par `Club::getSql()` — elles
  remplacent, en données vivantes, les nom / prénom / téléphone qu'on saisissait
  à la main.

> **L'ordre de déploiement de #327 est l'INVERSE de #325 et #326.** Ces deux-là
> ajoutaient ce que le code allait lire : SQL d'abord, déploiement ensuite.
> #327 retire ce que le code lisait : **déploiement d'abord**, `DROP COLUMN`
> ensuite. Dans l'autre sens, le code encore en place cherche des colonnes
> disparues.

> Les sauvegardes prises avant une migration destructrice se nomment
> **`zz_backup_*`** (convention posée par #327, `zz_backup_clubs_responsable_327`
> en est la première). `dump-schema.ps1` les écarte du schéma de CI : les y
> laisser ferait croire à une table du modèle.

> **`joueurs.id_compte` (FK, `ON DELETE SET NULL`, `UNIQUE`) au lieu d'une
> jointure sur l'email.** Trois raisons, toutes vérifiées en base : `joueurs`
> porte `email` **et** `email2` ; `comptes_acces`/`clubs` sont en **latin1** et
> `joueurs` en **utf8mb3**, donc la comparaison force une conversion et rend
> l'index inutilisable ; et **6 comptes sur 146** correspondent à *plusieurs*
> personnes — adresses de famille, adresse générique de club, doublon de saisie.

> **`UserManager::link_person_to_account()` ne lie que l'évident**, et se tait
> dans les trois cas où le lien serait un pari : compte déjà rattaché, aucune
> personne avec cet email, ou plusieurs. Ces cas-là se règlent à la main — c'est
> le but. La reprise initiale (134 liens sur 146 comptes en dev) suit la même
> règle.

> `joueurs.est_responsable_club` a été **supprimée** avec ce lot : jamais lue —
> ni droit, ni indicateur, ni requête. C'était un troisième marqueur de ce que
> porte `users_clubs`.

### Score saisi après les fiches équipes, forfait déclaré (issue #344)

`MatchMgr::save_match` (responsables) refuse un score (409, fiches manquantes
nommées) tant que les **deux** fiches équipes (`is_sign_team_dom/ext`) ne sont
pas signées — `assert_team_sheets_signed()`, admin exempté. Enregistrer seulement
l'arbitrage ou le commentaire, sans set saisi, reste libre. La saisie en direct
(`LiveScore::saveToMatch`) applique le même verrou.

**Forfait** : paramètre `forfeit` (`dom` | `ext`, l'équipe forfait), bouton
« Déclarer forfait » de `match.html`. Le serveur écrit lui-même 25-0 sur trois sets
(`MatchMgr::forfeit_sets`) et n'exige **aucune** fiche équipe : l'équipe
présente ne pourrait pas signer la sienne, `count_status` signalant la fiche vide
de l'absente. Les colonnes de sets sont `NOT NULL` : un set non joué vaut 0.

### Toute la base est en utf8mb4 (issue #334)

Un responsable de club n'a pas pu renommer son équipe : le formulaire renvoyait
`Conversion from collation utf8mb3_general_ci into latin1_swedish_ci impossible
for parameter`. Trente tables étaient en **latin1** ou **utf8mb3**, et la
connexion annonçait `utf8` — l'alias d'utf8mb3. Le script
`sql/updates/2026/017-convert_to_utf8mb4.sql` les a converties, et
`Database.php` annonce désormais `utf8mb4`.

> **Le « latin1 » de MySQL est en réalité cp1252.** C'est pourquoi les accents,
> l'apostrophe courbe, le tiret cadratin et l'euro passaient, alors que l'emoji,
> le « ✓ » et surtout **U+202F, l'espace insécable étroite, échouaient**. Cette
> dernière est le cas à retenir : **iOS en français l'insère tout seul avant
> `?`, `!`, `;` et `:`**, elle est invisible, et l'utilisateur ne peut donc ni
> la voir ni la retirer.

> **La conversion se joue sur le paramètre lié, pas sur la requête.** Un simple
> `UPDATE equipes SET nom_equipe = ?` échouait : MySQL convertit le paramètre
> vers le jeu de la colonne avant de l'écrire.

> **Une vue ne conserve pas le texte qu'on lui a donné.** MySQL le normalise et
> injecte un `convert(... using utf8mb3)` partout où un `CONCAT` mélangeait deux
> jeux — six dans `teams_view`, cinq dans `players_view`. Ces conversions ne
> sont **pas** dans les fichiers de `sql/views/` : il suffit de les rejouer
> après la conversion. Les laisser **retronquerait** ce qu'on vient d'élargir,
> et un emoji intact en base ressortirait en « ? » à l'écran.
> `Utf8mb4Test::test_la_vue_des_equipes_ne_retronque_pas_le_nom` le vérifie.

> **Ordre de déploiement : le code d'abord**, contrairement à #327. Une
> connexion utf8mb4 vers des tables encore en latin1 se comporte exactement
> comme avant — même jeu refusé, aucune régression. L'inverse laisserait les
> emoji se faire refuser sans raison visible.

> **Une nouvelle table doit rester en `utf8mb4_0900_ai_ci`**, la collation de la
> base et des 32 tables. Une table créée dans une autre ferait échouer toute
> jointure sur une colonne texte (« Illegal mix of collations »). La fonction
> stockée `SPLIT_STRING`, qui rendait du latin1 depuis 2019, a été recréée pour
> la même raison.

> `zz_backup_clubs_responsable_327` reste volontairement en latin1 : c'est une
> sauvegarde destinée à disparaître, elle n'est jointe à rien, et
> `dump-schema.ps1` l'écarte déjà du schéma de CI.

### Classement : barème FFVB pour les championnats (issue #347)

`Rank::uses_ffvb_scale()` décide du barème. **Championnats** (`m`, `f`, `mo`,
`Rank::FFVB_COMPETITIONS`) : 3-0/3-1 = 3 pts, 3-2 = 2, 2-3 = 1, 0-3/1-3 = 0,
forfait = -1 ; départage **victoires → quotient de sets → quotient de points**,
entièrement en SQL (`sql/get_rank_ffvb_by_competition_division.sql`), sans
confrontation directe. **Coupes** : barème UFOLEP inchangé (3/1/0, différence de
sets puis confrontation directe dans `Rank::applyHeadToHeadTieBreak`).

- Une équipe sans set concédé a un quotient **infini** (1e9 dans le tri) : un
  `NULL` trié en ordre décroissant l'aurait mise en dernier.
- `ranks_view` garde le barème UFOLEP (elle ne sert plus qu'aux coupes) : pour
  une équipe de championnat, `getRank(..., $id_team)` filtre le classement FFVB
  de sa division.
- `get_full_competition_rank('m')` (poules de la coupe Isoardi) concatène les
  classements FFVB division par division.
- Palmarès (`HallOfFame::getTop2ByDivision`) : barème FFVB seulement pour une
  période qui finit après `Rank::FFVB_SINCE` (2026-09-01) — régénérer une saison
  passée ne la réécrit pas. `sql/get_top2_by_division.sql` reçoit ses fragments
  de points et de tri par `str_replace` : jetons fixes, jamais une valeur du
  client, et jamais écrits tels quels dans un commentaire de la requête.

### Retardataires d'inscription (issue #338)
- L'indicateur **« Clubs sans aucune inscription »** (`sql/clubs_without_registration.sql`)
  travaille à la **maille club**, pas équipe : un club qui n'a rien inscrit du
  tout n'a pas commencé sa saisie. Celui qui en a inscrit deux sur trois relève
  de « Equipes non réengagées », qui liste les équipes une par une.
- La population attendue vient de `classements` — avoir joué la saison passée,
  et ne pas avoir déclaré forfait (`will_register_again`). Un club qui a dit
  qu'il ne revenait pas n'est pas en retard, il est parti.

> **Le garde-fou est la fenêtre d'inscription.** `register` garde ses lignes
> toute la saison : sans borne, les clubs absents resteraient signalés jusqu'au
> prochain millésime. La requête est donc encadrée par
> `competitions.start_register_date` / `limit_register_date` (bornes incluses,
> comme `Competition::is_registration_available()`), ce qui donne au passage
> `jours_restants` et **éteint la tuile toute seule** passée la date limite.
> Repousser cette date dans l'écran Compétitions rallonge d'autant la vie de
> l'indicateur.
>
> Conséquence pour les tests : `LateClubsTest` **ouvre lui-même la fenêtre** des
> trois championnats et la restaure dans son `tearDown`, sinon il passerait sans
> rien vérifier dix mois sur douze. Même parti pris que `registrations_setup.php`
> côté E2E.

### Sondage fair-play : échelle -- - = + ++ (issue #350)

Depuis la saison 2026-2027, chaque critère se note `--` `-` `=` `+` `++`, stocké
en **-2..+2** dans les mêmes colonnes (`tinyint` signé), prérempli à `=` (0).
Une note `--` exige un commentaire (contrôle serveur et formulaire).

`survey.scale_version` distingue les échelles : **1** = 0..10 étoiles (tous les
sondages d'avant, 0 = non noté), **2** = l'échelle courante (`Survey::SCALE_VERSION`).
`survey_view_raw` — donc le classement fair-play — ne retient que la version 2 :
les deux échelles ne se mélangent jamais. Elle ne filtre plus sur « somme des
notes > 0 », qui écartait un sondage tout à `=`. Un sondage de l'ancienne échelle
n'est pas repris dans le formulaire (`get_survey` renvoie un formulaire neuf).

Un sondage ne se relit que par l'équipe sondeuse : `Survey::getSql()` passe par
`users_teams`. Celui d'un admin hors équipe s'enregistre mais ne se relit pas.

### Décisions sur une inscription : valider, refuser, dévalider (issue #376)

`register.status` vaut `PENDING`, `VALIDATED` ou `REFUSED`. Chaque décision de
l'admin n'est permise que depuis certains statuts, contrôlés côté serveur (409)
et reflétés par l'écran (`DECISIONS` dans `Registrations.js`, bouton inactif
sinon) :

| Décision | Depuis | Effet |
|---|---|---|
| `validateRegistration` | PENDING, REFUSED | VALIDATED, email au club, motif effacé |
| `refuseRegistration($id, $reason)` | PENDING | REFUSED, motif **obligatoire** (≤ 1000 car.), email au club |
| `unvalidateRegistration` | VALIDATED | PENDING |

Le club voit le motif dans son espace et peut corriger une demande refusée :
l'enregistrer (`register()` par un non-admin) la **remet en PENDING**, motif
effacé. Une édition par l'admin garde le statut. Seules les VALIDATED sont
engagées (`set_up_season`, `get_pending_registrations`…). Les emails de décision
échappent le nom d'équipe et le motif (`notifyClub`).

### Grilles d'administration : filtres, mémoire, édition en masse (#309 à #311)

Trois capacités d'`AdminGrid`, offertes à tous les écrans :

- **Filtres par colonne (#310, `columnFilters.js`)** : bouton « Filtres ». Le
  type se **déduit des valeurs affichées** (après `format`) : liste si ≤ 20
  valeurs distinctes, plage du … au … si ce sont des dates jj/mm/aaaa, « contient »
  sinon — égalité stricte pour un nombre dans une cellule numérique. Une colonne
  force son type par `filter: 'text' | 'select' | 'date'`, ou s'exclut par
  `filter: false`. Filtrer vide la sélection, comme la recherche.
- **Mémoire de la vue (#311, `gridState.js`)** : recherche, tri, taille et page,
  filtres de colonnes, dans `localStorage`, clé `admin-grid:<chemin de route>`.
  Rien n'est restauré quand l'URL porte des paramètres (`?ids=`, #312). Une page
  qui n'existe plus se ramène à la dernière. « tout » vaut `pageSize = 0`.
  Les filtres **propres à un écran** passent par le mixin
  `persistedFilters(['status'])` et `@reset-view="resetPersistedFilters"`. Ne
  pas y mettre une saison (calendrier, palmarès) : elle serait périmée l'année
  suivante.
- **Recherche sur toute la ligne (#408)** : la recherche rapide parcourt TOUTES
  les données rendues par le serveur, affichées ou non (licence d'un joueur,
  email d'un compte…), plus les valeurs formatées des colonnes. Exclus :
  identifiants techniques et chemins (`NOT_SEARCHED` : `id`, `id_…`, `…_id`,
  `path_…`) et ce qu'un écran déclare dans `search-exclude`. Le HTML est
  réduit à son texte. Le texte cherchable est calculé **une fois par
  chargement** (`searchIndex`), pas à chaque frappe. Un terme `013_…` cherche
  aussi la licence sans préfixe (#404). Les filtres par colonne restent sur la
  valeur affichée.
- **Taille de page automatique (#408)** : tout reste côté navigateur ;
  paginer ne sert qu'à limiter ce qui est dessiné. Par défaut (`pageSize =
  null`) : « tout » jusqu'à 500 lignes, pages de 100 au-delà. Joueurs en
  « tout » (3 663 lignes) mettait 4 s à s'afficher, et 2 à 6 s par frappe.
  Un 25 mémorisé avant #408 (l'ancien défaut) est ignoré, sauf s'il a été
  choisi (`pageSizeChosen`).
- **Édition en masse (#309, `AdminBulkEditModal.js`)** : un écran déclare
  `:bulk-fields="['division', 'is_paid']"`, et « Éditer » s'ouvre alors sur
  plusieurs lignes. Chaque ligne est postée **complète**, comme par l'édition
  simple (`editPayload.js`, partagé avec `AdminEditModal`), champs cochés
  remplacés : n'envoyer que le champ modifié viderait les autres colonnes.
  Un appel par ligne, arrêt à la première erreur en nommant la ligne.

### Indicateurs de préparation de saison (#395, audit #398)

- **`club_contacts_view`** (`id_club`, `contact`) : le ou les comptes du club,
  sinon les emails des responsables d'équipe du club. C'est la règle unique
  « à qui écrire pour un club » des indicateurs. Ne pas la recopier en
  sous-requête. Migration `023` (dépôt Python).
- Les indicateurs fondés sur `register` **écartent les refusées**
  (`status <> 'REFUSED'`). Ils ne retiennent que la campagne en cours
  (`creation_date >= start_register_date`), et rapprochent inscription et
  équipe par la règle de #390 (`old_team_id`, sinon le nom dans la compétition).
- **Cotisations (#417)** : plus de relance hebdomadaire des clubs. Le bouton
  « Cotisations → comptabilité » de l'écran Inscriptions
  (`Register::send_membership_fees_to_accounting`) envoie **une fois par
  saison** à `Register::ACCOUNTING_EMAIL` le montant attendu de chaque club. Un
  second envoi répond 409, et l'écran propose de le renvoyer. Les montants
  viennent de `sql/register_invoices.sql`, que l'indicateur « Facture par club »
  affiche à l'identique : inscriptions VALIDÉES de la campagne, 10 € en
  masculin, 5 € en féminin et mixte.
- **Appliquer les créneaux demandés (#409)** : `Register::apply_registered_timeslots`,
  bouton de l'écran Inscriptions. Il corrige le décalage équipe par équipe, sans
  attendre l'initialisation : les créneaux de l'équipe sont remplacés par ceux
  de sa demande, en une transaction, et la contrainte horaire d'un créneau
  identique est gardée. Il écarte les demandes refusées, sans créneau, ou dont
  l'équipe n'existe pas encore. Le message, qui suit la convention
  `message` + `report` du routeur, les nomme.
- **Décalage des créneaux** : un créneau se compare en « gymnase (ville) jour
  heure ». Un créneau saisi deux fois ne compte qu'une fois, des deux côtés.
  L'écart est qualifié : `aucun créneau en place`, `ordre de préférence
  inversé`, `créneau modifié`. « Initialiser la saison » recrée les créneaux
  depuis les inscriptions : la tuile se vide alors d'elle-même.
- Les tests (`SeasonPrepIndicatorsTest`) **ouvrent eux-mêmes la fenêtre
  d'inscription** : la base de CI n'a pas de `start_register_date`.
- **Indicateurs de saison (#397)** :
  - une date de match se compare sur `matches.date_reception` (DATE), jamais
    sur `matchs_view`, qui la rend en texte jj/mm/aaaa : un `MAX` sur ce texte
    classait le 16/01 après le 13/03 ;
  - un indicateur qui compte des matchs archivés se borne à la saison en cours
    (1er juillet, comme `CalendarEvents::getCurrentSeason()`). Sinon, à
    l'intersaison, il mélange les matchs de l'an dernier et les équipes de
    cette année ;
  - un joueur se regroupe par `j.id`, jamais par « prénom nom » ;
  - un type de compétition se lit sur `code_competition`, jamais par un
    `LIKE` sur un nom d'équipe ou un code de match.

### Préparation de saison : division X, « Non affectées » (#388)

Déroulé : Inscriptions → « Divisions / rangs » (`Register::fill_ranks`), puis
« Initialiser la saison » (`set_up_season`, qui reconstruit `classements`
depuis `register` : ce qu'on range dans la réorganisation **avant**
l'initialisation est perdu), puis « Réorganiser les divisions ».

- `fill_rank` : équipe classée dans la compétition → sa division et son rang ;
  toute autre (nouvelle, ou existante non classée la saison passée) → division
  `Rank::DIVISION_TO_PLACE` (`X`), rang suivant le plus grand X de la
  compétition. Rejouable : une inscription non classée qui a déjà une division
  la garde ; une refusée n'est pas touchée.
- `Rank::insert_from_register` ne prend que les inscriptions **VALIDATED**.
- **Rapprochement inscription ↔ équipe** (`Rank::REGISTRATION_MATCHES_TEAM`,
  #390) : une réinscription (`old_team_id`) ne désigne **que** son ancienne
  équipe ; le nom ne sert qu'à une nouvelle équipe. Sinon un doublon homonyme
  passait pour inscrit et l'initialisation engageait les deux (deux « Trets &
  Furious » en 2026, nettoyées à la main en prod).
- `getUnassignedTeams` marque `registered` (demande non refusée qui désigne
  l'équipe) et `competition_has_registrations` ; l'écran masque
  par défaut les non inscrites, sauf dans une compétition sans inscriptions
  propres (coupes). La colonne X vient en tête, badge « à placer ».
- Les équipes **des divisions** portent les mêmes champs
  (`Rank::REGISTERED_COLUMNS`, partagé avec la liste « pas réinscrite » de
  #379) : sans inscription, badge « à retirer » et compteur par colonne. Pas de
  retrait automatique : avant l'initialisation, `classements` est encore la
  saison passée affichée sur le site. Les trous de `rank_start` qui en
  résultent sont sans effet (tri seulement, PHP comme Python).
- Pas de filtre de fenêtre d'inscription dans ces requêtes : au 30/09/2026,
  `register` ne contenait que la saison en cours. Des lignes d'une saison
  passée y seraient comptées (rangs X, `registered`, `insert_from_register`).
- **Ancien nom d'une équipe réinscrite (#402)** : `Register::create_or_update_team`
  renomme l'équipe sans changer son identifiant. Il est appelé par
  « Équipes / comptes » **et** par « Initialiser la saison ». L'ancien nom est
  donc figé à la demande, dans `register.old_team_name`
  (`Register::old_team_name_for`), et ne bouge plus ensuite. Une demande
  modifiée qui désigne la même ancienne équipe le garde ; une autre ancienne
  équipe reprend son nom à elle.
  - `Rank::RENAMING_COLUMNS` (`registered_name`, `former_name`) : l'écran
    affiche toujours le nom demandé, et « ex-… » quand l'ancien diffère. Ce
    sens ne dépend pas des boutons déjà passés.
  - La colonne « Ancien nom » des Inscriptions et le « anciennement … »
    public lisent la colonne, sinon l'équipe (demandes antérieures).
  - Pour la campagne 2026, la migration `024` n'a pu reprendre que les équipes
    pas encore renommées : « Équipes / comptes » avait déjà effacé les autres
    anciens noms.

### Grilles d'administration : en-tête figé (#386)

La grille occupe la hauteur de la fenêtre et **défile dans sa propre zone**
(`data-testid="grid-scroller"`), pas la page. Un `thead` ne peut coller qu'à
son conteneur défilant, et le défilement horizontal des tables larges en impose
un.

- **lg et plus** : titre, compteur et actions (`grid-toolbar`) restent en haut ;
  filtres de l'écran, recherche et table défilent dessous, et le `thead` (ligne
  des filtres de colonnes comprise) colle en haut de la zone.
- **Plus petit** : c'est la racine (`grid-root`) qui défile ; seul le `thead`
  colle.
- Les blocs au-dessus de la table portent `sticky left-0`, sinon ils suivraient
  le défilement horizontal.
- Le tiroir de détail (#308) est **frère** de la zone défilante, pas enfant : il
  ne défile pas avec les lignes et ne recouvre pas la barre d'outils.
- **Pas de `table-pin-rows`** (chaque ligne collerait à top 0, la ligne des
  filtres couvrirait les titres) ni de bordure entre les lignes du `thead` : en
  `border-collapse`, la bordure appartient à la table, ne suit pas l'en-tête
  collé, et les lignes transparaissaient par cette fente. Le trait sous l'en-tête
  est une ombre.
- Un test E2E qui fait défiler une grille agit sur `grid-scroller`
  (`scrollHeight`, `scrollTo`), plus sur `document.body`.

### Inscriptions en cours, publiques en page d'accueil (issue #379)

`register/getPublicRegistrations` (**public**) alimente `PublicRegistrations.js`
sur l'accueil. Par compétition : les demandes (tous statuts, **refus compris**,
sans le motif), et en championnat (`Competition::CHAMPIONSHIPS`) les équipes du
classement actuel sans demande (`NOT_REGISTERED`, « pas réinscrite »).

- **Liste blanche** : chaque ligne ne porte que
  `Register::PUBLIC_REGISTRATION_FIELDS` (club, équipe, statut, type, ancien
  nom), vérifié clé par clé en test. Ne jamais y ajouter responsable, gymnase,
  remarque, paiement ou motif.
- **Fenêtre** : visible dès `start_register_date`, jusqu'au démarrage. Mais
  `start_date` reste celle de la saison passée tant que la commission n'a pas
  saisi la nouvelle : une `start_date` antérieure à l'ouverture ne masque rien.
- **Seules comptent les demandes de la fenêtre** (`creation_date >=
  start_register_date`) : `register` garde ses lignes d'une saison à l'autre.
  Une réinscription se reconnaît à `old_team_id`, quelle que soit la
  compétition demandée (une équipe passée du féminin au mixte est réinscrite).

### Règlements lus dans le dossier Google Drive (issue #342)

Les règlements ne sont plus dans le code : `UfolepRules.js` (liste) et
`RulesDocument.js` (un règlement) affichent les documents du dossier Google
Drive de la commission, désigné par le registre `rules.folder` (URL ou
identifiant seul, écran « Base de registres »). La page Infos en propose les
PDF. `classes/RulesDocument.php` (`rules/getRulesList`, `rules/getRules?slug=`,
`rules/getRulesPdf?slug=`, publics) lit les listes de dossiers et les exports
HTML et PDF, et les garde dans `document_cache` 15 minutes. Si Google ne répond
pas, la dernière version est servie (statut `stale`), et on ne le relance pas
plus d'une fois toutes les 5 minutes.

- **Un sous-dossier par saison, nommé exactement `AAAA-AAAA`** (un nom comme
  « 2027-2028 brouillon » est ignoré : c'est ainsi qu'on prépare une saison sans
  la publier). Chaque règlement vient de la saison **la plus récente qui le
  contient** : une nouvelle saison ne porte que les règlements qui changent.
- **Un règlement est reconnu à son nom de fichier** (`RulesDocument::KINDS` :
  « GENERAL », « CHAMPIONNAT FEMININ »…, suffixe de saison ignoré), qui lui donne
  son libellé, son icône et sa place. Un document inconnu s'affiche quand même,
  sous son nom. La liste de dossier passe par la vue publique
  `drive.google.com/embeddedfolderview` (pas de clé d'API) : si Google en change
  le format, `parse_folder` est le seul endroit à reprendre.
- **Le partage reste en lecture seule** (« Tous les utilisateurs disposant du
  lien : Lecteur », sur le dossier) : le serveur n'a besoin que de lire. Aucune
  adresse Google ne sort du serveur — le lien « document original » est le PDF
  servi par `getRulesPdf` — donc aucun lien d'édition ne peut fuiter par le
  site. Si le téléchargement est interdit aux lecteurs, l'export échoue.
- **Jamais d'adresse Drive réelle dans le code** (dépôts publics) : ni dans une
  migration, ni dans un test, ni dans un seed. `rules.folder` se renseigne dans
  l'admin ; les tests utilisent des identifiants fictifs.
- **Le HTML est reconstruit, jamais recopié** (`RulesDocument::sanitize`) : liste
  blanche de balises, texte échappé, seuls `href` (http, https, mailto — la
  redirection `google.com/url?q=` retirée) et `colspan`/`rowspan` survivent. Le
  gras, l'italique et le souligné, que Google exprime en classes CSS,
  redeviennent des balises. D'où le `v-html` sans risque dans `RulesDocument.js`.
- Le premier paragraphe d'un document est son titre (retiré, la page a le sien).
  Un paragraphe « Article N : Titre » ouvre un article (`#article-N`) ; le
  récapitulatif en est tiré, par ordre alphabétique. Une mise en page qui casse
  ce motif fait disparaître les articles de la page.
- Tests : `RulesDocumentTest` (sans réseau, réponses simulées par URL) ; en E2E,
  `rules_setup.php` remplace le dossier par un dossier fictif en cache.

### Rôles utilisateurs (issue #245)
- Les rôles sont **dérivés et cumulables** — pas de table de profils :
  admin → `comptes_acces.is_admin` ; responsable d'équipe → ligne `users_teams` ;
  responsable de club → ligne `users_clubs`
- Flags posés en session au login : `is_admin`, `is_team_leader`, `is_club_leader`
  (+ `id_equipe`, `id_club`) — prédicats `UserManager::isAdmin()/isTeamLeader()/isClubLeader()`
- Côté frontend : `session_user.php` / `getCurrentUserDetails` exposent ces flags ;
  garde des pages match via `requireRoles(['admin', 'team_leader'])` (`pages/components/auth/guard.js`)
- « Agir en tant que » : sauvegarde/restauration des flags via `original_admin_*` en session
- **Un admin responsable d'équipe ou de club fait tout ce que fait un
  responsable depuis son espace (#251, #419).** Ne jamais écrire
  `if (isAdmin()) return false` dans une action de responsable : tester le rôle
  de responsable (`isTeamLeader()`, `isClubLeader()`), et laisser l'admin en
  plus. Quand un même point d'entrée sert l'administration ET l'espace
  responsable, c'est **l'écran** qui dit d'où il vient, pas le rôle :
  `add_to_my_team` (formulaire joueur, import de licences), `my_team`
  (historique), `id_club` vide (inscription depuis l'espace club). Sinon un
  admin-responsable remplit son équipe à chaque fiche modifiée dans
  l'administration (#407). Un admin qui joue un match comme responsable ne
  signe que pour son équipe. « Agir en tant que » depuis un compte de club ne
  donne jamais plus de droits que le club : ni l'admin, ni les autres clubs du
  compte cible.

> **Les trois rôles s'éditent depuis l'écran Utilisateurs**, et tous les trois
> par un **bouton d'action**, pas par un champ du formulaire : « Équipes
> liées… » et « Clubs liés… » écrivent dans `users_teams` / `users_clubs`, le
> troisième appelle `usermanager/setAdmin`. Une élévation de privilèges mérite
> sa confirmation, et non d'être basculée en passant par une correction
> d'adresse email.
>
> Le bouton admin avait disparu à la migration ExtJS → Vue (#265) : la méthode,
> sa règle d'accès et ses tests étaient restés, seul le câblage manquait, et il
> a fallu passer par la base pendant ce temps (#301). **Vérifier qu'un écran
> migré n'a pas perdu une action au passage** — le CRUD se voit, une action de
> barre d'outils non.
>
> **On ne peut pas se retirer son propre rôle admin** : le dernier
> administrateur qui se rétrograde n'a plus aucun moyen de revenir depuis
> l'application. Le refus est **côté serveur** (`UserManager::setAdmin`), pas
> dans le bouton.

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

### Validation d'ID en PHP
- Utiliser `!empty($id) && is_numeric($id)` pour vérifier les IDs de base de données
- `isset()` retourne true même pour `null`, préférer `!empty()`
- Le test `is_numeric` venait des IDs temporaires d'ExtJS (`"extModel1124-23"`) ;
  il reste utile, le formulaire modal Vue envoyant un `id` vide à la création

### Tests Unitaires Sélectifs
- Certains tests sont destructifs (modification de données réelles)
- Préférer exécuter les tests spécifiques : `--filter "test_method1|test_method2"`
- Éviter `phpunit unit_tests/` si la base contient des données de production

### Routeur REST PHP et Paramètres Nommés
- Le routeur `rest/action.php` appelle les méthodes avec des **paramètres nommés** PHP 8+
- Les méthodes CRUD doivent accepter les paramètres comme arguments de fonction, pas via `$_POST`
- Exemple : `public function saveNews($id = null, $title = '', $text = ''): void`
- **Donner une valeur par défaut à TOUS les paramètres** : un paramètre obligatoire
  que le formulaire n'envoie pas lève une `ArgumentCountError` (500)
- **Pas de `$dirtyFields`** (issue #377) : c'était ExtJS qui listait les champs
  modifiés ; il a été retiré de toutes les signatures (`AdminScreensTest` le
  garde). Pour tracer une modification dans le journal d'activité, le serveur
  compare lui-même : `$before = $this->row_before($id)` avant l'écriture, puis
  `build_activity($sujet, $before, $inputs)` (création, ou « - champ : ancien →
  nouveau », null si rien n'a changé)
- Retirer un paramètre d'une signature décale les **appels positionnels**, et
  PHP ignore sans erreur un argument en trop : chercher les appelants (tests
  compris) avant de toucher à l'ordre des paramètres

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
