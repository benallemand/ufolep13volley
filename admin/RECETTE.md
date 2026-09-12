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
| T4 | Ouvrir `/admin.php` | **Redirige vers `/admin/`** — l'ancienne administration ExtJS a été supprimée au lot 6, l'URL historique est conservée en redirection |
| T5 | Cliquer « Retour au site » | Arrive sur la home publique |
| T6 | Cliquer « Validation des matchs » dans le menu *Compétitions* | Arrive sur `/admin/matches.html`, la liste des matchs à certifier se charge |
| T7 | Depuis cette page, cliquer « Administration » | Revient sur `/admin/` |

> T6/T7 : `admin/matches.html` est une entrée Vite **à part**, pas une route de
> la SPA. Sa seule porte d'entrée était la barre d'outils d'`admin.php`, et le
> lot 6 l'a emportée avec elle : l'écran est resté joignable par son URL mais
> plus aucun lien n'y menait. Repéré par Benjamin en recette.

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
| G13 | Sur un écran à case à cocher, cocher puis enregistrer, puis rouvrir | La case est **restée cochée**. Avant #265 lot 4, elle repartait toujours à zéro : le formulaire postait `1` et le PHP comparait strictement à `'on'` |
| G14 | Décocher, enregistrer, rouvrir | La case est restée décochée |
| G15 | Sélectionner une ligne, puis **taper une recherche** qui la masque | La sélection est **vidée** et les boutons d'action se désactivent. Sans ça une action s'appliquerait à une ligne invisible (#288) |
| G16 | Trier une colonne de **date** (homologation, réception, fermeture…) | Tri **chronologique**, pas alphabétique. Le piège : `02/12/2025` doit venir **après** `15/11/2025`, alors qu'en texte il passait avant (#296) |
| G17 | Trier une colonne de date dans l'autre sens | L'ordre s'inverse exactement |
| G18 | Sur un écran à colonne image (joueurs), faire défiler la liste | Les vignettes se chargent **au fur et à mesure** (`loading="lazy"`), pas toutes d'un coup ; une photo absente affiche l'image de repli, jamais une icône cassée (#295) |
| G19 | Même écran, **console du navigateur ouverte** | **Aucun 404** sur `players_pics_low/`. La vignette est déduite du chemin plein par un `REPLACE` SQL et n'existe pas toujours : le serveur se rabat alors sur la photo pleine. Sans ce repli, la console s'emplissait de 404 |
| G20 | Sur **dates limites, matchs, équipes, commission**, créer une ligne puis en éditer une | L'enregistrement **aboutit** dans les deux cas. Ces quatre écrans déclarent un identifiant non standard (`id_date`, `id_match`, `id_equipe`, `id_commission`) : le formulaire doit poster CE nom-là. Sinon 500 — ou, pour commission, un **doublon** au lieu d'une mise à jour (#299) |

## Cas par écran

### Utilisateurs (`#/users`) — complété par #288

| # | Cas | Attendu |
|---|-----|---------|
| U1 | Créer un compte avec seulement un email | Créé ; le login vaut l'email (issue #247) |
| U2 | Éditer un compte, changer l'email | Modification visible après rechargement |
| U3 | Sélectionner un compte → « Réinitialiser le mot de passe » | Confirmation demandée, puis message de succès ; **l'email arrive dans Mailpit** (`/mailpit`) |
| U4 | Supprimer un compte de test | Disparaît de la liste |
| U5 | Vérifier la colonne « Admin » | « oui » pour les administrateurs, « non » sinon |
| U5a | Sélectionner un compte **non admin** | Le bouton annonce « Donner le rôle administrateur » |
| U5b | Cliquer, confirmer, laisser la liste se recharger | La colonne « Admin » passe à « oui » et l'écran Activité journalise « … a obtenu le rôle administrateur ». Le rechargement **vide la sélection** : le bouton redevient neutre et grisé |
| U5c | Resélectionner le compte | Le bouton annonce maintenant « Retirer le rôle administrateur » |
| U5c bis | Cliquer, confirmer | Retour à « non », journalisé « a perdu » |
| U5d | Sélectionner **son propre compte** et tenter de retirer le rôle | **Refusé** avec un message explicite ; la colonne reste à « oui » |
| U7 | Sélectionner un compte → « Équipes liées… » | Fenêtre avec les ~279 équipes, **les équipes actuelles déjà cochées** |
| U8 | Décocher une équipe, enregistrer, rouvrir | L'état est conservé |
| U9 | Sélectionner un compte → « Clubs liés… » | Idem avec les ~45 clubs |

> **U5d se vérifie côté serveur.** Le bouton se laisse cliquer — c'est
> volontaire : le refus doit venir de l'API, sinon la console d'un navigateur
> suffirait à le contourner. Sans ce garde-fou, un dernier administrateur qui
> se rétrograde n'a plus aucun moyen de revenir depuis l'application.

> **U7-U9 changent les droits du compte**, pas seulement un affichage : une
> ligne `users_teams` fait un responsable d'équipe, une ligne `users_clubs` un
> responsable de club (#245). Vérifier que les cases arrivent **préremplies** :
> sans ça, enregistrer détacherait tout.

> **Attention** : ne pas supprimer ni réinitialiser un compte réel. Créer un
> compte de test (`zz_test@…`) et travailler dessus.

### Clubs (`#/clubs`)

| # | Cas | Attendu |
|---|-----|---------|
| C1 | Créer un club (tous champs obligatoires remplis) | Créé, visible dans la liste |
| C2 | Créer un club en laissant un champ obligatoire vide | Le navigateur bloque l'envoi |
| C3 | Éditer le club, changer le prénom du responsable | Modification visible |
| C4 | Supprimer le club de test | Le compteur revient à sa valeur de départ |

### Équipes (`#/teams`) — complété par #288

| # | Cas | Attendu |
|---|-----|---------|
| E1 | Ouvrir la fenêtre de création | Les listes **Club** et **Compétition** sont remplies |
| E2 | Créer une équipe | Créée, avec le bon club et la bonne compétition |
| E3 | Cocher « Inscrite à la coupe » puis enregistrer | La colonne « Coupe » passe à « oui » |
| E4 | Supprimer l'équipe de test | Disparaît |

> Non repris du lot 0 : l'action **« Nommer responsable »** de la grille ExtJS.
> À traiter dans un lot ultérieur — en attendant, elle reste disponible dans
> l'ancienne administration.

### Joueurs (`#/players`) — complété par #288

| # | Cas | Attendu |
|---|-----|---------|
| J1 | Ouvrir l'écran | ~3 650 joueurs ; **le chargement prend ~8 s**, c'est connu |
| J2 | Cocher « Sans licence » | Le compteur tombe (~900) |
| J3 | Cocher « Sans club » **en plus** | Les filtres se cumulent, le compteur diminue encore |
| J4 | Décocher tout | Le compteur revient au total |
| J5 | Éditer un joueur, changer son club | La liste des clubs est remplie, la modification est visible |
| J6 | Cocher « Dans 2 équipes (même compétition) » | ~18 joueurs — ceux engagés deux fois dans la même compétition, ce qui est irrégulier |
| J7 | **Créer** un joueur | Créé. `savePlayer` déclarait 15 paramètres obligatoires et le formulaire en envoyait 8 : la création échouait en 500 depuis le lot 1 (#288) |
| J8 | Éditer un joueur, choisir une **photo**, enregistrer | La photo est enregistrée dans `players_pics/` et sa vignette dans `players_pics_low/` |
| J9 | Rouvrir le joueur sans toucher au champ photo, enregistrer | La photo précédente est **conservée** |
| J10 | Sélectionner des joueurs → « Associer à un club » | Fenêtre de sélection avec recherche ; après validation, la colonne Club est à jour |
| J11 | Idem → « Associer à une équipe » | Le joueur est rattaché à l'équipe, et au club de l'équipe si besoin |
| J12 | Cliquer « Importer un fichier de licences », choisir le PDF UFOLEP | Les joueurs existants sont mis à jour, les nouveaux créés. **Compter jusqu'à une minute** |

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

### Créneaux (`#/timeslots`) — #288

| # | Cas | Attendu |
|---|-----|---------|
| N1 | Ouvrir l'écran | ~295 créneaux : équipe, gymnase, jour, heure, contrainte horaire, priorité |
| N2 | Ouvrir la création | Équipe (~279) et Gymnase (~73) remplis ; **Jour** limité à lundi-vendredi ; **Heure** de 18:00 à 21:45 par quart d'heure |
| N3 | Créer un créneau, l'éditer, le supprimer | Cycle complet |
| N4 | Vérifier la priorité d'utilisation | Entier ≥ 1 ; 1 = créneau principal quand une équipe en a plusieurs |

> Écran **oublié** par la migration : l'admin ExtJS avait *Gestion des créneaux*
> (ce CRUD) **et** *Planning de la semaine* (la consultation), et l'inventaire
> du lot 6 les avait confondues.
>
> Les scripts Python de génération lisent `creneau` pour placer les matchs, et
> `matchs_view` en tire l'heure de réception : une erreur ici se propage au
> calendrier.

### Compétitions (`#/competitions`)

| # | Cas | Attendu |
|---|-----|---------|
| P1 | Créer une compétition | Créée ; « Matchs aller-retour » se coche |
| P2 | Éditer les dates (jj/mm/aaaa) | Modification visible dans la grille |
| P3 | Supprimer la compétition de test | Le compteur revient à sa valeur de départ |
| P6 | Sélectionner une compétition **non commencée**, cliquer « Remettre les points à zéro », lire la confirmation | Elle nomme la compétition et précise que matchs et engagements ne sont pas touchés |
| P7 | Confirmer sur une compétition **déjà commencée** | Refusé par le backend (`isCompetitionStarted`), message d'erreur |
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

### News (`#/news`) — lot 4

| # | Cas | Attendu |
|---|-----|---------|
| S1 | Ouvrir l'écran | 19 news, la colonne *Texte* est tronquée, *Publiée* en badge vert / gris |
| S2 | Créer une news avec le sélecteur de date | La date s'affiche en **ISO** (`aaaa-mm-jj`) dans la grille : cette colonne-là est stockée en ISO, contrairement aux autres écrans |
| S3 | Cocher « Désactivée », enregistrer | *Publiée* passe à **non** ; la news disparaît de la home |
| S4 | Rouvrir la news | La case « Désactivée » est **cochée** |
| S5 | Décocher, enregistrer | *Publiée* repasse à **oui** |
| S6 | Supprimer la news de test | Disparaît. La suppression est unitaire (`deleteNews($id)`) : sélectionner plusieurs lignes enchaîne les appels |

### Calendrier de la home (`#/calendar-events`) — lot 4

| # | Cas | Attendu |
|---|-----|---------|
| K1 | Ouvrir l'écran | 45 événements ; *Début* / *Fin* en `jj/mm/aaaa hh:mm`, ou **sans heure** quand l'heure est 00:00 (journée entière), et `— (ponctuel)` quand la fin est vide |
| K2 | Filtrer par saison | Seuls les événements de la saison restent |
| K3 | Créer un événement avec une saison au mauvais format (`2026`) | Le navigateur refuse : le format `aaaa-aaaa` est exigé |
| K4 | Créer un événement à 20:30, fin vide | Créé, *Fin* affiche `— (ponctuel)` |
| K5 | Éditer un événement « journée entière » | Le champ de début est prérempli à `T00:00` — ne pas y mettre d'heure, c'est ce qui fait qu'aucune heure ne s'affiche sur la home |
| K6 | Supprimer l'événement de test | Le compteur revient à sa valeur de départ |

### Emails (`#/emails`) — lot 4

| # | Cas | Attendu |
|---|-----|---------|
| M1 | Ouvrir l'écran | Les **500 derniers** emails, les plus récents en tête ; *Statut* en badge (vert `DONE`, orange `TO_DO`, rouge `ERROR`) ; la colonne *Contenu* montre du texte, pas du HTML |
| M2 | Vérifier la barre d'outils | **Pas** de Créer / Éditer / Supprimer, **pas** de cases à cocher — écran de consultation |
| M3 | Passer le sélecteur à 2000 | La grille recharge et le compteur suit |
| M4 | Passer à « tous » | Charge les ~6 000 emails. **Plusieurs Mo** : c'est lent, c'est normal, c'est la raison de la fenêtre par défaut |
| M5 | Cliquer « Relancer les erreurs » et confirmer | Les emails en `ERROR` repassent en `TO_DO` (aucun s'il n'y en a pas) |
| M6 | Cliquer « Récap créneaux » et confirmer | Un email par équipe est **inséré en file** ; les vérifier dans Mailpit après le passage du cron |
| M7 | **Cliquer une ligne** | Le message s'ouvre en **rendu HTML**, avec expéditeur, destinataires, dates et statut au-dessus (#288) |
| M8 | Vérifier le rendu | Il s'affiche dans une `iframe` sandboxée : ni script ni image distante ne s'exécute — un corps d'email est du HTML arbitraire |

> **M6 déclenche de vrais envois en production.** À ne jouer en recette que sur
> le conteneur local, où tout part dans Mailpit.

### Sondages (`#/surveys`) — lot 4

| # | Cas | Attendu |
|---|-----|---------|
| Q1 | Ouvrir l'écran | ~1 800 sondages renseignés ; la colonne *Match* compose `code (dom vs ext)` |
| Q2 | Cocher « Afficher aussi les sondages non renseignés » | Le total augmente : une ligne est créée dès qu'un sondage est ouvert, même sans réponse |
| Q3 | Vérifier la barre d'outils | Écran de consultation, aucune action d'écriture |

### Commission (`#/commission`) — lot 4

| # | Cas | Attendu |
|---|-----|---------|
| C1 | Ouvrir l'écran | 8 membres, colonne *Divisions attribuées* |
| C2 | Créer un membre, l'éditer, le supprimer | Cycle complet ; l'identifiant est `id_commission` |
| C3 | Sélectionner un membre, cliquer « Attribuer les divisions », saisir `m/1,f/2` | Les divisions apparaissent dans la colonne *Divisions attribuées* |
| C4 | Rejouer avec une saisie vide | Toutes les divisions du membre sont retirées |

> Le format attendu est `code_competition/division` — c'est ce que joignent
> `matchs_view` et `Commission::getByDivision`. Le prompt ExtJS suggérait
> `d1m,d2f`, qui ne correspondait à rien.

### Base de registres (`#/registry`) — lot 4

| # | Cas | Attendu |
|---|-----|---------|
| G1r | Ouvrir l'écran | ~210 entrées clé / valeur |
| G2r | Créer, éditer, supprimer une entrée `zz.test` | Cycle complet |

> **Écran à manipuler avec précaution** : le tirage au sort des phases finales
> vit ici, et `generate_huitiemes.py` le relit. Modifier une clé
> `finals.*` à la main peut casser une génération.

### Inscriptions (`#/registrations`) — lot 4

| # | Cas | Attendu |
|---|-----|---------|
| R1r | Ouvrir l'écran | Une ligne par demande ; *Statut* en badge (orange « en attente », vert « validée le … ») |
| R2r | Filtrer par statut | La liste suit |
| R3r | Éditer une demande, renseigner division et rang, enregistrer | Les deux valeurs apparaissent — **et rien d'autre n'a bougé** : club, compétition, responsable, créneaux sont transportés en champs cachés |
| R4r | Sélectionner une demande, cliquer « Valider » | Statut *validée le …*, et un email de notification est mis en file pour le club |
| R5r | Cliquer « Dévalider » | Retour à *en attente*, date de validation vidée |
| R6r | Cliquer « Divisions / rangs » | Les divisions et rangs sont calculés pour les demandes sélectionnées |
| R7r | Cliquer « Équipes / comptes » | Les équipes et les comptes responsables sont créés |

> **R4r/R5r ne fonctionnaient plus depuis le refus par défaut (#272)** :
> `validateRegistration` n'était pas déclaré dans `rest/access.php` et
> répondait « Action inconnue ». Corrigé dans ce lot — c'est le cas à vérifier
> en premier.
>
> **R7r est irréversible** : ne le jouer que sur le conteneur local.

### Indicateurs (`#/indicators`) — lot 5

Le seul écran qui n'est pas une grille : les cas génériques G1-G14 ne s'y
appliquent pas.

| # | Cas | Attendu |
|---|-----|---------|
| X1 | Ouvrir l'écran | Les tuiles apparaissent **progressivement** (six requêtes en vol), le compteur « n / 47 calculés » avance, des tuiles grises marquent ce qui reste. Compter ~30 s pour les 47 |
| X2 | Regarder le résultat | Une vingtaine de tuiles : **les alertes en rouge d'abord**, puis les informations en bleu, par valeur décroissante |
| X3 | Vérifier qu'aucune tuile n'affiche 0 | Un indicateur à zéro **n'est pas affiché** — le tableau de bord ne montre que ce sur quoi il y a à faire |
| X4 | Cocher « Alertes seulement » | Seules les tuiles rouges restent |
| X5 | Rechercher `joueurs` | Seuls les indicateurs dont le libellé contient le terme restent |
| X6 | Cliquer une tuile | Fenêtre avec le détail en tableau, une colonne par champ de la requête |
| X7 | Cliquer « Export » dans la fenêtre | Un CSV se télécharge, **accents corrects dans Excel** (BOM UTF-8) |
| X8 | Cliquer le bouton de rafraîchissement | Tout est recalculé depuis zéro |

> `ajax/indicators.php` n'est pas sous `rest/` : il porte sa propre garde admin
> depuis #284, où il répondait à n'importe qui. Si l'écran affiche « réservés aux
> administrateurs », c'est que la session n'est pas admin.

### Activité (`#/activity`) — lot 5

| # | Cas | Attendu |
|---|-----|---------|
| A1 | Ouvrir l'écran | Les **500 dernières** entrées, les plus récentes en tête ; la description est du texte, pas du HTML |
| A2 | Vérifier la barre d'outils | Écran de consultation : pas de Créer / Éditer / Supprimer, pas de cases à cocher |
| A3 | Passer le sélecteur à 2000, puis « toutes » | La grille recharge ; « toutes » représente ~2,5 Mo et 9 700 lignes, c'est lent, c'est la raison de la fenêtre |

### Palmarès (`#/hall-of-fame`) — lot 5

| # | Cas | Attendu |
|---|-----|---------|
| H1 | Ouvrir l'écran | ~500 titres, colonnes Période / Catégorie / Titre / Équipe |
| H2 | Filtrer par période | La liste se réduit à la saison choisie |
| H3 | Créer, éditer, supprimer un titre de test | Cycle complet |
| H4 | Sélectionner une ou deux lignes, cliquer « Diplômes » | Un PDF **paysage** s'ouvre dans un onglet, une page par titre |
| H5 | Cliquer « Générer depuis les matchs » | Fenêtre avec la compétition (9 choix), deux dates, la période et le type |
| H6 | Générer sur une compétition et une plage de dates réelles | Les titres sont créés et la grille se recharge |

> **H6 écrit dans le palmarès.** À ne jouer qu'en connaissance de cause : la
> génération insère les vainqueurs déduits des matchs de la plage, sans effacer
> ce qui existe. Un double appel crée des doublons.

### Bilan annuel (`#/bilan`) — lot 5

| # | Cas | Attendu |
|---|-----|---------|
| Z1 | Ouvrir l'écran | La saison est préremplie avec la **dernière saison terminée** (une saison finit en juin) |
| Z2 | Saisir `2026` | Message de format ; le bouton reste désactivé |
| Z3 | Cliquer « Charger les chiffres » | Tableau des matchs par compétition avec son total, puis clubs / licenciés / équipes récompensées / coupes, et la liste des coupes décernées |
| Z4 | Compléter les commentaires, cliquer « Télécharger le PDF » | Un PDF s'ouvre dans un onglet |
| Z5 | Vérifier les chiffres du PDF | Ils correspondent à l'aperçu — **`bilanPdf.php` les recalcule côté serveur**, seuls les commentaires viennent du formulaire |

### Agir en tant que — lot 5, sur l'écran Utilisateurs

| # | Cas | Attendu |
|---|-----|---------|
| U4 | Sélectionner un compte, cliquer « Agir en tant que », lire la confirmation | Elle nomme le compte et rappelle que le retour se fait depuis le bandeau du site |
| U5 | Confirmer | Redirection vers la home, la session porte l'identité du compte cible |
| U6 | Cliquer « revenir à mon compte admin » dans le bandeau | Retour au compte administrateur, l'admin est de nouveau accessible |

> L'admin ExtJS en faisait une fenêtre séparée avec sa propre liste de comptes.
> L'action est ici sur l'écran qui les liste déjà.
>
> **Après la bascule, la session n'est plus administratrice** : c'est pour ça que
> l'écran renvoie vers la home et non vers l'admin, qui se refuserait.

### Réorganiser les divisions (`#/divisions`) — lot 6, ferme #189

Écran de manipulation, pas une grille : les cas génériques ne s'y appliquent pas.

| # | Cas | Attendu |
|---|-----|---------|
| D1 | Ouvrir l'écran, choisir une compétition | Une colonne « Non affectées » en jaune, puis une colonne par division, numérotées, avec le compte d'équipes en titre |
| D2 | Glisser une équipe d'une division vers une autre | Elle change de colonne, les numéros de rang se renumérotent des deux côtés, le badge « modifications non enregistrées » apparaît |
| D3 | Glisser une équipe **sur une autre équipe** | Elle s'insère à cette position, pas en fin de colonne |
| D4 | **Sur mobile / tablette** : toucher une équipe, puis toucher une colonne | L'équipe se déplace. Le glisser-déposer natif ne marche pas au doigt, d'où ce second geste |
| D5 | Cliquer « Nouvelle division » | Une colonne vide apparaît, numérotée à la suite |
| D6 | Fermer une colonne **non vide** | Refusé avec un message : il faut la vider d'abord |
| D7 | Fermer une colonne vide | Elle disparaît (rien n'est écrit avant l'enregistrement) |
| D8 | Prendre une équipe **non affectée**, la mettre dans une division, enregistrer | Elle est ajoutée au classement, en dernier rang de la colonne |
| D9 | Prendre une équipe **d'une division**, la remettre dans « Non affectées », enregistrer | **Une confirmation nomme les équipes qui vont perdre leur classement.** En refusant, rien n'est écrit |
| D10 | Confirmer en D9 | La ligne de classement est supprimée, rang de départ compris |
| D11 | Recharger sans enregistrer après des déplacements | Tout revient à l'état en base |

> **D9/D10 suppriment des lignes de classement.** L'écran ExtJS le faisait sans
> rien demander — c'est comme ça qu'on perd un rang de départ sans s'en
> apercevoir. La confirmation nominative est un ajout de cette migration.

## Après la recette

Vérifier qu'**aucune donnée de test ne subsiste** : rechercher `ZZ` ou le préfixe
utilisé sur chaque écran touché.
