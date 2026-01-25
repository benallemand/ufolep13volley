---
description: Traitement complet d'un ticket GitHub avec TDD, PR, merge et tag
---

# Workflow: Traitement de Ticket GitHub

## Déclenchement
Ce workflow est déclenché lorsqu'un utilisateur demande de traiter un ticket GitHub avec une URL du type:
`https://github.com/benallemand/ufolep13volley/issues/{numero}`

## Prérequis
- GitHub CLI installé: `C:\Program Files\GitHub CLI\gh.exe`
- Authentification configurée: `gh auth login`

## Étapes du Workflow

### 1. Récupération et Compréhension du Ticket
```powershell
# Récupérer les informations du ticket via l'API GitHub
& "C:\Program Files\GitHub CLI\gh.exe" issue view {numero} --repo benallemand/ufolep13volley
```

**Actions:**
- Lire le titre et la description du ticket
- Identifier les labels (bug, feature, enhancement, etc.)
- Comprendre le périmètre: backend (PHP/Python), frontend (JS/Vue), ou les deux
- Résumer le besoin au développeur pour validation

### 2. Création de la Branche
```bash
# S'assurer d'être sur master à jour
git checkout master
git pull origin master

# Créer la branche dédiée
# Convention de nommage selon le type:
# - fix/issue-{numero} pour les bugs
# - feature/issue-{numero} pour les nouvelles fonctionnalités
# - refactor/issue-{numero} pour les refactorisations

git checkout -b {prefix}/issue-{numero}
```

### 3. Implémentation TDD (si backend PHP/Python)

#### 3.1 Identifier les fichiers de test concernés
- Si modification de `classes/MatchManager.php` → `unit_tests/MatchManagerTest.php`
- Si nouvelle classe → créer `unit_tests/{ClassName}Test.php`

#### 3.2 Écrire les tests AVANT l'implémentation
```php
<?php
// unit_tests/NouveauTest.php

require_once __DIR__ . '/../classes/index.php';

class NouveauTest extends UfolepTestCase
{
    public function test_nouvelle_fonctionnalite()
    {
        // Arrange
        $this->connect_as_admin();
        
        // Act
        $result = /* appel de la méthode à implémenter */;
        
        // Assert
        $this->assertEquals($expected, $result);
    }
}
```

#### 3.3 Vérifier que les tests échouent (RED)
```bash
c:\php\php.exe vendor/bin/phpunit unit_tests/NouveauTest.php
```

### 4. Implémentation de la Solution

**Principes:**
- Faire des modifications minimales et ciblées
- Respecter les conventions de code existantes
- Documenter les changements complexes
- Ne pas casser les fonctionnalités existantes

### 5. Validation des Tests (GREEN)

#### 5.1 Exécuter les nouveaux tests
```bash
c:\php\php.exe vendor/bin/phpunit unit_tests/NouveauTest.php
```

#### 5.2 Exécuter tous les tests pour régression
```bash
c:\php\php.exe vendor/bin/phpunit unit_tests/
```

#### 5.3 Boucle jusqu'à succès
- Si échec: corriger l'implémentation
- Si succès: passer à l'étape suivante

### 6. Commit et Push

```bash
# Ajouter les fichiers modifiés
git add .

# Commit avec message descriptif référençant le ticket
git commit -m "fix: description courte du fix

Résout #[numero]

- Détail des modifications
- Ajout de tests unitaires"

# Pousser la branche
git push -u origin {prefix}/issue-{numero}
```

### 7. Création de la Pull Request

```bash
# Créer la PR via GitHub CLI
gh pr create \
  --repo benallemand/ufolep13volley \
  --base master \
  --head {prefix}/issue-{numero} \
  --title "Fix #{numero}: {titre du ticket}" \
  --body "## Description
Résout #{numero}

## Changements
- Liste des modifications effectuées

## Tests
- [ ] Tests unitaires ajoutés/modifiés
- [ ] Tous les tests passent

## Checklist
- [ ] Code review demandée
- [ ] Documentation mise à jour si nécessaire"
```

## Variables d'Environnement Requises

Le token GitHub doit être configuré pour les commandes `gh`:
- **Windows**: Variable d'environnement `GITHUB_TOKEN` ou `gh auth login`
- Le token doit avoir les permissions: `repo`, `read:org`

## Mapping Types de Tickets → Préfixes de Branche

| Label GitHub | Préfixe Branche |
|--------------|-----------------|
| bug          | fix/            |
| feature      | feature/        |
| enhancement  | feature/        |
| refactor     | refactor/       |
| docs         | docs/           |
| test         | test/           |

