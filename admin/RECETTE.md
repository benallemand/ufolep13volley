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
| G11 | Sur un écran à date (`type: 'date'`), éditer une ligne existante | Le sélecteur de date **est prérempli** avec la date de la ligne — l'API parle en `jj/mm/aaaa`, l'`<input type="date">` en `aaaa-mm-jj`, la conversion se fait dans les deux sens |
| G12 | Enregistrer, puis regarder la grille | La date affichée est celle saisie, au format `jj/mm/aaaa` |

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

### Matchs (`#/matches`)

L'écran le plus fourni. Les 10 cas génériques s'appliquent, plus :

| # | Cas | Attendu |
|---|-----|---------|
| M1 | Ouvrir l'écran | ~560 matchs sur ~939 (vue « Saison en cours » par défaut) |
| M2 | Colonne **Liens**, première icône (ballon) | Ouvre `match.html` dans un nouvel onglet ; **verte** si les deux feuilles de match sont signées, rouge sinon |
| M3 | Deuxième icône (personnage) | Ouvre `team_sheets.html` ; verte si présents renseignés **et** fiches signées des deux côtés |
| M4 | Troisième icône (sondage) | Ouvre `survey.html` ; verte si les deux sondages sont remplis |
| M5 | Quatrième icône (enveloppe) | Ouvre le client mail avec les deux responsables en destinataires |
| M6 | Colonne **Statut** | Pastille verte « confirmé », orange « à confirmer », grise « archivé » |
| M7 | Changer de **Vue** | Les six vues filtrent : saison, prêts à valider, présents à renseigner, joueurs non valides, non certifiés, archivés. Saison + archivés doivent redonner le total |
| M8 | Vue « Prêts à valider » | **Peut légitimement afficher 0** : la règle exige un match non certifié, et en fin de saison tout est certifié |
| M9 | Sélectionner un match → **Archiver** | Confirmation, puis le match bascule en « archivé » |
| M10 | Sélectionner → **Confirmer** / **Dé-confirmer** | Le statut change en conséquence |
| M11 | Sélectionner → **Certifier** | Le match passe certifié |
| M12 | Sélectionner → **Inverser** | Domicile et extérieur permutent |
| M13 | Éditer un match | Listes Compétition, Domicile, Extérieur et Gymnase remplies ; les cases de signature reflètent l'état |

> Les actions d'écriture M9 à M12 modifient de vraies données : les jouer sur un
> match de test, ou en connaissance de cause.
>
> **La génération de matchs n'est pas reprise** : scripts Python
> (`ufolep13volley_python/calendar-agent/`).

### Compétitions (`#/competitions`)

| # | Cas | Attendu |
|---|-----|---------|
| P1 | Créer une compétition | Créée ; « Matchs aller-retour » se coche |
| P2 | Éditer les dates (jj/mm/aaaa) | Modification visible dans la grille |
| P3 | Supprimer la compétition de test | Le compteur revient à sa valeur de départ |
| P4 | Sélectionner une compétition **de test**, cliquer « Initialiser la saison », lire la confirmation | La confirmation nomme la compétition et annonce l'archivage des matchs, la suppression des comptes responsables et des créneaux |
| P5 | Annuler la confirmation | Rien ne se passe |

