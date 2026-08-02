#!/usr/bin/env bash
#
# Calcule la prochaine version semver a partir des conventional commits, et
# produit la section de CHANGELOG correspondante.
#
# Utilise par .github/workflows/auto-tag.yml, mais executable a la main pour
# verifier ce que donnerait un merge sans rien pousser :
#
#     bash .github/ci/next-release.sh --dry-run
#
# Sorties (si $GITHUB_OUTPUT est defini) :
#   version       -> ex. v1.2.0, vide si rien a publier
#   previous_tag  -> tag de depart de la comparaison
#   changelog     -> chemin du fichier contenant la section de CHANGELOG
#
# Regles de bump. Le semver strict ne bumpe que sur feat/fix, ce qui laisserait
# les merges dependabot (chore(deps)) sans version alors que c'est precisement
# le cas d'usage vise. On retient donc :
#
#   BREAKING CHANGE, ou type suivi de '!'  -> majeur
#   feat                                    -> mineur
#   tout le reste (fix, chore, deps, ci...) -> patch
#
# Autrement dit tout merge apportant au moins un commit produit un tag.

set -euo pipefail

DRY_RUN=0
[ "${1:-}" = "--dry-run" ] && DRY_RUN=1

CHANGELOG_SECTION="${RUNNER_TEMP:-/tmp}/changelog_section.md"

# --- Point de depart -------------------------------------------------------
# Seuls les tags semver comptent : les 48 tags horodates historiques
# (YYYYMMDDHHMM) ne commencent pas par 'v' et sont donc ignores.
previous_tag="$(git tag -l 'v[0-9]*.[0-9]*.[0-9]*' --sort=-v:refname | head -n1 || true)"

if [ -n "$previous_tag" ]; then
    range="${previous_tag}..HEAD"
    version_core="${previous_tag#v}"
    major="${version_core%%.*}"
    remainder="${version_core#*.}"
    minor="${remainder%%.*}"
    patch="${remainder#*.}"
    first_release=0
else
    # Premier passage : on demarre a v1.0.0. On borne quand meme la lecture des
    # commits au dernier tag horodate, sinon le CHANGELOG initial reprendrait
    # toute l'histoire du depot.
    last_any_tag="$(git describe --tags --abbrev=0 2>/dev/null || true)"
    if [ -n "$last_any_tag" ]; then
        range="${last_any_tag}..HEAD"
    else
        range="HEAD"
    fi
    previous_tag="$last_any_tag"
    first_release=1
fi

commit_count="$(git log --no-merges --invert-grep --grep="^chore(release)" --format=%H "$range" | wc -l | tr -d ' ')"
if [ "$commit_count" -eq 0 ]; then
    echo "Aucun commit depuis ${previous_tag:-le debut} : rien a publier."
    if [ -n "${GITHUB_OUTPUT:-}" ]; then
        echo "version=" >> "$GITHUB_OUTPUT"
    fi
    exit 0
fi

# --- Niveau de bump --------------------------------------------------------
bump="patch"

while IFS= read -r subject; do
    case "$subject" in
        *!:*)
            bump="major"
            ;;
        feat:*|feat\(*)
            [ "$bump" = "major" ] || bump="minor"
            ;;
    esac
done < <(git log --no-merges --invert-grep --grep="^chore(release)" --format=%s "$range")

if git log --no-merges --invert-grep --grep="^chore(release)" --format=%B "$range" | grep -q '^BREAKING CHANGE'; then
    bump="major"
fi

if [ "$first_release" -eq 1 ]; then
    next_version="v1.0.0"
else
    case "$bump" in
        major) major=$((major + 1)); minor=0; patch=0 ;;
        minor) minor=$((minor + 1)); patch=0 ;;
        patch) patch=$((patch + 1)) ;;
    esac
    next_version="v${major}.${minor}.${patch}"
fi

# --- Section de CHANGELOG --------------------------------------------------
today="$(date +%Y-%m-%d)"
{
    echo "## ${next_version} — ${today}"
    echo
} > "$CHANGELOG_SECTION"

# $1 = titre de section, $2..= motifs de sujet a retenir
append_section() {
    local title="$1"
    shift
    local matched=0
    local body=""
    while IFS= read -r subject; do
        for pattern in "$@"; do
            case "$subject" in
                $pattern)
                    body="${body}- ${subject}"$'\n'
                    matched=1
                    break
                    ;;
            esac
        done
    done < <(git log --no-merges --invert-grep --grep="^chore(release)" --format=%s --reverse "$range")
    if [ "$matched" -eq 1 ]; then
        {
            echo "### ${title}"
            echo
            printf '%s' "$body"
            echo
        } >> "$CHANGELOG_SECTION"
    fi
}

append_section "Fonctionnalites" 'feat:*' 'feat(*'
append_section "Corrections" 'fix:*' 'fix(*'
append_section "Dependances" 'chore(deps*' 'build(deps*' 'deps:*'
append_section "Technique" 'ci:*' 'ci(*' 'refactor:*' 'refactor(*' 'perf:*' 'perf(*' \
    'test:*' 'test(*' 'docs:*' 'docs(*' 'style:*' 'style(*' 'build:*' 'chore:*'

echo "previous_tag = ${previous_tag:-<aucun>}"
echo "bump         = ${bump}"
echo "next_version = ${next_version}"
echo "commits      = ${commit_count}"

if [ "$DRY_RUN" -eq 1 ]; then
    echo
    echo "----- section de CHANGELOG -----"
    cat "$CHANGELOG_SECTION"
    exit 0
fi

if [ -n "${GITHUB_OUTPUT:-}" ]; then
    {
        echo "version=${next_version}"
        echo "previous_tag=${previous_tag}"
        echo "changelog=${CHANGELOG_SECTION}"
    } >> "$GITHUB_OUTPUT"
fi
