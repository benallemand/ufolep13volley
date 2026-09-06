<?php
/**
 * E2E test helper — ouvre une session administrateur, sans autre effet de bord.
 *
 * Les specs de la migration de l'admin (issue #265) en ont toutes besoin. Le
 * helper existant `messages_setup.php` ouvrait bien une session admin, mais en
 * créant au passage une équipe et un email : dépendre de lui pour un test
 * d'interface d'administration mélangeait deux jeux de données.
 *
 * Returns JSON: { session_id, login, is_admin }
 *
 * SECURITY: this file must never be deployed to production.
 */
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$isTestEnv   = getenv('APP_ENV') === 'test';
if (!$isLocalhost && !$isTestEnv) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: application/json');

session_start();
$_SESSION['is_admin'] = true;
$_SESSION['login']    = 'e2e_admin';
$_SESSION['id_user']  = 1;

echo json_encode([
    'session_id' => session_id(),
    'login'      => $_SESSION['login'],
    'is_admin'   => true,
]);
