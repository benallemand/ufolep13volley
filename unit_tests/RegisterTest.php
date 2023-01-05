<?php
require_once __DIR__ . '/../classes/Register.php';

use PHPUnit\Framework\TestCase;

class RegisterTest extends TestCase
{
    private Register $register;

    /**
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->register = new Register();
    }


    /**
     * @throws Exception
     */
    public function test_set_up_season()
    {
        //230105:PASS
        $competition_mgr = new Competition();
        $competition_kh = $competition_mgr->getCompetition('kh');
        $this->register->set_up_season($competition_kh['id']);
        // test for isoardi, where registration is automatic
        $competition_isoardi = $competition_mgr->getCompetition('c');
        $this->register->set_up_season($competition_isoardi['id']);
    }

}