## Règles TDD

1. **RED**: Écrire un test qui échoue
2. **GREEN**: Écrire le code minimal pour faire passer le test
3. **REFACTOR**: Améliorer le code tout en gardant les tests verts

## Structure des Tests PHP

```
unit_tests/
├── UfolepTestCase.php      # Classe de base avec helpers
├── CompetitionTest.php     # Tests sur les compétitions
├── EmailsTest.php          # Tests d'envoi d'emails
├── FilesTest.php           # Tests de gestion de fichiers
├── MatchManagerTest.php    # Tests sur les matchs
├── PlayersTest.php         # Tests sur les joueurs
├── RankTest.php            # Tests sur les classements
├── RegisterTest.php        # Tests sur les inscriptions
└── files/                  # Fichiers de test
```

### 8. Validation Utilisateur

**IMPORTANT:** Ne JAMAIS merger automatiquement. Toujours demander confirmation à l'utilisateur avant de merger.
- Attendre que l'utilisateur teste les modifications
- Attendre son "go" explicite pour merger

### 9. Merge de la PR

```powershell
# Merger la PR (avec --admin si branch protection)
& "C:\Program Files\GitHub CLI\gh.exe" pr merge {numero_pr} --repo benallemand/ufolep13volley --merge --admin
```

### 10. Fermeture du Ticket

```powershell
# Fermer explicitement le ticket avec un commentaire
& "C:\Program Files\GitHub CLI\gh.exe" issue close {numero} --repo benallemand/ufolep13volley --comment "Fermé par PR #{numero_pr} - Tag {yyyymmddhhii}"
```

**Note:** Même si `Résout #{numero}` devrait fermer l'issue automatiquement, cette étape garantit la fermeture.

### 11. Tag de Release

```powershell
# Mettre à jour master local
git checkout master
git pull origin master

# Créer un tag avec la date au format yyyymmddhhii
git tag {yyyymmddhhii}
git push origin {yyyymmddhhii}
```

## Scripts SQL de Migration

Les scripts SQL sont stockés dans le projet **ufolep13volley_python**:
- Emplacement: `C:\Users\benal\PycharmProjects\ufolep13volley_python\sql\updates\{année}\`
- Convention de nommage: `{numéro}-{description}.sql` (ex: `001-add_remarques_to_gymnase.sql`)
- **Important**: Commit et push directement sur master dans ufolep13volley_python (pas de PR nécessaire)

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
- Pattern: Admin OU responsable de l'équipe concernée
- Stocker `id_equipe` en session pour les vérifications

### Debug de Features en Temps Réel
- Créer un fichier `debug_{feature}.php` pour tester/modifier les données
- Permet de mettre à jour les dates pour simuler "aujourd'hui"
- Utile pour les tests manuels sans attendre la vraie date

### Requêtes AJAX ExtJS
- Toujours spécifier `method: 'GET'` pour les lectures, `method: 'POST'` pour les écritures
- Par défaut ExtJS utilise POST, ce qui n'est pas toujours approprié

### Validation d'ID en PHP
- Utiliser `!empty($id) && is_numeric($id)` pour vérifier les IDs de base de données
- ExtJS génère des IDs temporaires comme `"extModel1124-23"` pour les nouveaux records
- `isset()` retourne true même pour `null`, préférer `!empty()`

### Tests Unitaires Sélectifs
- Certains tests sont destructifs (modification de données réelles)
- Préférer exécuter les tests spécifiques: `--filter "test_method1|test_method2"`
- Éviter `phpunit unit_tests/` si la base contient des données de production

### Routeur REST PHP et Paramètres Nommés
- Le routeur `rest/action.php` appelle les méthodes avec des **paramètres nommés** PHP 8+
- Les méthodes CRUD doivent accepter les paramètres comme arguments de fonction, pas via `$_POST`
- Exemple: `public function saveNews($id = null, $title = '', $text = ''): void`
- ExtJS envoie automatiquement `$dirtyFields` lors du submit de formulaire - l'ajouter comme paramètre optionnel

### Frontend ExtJS - Patterns à Suivre
- **Grilles admin**: Utiliser des fenêtres d'édition modales (pattern `window.Window`), pas le plugin `rowediting`
- Créer: `js/view/{entity}/AdminGrid.js` pour la grille
- Créer: `js/view/{entity}/Edit.js` pour la fenêtre d'édition modale
- Le formulaire soumet directement à l'URL définie, le handler `save` du controller gère la soumission
- Ajouter les refs: `formPanelEdit{Entity}`, `windowEdit{Entity}`, `manage{Entity}Grid`
