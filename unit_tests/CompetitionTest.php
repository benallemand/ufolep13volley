<?php


use PHPUnit\Framework\TestCase;
require_once __DIR__ . "/../classes/Competition.php";

class CompetitionTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testInit_classements_isoardi()
    {
        $competition = new Competition();
        $competition->init_classements_isoardi(true);
    }
}
