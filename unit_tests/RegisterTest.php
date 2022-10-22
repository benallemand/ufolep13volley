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
        //221022:PASS
        $this->register->set_up_season();
    }

}
