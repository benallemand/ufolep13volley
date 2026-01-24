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
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_sort_cup_rank_khoury_hanna()
    {
        print_r($this->rank->sort_cup_rank('kh'));
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_get_rank_by_comp_div()
    {
        print_r($this->rank->getRank('c', '14'));
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_get()
    {
        $result = $this->rank->getLeader('ut', '1');
        print_r($result);
        $result = $this->rank->getViceLeader('ut', '1');
        print_r($result);
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_getUnassignedTeams()
    {
        $result = $this->rank->getUnassignedTeams('m');
        print_r($result);
        $this->assertIsArray($result);
    }

    /**
     * @throws Exception
     */
    public function test_getRanksByCompetitionGroupedByDivision()
    {
        $result = $this->rank->getRanksByCompetitionGroupedByDivision('m');
        print_r($result);
        $this->assertIsArray($result);
        // Should have divisions as keys
        if (!empty($result)) {
            $firstKey = array_key_first($result);
            $this->assertIsArray($result[$firstKey]);
        }
    }

    /**
     * @throws Exception
     */
    public function test_updateRanksBatch()
    {
        // Get current ranks for competition 'ut' (unit test)
        $currentRanks = $this->rank->getRanks("c.code_competition = 'ut'");
        if (empty($currentRanks)) {
            $this->markTestSkipped('No ranks found for ut competition');
        }
        
        // Prepare batch update data (just update rank_start)
        $updates = [];
        foreach ($currentRanks as $rank) {
            $updates[] = [
                'id' => $rank['id'],
                'division' => $rank['division'],
                'rank_start' => $rank['rank_start']
            ];
        }
        
        $result = $this->rank->updateRanksBatch('ut', json_encode($updates));
        $this->assertTrue($result['success']);
    }

}
