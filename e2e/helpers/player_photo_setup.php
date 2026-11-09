<?php
/**
 * Issue #461 — helper E2E : session de responsable sur une équipe engagée, et
 * le premier joueur de son effectif, dont on remplace la photo.
 *
 * `?teardown=1&id=…&id_photo=…` remet la photo d'origine du joueur et supprime
 * la photo envoyée par le test (ligne `photos`, fichiers et journal).
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
    if (!empty($_GET['teardown'])) {
        $id = (int)$_GET['id'];
        $original = ($_GET['id_photo'] ?? '') === '' ? null : (int)$_GET['id_photo'];
        $current = $sql->execute(
            "SELECT j.nom, j.prenom, j.id_photo, p.path_photo FROM joueurs j LEFT JOIN photos p ON p.id = j.id_photo WHERE j.id = ?",
            array(array('type' => 'i', 'value' => $id)));
        if (!empty($current) && (int)$current[0]['id_photo'] !== (int)$original) {
            $path = (string)$current[0]['path_photo'];
            foreach (array($path, str_replace('players_pics', 'players_pics_low', $path)) as $file) {
                if (str_starts_with($file, 'players_pics') && is_file(__DIR__ . '/../../' . $file)) {
                    unlink(__DIR__ . '/../../' . $file);
                }
            }
            $sql->execute("UPDATE joueurs SET id_photo = ? WHERE id = ?",
                array(array('type' => 'i', 'value' => $original), array('type' => 'i', 'value' => $id)));
            $sql->execute("DELETE FROM photos WHERE id = ?",
                array(array('type' => 'i', 'value' => (int)$current[0]['id_photo'])));
            $sql->execute(
                "DELETE FROM activity WHERE comment = ? AND activity_date >= NOW() - INTERVAL 1 HOUR",
                array(array('type' => 's', 'value' => "Une nouvelle photo a ete transmise pour le joueur {$current[0]['prenom']} {$current[0]['nom']}")));
        }
        echo json_encode(['success' => true]);
        exit(0);
    }

    $rows = $sql->execute(
        "SELECT je.id_equipe, j.id, UPPER(j.nom) AS nom, j.prenom, j.id_photo
         FROM joueur_equipe je
         JOIN joueurs j ON j.id = je.id_joueur
         JOIN classements c ON c.id_equipe = je.id_equipe
         WHERE j.nom <> '' AND j.prenom <> ''
         ORDER BY je.id_equipe, j.id
         LIMIT 1");
    if (empty($rows)) {
        throw new Exception('aucun joueur dans une équipe engagée');
    }
    $player = $rows[0];

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    // profil admin, comme match_actions_setup.php : pas de contrôle d'équipe
    $account = $sql->execute(
        "SELECT id FROM comptes_acces WHERE id = 1
         UNION ALL
         SELECT MIN(id) FROM comptes_acces WHERE is_admin + 0 > 0
         LIMIT 1");
    // `?admin=0` : responsable ordinaire (issue #471, l'envoi forcé d'une photo
    // sans visage reconnu est réservé à l'admin). Les contrôles d'équipe passent
    // par l'équipe de session.
    $_SESSION['is_admin'] = ($_GET['admin'] ?? '1') !== '0';
    $_SESSION['is_team_leader'] = true;
    $_SESSION['login'] = 'e2e_test';
    $_SESSION['id_user'] = (int)($account[0]['id'] ?? 1);
    $_SESSION['id_equipe'] = (int)$player['id_equipe'];

    echo json_encode([
        'id' => (int)$player['id'],
        'nom' => $player['nom'],
        'prenom' => $player['prenom'],
        'id_photo' => $player['id_photo'] === null ? null : (int)$player['id_photo'],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
