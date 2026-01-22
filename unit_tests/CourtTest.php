<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once 'UfolepTestCase.php';

require_once __DIR__ . "/../classes/Court.php";

class CourtTest extends UfolepTestCase
{
    private Court $court;

    protected function setUp(): void
    {
        $this->court = new Court();
        $this->connect_as_admin();
    }

    public function test_getGymnasiums_returns_remarques_field()
    {
        // Act
        $gymnasiums = $this->court->getGymnasiums();
        
        // Assert - vérifier que le champ remarques existe dans les résultats
        $this->assertNotEmpty($gymnasiums, "La liste des gymnases ne devrait pas être vide");
        
        $firstGymnasium = $gymnasiums[0];
        $this->assertArrayHasKey('remarques', $firstGymnasium, "Le champ 'remarques' devrait exister dans les résultats");
    }

    public function test_saveGymnasium_with_remarques()
    {
        // Arrange - créer un gymnase de test avec remarques
        $testName = 'Test Gymnasium ' . time();
        $testRemarques = 'Code portillon: 1234#';
        
        // Act - sauvegarder avec remarques
        $this->court->saveGymnasium(
            $testName,
            '123 Test Street',
            '13000',
            'Marseille',
            '43.2965,5.3698',
            1,
            'remarques',
            null,
            $testRemarques
        );
        
        // Assert - vérifier que les remarques sont bien sauvegardées
        $gymnasiums = $this->court->getGymnasiums();
        $found = false;
        foreach ($gymnasiums as $gym) {
            if ($gym['nom'] === $testName) {
                $found = true;
                $this->assertEquals($testRemarques, $gym['remarques'], "Les remarques devraient être sauvegardées");
                
                // Cleanup - supprimer le gymnase de test
                $this->sql->execute("DELETE FROM gymnase WHERE id = ?", [
                    ['type' => 'i', 'value' => $gym['id']]
                ]);
                break;
            }
        }
        
        $this->assertTrue($found, "Le gymnase de test devrait avoir été créé");
    }

    public function test_update_gymnasium_remarques()
    {
        // Arrange - créer un gymnase puis le modifier
        $testName = 'Test Gym Update ' . time();
        $initialRemarques = 'Initial remarques';
        $updatedRemarques = 'Updated remarques - Code: 5678*';
        
        $this->court->saveGymnasium(
            $testName,
            '456 Update Street',
            '13001',
            'Aix-en-Provence',
            '43.5297,5.4474',
            2,
            null,
            null,
            $initialRemarques
        );
        
        // Récupérer l'ID du gymnase créé
        $gymnasiums = $this->court->getGymnasiums();
        $gymId = null;
        foreach ($gymnasiums as $gym) {
            if ($gym['nom'] === $testName) {
                $gymId = $gym['id'];
                break;
            }
        }
        
        $this->assertNotNull($gymId, "Le gymnase devrait avoir été créé");
        
        // Act - mettre à jour les remarques
        $this->court->saveGymnasium(
            $testName,
            '456 Update Street',
            '13001',
            'Aix-en-Provence',
            '43.5297,5.4474',
            2,
            'remarques',
            $gymId,
            $updatedRemarques
        );
        
        // Assert - vérifier la mise à jour
        $gymnasiums = $this->court->getGymnasiums();
        foreach ($gymnasiums as $gym) {
            if ($gym['id'] == $gymId) {
                $this->assertEquals($updatedRemarques, $gym['remarques'], "Les remarques devraient être mises à jour");
                
                // Cleanup
                $this->sql->execute("DELETE FROM gymnase WHERE id = ?", [
                    ['type' => 'i', 'value' => $gymId]
                ]);
                break;
            }
        }
    }
}
