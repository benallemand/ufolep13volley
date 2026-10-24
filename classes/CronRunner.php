<?php

/**
 * Tâches planifiées lancées par HTTP (issue #450).
 *
 * Pendant la panne des crons OVH, GitHub Actions lance les tâches. En SSH,
 * l'hébergement refuse `mail()` (« User … is not allowed to submit mail ») et
 * ferme en sortie les ports SMTP : aucun e-mail des crons ne partait. Côté web,
 * `mail()` fonctionne : `rest/cron.php` y fait tourner la tâche, sur appel
 * authentifié par un jeton partagé.
 */
class CronRunner
{
    public const TASKS = array('hourly', 'daily', 'weekly');

    /**
     * Seul un POST qui porte le jeton du `.env` passe. Sans jeton configuré,
     * rien ne passe : le point d'entrée est fermé par défaut.
     */
    public static function authorized(string $method, ?string $token, ?string $expected): bool
    {
        if ($method !== 'POST' || trim((string)$expected) === '' || $token === null) {
            return false;
        }
        return hash_equals($expected, $token);
    }

    /**
     * Script de la tâche, ou null si elle n'est pas dans la liste : le nom ne
     * sert jamais à composer un chemin.
     */
    public static function script(?string $task): ?string
    {
        if (!in_array($task, self::TASKS, true)) {
            return null;
        }
        return __DIR__ . '/../cron/' . $task . '.php';
    }

    /**
     * Les scripts rendent la main même en cas d'erreur : un échec se lit dans
     * leur sortie (exception en JSON, envoi d'e-mail refusé).
     */
    public static function failed(string $output): bool
    {
        return str_contains($output, '"success":false')
            || str_contains($output, "Erreur dans l'envoi de mail");
    }
}
