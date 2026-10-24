<?php
/**
 * Lance une tâche planifiée côté web, là où `mail()` fonctionne (issue #450).
 *
 * Appelé par `.github/workflows/crons-ssh.yml` :
 *   POST /rest/cron.php?task=hourly|daily|weekly
 *   X-Cron-Token: <CRON_TOKEN du .env>
 *
 * Réponse JSON : { success, task, output } ; 401 sans le bon jeton, 404 pour
 * une tâche inconnue, 500 si la tâche a échoué (le job GitHub échoue alors).
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/CronRunner.php';
require_once __DIR__ . '/json_response.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();

$method = $_SERVER['REQUEST_METHOD'] ?? '';
$token = $_SERVER['HTTP_X_CRON_TOKEN'] ?? null;
if (!CronRunner::authorized($method, $token, $_ENV['CRON_TOKEN'] ?? null)) {
    http_response_code(401);
    send_json(array('success' => false, 'message' => 'Non autorisé'));
    exit;
}
$task = $_GET['task'] ?? null;
$script = CronRunner::script($task);
if ($script === null) {
    http_response_code(404);
    send_json(array('success' => false, 'message' => 'Tâche inconnue'));
    exit;
}

// La file d'e-mails peut prendre du temps : on va au bout même si l'appelant
// coupe la connexion.
ignore_user_abort(true);
set_time_limit(300);
ob_start();
require $script;
$output = (string)ob_get_clean();

$failed = CronRunner::failed($output);
http_response_code($failed ? 500 : 200);
send_json(array('success' => !$failed, 'task' => $task, 'output' => $output));
