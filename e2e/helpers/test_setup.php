<?php
/**
 * E2E test helper — creates an admin session + a test match scheduled for today.
 * Returns JSON: { code_match, id_equipe_dom, id_equipe_ext, error? }
 *
 * SECURITY: this file must never be deployed to production.
 * It is safe only in local/test environments.
 */
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$isTestEnv   = getenv('APP_ENV') === 'test';
if (!$isLocalhost && !$isTestEnv) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/SqlManager.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

try {
    $sql = new SqlManager();

    // --- Équipes : réutiliser les deux premières existantes ---
    $teams = $sql->execute("SELECT id_equipe FROM equipes ORDER BY id_equipe LIMIT 2");
    if (count($teams) < 2) {
        throw new RuntimeException('Not enough teams in database (need at least 2)');
    }
    $id_equipe_dom = (int)$teams[0]['id_equipe'];
    $id_equipe_ext = (int)$teams[1]['id_equipe'];

    // --- Compétition de test dédiée (créée si absente) ---
    // matchs_view fait un INNER JOIN sur competitions, donc la compétition doit exister
    $existing_comp = $sql->execute(
        "SELECT id FROM competitions WHERE code_competition = 'e2' LIMIT 1"
    );
    if (empty($existing_comp)) {
        $sql->execute(
            "INSERT INTO competitions SET code_competition = 'e2', libelle = 'E2E Test', id_compet_maitre = 'e2'"
        );
    }

    // --- Journée de test dédiée (créée si absente) ---
    $existing_journee = $sql->execute(
        "SELECT id FROM journees WHERE code_competition = 'e2' LIMIT 1"
    );
    if (empty($existing_journee)) {
        $id_journee = $sql->execute(
            "INSERT INTO journees SET code_competition = 'e2', numero = 99, nommage = 'E2E Test'"
        );
    } else {
        $id_journee = (int)$existing_journee[0]['id'];
    }

    // --- Gymnase de test dédié (créé si absent) ---
    $existing_gym = $sql->execute(
        "SELECT id FROM gymnase WHERE nom = 'Gymnase E2E Test' LIMIT 1"
    );
    if (empty($existing_gym)) {
        $id_gymnasium = $sql->execute(
            "INSERT INTO gymnase SET nom = 'Gymnase E2E Test'"
        );
    } else {
        $id_gymnasium = (int)$existing_gym[0]['id'];
    }

    // --- Match du jour (code unique par timestamp pour éviter les conflits) ---
    $code_match = 'E2E_' . date('ymdHis'); // 4+1+12 = 17 chars <= varchar(20)

    // Supprimer un éventuel match précédent avec le même préfixe (nettoyage préventif)
    $sql->execute("DELETE FROM live_scores WHERE id_match LIKE 'E2E_%'");
    $sql->execute("DELETE FROM matches WHERE code_match LIKE 'E2E_%'");

    $sql->execute(
        "INSERT INTO matches SET
            code_match      = ?,
            code_competition= 'e2',
            division        = '1',
            id_equipe_dom   = ?,
            id_equipe_ext   = ?,
            date_reception  = CURRENT_DATE,
            date_original   = CURRENT_DATE,
            id_journee      = ?,
            id_gymnasium    = ?,
            match_status    = 'CONFIRMED'",
        [
            ['type' => 's', 'value' => $code_match],
            ['type' => 'i', 'value' => $id_equipe_dom],
            ['type' => 'i', 'value' => $id_equipe_ext],
            ['type' => 'i', 'value' => $id_journee],
            ['type' => 'i', 'value' => $id_gymnasium],
        ]
    );

    // --- Session admin ---
    session_start();
    $_SESSION['profile_name'] = 'ADMINISTRATEUR';
    $_SESSION['login']        = 'e2';
    $_SESSION['id_user']      = 1;
    $_SESSION['id_equipe']    = null;

    echo json_encode([
        'code_match'    => $code_match,
        'id_equipe_dom' => $id_equipe_dom,
        'id_equipe_ext' => $id_equipe_ext,
        'session_id'    => session_id(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
