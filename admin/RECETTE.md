# Recette manuelle de l'administration Vue (issue #265)

Cas d'usage à jouer à la main pour valider la migration, écran par écran.

**Pourquoi manuellement.** Les écrans d'administration coûtent cher à couvrir en
Playwright : `player/getPlayers` renvoie 2,5 Mo en ~8 s, chaque test paie une
connexion et un chargement, et un parcours de la barre latérale dépend du rendu
du drawer à la largeur de test. Une campagne complète passait de 20 s à plus de
10 minutes. Le rapport coût/valeur n'y est pas.

Ce qui reste automatisé dans `e2e/tests/issue_265_admin_socle.spec.js` est ce qui
est rapide **et** stable : la garde d'accès et le comportement de la grille
générique (recherche, tri, sélection). Le reste est ici.

## Préparation

```bash
docker compose down; docker compose up -d --build      # mode home server
```

Se connecter en administrateur, puis ouvrir **https://biggyben.freeboxos.fr/admin/**

> En local : `npm run e2e:up` puis http://localhost/admin/

## Cas transverses — à rejouer à chaque lot

| # | Cas | Attendu |
|---|-----|---------|
| T1 | Ouvrir `/admin/` **sans être connecté** | Redirection vers la page de connexion, message « profil suffisant » |
| T2 | Ouvrir `/admin/` connecté en **responsable d'équipe** (non admin) | Même redirection |
| T3 | Réduire la fenêtre à une largeur mobile | Menu burger, tableau qui défile dans son conteneur, **pas** de défilement horizontal de la page |
| T4 | Cliquer « Ancienne administration » | Arrive sur `admin.php`, l'admin ExtJS fonctionne toujours |
| T5 | Cliquer « Retour au site » | Arrive sur la home publique |

## Cas génériques — à rejouer sur **chaque** écran

La grille est un composant unique : un défaut vu sur un écran vaut pour tous.

| # | Cas | Attendu |
|---|-----|---------|
| G1 | L'écran s'ouvre | Les lignes se chargent, le compteur « X / Y » est cohérent, aucun bandeau rouge |
| G2 | Taper un terme dans la recherche | Le compteur diminue, les lignes correspondent |
| G3 | Taper `terme1, terme2` | Les lignes de **l'un ou l'autre** remontent (OU, pas ET) |
| G4 | Vider la recherche | Le compteur revient au total |
| G5 | Cliquer un en-tête de colonne, puis à nouveau | Tri croissant, puis décroissant |
| G6 | Changer « par page » | La pagination suit, le pied de page indique la bonne page |
| G7 | Cocher une ligne | « Éditer » et « Supprimer » s'activent |
| G8 | Cocher la case d'en-tête | Toutes les lignes de la page en cours sont cochées |
| G9 | Cliquer « Export » | Un CSV se télécharge, s'ouvre dans Excel **avec les accents corrects**, contient les lignes filtrées |
| G10 | Cliquer le bouton de rafraîchissement | Les données rechargent, la sélection est vidée |

## Cas par écran

### Utilisateurs (`#/users`)

| # | Cas | Attendu |
|---|-----|---------|
| U1 | Créer un compte avec seulement un email | Créé ; le login vaut l'email (issue #247) |
| U2 | Éditer un compte, changer l'email | Modification visible après rechargement |
| U3 | Sélectionner un compte → « Réinitialiser le mot de passe » | Confirmation demandée, puis message de succès ; **l'email arrive dans Mailpit** (`/mailpit`) |
| U4 | Supprimer un compte de test | Disparaît de la liste |
| U5 | Vérifier la colonne « Admin » | « oui » pour les administrateurs, « non » sinon |

> **Attention** : ne pas supprimer ni réinitialiser un compte réel. Créer un
> compte de test (`zz_test@…`) et travailler dessus.

### Clubs (`#/clubs`)

| # | Cas | Attendu |
|---|-----|---------|
| C1 | Créer un club (tous champs obligatoires remplis) | Créé, visible dans la liste |
| C2 | Créer un club en laissant un champ obligatoire vide | Le navigateur bloque l'envoi |
| C3 | Éditer le club, changer le prénom du responsable | Modification visible |
| C4 | Supprimer le club de test | Le compteur revient à sa valeur de départ |

### Équipes (`#/teams`)

| # | Cas | Attendu |
|---|-----|---------|
| E1 | Ouvrir la fenêtre de création | Les listes **Club** et **Compétition** sont remplies |
| E2 | Créer une équipe | Créée, avec le bon club et la bonne compétition |
| E3 | Cocher « Inscrite à la coupe » puis enregistrer | La colonne « Coupe » passe à « oui » |
| E4 | Supprimer l'équipe de test | Disparaît |

> Non repris du lot 0 : l'action **« Nommer responsable »** de la grille ExtJS.
> À traiter dans un lot ultérieur — en attendant, elle reste disponible dans
> l'ancienne administration.

### Joueurs (`#/players`)

| # | Cas | Attendu |
|---|-----|---------|
| J1 | Ouvrir l'écran | ~3 650 joueurs ; **le chargement prend ~8 s**, c'est connu |
| J2 | Cocher « Sans licence » | Le compteur tombe (~900) |
| J3 | Cocher « Sans club » **en plus** | Les filtres se cumulent, le compteur diminue encore |
| J4 | Décocher tout | Le compteur revient au total |
| J5 | Éditer un joueur, changer son club | La liste des clubs est remplie, la modification est visible |

> Non repris du lot 0 : **import d'un fichier de licences**, **association en
> masse à un club / une équipe**, **photo du joueur**. Restent dans l'ancienne
> administration.

### Gymnases (`#/gymnasiums`)

| # | Cas | Attendu |
|---|-----|---------|
| Y1 | Créer un gymnase | Créé ; « Nombre de terrains » n'accepte que 1 à 6 |
| Y2 | Éditer, changer la ville | Modification visible |
| Y3 | Supprimer le gymnase de test | Le compteur revient à sa valeur de départ |

## Après la recette

Vérifier qu'**aucune donnée de test ne subsiste** : rechercher `ZZ` ou le préfixe
utilisé sur chaque écran touché.
