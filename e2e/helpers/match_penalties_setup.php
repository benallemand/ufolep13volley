<?php
/**
 * Issue #458 — helper E2E : un match fictif `E2E458` entre deux équipes de
 * championnat, et ses deux pénalités automatiques (le point est retiré au
 * classement, comme le ferait le cron). `?teardown=1` rend les points encore
 * retirés et supprime le match (les pénalités partent avec lui).
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

const CODE_MATCH = 'E2E458';

function teardown(SqlManager $sql): void
{
    // les pénalités encore actives ont retiré un point : on le rend
    $sql->execute(
        "UPDATE classements c JOIN match_penalties p
             ON p.id_equipe = c.id_equipe AND p.code_competition = c.code_competition
         JOIN matches m ON m.id_match = p.id_match
         SET c.penalite = GREATEST(c.penalite - 1, 0)
         WHERE m.code_match = ? AND p.cancelled_at IS NULL",
        array(array('type' => 's', 'value' => CODE_MATCH)));
    $sql->execute("DELETE FROM matches WHERE code_match = ?", array(array('type' => 's', 'value' => CODE_MATCH)));
    $sql->execute("DELETE FROM activity WHERE comment LIKE ?", array(array('type' => 's', 'value' => '%' . CODE_MATCH . '%')));
}

try {
    $sql = new SqlManager();
    teardown($sql);
    if (!empty($_GET['teardown'])) {
        echo json_encode(['success' => true]);
        exit(0);
    }
    $teams = $sql->execute(
        "SELECT c.id_equipe, c.division, e.nom_equipe FROM classements c JOIN equipes e ON e.id_equipe = c.id_equipe
         WHERE c.code_competition = 'm' ORDER BY c.division, c.id_equipe LIMIT 2");
    if (count($teams) < 2 || $teams[0]['division'] !== $teams[1]['division']) {
        throw new Exception('deux équipes de la même division du championnat masculin sont nécessaires');
    }
    $id_court = (int)$sql->execute("SELECT MIN(id) AS id FROM gymnase")[0]['id'];
    $id_match = $sql->execute(
        "INSERT INTO matches SET code_match = ?, code_competition = 'm', division = ?,
             id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
             date_reception = CURRENT_DATE - INTERVAL 8 DAY, date_original = CURRENT_DATE - INTERVAL 8 DAY,
             match_status = 'CONFIRMED'",
        array(array('type' => 's', 'value' => CODE_MATCH), array('type' => 's', 'value' => $teams[0]['division']),
              array('type' => 'i', 'value' => $teams[0]['id_equipe']), array('type' => 'i', 'value' => $teams[1]['id_equipe']),
              array('type' => 'i', 'value' => $id_court)));
    $penalties = array();
    foreach ($teams as $team) {
        $penalties[] = array(
            'id' => (int)$sql->execute(
                "INSERT INTO match_penalties SET id_match = ?, id_equipe = ?, code_competition = 'm', reason = 'feuille_non_signee_48h'",
                array(array('type' => 'i', 'value' => $id_match), array('type' => 'i', 'value' => $team['id_equipe']))),
            'id_equipe' => (int)$team['id_equipe'],
            'equipe' => $team['nom_equipe'],
        );
        $sql->execute("UPDATE classements SET penalite = penalite + 1 WHERE id_equipe = ? AND code_competition = 'm'",
            array(array('type' => 'i', 'value' => $team['id_equipe'])));
    }
    echo json_encode(['code_match' => CODE_MATCH, 'penalties' => $penalties]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
