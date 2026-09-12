# Recette manuelle — pages publiques et espace responsable

Pendant de `admin/RECETTE.md`, qui ne couvre que l'administration. Ce document
recense les cas à rejouer sur la page d'accueil et sur l'espace responsable
(`/pages/my_page.html`).

## Préparation

Stack en mode home server, sur `https://biggyben.freeboxos.fr` :

```bash
docker compose up -d --build
```

Le code est servi **depuis l'image** : toute modification demande un `--build`.

---

## Calendriers (issue #290)

### C1 — Timeline de l'agenda, page d'accueil

Sur `/pages/home.html`, faire défiler jusqu'à « calendrier {saison} ».

| À vérifier | Attendu |
|---|---|
| Titre | la saison courante, pas l'année civile ; de janvier à juin c'est la saison ouverte en septembre précédent |
| Axe | dix mois, de septembre à juin, en français et abrégés |
| Périodes | une **barre** par période, à cheval sur les mois qu'elle couvre |
| Libellés répétés | « Championnats » et « Vacances » reviennent plusieurs fois dans la saison : ils doivent partager **une seule ligne** |
| Rendez-vous ponctuels | des **losanges**, tous regroupés sur la ligne « Réunions et rendez-vous » |
| Repère du jour | trait rouge vertical à la date du jour — **absent** en juillet et en août, hors saison |
| Survol | le détail apparaît : libellé, heure s'il y en a, dates de début et de fin |
| Ordre des lignes | chronologique, par première occurrence — pas alphabétique |
| Agenda vide | message « Aucun événement au calendrier pour cette saison », pas un cadre vide |

Sur téléphone (ou fenêtre étroite) : la timeline **défile horizontalement**, elle
ne se comprime pas. Le reste de la page ne doit pas défiler latéralement.

### C2 — Calendrier des matchs, espace responsable

Se connecter en responsable d'équipe, puis menu **matchs → calendrier**.

| À vérifier | Attendu |
|---|---|
| Vue par défaut | « Mois », ouverte sur le mois courant — ou sur septembre si le jour est hors saison |
| Vue « Saison » | les dix mois d'un coup |
| Vue « À venir » | liste chronologique des trois prochains mois |
| Sources | deux cases : mes matchs (vert), matchs du club (orange) ; décocher retire les matchs correspondants et met à jour le compteur |
| Agenda | **aucun** événement de commission ici : il n'est affiché que par la timeline, en dessous |
| Doublons | un match de **mon** équipe ne doit apparaître **qu'une fois**, en vert — `getMyClubMatches` le renvoie aussi, il est dédoublonné |
| Heures | comparer trois heures affichées aux `heure_reception` en base : elles doivent être **identiques**, sans décalage d'une heure |
| Match sans heure | s'affiche en « journée entière », **jamais** à 00:00 |
| Clic sur un match | ouvre `/match.html?id_match=…` |
| Week-ends | masqués tant qu'aucun événement d'un seul jour n'en occupe un ; ils réapparaissent dès qu'un rendez-vous est posé un samedi ou un dimanche |

### C3 — Responsable de club sans équipe propre

Cas particulier à ne pas oublier : un compte responsable de club **sans** ligne
dans `users_teams`. `getMesMatches` peut alors échouer légitimement.

- Le calendrier doit tout de même afficher les matchs du club.
- Aucun message d'erreur ne doit s'afficher pour l'appel « mes matchs ».

### C4 — Cloisonnement

Depuis un compte responsable, vérifier que le calendrier n'affiche **que** les
matchs de son équipe et de son club — aucun match d'un autre club. Le périmètre
est décidé côté serveur (session), pas par le front.

### C5 — La page d'accueil reste publique

Déconnecté, sur `/pages/home.html` : la timeline de l'agenda s'affiche, et
**aucun match nominatif** n'apparaît.

---

## Effectif figé en Coupe Khoury Hanna (issue #32)

### K1 — Avant le premier match signé

Responsable d'une équipe **KH** dont aucune fiche n'est encore signée, écran
**effectif** : la recherche « Ajouter un joueur existant », le bouton
**créer…** et l'import PDF sont présents, et l'ajout fonctionne.

### K2 — Après signature de la fiche

Même écran, pour une équipe KH ayant signé la fiche d'un de ses matchs :

| À vérifier | Attendu |
|---|---|
| Bandeau | « Effectif figé pour cette compétition », avec le code du match et sa date |
| Actions d'ajout | recherche, **créer…** et import PDF **absents** |
| Liste de l'effectif | toujours affichée, les rôles (responsable, suppléant, capitaine) restent modifiables |
| Refus serveur | forcer l'appel `player/addPlayerToMyTeam` doit répondre **403** avec le message, pas 500 |

### K3 — Les autres compétitions ne sont pas touchées

Basculer sur une équipe de **championnat** du même club : l'ajout reste
possible, aucun bandeau. La règle ne vaut que pour la Khoury Hanna.

### K4 — La phase finale verrouille aussi

Une équipe dont la seule fiche signée l'a été sur un match **`kf`** doit être
figée : les matchs de phase finale réutilisent les identifiants d'équipe `kh`.

### K5 — Dérogation par la commission

Depuis l'administration, associer un joueur à une équipe KH figée : l'ajout
**passe**. Vérifier ensuite dans l'écran **Activité** la trace
« Ajout DEROGATOIRE de … (effectif fige depuis le match …) ».
