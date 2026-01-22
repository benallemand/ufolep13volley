<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . "/../classes/Players.php";
require_once 'UfolepTestCase.php';

class PlayersTest extends UfolepTestCase
{
    private Players $players;

    protected function setUp(): void
    {
        parent::setUp();
        $this->players = new Players();
    }

    public function test_generateLowPhoto()
    {
        $this->players->generateLowPhoto('players_pics/AHOUANSOUVirginie1.jpg');
        $this->assertTrue(1 == 1);
    }

    public function test_getPlayersPdf()
    {
        $this->connect_as_admin();
        $this->players->getPlayersPdf(344);
        $this->assertTrue(1 == 1);
    }

    public function test_import_licence_ardeche_creates_club()
    {
        $this->connect_as_admin();
        
        $club = new Club();
        $files = new Files();
        
        // Supprimer le club et le joueur s'ils existent déjà (pour pouvoir rejouer le test)
        $existingClub = $club->get_one("affiliation_number = ?", array(array('type' => 's', 'value' => '007281005')));
        if (!empty($existingClub)) {
            $club->delete($existingClub['id']);
        }
        
        $existingPlayer = $this->players->get_one("num_licence = ?", array(array('type' => 's', 'value' => '99996743')));
        if (!empty($existingPlayer)) {
            $this->players->delete($existingPlayer['id']);
        }
        
        // Extraire les données de la licence
        $licences = $files->get_licences_data(__DIR__ . '/files/licences/licence-ardeche.pdf');
        $this->assertCount(1, $licences);
        
        $licence = $licences[0];
        
        // Importer la licence (doit créer le club automatiquement)
        $this->players->search_player_and_save_from_licence($licence);
        
        // Vérifier que le club a été créé
        $newClub = $club->get_one("affiliation_number = ?", array(array('type' => 's', 'value' => '007281005')));
        $this->assertNotEmpty($newClub, "Le club devrait avoir été créé");
        $this->assertEquals('AMICALE LAIQUE SAINT PERAY', $newClub['nom']);
        
        // Vérifier que le joueur a été créé
        $newPlayer = $this->players->get_one("num_licence = ?", array(array('type' => 's', 'value' => '99996743')));
        $this->assertNotEmpty($newPlayer, "Le joueur devrait avoir été créé");
        $this->assertEquals('DELOUCHE', $newPlayer['nom']);
        $this->assertEquals('HUGO', $newPlayer['prenom']);
        $this->assertEquals('07', $newPlayer['departement_affiliation']);
        $this->assertEquals($newClub['id'], $newPlayer['id_club']);
        
        // Nettoyage
        $this->players->delete($newPlayer['id']);
        $club->delete($newClub['id']);
    }

    public function test_set_leader_requires_email_and_telephone()
    {
        $this->connect_as_admin();
        
        // Créer un joueur sans email ni téléphone
        $playerId = $this->players->update_player(
            null, 'Test', 'Leader', 'M', '13', 1, null, null, null, null, null
        );
        
        // Créer une équipe temporaire pour le test
        $team = new Team();
        $teamId = $team->create_team('m', 'Test Team Leader', 1);
        
        try {
            // Doit lever une exception car pas d'email ni téléphone
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Ce joueur doit avoir une adresse email et un numéro de téléphone");
            $this->players->set_leader([$playerId], $teamId);
        } finally {
            // Nettoyage
            $this->players->delete($playerId);
            $team->delete($teamId);
        }
    }

