# Workflow: Configuration GitHub CLI

## Description
Configuration initiale de GitHub CLI pour permettre l'automatisation des opérations sur les tickets et pull requests.

## Prérequis
- Git installé et configuré
- Accès au repository https://github.com/benallemand/ufolep13volley

## Étapes de Configuration

### 1. Installation de GitHub CLI

#### Windows (via winget)
```powershell
winget install --id GitHub.cli
```

#### Windows (via Chocolatey)
```powershell
choco install gh
```

#### Vérifier l'installation
```powershell
gh --version
```

### 2. Authentification GitHub

#### Option A: Authentification Interactive (Recommandée)
```powershell
gh auth login
```
Suivre les instructions:
1. Choisir `GitHub.com`
2. Choisir `HTTPS`
3. Choisir `Login with a web browser`
4. Copier le code affiché et l'entrer sur https://github.com/login/device

#### Option B: Authentification par Token

1. **Créer un Personal Access Token (PAT):**
   - Aller sur https://github.com/settings/tokens
   - Cliquer sur "Generate new token (classic)"
   - Nom: `windsurf-ufolep13volley`
   - Expiration: selon préférence (90 days recommandé)
   - Scopes requis:
     - `repo` (accès complet aux repositories privés)
     - `read:org` (lecture des organisations)
     - `workflow` (si besoin de déclencher des workflows)

2. **Configurer le token:**
```powershell
# Option 1: Variable d'environnement (session courante)
$env:GITHUB_TOKEN = "ghp_votre_token_ici"

# Option 2: Variable d'environnement permanente
[Environment]::SetEnvironmentVariable("GITHUB_TOKEN", "ghp_votre_token_ici", "User")

# Option 3: Authentification directe gh
gh auth login --with-token
# Puis coller le token
```

### 3. Vérification de la Configuration

```powershell
# Vérifier l'authentification
gh auth status

# Tester l'accès au repository
gh repo view benallemand/ufolep13volley

# Lister les issues ouvertes
gh issue list --repo benallemand/ufolep13volley
```

### 4. Configuration Git (si pas déjà fait)

```powershell
git config --global user.name "Votre Nom"
git config --global user.email "votre.email@example.com"
```

## Sécurité

### Bonnes Pratiques
- **Ne jamais** committer de tokens dans le code
- Utiliser des tokens avec expiration
- Révoquer les tokens inutilisés sur https://github.com/settings/tokens
- Le fichier `.gitignore` exclut déjà `.env` et les fichiers sensibles

### Stockage Sécurisé Windows
Le token est stocké de manière sécurisée par `gh auth login` dans le Windows Credential Manager.

Pour vérifier:
```powershell
# Voir où les credentials sont stockées
gh auth status --show-token
```

## Commandes Utiles

```powershell
# Voir un ticket
gh issue view 123 --repo benallemand/ufolep13volley

# Créer une PR
gh pr create --repo benallemand/ufolep13volley

# Lister les PRs
gh pr list --repo benallemand/ufolep13volley

# Voir le statut des checks sur une PR
gh pr checks --repo benallemand/ufolep13volley
```

## Dépannage

### Token expiré
```powershell
gh auth refresh
```

### Réinitialiser l'authentification
```powershell
gh auth logout
gh auth login
```

### Vérifier les permissions du token
Aller sur https://github.com/settings/tokens et vérifier les scopes.
