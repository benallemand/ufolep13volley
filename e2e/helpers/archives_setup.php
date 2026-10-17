<?php
/**
 * E2E test helper (#435) — une saison archivée de test, 2001-2002, sans
 * aucune donnée réelle : deux équipes, un match archivé en division Z9 du
 * championnat masculin, reconstitué (source REBUILT) avec une pénalité.
 *
 * `archives_teardown.php` (ou le prochain appel) supprime tout.
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
require_once __DIR__ . '/../../classes/Rank.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

try {
    $sql = new SqlManager();
    require __DIR__ . '/archives_cleanup.inc.php';
    archives_cleanup($sql);

    $id_court = $sql->execute("SELECT id FROM gymnase ORDER BY id LIMIT 1")[0]['id'] ?? null;
    $teams = array();
    foreach (array('E2E Archives A', 'E2E Archives B') as $name) {
        $teams[] = (int)$sql->execute("INSERT INTO equipes SET code_competition = 'm', nom_equipe = ?",
            array(array('type' => 's', 'value' => $name)));
    }
    $sql->execute(
        "INSERT INTO matches SET code_match = 'E2EARCH1', code_competition = 'm', division = 'Z9',
            id_equipe_dom = ?, id_equipe_ext = ?, date_reception = '2001-11-06', date_original = '2001-11-06',
            id_gymnasium = ?, match_status = 'ARCHIVED', is_sign_match_dom = 1, is_sign_match_ext = 1,
            set_1_dom = 25, set_1_ext = 20, set_2_dom = 25, set_2_ext = 18, set_3_dom = 25, set_3_ext = 22",
        array(
            array('type' => 'i', 'value' => $teams[0]),
            array('type' => 'i', 'value' => $teams[1]),
            array('type' => 'i', 'value' => $id_court),
        ));
    (new Rank())->rebuild_period('m', array('saison' => '2001-2002', 'demi_saison' => 1),
        '2001-07-01', '2002-01-31', array(), array($teams[1] => 1));

    echo json_encode(['success' => true, 'saison' => '2001-2002', 'demi' => 1, 'competition' => 'm', 'division' => 'Z9']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
