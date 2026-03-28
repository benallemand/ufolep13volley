<?php
/**
 * E2E test helper — creates real 1/8-finale matches for kf and cf competitions
 * with date_reception and id_gymnasium so that .match-date-display and the modal
 * are properly rendered in the bracket viewer.
 *
 * SECURITY: this file must never be deployed to production.
 */
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$isTestEnv   = getenv('APP_ENV') === 'test';
if (!$isLocalhost && !$isTestEnv) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/SqlManager.php';
require_once __DIR__ . '/../../classes/Generic.php';
require_once __DIR__ . '/../../classes/Rank.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

try {
    $sql = new SqlManager();
    $rank = new Rank();

    // --- Gymnase de test (réutilisé depuis test_setup si existant) ---
    $gymResult = $sql->execute("SELECT id FROM gymnase WHERE nom = 'Gymnase E2E Test' LIMIT 1");
    if (empty($gymResult)) {
        $id_gymnasium = $sql->execute("INSERT INTO gymnase SET nom = 'Gymnase E2E Test'");
    } else {
        $id_gymnasium = (int)$gymResult[0]['id'];
    }

    $createdMatches = [];

    foreach (['kf', 'cf'] as $code) {
        // Lire le tirage pour trouver les paires d'équipes des 1/8
        try {
            $draw = $rank->getFinalsDrawResolved($code);
        } catch (Exception $e) {
            // Pas de tirage pour cette compétition — on passe
            continue;
        }

        // Trouver la première paire avec deux équipes résolues
        $firstPairing = null;
        foreach ($draw['rounds']['1_8'] as $match) {
            if (!empty($match['team1_resolved']) && !empty($match['team2_resolved'])) {
                $firstPairing = $match;
                break;
            }
        }

        if (!$firstPairing) continue;

        $id_equipe_dom = (int)$firstPairing['team1_resolved']['id_equipe'];
        $id_equipe_ext = (int)$firstPairing['team2_resolved']['id_equipe'];
        $code_upper    = strtoupper($code);

        // Nettoyage préventif des anciens matchs E2E pour cette compétition
        $sql->execute("DELETE FROM live_scores WHERE id_match LIKE 'E2E_{$code_upper}_%'");
        $sql->execute("DELETE FROM matches WHERE code_match LIKE 'E2E_{$code_upper}_%'");

        $code_match = 'E2E_' . $code_upper . '_' . date('ymdHis');

        $sql->execute(
            "INSERT INTO matches SET
                code_match       = ?,
                code_competition = ?,
                division         = '1',
                id_equipe_dom    = ?,
                id_equipe_ext    = ?,
                date_reception   = '2026-06-01',
                date_original    = '2026-06-01',
                id_gymnasium     = ?,
                match_status     = 'CONFIRMED'",
            [
                ['type' => 's', 'value' => $code_match],
                ['type' => 's', 'value' => $code],
                ['type' => 'i', 'value' => $id_equipe_dom],
                ['type' => 'i', 'value' => $id_equipe_ext],
                ['type' => 'i', 'value' => $id_gymnasium],
            ]
        );

        $createdMatches[$code] = $code_match;
    }

    echo json_encode(['success' => true, 'matches' => $createdMatches]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
