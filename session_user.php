<?php
require_once __DIR__ . '/classes/UserManager.php';
if (!UserManager::is_connected()) {
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}
header('Content-Type: application/json');
echo json_encode($_SESSION);
?>
