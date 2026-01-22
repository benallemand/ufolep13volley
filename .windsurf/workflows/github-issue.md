# Workflow: Traitement de Ticket GitHub

## Déclenchement
Ce workflow est déclenché lorsqu'un utilisateur demande de traiter un ticket GitHub avec une URL du type:
`https://github.com/benallemand/ufolep13volley/issues/{numero}`

## Étapes du Workflow

### 1. Récupération et Compréhension du Ticket
```bash
# Récupérer les informations du ticket via l'API GitHub
gh issue view {numero} --repo benallemand/ufolep13volley
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

## Scripts SQL de Migration

Les scripts SQL sont stockés dans le projet **ufolep13volley_python**:
- Emplacement: `C:\Users\benal\PycharmProjects\ufolep13volley_python\sql\updates\{année}\`
- Convention de nommage: `{numéro}-{description}.sql` (ex: `001-add_remarques_to_gymnase.sql`)
- **Important**: Créer également une branche et PR dans ufolep13volley_python pour les scripts SQL
