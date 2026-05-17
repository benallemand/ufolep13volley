<?php
/**
 * E2E test helper — crée une session responsable d'équipe + un email de test non lu.
 * Returns JSON: { id_equipe, id_email, session_id }
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
require_once __DIR__ . '/../../classes/Emails.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

try {
    $sql = new SqlManager();

    // Récupérer une équipe dont le responsable a un email
    $rows = $sql->execute(
        "SELECT je.id_equipe, j.email
         FROM joueur_equipe je
         JOIN joueurs j ON j.id = je.id_joueur
         WHERE je.is_leader + 0 > 0
           AND j.email IS NOT NULL
           AND j.email != ''
         LIMIT 1"
    );
    if (empty($rows)) {
        throw new RuntimeException('No team leader with email found in database');
    }
    $id_equipe    = (int)$rows[0]['id_equipe'];
    $leader_email = $rows[0]['email'];

    // Créer un email de test non lu, destiné à ce responsable
    $sql->execute(
        "DELETE FROM emails WHERE subject = 'E2E_MESSAGES_TEST'"
    );
    $id_email = $sql->execute(
        "INSERT INTO emails SET
            from_email     = 'test@ufolep13volley.org',
            to_email       = ?,
            cc             = '',
            bcc            = '',
            subject        = 'E2E_MESSAGES_TEST',
            body           = '<p>Ceci est un message de test E2E pour l\'issue #221.</p>',
            sending_status = 'DONE',
            is_read        = 0",
        [['type' => 's', 'value' => $leader_email]]
    );

    // Ouvrir session admin (contourne la vérification d'équipe côté backend)
    session_start();
    $_SESSION['profile_name'] = 'ADMINISTRATEUR';
    $_SESSION['login']        = 'e2e_test';
    $_SESSION['id_user']      = 1;
    $_SESSION['id_equipe']    = $id_equipe;

    echo json_encode([
        'id_equipe'  => $id_equipe,
        'id_email'   => (int)$id_email,
        'session_id' => session_id(),
        'leader_email' => $leader_email,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
