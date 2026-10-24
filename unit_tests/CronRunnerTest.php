<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/CronRunner.php';

use PHPUnit\Framework\TestCase;

/**
 * Issue #450 — tâches planifiées lancées par `rest/cron.php`, côté web.
 */
class CronRunnerTest extends TestCase
{
    public function test_seul_un_post_avec_le_bon_jeton_passe(): void
    {
        $this->assertTrue(CronRunner::authorized('POST', 's3cr3t', 's3cr3t'));
        $this->assertFalse(CronRunner::authorized('POST', 'faux', 's3cr3t'));
        $this->assertFalse(CronRunner::authorized('POST', null, 's3cr3t'));
        // un lien ouvert dans un navigateur (GET) ne lance rien
        $this->assertFalse(CronRunner::authorized('GET', 's3cr3t', 's3cr3t'));
    }

    public function test_sans_jeton_configure_rien_ne_passe(): void
    {
        $this->assertFalse(CronRunner::authorized('POST', '', ''));
        $this->assertFalse(CronRunner::authorized('POST', '', null));
        $this->assertFalse(CronRunner::authorized('POST', ' ', ' '));
    }

    public function test_seules_les_trois_taches_sont_lancees(): void
    {
        foreach (CronRunner::TASKS as $task) {
            $this->assertFileExists(CronRunner::script($task), $task);
        }
        foreach (array(null, '', 'test', '../classes/Emails', 'hourly.php', 'Hourly') as $task) {
            $this->assertNull(CronRunner::script($task), var_export($task, true));
        }
    }

    public function test_un_echec_se_lit_dans_la_sortie(): void
    {
        $this->assertFalse(CronRunner::failed(''));
        $this->assertTrue(CronRunner::failed('{"success":false,"message":"Base indisponible"}'));
        // sortie de send_pending_emails() quand un envoi échoue (#450)
        $this->assertTrue(CronRunner::failed(
            "ERRORErreur dans l'envoi de mail : Could not instantiate mail function."));
    }
}
