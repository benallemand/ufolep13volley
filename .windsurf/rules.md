# Windsurf Rules - UFOLEP13 Volleyball

## Projet
Application web de gestion de championnats de volleyball UFOLEP 13.

## Stack Technique
- **Backend**: PHP 8+, MySQL
- **Frontend**: Vue.js, Tailwind CSS, DaisyUI (client), ExtJS/Sencha (admin)
- **Tests**: PHPUnit (unit_tests/)
- **Déploiement**: GitHub Actions + OVH

## ⚠️ Points Critiques

### Encodage des fichiers
- **IMPORTANT**: Utiliser LF (Unix) et non CRLF (Windows) pour les fins de ligne
- Les fichiers avec CRLF peuvent causer des problèmes lors des éditions
- Configurer l'éditeur pour utiliser LF par défaut

### Scripts SQL de modification de structure
- **Les scripts SQL de modification de la base de données doivent être stockés dans le projet `ufolep13volley_python`**
- Emplacement: `C:\Users\benal\PycharmProjects\ufolep13volley_python\sql\updates\{année}\`
- Convention: `{numéro}-{description}.sql` (ex: `001-add_remarques_to_gymnase.sql`)
- Créer une branche et PR séparée dans `ufolep13volley_python` pour les scripts SQL

## Conventions de Code

### PHP
- Classes dans `classes/`
- Tests unitaires dans `unit_tests/`
- Étendre `UfolepTestCase` pour les tests nécessitant une connexion DB
- Utiliser les variables d'environnement via `.env`

### JavaScript/Vue.js
- Composants dans `js/`
- Utiliser Tailwind CSS et DaisyUI pour le styling

## Commandes Utiles

### Tests PHP
```bash
c:\php\php.exe vendor/bin/phpunit unit_tests/
c:\php\php.exe vendor/bin/phpunit unit_tests/NomDuTest.php
```

### Git
```bash
git checkout master
git pull origin master
git checkout -b fix/issue-{numero}
git push -u origin fix/issue-{numero}
```

## Structure des Tests
Les tests unitaires héritent de `UfolepTestCase` qui fournit:
- `$this->sql` : Instance SqlManager pour accès DB
- `connect_as_admin()` : Simule une session admin
- `connect_as_team_leader($id_equipe)` : Simule une session responsable équipe

## GitHub
- Repository: https://github.com/benallemand/ufolep13volley
- Issues: https://github.com/benallemand/ufolep13volley/issues
- Le token GitHub est stocké dans les variables d'environnement système

### GitHub CLI (`gh`)
- Pour créer une issue via `gh`, rédiger le contenu dans un fichier `.md` temporaire puis utiliser `--body-file <chemin>` (ne pas utiliser `--body` inline)
- Créer le fichier `.md` par écriture directe (outil d'édition/écriture de fichier) plutôt que via des scripts shell complexes
- Supprimer le fichier temporaire à la fin (après création de l'issue)
