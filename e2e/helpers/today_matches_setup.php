<?php
/**
 * E2E test helper (#230) — crée un VRAI match programmé aujourd'hui afin de
 * valider l'encart "Matchs du jour" de la page d'accueil.
 *
 * matchs_view fait plusieurs INNER JOIN (competitions, equipes, gymnase et
 * JOURNEES via id_journee). Pour que la ligne remonte dans la vue, on recopie
 * toutes les clés étrangères d'un match réel déjà visible (y compris
 * id_journee, qui était la pièce manquante) et on ne change que la date.
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

    // Nettoyage préventif (préfixe court : code_match est en VARCHAR(20))
    $sql->execute("DELETE FROM live_scores WHERE id_match LIKE 'E2D%'");
    $sql->execute("DELETE FROM matches WHERE code_match LIKE 'E2D%'");

    // Réutiliser les FK d'un match qui apparaît DÉJÀ dans matchs_view : en
    // échantillonnant depuis la vue (et non la table matches brute), on garantit
    // que toutes ses jointures INNER (competitions, equipes, ...) sont déjà
    // satisfaites — la nouvelle ligne héritera donc de FK valides et remontera
    // dans la vue. (Échantillonner depuis `matches` risquait de tomber sur un
    // match orphelin, ex. compétition 'ut' supprimée.)
    // id_journee / id_gymnasium sont des LEFT JOIN dans la vue (non requis pour
    // la visibilité), donc on n'impose pas qu'ils soient non-null : on prend
    // n'importe quelle ligne de la vue.
    $sample = $sql->execute(
        "SELECT code_competition, division, id_equipe_dom, id_equipe_ext, id_gymnasium, id_journee
         FROM matchs_view
         LIMIT 1"
    );
    if (empty($sample)) {
        throw new Exception("Aucun match modèle dans matchs_view en base de test.");
    }
    $s = $sample[0];

    $code_match = 'E2D' . date('ymdHis'); // 3 + 12 = 15 car. (<= VARCHAR(20))

    // id_gymnasium peut être null dans l'échantillon ; on l'omet alors du SET.
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

    $sql->execute(
        "INSERT INTO matches SET
            code_match       = ?,
            code_competition = ?,
            division         = ?,
            id_equipe_dom    = ?,
            id_equipe_ext    = ?,
            $gymClause
            date_reception   = CURRENT_DATE,
            date_original    = CURRENT_DATE,
            match_status     = 'CONFIRMED'",
        $bindings
    );

    // Nouvelle de test (pour valider l'affichage repliable des dernières
    // nouvelles). is_disabled = 0 pour qu'elle remonte dans getLastNews.
    $sql->execute("DELETE FROM news WHERE title = 'E2E News test'");
    $sql->execute(
        "INSERT INTO news (title, text, file_path, news_date, is_disabled) VALUES (?, ?, ?, NOW(), 0)",
        [
            ['type' => 's', 'value' => 'E2E News test'],
            ['type' => 's', 'value' => '<p>Contenu detaille E2E test</p>'],
            ['type' => 's', 'value' => ''],
        ]
    );

    echo json_encode(['success' => true, 'code_match' => $code_match]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