> **P4/P5 : ne jamais confirmer sur une compétition réelle.** `set_up_season`
> archive les matchs en cours, supprime les comptes responsables et les créneaux,
> puis les recrée depuis les engagements. C'est l'action la plus destructive de
> l'administration. Elle n'était joignable que par `matchmgr/generateAll`, parti
> avec le moteur de génération (#279).

> Les actions de **génération** du menu ExtJS (matchs, phases finales, palmarès)
> ne sont **pas** reprises : elles passent par les scripts Python du dépôt
> `ufolep13volley_python` (`calendar-agent/`). Le moteur PHP a été supprimé (#279),
> ainsi que l'écran **Journées** : la notion n'existe plus en base.

### Divisions / poules (`#/ranks`)

| # | Cas | Attendu |
|---|-----|---------|
| R1 | Ouvrir l'écran | ~216 engagements |
| R2 | Ouvrir la création | Listes **Compétition** (9) et **Équipe** (~280) remplies |
| R3 | Éditer un engagement, changer le classement initial | Modification visible |

> La **réorganisation par glisser-déposer** (issue #189) n'est pas reprise :
> c'est un écran à part, pas une grille. Reste dans l'ancienne administration.

### Dates limites (`#/limit-dates`)

| # | Cas | Attendu |
|---|-----|---------|
| L1 | Ouvrir l'écran | Une ligne par compétition ayant une date limite |
| L2 | Créer une date limite | Liste des compétitions remplie |
| L3 | Éditer la date (jj/mm/aaaa) | Modification visible |

### Planning de la semaine (`#/week-schedule`)

| # | Cas | Attendu |
|---|-----|---------|
| W1 | Ouvrir l'écran | ~295 créneaux, gymnase / jour / heure / équipe |
| W2 | Vérifier la barre d'outils | **Pas** de bouton Créer / Éditer / Supprimer, **pas** de colonne de cases à cocher — écran de consultation |
| W3 | Trier par gymnase, puis rechercher une équipe | Fonctionnent comme sur les autres écrans |

### Gymnases (`#/gymnasiums`)

| # | Cas | Attendu |
|---|-----|---------|
| Y1 | Créer un gymnase | Créé ; « Nombre de terrains » n'accepte que 1 à 6 |
| Y2 | Éditer, changer la ville | Modification visible |
| Y3 | Supprimer le gymnase de test | Le compteur revient à sa valeur de départ |

### Dates interdites (`#/blacklist-dates`) — lot 3

| # | Cas | Attendu |
|---|-----|---------|
| B1 | Ouvrir l'écran | **Peut être vide** : la table `blacklist_date` n'est remplie qu'avant une génération de calendrier |
| B2 | Créer une date | Le sélecteur de date s'ouvre ; la ligne apparaît au format `jj/mm/aaaa` |
| B3 | Éditer la date, la changer | Le sélecteur est prérempli, la modification est visible dans la grille |
| B4 | Supprimer la date de test | Le compteur revient à sa valeur de départ |

### Fermetures de gymnase (`#/gymnasium-closures`) — lot 3

| # | Cas | Attendu |
|---|-----|---------|
| F1 | Ouvrir l'écran | Une ligne par fermeture, gymnase nommé « Nom (ville) » |
| F2 | Ouvrir la création | Liste **Gymnase** remplie (~73), libellés « Ville - Nom - Adresse » |
| F3 | Créer une fermeture, puis la supprimer | Créée puis retirée ; le compteur revient à sa valeur de départ |

> Le même besoin existe côté **responsable de club**, dans son espace : cet écran
> est la vue d'ensemble de l'administrateur. `saveBlacklistGymnase` porte le
> contrôle de périmètre, qu'un admin traverse.

### Indisponibilités d'équipe (`#/team-unavailabilities`) — lot 3

| # | Cas | Attendu |
|---|-----|---------|
| I1 | Ouvrir l'écran | **Peut être vide** ; équipe nommée « Nom (code compétition) » |
| I2 | Ouvrir la création | Liste **Équipe** remplie (~280), libellés « Équipe (club) - Compétition(division) » |
| I3 | Créer puis supprimer une indisponibilité | Le compteur revient à sa valeur de départ |

### Équipes incompatibles (`#/incompatible-teams`) — lot 3

| # | Cas | Attendu |
|---|-----|---------|
| N1 | Ouvrir l'écran | Une ligne par contrainte, deux colonnes d'équipes |
| N2 | Ouvrir la création | **Deux** listes d'équipes, remplies toutes les deux |
| N3 | Créer une contrainte, la vérifier dans la grille, la supprimer | Les deux libellés d'équipe s'affichent ; le compteur revient à sa valeur de départ |

### Ententes entre clubs (`#/club-friendships`) — lot 3

| # | Cas | Attendu |
|---|-----|---------|
| E1 | Ouvrir l'écran | **Peut être vide** ; deux colonnes de clubs |
| E2 | Ouvrir la création | **Deux** listes de clubs remplies (~45) |
| E3 | Créer une entente, la vérifier, la supprimer | Les deux noms de clubs s'affichent ; le compteur revient à sa valeur de départ |

### Périodes interdites par ville (`#/city-closures`) — lot 3

| # | Cas | Attendu |
|---|-----|---------|
| V1 | Ouvrir l'écran | **Peut être vide** ; colonnes Ville / Du / Au |
| V2 | Ouvrir la création | Liste **Ville** remplie (~42, villes des gymnases), **deux** sélecteurs de date |
| V3 | Créer une période, la vérifier, la supprimer | Les deux dates s'affichent en `jj/mm/aaaa` ; le compteur revient à sa valeur de départ |

> Écran créé pendant le COVID pour neutraliser une commune entière. Repris à
> l'identique : c'est le seul écran de planification qui porte une période et
> non une date isolée.

## Après la recette

Vérifier qu'**aucune donnée de test ne subsiste** : rechercher `ZZ` ou le préfixe
utilisé sur chaque écran touché.
