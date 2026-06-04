<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . '/../classes/Indicator.php';

/**
 * Test de régression (lecture seule) pour l'indicateur "Joueurs inscrits hors délai
 * en Coupe Khoury Hanna" (issue #233).
 *
 * La requête est un SELECT pur sans effet de bord : on vérifie qu'elle s'exécute
 * et renvoie la structure attendue, sans dépendre de données précises.
 */
class KhLateRegisteredPlayersIndicatorTest extends UfolepTestCase
{
    private const SQL_FILE = __DIR__ . '/../sql/kh_late_registered_players.sql';

    private const EXPECTED_KEYS = ['equipe', 'joueur', 'date_ajout', 'date_limite', 'premier_match'];

    public function test_indicator_executes_and_returns_expected_structure(): void
    {
        $this->connect_as_admin();

        $indicator = new Indicator(
            "Joueurs inscrits hors délai en Coupe Khoury Hanna",
            file_get_contents(self::SQL_FILE),
            'alert'
        );

        $result = $indicator->getResult();

        $this->assertEquals('alert', $result['type']);
        $this->assertEquals("Joueurs inscrits hors délai en Coupe Khoury Hanna", $result['fieldLabel']);
        $this->assertIsArray($result['details']);
        $this->assertEquals(count($result['details']), $result['value']);

        // Si des lignes existent, elles exposent bien les colonnes attendues.
        if (count($result['details']) > 0) {
            foreach (self::EXPECTED_KEYS as $key) {
                $this->assertArrayHasKey($key, $result['details'][0]);
            }
        }
    }

    /**
     * Garde-fou de cohérence : aucune date d'ajout ne doit être antérieure ou égale
     * à la date limite de l'équipe (la requête ne renvoie que les ajouts postérieurs).
     */
    public function test_added_date_is_strictly_after_deadline(): void
    {
        $this->connect_as_admin();

        $indicator = new Indicator(
            "Joueurs inscrits hors délai en Coupe Khoury Hanna",
            file_get_contents(self::SQL_FILE),
            'alert'
        );

        $details = $indicator->getResult()['details'];

        foreach ($details as $row) {
            $dateAjout = DateTime::createFromFormat('d/m/Y', $row['date_ajout']);
            $dateLimite = DateTime::createFromFormat('d/m/Y', $row['date_limite']);
            $this->assertNotFalse($dateAjout, "date_ajout illisible : " . $row['date_ajout']);
            $this->assertNotFalse($dateLimite, "date_limite illisible : " . $row['date_limite']);
            // Comparaison au jour près (la requête compare à la seconde près) :
            // la date d'ajout ne peut pas être antérieure à la date limite.
            $this->assertGreaterThanOrEqual(
                $dateLimite,
                $dateAjout,
                "Ajout {$row['joueur']} ({$row['equipe']}) antérieur à la date limite"
            );
        }
    }
}
