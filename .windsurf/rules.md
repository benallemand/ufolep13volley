# Windsurf Rules - UFOLEP13 Volleyball

## Projet
Application web de gestion de championnats de volleyball UFOLEP 13.

## Stack Technique
- **Backend**: PHP 8+, MySQL
- **Frontend**: Vue.js, ExtJS, Tailwind CSS, DaisyUI
- **Tests**: PHPUnit (unit_tests/)
- **Déploiement**: GitHub Actions + OVH

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
