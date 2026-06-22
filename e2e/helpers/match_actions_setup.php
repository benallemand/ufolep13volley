<?php
/**
 * E2E test helper (#240) — ouvre une session responsable d'équipe et crée un
 * match DÉJÀ JOUÉ (date passée) sans aucune donnée saisie, de sorte qu'une
 * action soit en attente ("Remplir les joueurs présents") et qu'un toast
 * apparaisse à la connexion.
 *
 * On recopie les FK d'un match déjà visible dans matchs_view (cf.
 * today_matches_setup.php) pour que la ligne insérée remonte bien dans la vue.
 *
 * Returns JSON: { success, id_match, code_match, id_equipe, equipe_adverse, expected_label }
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
require_once __DIR__ . '/../../classes/MatchMgr.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

try {
    $sql = new SqlManager();

    // Nettoyage préventif (préfixe court : code_match est en VARCHAR(20))
    $sql->execute("DELETE FROM matches WHERE code_match LIKE 'E2A%'");

    // Échantillon depuis matchs_view => FK valides garanties (jointures INNER OK).
    $sample = $sql->execute(
        "SELECT code_competition, division, id_equipe_dom, id_equipe_ext, id_gymnasium, id_journee
         FROM matchs_view
         LIMIT 1"
    );
    if (empty($sample)) {
        throw new Exception("Aucun match modèle dans matchs_view en base de test.");
    }
    $s = $sample[0];

    $code_match = 'E2A' . date('ymdHis'); // 3 + 12 = 15 car. (<= VARCHAR(20))
    $hasGym = !empty($s['id_gymnasium']);
    $gymClause = $hasGym ? "id_gymnasium = ?," : "";

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

    // Match daté d'il y a 7 jours, sans score/joueurs/signatures => action en attente.
    $id_match = $sql->execute(
        "INSERT INTO matches SET
            code_match       = ?,
            code_competition = ?,
            division         = ?,
            id_equipe_dom    = ?,
            id_equipe_ext    = ?,
            $gymClause
            date_reception   = CURRENT_DATE - INTERVAL 7 DAY,
            date_original    = CURRENT_DATE - INTERVAL 7 DAY,
            match_status     = 'CONFIRMED'",
        $bindings
    );

    $id_equipe = (int)$s['id_equipe_dom'];

    // Calcule la VRAIE prochaine action attendue (selon l'état réel dans matchs_view),
    // pour que le test cible le bon libellé sans présumer du comportement de la vue.
    $rows = (new MatchMgr())->get_matches("m.code_match = '$code_match'");
    if (empty($rows)) {
        throw new Exception("Le match de test n'apparaît pas dans matchs_view.");
    }
    $row = $rows[0];
    $equipe_adverse = $row['equipe_ext'];
    // Présents saisis ? (match neuf => aucun match_player => false)
    $cnt = $sql->execute(
        "SELECT COUNT(*) AS c FROM match_player mp
         JOIN joueur_equipe je ON je.id_joueur = mp.id_player
         WHERE mp.id_match = ? AND je.id_equipe = ?",
        [['type' => 'i', 'value' => (int)$row['id_match']], ['type' => 'i', 'value' => $id_equipe]]
    );
    $presentsFilled = ((int)($cnt[0]['c'] ?? 0)) > 0;
    $action = (new MatchMgr())->getNextMatchActionForSide($row, 'dom', $presentsFilled);
    if ($action === null) {
        throw new Exception("Le match de test n'a aucune action en attente (état inattendu).");
    }

    // Ouvrir une session "responsable" de l'équipe dom (profil admin pour
    // contourner les vérifications d'équipe, comme messages_setup.php).
    // La session peut déjà être active (démarrée par MatchMgr/Generic) : on évite
    // alors un Notice qui casserait le JSON renvoyé.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['profile_name'] = 'ADMINISTRATEUR';
    $_SESSION['login']        = 'e2e_test';
    $_SESSION['id_user']      = 1;
    $_SESSION['id_equipe']    = $id_equipe;

    echo json_encode([
        'success'        => true,
        'id_match'       => (int)$id_match,
        'code_match'     => $code_match,
        'id_equipe'      => $id_equipe,
        'equipe_adverse' => $equipe_adverse,
        'expected_action' => $action['action'],
        'expected_label' => $action['label'],
        'expected_url'   => $action['url'],
        'session_id'     => session_id(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
