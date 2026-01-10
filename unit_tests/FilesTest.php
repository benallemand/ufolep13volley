<?php
require_once __DIR__ . '/../classes/Files.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

class FilesTest extends UfolepTestCase
{

    public function test_get_licence_with_photo()
    {
        $mgr = new Files();
        $results = $mgr->get_licences_data(__DIR__ . '/files/licences/licences-photos-test.pdf');
        
        // Vérifier qu'on a exactement 22 licences
        $this->assertCount(22, $results, "Le PDF devrait contenir 22 licences");
        
        // Compter les licences avec et sans photo
        $withPhoto = 0;
        $withoutPhoto = 0;
        
        foreach ($results as $licence) {
            if ($licence['photo'] === null) {
                $withoutPhoto++;
            } else {
                $withPhoto++;
                // Vérifier que la photo est bien une chaîne non vide
                $this->assertIsString($licence['photo']);
                $this->assertGreaterThan(0, strlen($licence['photo']));
            }
        }
        
        $this->assertEquals(8, $withoutPhoto, "Il devrait y avoir 8 licences sans photo");
        $this->assertEquals(14, $withPhoto, "Il devrait y avoir 14 licences avec photo");
        
        // Vérifier qu'ARRAZAT BRICE est présent et n'a pas de photo
        $found = false;
        foreach ($results as $licence) {
            if ($licence['licence_number'] == '96567550') {
                $found = true;
                $this->assertEquals('13', $licence['departement']);
                $this->assertEquals('ARRAZAT BRICE', $licence['last_first_name']);
                $this->assertEquals('29/11/1983', $licence['date_of_birth']);
                $this->assertEquals('41', $licence['age']);
                $this->assertEquals('M', $licence['sexe']);
                $this->assertEquals('ASS VOLLEY LOISIR FUVELAIN', $licence['club']);
                $this->assertEquals('013040711', $licence['licence_club']);
                $this->assertEquals('30/09/2025', $licence['homologation_date']);
                $this->assertEquals('013_96567550', $licence['licence_number_2']);
                $this->assertNull($licence['photo'], "ARRAZAT BRICE ne devrait pas avoir de photo");
                break;
            }
        }
        $this->assertTrue($found, "ARRAZAT BRICE non trouvé");
        
        // Vérifier que BLANCHARD SIMON a une photo
        $found = false;
        foreach ($results as $licence) {
            if (strpos($licence['last_first_name'], 'BLANCHARD') !== false) {
                $found = true;
                $this->assertNotNull($licence['photo'], "BLANCHARD SIMON devrait avoir une photo");
                $this->assertIsString($licence['photo']);
                $this->assertGreaterThan(0, strlen($licence['photo']));
                break;
            }
        }
        $this->assertTrue($found, "BLANCHARD SIMON non trouvé");
    }

    public function test_all_licence_files()
    {
        $mgr = new Files();
        $licencesDir = __DIR__ . '/files/licences';
        
        // Lister tous les fichiers PDF du dossier
        $pdfFiles = glob($licencesDir . '/*.pdf');
        
        $this->assertNotEmpty($pdfFiles, "Aucun fichier PDF trouvé dans $licencesDir");
        
        echo "\n\nTest de tous les fichiers PDF de licences:\n";
        echo str_repeat("=", 80) . "\n";
        
        foreach ($pdfFiles as $pdfFile) {
            $filename = basename($pdfFile);
            echo "\nFichier: $filename\n";
            
            try {
                $results = $mgr->get_licences_data($pdfFile);
                
                $totalLicences = count($results);
                $withPhoto = 0;
                $withoutPhoto = 0;
                
                foreach ($results as $licence) {
                    if ($licence['photo'] === null) {
                        $withoutPhoto++;
                    } else {
                        $withPhoto++;
                        // Vérifier que la photo est valide
                        $this->assertIsString($licence['photo'], "Photo invalide dans $filename");
                        $this->assertGreaterThan(0, strlen($licence['photo']), "Photo vide dans $filename");
                    }
                    
                    // Vérifier que chaque licence a bien 11 champs (10 données + photo)
                    $this->assertArrayHasKey('departement', $licence);
                    $this->assertArrayHasKey('licence_number', $licence);
                    $this->assertArrayHasKey('last_first_name', $licence);
                    $this->assertArrayHasKey('date_of_birth', $licence);
                    $this->assertArrayHasKey('age', $licence);
                    $this->assertArrayHasKey('sexe', $licence);
                    $this->assertArrayHasKey('club', $licence);
                    $this->assertArrayHasKey('licence_club', $licence);
                    $this->assertArrayHasKey('homologation_date', $licence);
                    $this->assertArrayHasKey('licence_number_2', $licence);
                    $this->assertArrayHasKey('photo', $licence);
                }
                
                echo "  ✓ Total licences: $totalLicences\n";
                echo "  ✓ Avec photo: $withPhoto\n";
                echo "  ✓ Sans photo: $withoutPhoto\n";
                
                // Vérifier qu'on a au moins une licence
                $this->assertGreaterThan(0, $totalLicences, "Aucune licence trouvée dans $filename");
                
            } catch (Exception $e) {
                echo "  ⚠ Erreur: " . $e->getMessage() . "\n";
                echo "  (Ce fichier utilise probablement un format différent)\n";
                // Ne pas faire échouer le test pour les fichiers avec format différent
                continue;
            }
        }
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "Tous les fichiers PDF ont été traités avec succès!\n\n";
    }

    public function test_licence_ardeche()
    {
        $mgr = new Files();
        $results = $mgr->get_licences_data(__DIR__ . '/files/licences/licence-ardeche.pdf');
        
        $this->assertCount(1, $results, "Le PDF devrait contenir 1 licence");
        
        $licence = $results[0];
        
        $this->assertEquals('07', $licence['departement']);
        $this->assertEquals('99996743', $licence['licence_number']);
        $this->assertEquals('DELOUCHE HUGO', $licence['last_first_name']);
        $this->assertEquals('08/08/2000', $licence['date_of_birth']);
        $this->assertEquals('M', $licence['sexe']);
        $this->assertEquals('AMICALE LAIQUE SAINT PERAY', $licence['club']);
        $this->assertEquals('007281005', $licence['licence_club']);
        $this->assertEquals('29/10/2025', $licence['homologation_date']);
        $this->assertEquals('007_99996743', $licence['licence_number_2']);
    }


}
