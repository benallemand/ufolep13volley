<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';

/**
 * Journal d'activité (`activity`).
 */
class ActivityJournalTest extends UfolepTestCase
{
    /**
     * Issue #423 : `activity.id` était un SMALLINT (32 767 au plus). Les
     * lignes anciennes sont purgées mais l'auto-incrément continue : en prod,
     * 28 329 le 12/10/2026 à environ 95 lignes par jour, soit la saturation
     * vers fin novembre, et l'échec de toute écriture qui journalise.
     */
    public function test_l_identifiant_du_journal_ne_sature_pas(): void
    {
        $rows = $this->sql->execute(
            "SELECT data_type FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'activity' AND column_name = 'id'");
        self::assertContains($rows[0]['data_type'] ?? $rows[0]['DATA_TYPE'], array('int', 'bigint'));
    }
}
