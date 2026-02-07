<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . "/../classes/Rank.php";

class RankTest extends UfolepTestCase
{
    private Rank $rank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rank = new Rank();
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
