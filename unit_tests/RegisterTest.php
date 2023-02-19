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
    public function test_set_up_season_masc()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        // test for masc
        $competition_masc = $competition_mgr->getCompetition('m');
        $this->register->set_up_season($competition_masc['id']);
    }

    /**
     * @throws Exception
     */
    public function test_set_up_season_fem()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        // test for masc
        $competition_masc = $competition_mgr->getCompetition('f');
        $this->register->set_up_season($competition_masc['id']);
    }

    /**
     * @throws Exception
     */
    public function test_set_up_season_mo()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        // test for masc
        $competition_masc = $competition_mgr->getCompetition('mo');
        $this->register->set_up_season($competition_masc['id']);
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

    /**
     * @throws Exception
     */
    public function test_get_2nd_half_registrations()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        $comp = $competition_mgr->getCompetition('f');
        $this->assertCount(2, $this->register->get_2nd_half_registrations($comp['id']));
        $comp = $competition_mgr->getCompetition('mo');
        $this->assertCount(1, $this->register->get_2nd_half_registrations($comp['id']));
        $comp = $competition_mgr->getCompetition('m');
        $this->assertCount(0, $this->register->get_2nd_half_registrations($comp['id']));
    }

}