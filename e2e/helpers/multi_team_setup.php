<?php
/**
 * E2E test helper (#421) — un compte responsable de deux équipes, dont
 * l'équipe courante (id_equipe) ne joue PAS le match de test : le match est
 * celui de son autre équipe, non confirmé, donc à date modifiable.
 *
 * Returns JSON: { success, id_match, id_equipe_match, id_equipe_courante }
 *
 * SECURITY: ne doit jamais être déployé en production.
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
    require __DIR__ . '/multi_team_cleanup.inc.php';
    multi_team_cleanup($sql);

    // FK valides : on recopie un match visible dans matchs_view
    $sample = $sql->execute(
        "SELECT code_competition, division, id_equipe_dom, id_equipe_ext, id_gymnasium
         FROM matchs_view
         LIMIT 1"
    );
    if (empty($sample)) {
        throw new Exception("Aucun match modèle dans matchs_view en base de test.");
    }
    $s = $sample[0];
    $id_equipe_match = (int)$s['id_equipe_dom'];
    // l'équipe courante : une autre équipe, qui ne joue pas ce match
    $other = $sql->execute(
        "SELECT id_equipe FROM equipes WHERE id_equipe NOT IN (?, ?) ORDER BY id_equipe LIMIT 1",
        [['type' => 'i', 'value' => $id_equipe_match], ['type' => 'i', 'value' => (int)$s['id_equipe_ext']]]
    );
    if (empty($other)) {
        throw new Exception("Aucune seconde équipe en base de test.");
    }
    $id_equipe_courante = (int)$other[0]['id_equipe'];

    $id_match = $sql->execute(
        "INSERT INTO matches SET
            code_match       = ?,
            code_competition = ?,
            division         = ?,
            id_equipe_dom    = ?,
            id_equipe_ext    = ?,
            id_gymnasium     = ?,
            date_reception   = CURRENT_DATE + INTERVAL 30 DAY,
            date_original    = CURRENT_DATE + INTERVAL 30 DAY,
            match_status     = 'NOT_CONFIRMED'",
        [
            ['type' => 's', 'value' => 'E2M' . date('ymdHis')],
            ['type' => 's', 'value' => $s['code_competition']],
            ['type' => 's', 'value' => $s['division']],
            ['type' => 'i', 'value' => $id_equipe_match],
            ['type' => 'i', 'value' => (int)$s['id_equipe_ext']],
            ['type' => 'i', 'value' => empty($s['id_gymnasium']) ? null : (int)$s['id_gymnasium']],
        ]
    );

    $id_user = $sql->execute(
        "INSERT INTO comptes_acces SET login = 'e2e_multi_team', email = 'e2e_multi_team@ufolep.test', is_admin = 0");
    foreach ([$id_equipe_match, $id_equipe_courante] as $id_equipe) {
        $sql->execute("INSERT INTO users_teams SET user_id = ?, team_id = ?",
            [['type' => 'i', 'value' => (int)$id_user], ['type' => 'i', 'value' => $id_equipe]]);
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['login']          = 'e2e_multi_team';
    $_SESSION['id_user']        = (int)$id_user;
    $_SESSION['is_admin']       = false;
    $_SESSION['is_team_leader'] = true;
    $_SESSION['id_equipe']      = $id_equipe_courante;
    $_SESSION['is_club_leader'] = false;

    echo json_encode([
        'success'            => true,
        'id_match'           => (int)$id_match,
        'id_equipe_match'    => $id_equipe_match,
        'id_equipe_courante' => $id_equipe_courante,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
