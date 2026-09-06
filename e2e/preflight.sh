#!/usr/bin/env bash
# Vérifie que la stack est réellement prête avant de lancer les tests E2E.
#
# Raison d'être : une campagne lancée sur une stack incomplète échoue en masse
# pour de mauvaises raisons — conteneur php absent, APP_ENV perdu (donc helpers
# en 403), ou image périmée. Diagnostiquer ça après coup coûte plus cher que de
# le vérifier avant.
#
# Usage : bash e2e/preflight.sh [compose-args...]
#   ex.  bash e2e/preflight.sh -f docker-compose.yml -f docker-compose.dev.yml \
#                              -f docker-compose.e2e-dev.yml

set -u
COMPOSE_ARGS=("$@")
BASE="${BASE_URL:-http://localhost}"
fail=0

check() {
    local label="$1" expected="$2" actual="$3"
    if [ "$actual" = "$expected" ]; then
        printf "  \033[32mOK\033[0m   %-46s %s\n" "$label" "$actual"
    else
        printf "  \033[31mKO\033[0m   %-46s %s (attendu : %s)\n" "$label" "$actual" "$expected"
        fail=1
    fi
}

echo "Préflight E2E"

# 1. Les conteneurs nécessaires tournent-ils ?
for svc in php caddy; do
    state=$(docker compose "${COMPOSE_ARGS[@]}" ps --status running --services 2>/dev/null | grep -cx "$svc")
    check "conteneur $svc démarré" "1" "$state"
done

# 2. APP_ENV=test : sans lui, tous les helpers répondent 403
app_env=$(docker compose "${COMPOSE_ARGS[@]}" exec -T php sh -c 'echo $APP_ENV' 2>/dev/null | tr -d '\r')
check "APP_ENV dans le conteneur php" "test" "${app_env:-<vide>}"

# 3. Le serveur répond-il, et sert-il bien l'application ?
check "page d'accueil servie" "200" \
    "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/pages/home.html")"

# 4. Un helper E2E est-il joignable ? (preuve de bout en bout d'APP_ENV)
check "helpers E2E autorisés" "200" \
    "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/e2e/helpers/test_teardown.php")"

# 5. La base répond-elle à travers l'API ?
check "API REST + base de données" "200" \
    "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/rest/action.php/competition/getCompetitions")"

# 6. Le bundle Vite est-il construit ET servi ? (pages blanches sinon)
#    On ne teste PAS dist/.vite/manifest.json : le .htaccess renvoie 404 sur tout
#    segment commençant par un point (protection du .env), c'est voulu.
#    On vérifie donc le vrai contrat : la page référence un asset hashé, et cet
#    asset se charge.
asset=$(curl -s "$BASE/pages/home.html" | grep -oE '/dist/assets/[A-Za-z0-9._/-]+\.js' | head -1)
if [ -z "$asset" ]; then
    check "bundle Vite servi (asset hashé)" "un asset" "aucun (dist/ non construit ?)"
else
    check "bundle Vite servi ($(basename "$asset"))" "200" \
        "$(curl -s -o /dev/null -w '%{http_code}' "$BASE$asset")"
fi

echo
if [ "$fail" -ne 0 ]; then
    echo "Préflight en échec : ne pas lancer la campagne, elle échouerait pour de mauvaises raisons."
    exit 1
fi
echo "Stack prête."
