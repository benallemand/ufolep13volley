<?php
/**
 * E2E test helper (#235) — crée deux VRAIS matchs programmés aujourd'hui :
 *  - un AVEC score final renseigné (3-0, sets 25-0) → is_match_score_filled = 1
 *  - un SANS score (0-0)                            → is_match_score_filled = 0
 *
 * Sert à valider :
 *  - l'affichage du score final dans l'encart "Matchs du jour" (home)
 *  - sur live.html : masquage du bouton scoreur + affichage du score final
 *
 * Comme today_matches_setup.php (#230), on recopie les FK d'un match déjà
 * visible dans matchs_view pour que les nouvelles lignes y remontent.
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

    // Nettoyage préventif (préfixe E2F, code_match en VARCHAR(20))
    $sql->execute("DELETE FROM live_scores WHERE id_match LIKE 'E2F%'");
    $sql->execute("DELETE FROM matches WHERE code_match LIKE 'E2F%'");

    // Échantillon depuis la vue : garantit des FK valides (cf. #230).
    $sample = $sql->execute(
        "SELECT code_competition, division, id_equipe_dom, id_equipe_ext, id_gymnasium, id_journee
         FROM matchs_view
         LIMIT 1"
    );
    if (empty($sample)) {
        throw new Exception("Aucun match modèle dans matchs_view en base de test.");
    }
    $s = $sample[0];

    $hasGym = !empty($s['id_gymnasium']);
    $gymClause = $hasGym ? "id_gymnasium = ?," : "";

    $insertMatch = static function (string $code_match, bool $withScore) use ($sql, $s, $hasGym, $gymClause) {
        $bindings = [
            ['type' => 's', 'value' => $code_match],
            ['type' => 's', 'value' => $s['code_competition']],
            ['type' => 's', 'value' => $s['division']],
            ['type' => 'i', 'value' => (int)$s['id_equipe_dom']],
            ['type' => 'i', 'value' => (int)$s['id_equipe_ext']],
        ];
        if ($hasGym) {
            $bindings[] = ['type' => 'i', 'value' => (int)$s['id_gymnasium']];
        }
        // Score final 3-0 : 3 sets gagnés 25-0 par l'équipe domicile.
        // is_match_score_filled (matchs_view) passe à 1 dès qu'une équipe gagne 3 sets.
        $scoreClause = $withScore
            ? "set_1_dom = 25, set_1_ext = 0,
               set_2_dom = 25, set_2_ext = 0,
               set_3_dom = 25, set_3_ext = 0,"
            : "";
        $sql->execute(
            "INSERT INTO matches SET
                code_match       = ?,
                code_competition = ?,
                division         = ?,
                id_equipe_dom    = ?,
                id_equipe_ext    = ?,
                $gymClause
                $scoreClause
                date_reception   = CURRENT_DATE,
                date_original    = CURRENT_DATE,
                match_status     = 'CONFIRMED'",
            $bindings
        );
    };

    $codeScored   = 'E2FS' . date('ymdHis'); // 4 + 12 = 16 car. (<= VARCHAR(20))
    $codeUnscored = 'E2FN' . date('ymdHis');

    $insertMatch($codeScored, true);
    $insertMatch($codeUnscored, false);

    echo json_encode([
        'success'        => true,
        'code_scored'    => $codeScored,
        'code_unscored'  => $codeUnscored,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
