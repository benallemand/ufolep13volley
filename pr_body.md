## Description
Résout #188

Implémentation de l'édition en ligne de la table News via l'interface d'administration ExtJS.

## Changements

### Backend (PHP)
- `News::getAllNews()` - Récupérer toutes les news (y compris désactivées)
- `News::saveNews()` - Créer/modifier une news
- `News::deleteNews()` - Supprimer une news

### Frontend (ExtJS)
- `js/model/News.js` - Modèle de données News
- `js/store/AdminNews.js` - Store pour charger les news
- `js/view/news/AdminGrid.js` - Grille avec édition en ligne (RowEditing)
- Menu "Gestion des news" dans administration.js
- Méthodes controller: showNewsGrid, addNews, deleteNews, saveNews

## Tests
- [x] 7 tests unitaires ajoutés
- [x] Tous les tests passent

## Fonctionnalités
- [x] L'admin peut voir toutes les news (actives et désactivées)
- [x] L'admin peut ajouter une nouvelle news
- [x] L'admin peut modifier une news existante en ligne
- [x] L'admin peut supprimer une news
- [x] L'admin peut activer/désactiver une news

## Sécurité
- Seuls les admins peuvent créer/modifier/supprimer les news
- Vérification du profil dans les méthodes PHP
