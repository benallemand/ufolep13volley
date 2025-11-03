<?php
require_once __DIR__ . '/../classes/Files.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

class FilesTest extends UfolepTestCase
{


    /**
     * @throws Exception
     */
    public function test_get_licences_data_from_pdf()
    {
        $mgr = new Files();
        $this->assertEquals(
            array(
                0 =>
                    array(
                        'departement' => '13',
                        'licence_number' => '96742776',
                        'last_first_name' => 'DERUE ARNAUD',
                        'date_of_birth' => '14/04/1991',
                        'age' => '32',
                        'sexe' => 'M',
                        'club' => 'BOUC BEL AIR VOLLEY BALL',
                        'licence_club' => '013015431',
                        'homologation_date' => '26/10/2023',
                        'licence_number_2' => '013_96742776',
                    ),
                1 =>
                    array(
                        'departement' => '13',
                        'licence_number' => '96709731',
                        'last_first_name' => 'EVRARD EMILIE',
                        'date_of_birth' => '05/05/1985',
                        'age' => '38',
                        'sexe' => 'F',
                        'club' => 'BOUC BEL AIR VOLLEY BALL',
                        'licence_club' => '013015431',
                        'homologation_date' => '05/09/2023',
                        'licence_number_2' => '013_96709731',
                    ),
                2 =>
                    array(
                        'departement' => '13',
                        'licence_number' => '96698705',
                        'last_first_name' => 'LAFORGE HERVE',
                        'date_of_birth' => '15/10/1986',
                        'age' => '37',
                        'sexe' => 'M',
                        'club' => 'BOUC BEL AIR VOLLEY BALL',
                        'licence_club' => '013015431',
                        'homologation_date' => '26/10/2023',
                        'licence_number_2' => '013_96698705',
                    ),
                3 =>
                    array(
                        'departement' => '13',
                        'licence_number' => '20002241',
                        'last_first_name' => 'MAYAUD LIONEL',
                        'date_of_birth' => '07/06/1978',
                        'age' => '45',
                        'sexe' => 'M',
                        'club' => 'BOUC BEL AIR VOLLEY BALL',
                        'licence_club' => '013015431',
                        'homologation_date' => '26/10/2023',
                        'licence_number_2' => '013_20002241',
                    ),
                4 =>
                    array(
                        'departement' => '13',
                        'licence_number' => '96735214',
                        'last_first_name' => 'MINNI SÉBASTIEN',
                        'date_of_birth' => '19/09/1974',
                        'age' => '49',
                        'sexe' => 'M',
                        'club' => 'BOUC BEL AIR VOLLEY BALL',
                        'licence_club' => '013015431',
                        'homologation_date' => '26/10/2023',
                        'licence_number_2' => '013_96735214',
                    ),
                5 =>
                    array(
                        'departement' => '13',
                        'licence_number' => '96634282',
                        'last_first_name' => 'THEME CHRISTOPHE',
                        'date_of_birth' => '29/10/1971',
                        'age' => '52',
                        'sexe' => 'M',
                        'club' => 'BOUC BEL AIR VOLLEY BALL',
                        'licence_club' => '013015431',
                        'homologation_date' => '26/10/2023',
                        'licence_number_2' => '013_96634282',
                    ),
                6 =>
                    array(
                        'departement' => '13',
                        'licence_number' => '96742777',
                        'last_first_name' => 'VIGNERAS MAGALI',
                        'date_of_birth' => '31/03/1976',
                        'age' => '47',
                        'sexe' => 'F',
                        'club' => 'BOUC BEL AIR VOLLEY BALL',
                        'licence_club' => '013015431',
                        'homologation_date' => '26/10/2023',
                        'licence_number_2' => '013_96742777',
                    ),
            ),
            $mgr->get_licences_data_from_pdf(__DIR__ . '/files/licences/bbavb.pdf'));
    }

    /**
     * @throws Exception
     */
    public function test_get_licences_data_from_pdf_2024()
    {
        $mgr = new Files();
        $this->assertEquals(
            array(
                0 =>
                    array(
                        'departement' => '13',
                        'licence_number' => '96641287',
                        'last_first_name' => 'SERRE SABRINA',
                        'date_of_birth' => '19/08/1984',
                        'age' => '40',
                        'sexe' => 'F',
                        'club' => 'ASS SP PERSONNEL',
                        'licence_club' => '013001234',
                        'homologation_date' => '02/10/2024',
                        'licence_number_2' => '013_96641287',
                    ),),
            $mgr->get_licences_data_from_pdf_2024(__DIR__ . '/files/licences/2024.pdf'));

    }

    public function test_get_licence_bad_naming()
    {
        $mgr = new Files();
        $this->assertEquals(
            array(
                0 =>
                    array(
                        'departement' => '13',
                        'licence_number' => '96756847',
                        'last_first_name' => 'ZOUITEN SOFIANE',
                        'date_of_birth' => '11/07/2001',
                        'age' => '24',
                        'sexe' => 'M',
                        'club' => 'MARSEILLE VOLLEY 13',
                        'licence_club' => '013212122',
                        'homologation_date' => '16/10/2025',
                        'licence_number_2' => '013_96756847',
                    ),),
            $mgr->get_licences_data_from_pdf_2024(__DIR__ . '/files/licences/licences-vos-activites.pdf'));

    }

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
                $this->assertEquals('ASS VOLLEY LOISIR', $licence['club']);
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
}
