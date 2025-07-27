<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../classes/HallOfFame.php";

class HallOfFameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->limit_date = new LimitDate();
    }

    /**
     * @throws Exception
     */
    public function test_generate_hall_of_fame()
    {
        $hof = new HallOfFame();
        $competition = new Competition();
        foreach (array('ut') as $code) {
            $comp = $competition->getCompetition($code);
            $limit_dates = $this->limit_date->getLimitDates();
            foreach ($limit_dates as $limit_date) {
                if ($limit_date['code_competition'] == $code) {
                    $this->limit_date->delete($limit_date['id_date']);
                    break;
                }
            }
            $this->limit_date->saveLimitDate(
                $code,
                date('d/m/Y', strtotime('-1 week')));
            $hof->generateHallOfFame($comp['id']);
        }
        $this->assertTrue(1 == 1);
    }
}
