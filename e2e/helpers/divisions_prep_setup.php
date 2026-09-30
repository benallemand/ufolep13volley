<?php
/**
 * Issue #388 — helper E2E : une compétition de test « dx » en préparation de
 * saison, pour l'écran Réorganiser les divisions.
 *
 *   - « E2E Prep Classee »  : en division 1 ;
 *   - « E2E Prep A placer » : en division X (à placer) ;
 *   - « E2E Prep Inscrite » : hors classement, avec une inscription en attente ;
 *   - « E2E Prep Ancienne » : hors classement, sans inscription (ancienne saison).
 *
 * `?teardown=1` retire le tout.
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
    $sql->execute("DELETE FROM register WHERE new_team_name LIKE 'E2E Prep %'");
    $sql->execute("DELETE FROM classements WHERE code_competition = 'dx'");
    $sql->execute("DELETE FROM equipes WHERE code_competition = 'dx'");
    $sql->execute("DELETE FROM competitions WHERE code_competition = 'dx'");
    $sql->execute("DELETE FROM clubs WHERE nom = 'E2E Prep Club'");
    if (!empty($_GET['teardown'])) {
        echo json_encode(['success' => true]);
        exit(0);
    }
    $id_competition = (int)$sql->execute(
        "INSERT INTO competitions SET code_competition = 'dx', libelle = 'E2E Préparation', id_compet_maitre = 'dx'");
    $id_club = (int)$sql->execute("INSERT INTO clubs SET nom = 'E2E Prep Club'");
    $team = static fn(string $name): int => (int)$sql->execute(
        "INSERT INTO equipes SET code_competition = 'dx', nom_equipe = ?, id_club = ?",
        [['type' => 's', 'value' => $name], ['type' => 'i', 'value' => $id_club]]);
    $rank = static fn(int $id_team, string $division) => $sql->execute(
        "INSERT INTO classements SET code_competition = 'dx', division = ?, id_equipe = ?, rank_start = 1, penalite = 0",
        [['type' => 's', 'value' => $division], ['type' => 'i', 'value' => $id_team]]);

    $rank($team('E2E Prep Classee'), '1');
    $rank($team('E2E Prep A placer'), 'X');
    $team('E2E Prep Inscrite');
    $team('E2E Prep Ancienne');
    $sql->execute(
        "INSERT INTO register SET new_team_name = 'E2E Prep Inscrite', id_club = ?, id_competition = ?,
             leader_name = 'E2EPREP', leader_first_name = 'Test', leader_email = 'e2e_prep@ufolep.test',
             leader_phone = '0600000000', status = 'PENDING'",
        [['type' => 'i', 'value' => $id_club], ['type' => 'i', 'value' => $id_competition]]);
    echo json_encode(['id_competition' => $id_competition]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
