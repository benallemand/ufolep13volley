<?php
require_once __DIR__ . '/../classes/Register.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'UfolepTestCase.php';

class RegisterTest extends UfolepTestCase
{
    private Register $register;

    /**
     * @param $id
     * @return void
     * @throws Exception
     */
    public function delete_registrations_from_competition($id): void
    {
        $registrations = $this->register->get("id_competition = ?", array(array('type' => 'i', 'value' => $id)));
        $registrations_ids = array();
        foreach ($registrations as $registration) {
            $registrations_ids[] = $registration['id'];
        }
        if (count($registrations_ids) > 0) {
            $this->register->delete(implode(',', $registrations_ids));
        }
    }

    /**
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connect_as_admin();
        $this->register = new Register();
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        $competition_mgr = new Competition();
        foreach (array('f', 'm', 'mo') as $code_competition) {
            $comp = $competition_mgr->getCompetition($code_competition);
            if ($comp) {
                $registrations = $this->register->get("id_competition = ? AND nom_equipe LIKE 'test_%'", array(array('type' => 'i', 'value' => $comp['id'])));
                $ids = array_column($registrations, 'id');
                if (count($ids) > 0) {
                    $this->register->delete(implode(',', $ids));
                }
            }
        }
        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function test_get_pending_registrations()
    {
        $competition_mgr = new Competition();
        $comp = $competition_mgr->getCompetition('m');
        try {
            $this->register->register(
                'test_m_pending_' . time(),
                41, // club de test
                $comp['id'],
                null,
                'test_leader_name',
                'test_leader_first_name',
                'test_leader_email',
                'test_leader_phone',
                null,
                null,
                null,
                null,
                null,
                null,
                'test_register');
        } catch (Exception $exception) {
            $this->assertEquals($exception->getCode(), 201);
        }
        $pending_registrations = $this->register->get_pending_registrations($comp['id']);
        $this->assertNotEmpty($pending_registrations);
    }

    /**
     * @throws Exception
     */
    public function test_get_2nd_half_registrations()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        foreach (array('f', 'm', 'mo') as $code_competition) {
            $comp = $competition_mgr->getCompetition($code_competition);
            $this->delete_registrations_from_competition($comp['id']);
            try {
                $this->register->register(
                    'test_' . $code_competition . '_' . date('Ymd-m-Y-H-i-s'),
                    41, // club de test
                    $comp['id'],
                    null,
                    'test_leader_name',
                    'test_leader_first_name',
                    'test_leader_email',
                    'test_leader_phone',
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    'test_register');
            } catch (Exception $exception) {
                $this->assertEquals($exception->getCode(), 201);
            }
            $this->assertCount(1, $this->register->get_2nd_half_registrations($comp['id']));
        }
    }
}