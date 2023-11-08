<?php
require_once __DIR__ . '/../classes/Files.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class FilesTest extends TestCase
{

    public function testDownload_match_file()
    {
        $mgr = new Files();
        $mgr->download_match_file('match_files/M2210211file11.PDF');
    }

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
}