    public function test_set_leader_with_email_only_fails()
    {
        $this->connect_as_admin();
        
        // Créer un joueur avec email mais sans téléphone
        $playerId = $this->players->update_player(
            null, 'Test', 'EmailOnly', 'M', '13', 1, null, null, null, null, 'test@email.com'
        );
        
        $team = new Team();
        $teamId = $team->create_team('m', 'Test Team EmailOnly', 1);
        
        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Ce joueur doit avoir une adresse email et un numéro de téléphone");
            $this->players->set_leader([$playerId], $teamId);
        } finally {
            $this->players->delete($playerId);
            $team->delete($teamId);
        }
    }

    public function test_set_leader_with_phone_only_fails()
    {
        $this->connect_as_admin();
        
        // Créer un joueur avec téléphone mais sans email
        $playerId = $this->players->update_player(
            null, 'Test', 'PhoneOnly', 'M', '13', 1, null, null, '0612345678', null
        );
        
        $team = new Team();
        $teamId = $team->create_team('m', 'Test Team PhoneOnly', 1);
        
        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Ce joueur doit avoir une adresse email et un numéro de téléphone");
            $this->players->set_leader([$playerId], $teamId);
        } finally {
            $this->players->delete($playerId);
            $team->delete($teamId);
        }
    }

    public function test_set_leader_with_email_and_phone_succeeds()
    {
        $this->connect_as_admin();
        
        // Créer un joueur avec email ET téléphone
        $playerId = $this->players->update_player(
            null, 'Test', 'Complete', 'M', '13', 1, null, null, '0612345678', 'test@complete.com'
        );
        
        $team = new Team();
        $teamId = $team->create_team('m', 'Test Team Complete', 1);
        
        try {
            // Ne doit PAS lever d'exception
            $this->players->set_leader([$playerId], $teamId);
            $this->assertTrue(true, "set_leader a réussi avec email et téléphone");
        } finally {
            $this->players->delete($playerId);
            $team->delete($teamId);
        }
    }

    public function test_set_vice_leader_requires_email_and_telephone()
    {
        $this->connect_as_admin();
        
        // Créer un joueur sans email ni téléphone
        $playerId = $this->players->update_player(
            null, 'Test', 'ViceLeader', 'M', '13', 1, null, null, null, null, null
        );
        
        $team = new Team();
        $teamId = $team->create_team('m', 'Test Team ViceLeader', 1);
        
        // Ajouter le joueur à l'équipe d'abord (requis pour set_vice_leader)
        $this->players->addPlayerToTeam($playerId, $teamId);
        
        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Ce joueur doit avoir une adresse email et un numéro de téléphone");
            $this->players->set_vice_leader([$playerId], $teamId);
        } finally {
            $this->players->delete($playerId);
            $team->delete($teamId);
        }
    }

    public function test_import_all_licences_from_test_directory()
    {
        $this->connect_as_admin();
        
        $files = new Files();
        $licencesDir = __DIR__ . '/files/licences';
        $pdfFiles = glob($licencesDir . '/*.pdf');
        
        $this->assertNotEmpty($pdfFiles, "Aucun fichier PDF trouvé dans $licencesDir");
        
        $totalImported = 0;
        $totalErrors = 0;
        $createdPlayerIds = [];
        
        fwrite(STDERR, "\n\nTest d'import de toutes les licences:\n");
        fwrite(STDERR, str_repeat("=", 80) . "\n");
        
        foreach ($pdfFiles as $pdfFile) {
            $filename = basename($pdfFile);
            fwrite(STDERR, "\nFichier: $filename ... ");
            
            try {
                $licences = $files->get_licences_data($pdfFile);
                fwrite(STDERR, count($licences) . " licences trouvées\n");
                
                $fileImported = 0;
                $fileErrors = 0;
                $licenceCount = count($licences);
                
                foreach ($licences as $index => $licence) {
                    fwrite(STDERR, "  [" . ($index + 1) . "/$licenceCount] {$licence['last_first_name']} ... ");
                    
                    try {
                        $this->players->search_player_and_save_from_licence($licence);
                        $fileImported++;
                        fwrite(STDERR, "OK\n");
                        
                        // Récupérer l'ID du joueur créé/mis à jour pour le nettoyage
                        $player = $this->players->get_one(
                            "num_licence = ? AND departement_affiliation = ?",
                            array(
                                array('type' => 's', 'value' => $licence['licence_number']),
                                array('type' => 's', 'value' => $licence['departement'])
                            )
                        );
                        if (!empty($player)) {
                            $createdPlayerIds[] = $player['id'];
                        }
                    } catch (Exception $e) {
                        $fileErrors++;
                        fwrite(STDERR, "ERREUR: " . $e->getMessage() . "\n");
                    }
                }
                
                $totalImported += $fileImported;
                $totalErrors += $fileErrors;
                fwrite(STDERR, "  → Résumé: $fileImported importées, $fileErrors erreurs\n");
                
            } catch (Exception $e) {
                fwrite(STDERR, "ERREUR lecture PDF: " . $e->getMessage() . "\n");
            }
        }
        
        fwrite(STDERR, "\n" . str_repeat("=", 80) . "\n");
        fwrite(STDERR, "TOTAL: $totalImported licences importées, $totalErrors erreurs\n\n");
        
        $this->assertGreaterThan(0, $totalImported, "Au moins une licence devrait être importée");
    }

}
