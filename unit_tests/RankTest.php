<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . "/../classes/Rank.php";

class RankTest extends UfolepTestCase
{
    private Rank $rank;
    private ?int $clubId = null;
    private ?int $competitionId = null;
    private array $equipeIds = [];
    private array $classementIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->rank = new Rank();
    }

    private function createTestData(): void
    {
        $this->clubId = $this->sql->execute(
            "INSERT INTO clubs SET nom = 'UT Club'"
        );
        $this->competitionId = $this->sql->execute(
            "INSERT INTO competitions SET code_competition = 'ut', libelle = 'Unit Test', id_compet_maitre = 'ut'"
        );
        for ($i = 1; $i <= 2; $i++) {
            $equipeId = $this->sql->execute(
                "INSERT INTO equipes SET nom_equipe = ?, code_competition = 'ut', id_club = ?",
                [
                    ['type' => 's', 'value' => "UT Equipe $i"],
                    ['type' => 'i', 'value' => $this->clubId],
                ]
            );
            $this->equipeIds[] = $equipeId;
            $classementId = $this->sql->execute(
                "INSERT INTO classements SET code_competition = 'ut', division = '1', id_equipe = ?, rank_start = ?, penalite = 0",
                [
                    ['type' => 'i', 'value' => $equipeId],
                    ['type' => 'i', 'value' => $i],
                ]
            );
            $this->classementIds[] = $classementId;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->classementIds as $id) {
            $this->sql->execute("DELETE FROM classements WHERE id = ?", [['type' => 'i', 'value' => $id]]);
        }
        foreach ($this->equipeIds as $id) {
            $this->sql->execute("DELETE FROM equipes WHERE id_equipe = ?", [['type' => 'i', 'value' => $id]]);
        }
        if ($this->competitionId) {
            $this->sql->execute("DELETE FROM competitions WHERE id = ?", [['type' => 'i', 'value' => $this->competitionId]]);
        }
        if ($this->clubId) {
            $this->sql->execute("DELETE FROM clubs WHERE id = ?", [['type' => 'i', 'value' => $this->clubId]]);
        }
        $this->classementIds = [];
        $this->equipeIds = [];
        $this->competitionId = null;
        $this->clubId = null;
        parent::tearDown();
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
        $this->createTestData();
        $this->connect_as_admin();

        $currentRanks = $this->rank->getRanks("c.code_competition = 'ut'");
        $this->assertNotEmpty($currentRanks, 'Test data should have created ranks for ut competition');

        // Prepare batch update data: swap rank_start values
        $updates = [];
        foreach ($currentRanks as $rank) {
            $updates[] = [
                'id' => $rank['id'],
                'division' => $rank['division'],
                'rank_start' => count($currentRanks) - $rank['rank_start'] + 1
            ];
        }

        $result = $this->rank->updateRanksBatch('ut', json_encode($updates));
        $this->assertTrue($result['success']);
        $this->assertEquals(count($currentRanks), $result['updated']);

        // Verify ranks were actually swapped
        $updatedRanks = $this->rank->getRanks("c.code_competition = 'ut'");
        foreach ($updatedRanks as $updatedRank) {
            $original = array_filter($currentRanks, fn($r) => $r['id'] === $updatedRank['id']);
            $original = array_values($original)[0];
            $expectedRank = count($currentRanks) - $original['rank_start'] + 1;
            $this->assertEquals($expectedRank, $updatedRank['rank_start']);
        }
    }

}
