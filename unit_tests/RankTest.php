<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../classes/Rank.php";

class RankTest extends TestCase
{
    private Rank $rank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sql_manager = new SqlManager();
        $this->rank = new Rank();
    }

    /**
     * @throws Exception
     */
    public function test_sort_cup_rank_isoardi()
    {
        print_r($this->rank->sort_cup_rank('c'));
    }

    public function test_sort_cup_rank_khoury_hanna()
    {
        print_r($this->rank->sort_cup_rank('kh'));
    }

    public function test_get_rank_by_comp_div()
    {
        print_r($this->rank->getRank('c', '14'));
    }

    public function test_get()
    {
        $result = $this->rank->getLeader('m', '1');
        print_r($result);
        $result = $this->rank->getViceLeader('m', '1');
        print_r($result);
    }

}
