<?php
/**
 * Issue #467 — helper E2E : session de responsable (non admin) sur une équipe
 * du championnat masculin inscrite à la coupe Isoardi, période de
 * désinscription ouverte (`?state=open`) ou close (`?state=closed`).
 *
 * La période est celle des inscriptions en coupe Khoury Hanna : le helper
 * renvoie les dates d'origine, que `?teardown=1&…` remet en place avec le
 * choix d'origine de l'équipe.
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

/** '' ou une date 'aaaa-mm-jj' : NULL pour la base si vide. */
function date_or_null(string $value): ?string
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
}

try {
    $sql = new SqlManager();
    if (!empty($_GET['teardown'])) {
        $id_equipe = (int)$_GET['id_equipe'];
        $sql->execute("UPDATE equipes SET is_cup_registered = ? WHERE id_equipe = ?", array(
            array('type' => 'i', 'value' => (int)$_GET['cup']),
            array('type' => 'i', 'value' => $id_equipe),
        ));
        $sql->execute(
            "UPDATE competitions SET start_register_date = ?, limit_register_date = ? WHERE code_competition = 'kh'",
            array(
                array('type' => 's', 'value' => date_or_null((string)($_GET['start'] ?? ''))),
                array('type' => 's', 'value' => date_or_null((string)($_GET['limit'] ?? ''))),
            ));
        $sql->execute(
            "DELETE FROM activity WHERE id_team = ? AND comment LIKE '%coupe Isoardi' AND activity_date >= NOW() - INTERVAL 1 HOUR",
            array(array('type' => 'i', 'value' => $id_equipe)));
        echo json_encode(['success' => true]);
        exit(0);
    }

    $team = $sql->execute(
        // une équipe qui a un compte responsable : la page « Mon équipe » exige
        // un compte connecté
        "SELECT e.id_equipe, e.nom_equipe, e.is_cup_registered + 0 AS cup, MIN(ca.id) AS id_user
         FROM equipes e
         JOIN classements c ON c.id_equipe = e.id_equipe AND c.code_competition = 'm'
         JOIN users_teams ut ON ut.team_id = e.id_equipe
         JOIN comptes_acces ca ON ca.id = ut.user_id AND ca.is_admin + 0 = 0
         WHERE e.code_competition = 'm'
         GROUP BY e.id_equipe, e.nom_equipe, e.is_cup_registered
         ORDER BY e.id_equipe
         LIMIT 1");
    if (empty($team)) {
        throw new Exception('aucune équipe engagée en championnat masculin');
    }
    $team = $team[0];
    $kh = $sql->execute(
        "SELECT IFNULL(DATE_FORMAT(start_register_date, '%Y-%m-%d'), '') AS start,
                IFNULL(DATE_FORMAT(limit_register_date, '%Y-%m-%d'), '') AS `limit`
         FROM competitions WHERE code_competition = 'kh'")[0];

    $sql->execute("UPDATE equipes SET is_cup_registered = 1 WHERE id_equipe = ?",
        array(array('type' => 'i', 'value' => (int)$team['id_equipe'])));
    $sql->execute(($_GET['state'] ?? 'open') === 'closed'
        ? "UPDATE competitions SET start_register_date = CURDATE() - INTERVAL 30 DAY,
                                   limit_register_date = CURDATE() - INTERVAL 1 DAY
           WHERE code_competition = 'kh'"
        : "UPDATE competitions SET start_register_date = CURDATE() - INTERVAL 1 DAY,
                                   limit_register_date = CURDATE() + INTERVAL 10 DAY
           WHERE code_competition = 'kh'");

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    // responsable d'équipe ordinaire : pas d'admin, qui passerait les contrôles
    $_SESSION['is_admin'] = false;
    $_SESSION['is_team_leader'] = true;
    $_SESSION['is_club_leader'] = false;
    $_SESSION['login'] = 'e2e_test';
    $_SESSION['id_user'] = (int)$team['id_user'];
    $_SESSION['id_equipe'] = (int)$team['id_equipe'];

    echo json_encode([
        'id_equipe' => (int)$team['id_equipe'],
        'nom_equipe' => $team['nom_equipe'],
        'cup' => (int)$team['cup'],
        'start' => $kh['start'],
        'limit' => $kh['limit'],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
