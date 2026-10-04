<?php
/**
 * Issue #409 (lot 2) — helper E2E : deux fiches d'un même joueur à fusionner.
 *
 *   - « E2EMERGE Vraie » : dans une équipe de test, homologuée ;
 *   - « E2EMERGE Doublon » : sans équipe, avec un numéro de licence.
 *
 * Rend leurs identifiants. `?teardown=1` retire le tout.
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
    $sql->execute("DELETE FROM joueur_equipe WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe = 'E2E Merge Equipe')");
    $sql->execute("DELETE FROM joueurs WHERE nom = 'E2EMERGE'");
    $sql->execute("DELETE FROM equipes WHERE nom_equipe = 'E2E Merge Equipe'");
    $sql->execute("DELETE FROM clubs WHERE nom = 'E2E Merge Club'");
    $sql->execute("DELETE FROM activity WHERE comment LIKE '%E2EMERGE%'");
    if (!empty($_GET['teardown'])) {
        echo json_encode(['success' => true]);
        exit(0);
    }
    $id_club = (int)$sql->execute("INSERT INTO clubs SET nom = 'E2E Merge Club'");
    $id_team = (int)$sql->execute("INSERT INTO equipes SET nom_equipe = 'E2E Merge Equipe', code_competition = 'm', id_club = ?",
        [['type' => 'i', 'value' => $id_club]]);
    $real = (int)$sql->execute(
        "INSERT INTO joueurs SET nom = 'E2EMERGE', prenom = 'Vraie', sexe = 'M', id_club = ?, date_homologation = CURRENT_DATE",
        [['type' => 'i', 'value' => $id_club]]);
    $sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?",
        [['type' => 'i', 'value' => $real], ['type' => 'i', 'value' => $id_team]]);
    $duplicate = (int)$sql->execute(
        "INSERT INTO joueurs SET nom = 'E2EMERGE', prenom = 'Doublon', sexe = 'M', id_club = ?, num_licence = 'E2E0000001'",
        [['type' => 'i', 'value' => $id_club]]);
    echo json_encode(['real' => $real, 'duplicate' => $duplicate]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
