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
    public function test_set_up_season_kh()
    {
        //230105:PASS
        $competition_mgr = new Competition();
        $competition_kh = $competition_mgr->getCompetition('kh');
        $this->register->set_up_season($competition_kh['id']);
    }

    /**
     * @throws Exception
     */
    public function test_set_up_season_isoardi()
    {
        //230105:PASS
        $competition_mgr = new Competition();
        // test for isoardi, where registration is automatic
        $competition_isoardi = $competition_mgr->getCompetition('c');
        $this->register->set_up_season($competition_isoardi['id']);
    }

    /**
     * @throws Exception
     */
    public function test_get_pending_registrations()
    {
        //230123:PASS
        $competition_mgr = new Competition();
        $competition_kh = $competition_mgr->getCompetition('kh');
        $pending_registrations = $this->register->get_pending_registrations($competition_kh['id']);
        $this->assertNotEmpty($pending_registrations);
    }

}