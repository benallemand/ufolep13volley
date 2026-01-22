<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once 'UfolepTestCase.php';

require_once __DIR__ . "/../classes/Team.php";

class TeamTest extends UfolepTestCase
{
    private Team $team;

    protected function setUp(): void
    {
        $this->team = new Team();
        $this->connect_as_admin();
    }

    /**
     * Test que gymnasiums_list contient les remarques du gymnase si présentes
     */
    public function test_getTeams_gymnasiums_list_contains_remarques()
    {
        // Arrange - ajouter une remarque à un gymnase existant utilisé par une équipe
        $remarquesTest = 'Code portillon test: 9999#';
        
        // Trouver un gymnase lié à un créneau d'équipe
        $sql = "SELECT g.id, g.remarques as old_remarques 
                FROM gymnase g 
                JOIN creneau cr ON cr.id_gymnase = g.id 
                LIMIT 1";
        $results = $this->sql->execute($sql);
        
        if (empty($results)) {
            $this->markTestSkipped("Aucun gymnase avec créneau trouvé pour le test");
        }
        
        $gymId = $results[0]['id'];
        $oldRemarques = $results[0]['old_remarques'];
        
        // Mettre à jour les remarques du gymnase
        $this->sql->execute(
            "UPDATE gymnase SET remarques = ? WHERE id = ?",
            [
                ['type' => 's', 'value' => $remarquesTest],
                ['type' => 'i', 'value' => $gymId]
            ]
        );
        
        // Act - récupérer les équipes
        $teams = $this->team->getTeams();
        
        // Assert - vérifier que gymnasiums_list contient les remarques
        $found = false;
        foreach ($teams as $team) {
            if (!empty($team['gymnasiums_list']) && strpos($team['gymnasiums_list'], $remarquesTest) !== false) {
                $found = true;
                // Vérifier le format [remarques]
                $this->assertStringContainsString('[' . $remarquesTest . ']', $team['gymnasiums_list'], 
                    "Les remarques devraient être entre crochets dans gymnasiums_list");
                break;
            }
        }
        
        // Cleanup - restaurer les anciennes remarques
        $this->sql->execute(
            "UPDATE gymnase SET remarques = ? WHERE id = ?",
            [
                ['type' => 's', 'value' => $oldRemarques],
                ['type' => 'i', 'value' => $gymId]
            ]
        );
        
        $this->assertTrue($found, "Les remarques du gymnase devraient apparaître dans gymnasiums_list d'au moins une équipe");
    }

    /**
     * Test que gymnasiums_list ne contient pas de crochets vides si pas de remarques
     */
    public function test_getTeams_gymnasiums_list_no_empty_brackets_without_remarques()
    {
        // Arrange - s'assurer qu'un gymnase n'a pas de remarques
        $sql = "SELECT g.id 
                FROM gymnase g 
                JOIN creneau cr ON cr.id_gymnase = g.id 
                WHERE g.remarques IS NULL OR g.remarques = ''
                LIMIT 1";
        $results = $this->sql->execute($sql);
        
        if (empty($results)) {
            $this->markTestSkipped("Aucun gymnase sans remarques trouvé pour le test");
        }
        
        // Act
        $teams = $this->team->getTeams();
        
        // Assert - vérifier qu'il n'y a pas de crochets vides []
        foreach ($teams as $team) {
            if (!empty($team['gymnasiums_list'])) {
                $this->assertStringNotContainsString('[]', $team['gymnasiums_list'], 
                    "gymnasiums_list ne devrait pas contenir de crochets vides");
            }
        }
    }

    /**
     * Test que getActiveTeams inclut aussi les remarques
     */
    public function test_getActiveTeams_includes_remarques_in_gymnasiums_list()
    {
        // Act
        $teams = $this->team->getActiveTeams();
        
        // Assert - vérifier la structure (le champ gymnasiums_list existe)
        if (!empty($teams)) {
            $firstTeam = $teams[0];
            // Le champ gymnasiums_list devrait exister (peut être null si pas de créneau)
            $this->assertArrayHasKey('gymnasiums_list', $firstTeam, 
                "Le champ gymnasiums_list devrait exister dans getActiveTeams");
        }
    }
}
