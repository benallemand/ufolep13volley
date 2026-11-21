<?php
/**
 * E2E test helper (#490) — page de match unique, parcours des deux équipes.
 *
 * - `?action=setup` (défaut) : crée deux équipes de 4 joueurs avec photo, un
 *   match confirmé joué la veille, et ouvre la session du responsable de
 *   l'équipe à domicile ;
 * - `?action=as&side=dom|ext` : bascule la session sur le responsable de
 *   l'autre équipe (même navigateur, même cookie) ;
 * - `?action=touch` : l'« autre équipe » enregistre un présent de plus,
 *   directement en base, pour provoquer un conflit de version ;
 * - `?action=teardown` : supprime tout ce que le helper a créé.
 *
 * Returns JSON: { success, id_match, code_match, teams: {dom, ext}, players: {dom: [...], ext: [...]} }
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

const PREFIX = 'E2E490';
const CODE = 'E2F490';
const COMPETITION = 'e9';

function teardown(SqlManager $sql): void
{
    $sql->execute("DELETE FROM match_player WHERE id_match IN (SELECT id_match FROM matches WHERE code_match = ?)",
        [['type' => 's', 'value' => CODE]]);
    $sql->execute("DELETE FROM survey WHERE id_match IN (SELECT id_match FROM matches WHERE code_match = ?)",
        [['type' => 's', 'value' => CODE]]);
    $sql->execute("DELETE FROM live_scores WHERE id_match = ?", [['type' => 's', 'value' => CODE]]);
    $sql->execute("DELETE FROM matches WHERE code_match = ?", [['type' => 's', 'value' => CODE]]);
    $sql->execute("DELETE FROM activity WHERE comment LIKE ?", [['type' => 's', 'value' => '%' . CODE . '%']]);
    $sql->execute("DELETE FROM joueur_equipe WHERE id_joueur IN (SELECT id FROM joueurs WHERE nom = ?)",
        [['type' => 's', 'value' => PREFIX]]);
    $sql->execute("DELETE FROM joueurs WHERE nom = ?", [['type' => 's', 'value' => PREFIX]]);
    $sql->execute("DELETE FROM photos WHERE path_photo = 'players_pics/e2e490.jpg'");
    $sql->execute("DELETE FROM creneau WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE ?)",
        [['type' => 's', 'value' => PREFIX . ' %']]);
    $sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE ?", [['type' => 's', 'value' => PREFIX . ' %']]);
    $sql->execute("DELETE FROM clubs WHERE nom LIKE ?", [['type' => 's', 'value' => PREFIX . ' club %']]);
    $sql->execute("DELETE FROM gymnase WHERE nom = ?", [['type' => 's', 'value' => PREFIX . ' gymnase']]);
    $sql->execute("DELETE FROM competitions WHERE code_competition = ?", [['type' => 's', 'value' => COMPETITION]]);
}

function match_row(SqlManager $sql): array
{
    $rows = $sql->execute("SELECT id_match, id_equipe_dom, id_equipe_ext FROM matches WHERE code_match = ?",
        [['type' => 's', 'value' => CODE]]);
    if (empty($rows)) {
        throw new Exception("Match de test absent : lancer d'abord action=setup.");
    }
    return $rows[0];
}

function open_session(SqlManager $sql, int $id_equipe): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $account = $sql->execute(
        "SELECT id FROM comptes_acces WHERE id = 1
         UNION ALL
         SELECT MIN(id) FROM comptes_acces WHERE is_admin + 0 > 0
         LIMIT 1");
    $_SESSION['is_admin'] = false;
    $_SESSION['is_team_leader'] = true;
    $_SESSION['login'] = 'e2e_test';
    $_SESSION['id_user'] = (int)($account[0]['id'] ?? 1);
    $_SESSION['id_equipe'] = $id_equipe;
}

try {
    $sql = new SqlManager();
    $action = $_GET['action'] ?? 'setup';

    if ($action === 'teardown') {
        teardown($sql);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'as') {
        $match = match_row($sql);
        $side = ($_GET['side'] ?? 'dom') === 'ext' ? 'ext' : 'dom';
        open_session($sql, (int)$match["id_equipe_$side"]);
        echo json_encode(['success' => true, 'side' => $side, 'session_id' => session_id()]);
        exit;
    }
    if ($action === 'played') {
        // match joué : 3 présents par équipe, fiches signées, score 3-0
        $match = match_row($sql);
        foreach (['dom', 'ext'] as $side) {
            $sql->execute(
                "INSERT INTO match_player (id_match, id_player)
                 SELECT ?, je.id_joueur FROM joueur_equipe je WHERE je.id_equipe = ? ORDER BY je.id_joueur LIMIT 3",
                [['type' => 'i', 'value' => (int)$match['id_match']], ['type' => 'i', 'value' => (int)$match["id_equipe_$side"]]]);
        }
        $sql->execute("UPDATE matches SET is_sign_team_dom = 1, is_sign_team_ext = 1,
                         set_1_dom = 25, set_1_ext = 20, set_2_dom = 25, set_2_ext = 18, set_3_dom = 25, set_3_ext = 22
                       WHERE id_match = ?", [['type' => 'i', 'value' => (int)$match['id_match']]]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'touch') {
        // l'autre équipe ajoute son 4e joueur pendant que la page est ouverte
        $match = match_row($sql);
        $player = $sql->execute(
            "SELECT j.id FROM joueurs j JOIN joueur_equipe je ON je.id_joueur = j.id
             WHERE je.id_equipe = ? AND j.id NOT IN (SELECT id_player FROM match_player WHERE id_match = ?)
             ORDER BY j.id LIMIT 1",
            [['type' => 'i', 'value' => (int)$match['id_equipe_ext']], ['type' => 'i', 'value' => (int)$match['id_match']]]);
        $sql->execute("INSERT INTO match_player SET id_match = ?, id_player = ?",
            [['type' => 'i', 'value' => (int)$match['id_match']], ['type' => 'i', 'value' => (int)$player[0]['id']]]);
        echo json_encode(['success' => true, 'id_player' => (int)$player[0]['id']]);
        exit;
    }

    teardown($sql);
    $sql->execute("INSERT INTO competitions SET code_competition = ?, libelle = 'Championnat E2E', id_compet_maitre = ?",
        [['type' => 's', 'value' => COMPETITION], ['type' => 's', 'value' => COMPETITION]]);
    $id_photo = $sql->execute("INSERT INTO photos SET path_photo = 'players_pics/e2e490.jpg'");
    $id_gym = $sql->execute("INSERT INTO gymnase SET nom = ?", [['type' => 's', 'value' => PREFIX . ' gymnase']]);
    $names = ['dom' => 'Les Mouettes', 'ext' => 'Les Goélands'];
    $first = ['dom' => ['Alice', 'Bruno', 'Chloé', 'David'], 'ext' => ['Emma', 'Farid', 'Gaëlle', 'Hugo']];
    $teams = [];
    $players = [];
    foreach (['dom', 'ext'] as $side) {
        $id_club = $sql->execute("INSERT INTO clubs SET nom = ?", [['type' => 's', 'value' => PREFIX . " club $side"]]);
        $teams[$side] = (int)$sql->execute("INSERT INTO equipes SET code_competition = ?, nom_equipe = ?, id_club = ?",
            [['type' => 's', 'value' => COMPETITION], ['type' => 's', 'value' => PREFIX . ' ' . $names[$side]],
             ['type' => 'i', 'value' => $id_club]]);
        foreach ($first[$side] as $i => $prenom) {
            $id = (int)$sql->execute(
                "INSERT INTO joueurs SET nom = ?, prenom = ?, sexe = ?, id_photo = ?, id_club = ?,
                        num_licence = ?, date_homologation = CURRENT_DATE - INTERVAL 30 DAY, email = ?",
                [['type' => 's', 'value' => PREFIX], ['type' => 's', 'value' => $prenom],
                 ['type' => 's', 'value' => $i % 2 ? 'M' : 'F'], ['type' => 'i', 'value' => $id_photo],
                 ['type' => 'i', 'value' => $id_club], ['type' => 's', 'value' => "E2E490$side$i"],
                 ['type' => 's', 'value' => $i === 0 ? "e2e490_$side@test.local" : null]]);
            $sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?, is_leader = ?",
                [['type' => 'i', 'value' => $id], ['type' => 'i', 'value' => $teams[$side]],
                 ['type' => 'i', 'value' => $i === 0 ? 1 : 0]]);
            $players[$side][] = ['id' => $id, 'name' => $prenom . ' ' . PREFIX];
        }
    }
    $sql->execute("INSERT INTO creneau SET id_gymnase = ?, jour = ELT(WEEKDAY(CURRENT_DATE - INTERVAL 1 DAY) + 2,
                    'Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), heure = '20:30', id_equipe = ?",
        [['type' => 'i', 'value' => $id_gym], ['type' => 'i', 'value' => $teams['dom']]]);
    $id_match = (int)$sql->execute(
        "INSERT INTO matches SET code_match = ?, code_competition = ?, division = '1',
            id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
            date_reception = CURRENT_DATE - INTERVAL 1 DAY, date_original = CURRENT_DATE - INTERVAL 1 DAY,
            match_status = 'CONFIRMED'",
        [['type' => 's', 'value' => CODE], ['type' => 's', 'value' => COMPETITION],
         ['type' => 'i', 'value' => $teams['dom']], ['type' => 'i', 'value' => $teams['ext']],
         ['type' => 'i', 'value' => $id_gym]]);
    open_session($sql, $teams['dom']);
    echo json_encode([
        'success' => true,
        'id_match' => $id_match,
        'code_match' => CODE,
        'teams' => ['dom' => PREFIX . ' ' . $names['dom'], 'ext' => PREFIX . ' ' . $names['ext']],
        'players' => $players,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
