<?php
require_once __DIR__ . '/classes/UserManager.php';
if (!UserManager::is_connected()) {
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}
header('Content-Type: application/json');
$response = $_SESSION;
$response['is_acting_as'] = UserManager::is_acting_as();
if (UserManager::is_acting_as()) {
    $response['original_admin'] = UserManager::get_original_admin();
}
echo json_encode($response);
?>
