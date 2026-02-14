<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . "/../classes/Rank.php";
require_once __DIR__ . "/../classes/Registry.php";

class FinalsDrawTest extends UfolepTestCase
{
    private Rank $rank;
    private Registry $registry;
    private array $registryIds = [];
    private ?int $clubId = null;
    private ?int $competitionIdKh = null;
    private ?int $competitionIdKf = null;
    private array $equipeIds = [];
    private array $classementIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->rank = new Rank();
        $this->registry = new Registry();
    }

    /**
     * Create test data: a club, 2 competitions (kh pool + kf finals),
     * 4 teams with pool rankings, and registry entries for the draw
     */
    private function createTestData(): void
    {
        $this->connect_as_admin();

        // Create a test club
        $this->clubId = $this->sql->execute(
            "INSERT INTO clubs SET nom = 'UT Club Finals'"
        );

        // Create competition for pool phase (uh) and finals (uf)
        $this->competitionIdKh = $this->sql->execute(
            "INSERT INTO competitions SET code_competition = 'uh', libelle = 'UT Coupe KH', id_compet_maitre = 'uh'"
        );
        $this->competitionIdKf = $this->sql->execute(
            "INSERT INTO competitions SET code_competition = 'uf', libelle = 'UT Coupe KF Finales', id_compet_maitre = 'uh'"
        );

        // Create 4 teams in the pool competition
        for ($i = 1; $i <= 4; $i++) {
            $equipeId = $this->sql->execute(
                "INSERT INTO equipes SET nom_equipe = ?, code_competition = 'uh', id_club = ?",
                [
                    ['type' => 's', 'value' => "UT Equipe Finals $i"],
                    ['type' => 'i', 'value' => $this->clubId],
                ]
            );
            $this->equipeIds[] = $equipeId;

            // Put teams in 2 pools: teams 1,2 in pool 1, teams 3,4 in pool 2
            $division = $i <= 2 ? '1' : '2';
            $rank_start = $i <= 2 ? $i : $i - 2;
            $classementId = $this->sql->execute(
                "INSERT INTO classements SET code_competition = 'uh', division = ?, id_equipe = ?, rank_start = ?, penalite = 0",
                [
                    ['type' => 's', 'value' => $division],
                    ['type' => 'i', 'value' => $equipeId],
                    ['type' => 'i', 'value' => $rank_start],
                ]
            );
            $this->classementIds[] = $classementId;
        }
    }

    /**
     * Insert registry entries for a finals draw
     */
    private function insertDrawRegistry(string $code_competition_finals, array $draw): void
    {
        foreach ($draw as $matchNum => $match) {
            $keyTeam1 = "finals_draw.$code_competition_finals.1_8.$matchNum.team1";
            $keyTeam2 = "finals_draw.$code_competition_finals.1_8.$matchNum.team2";
            $id1 = $this->sql->execute(
                "INSERT INTO registry SET registry_key = ?, registry_value = ?",
                [
                    ['type' => 's', 'value' => $keyTeam1],
                    ['type' => 's', 'value' => $match['team1']],
                ]
            );
            $this->registryIds[] = $id1;
            $id2 = $this->sql->execute(
                "INSERT INTO registry SET registry_key = ?, registry_value = ?",
                [
                    ['type' => 's', 'value' => $keyTeam2],
                    ['type' => 's', 'value' => $match['team2']],
                ]
            );
            $this->registryIds[] = $id2;
        }
    }

    protected function tearDown(): void
    {
        // Clean registry entries
        foreach ($this->registryIds as $id) {
            $this->sql->execute("DELETE FROM registry WHERE id = ?", [['type' => 'i', 'value' => $id]]);
        }
        // Clean classements
        foreach ($this->classementIds as $id) {
            $this->sql->execute("DELETE FROM classements WHERE id = ?", [['type' => 'i', 'value' => $id]]);
        }
        // Clean equipes
        foreach ($this->equipeIds as $id) {
            $this->sql->execute("DELETE FROM equipes WHERE id_equipe = ?", [['type' => 'i', 'value' => $id]]);
        }
        // Clean competitions
        if ($this->competitionIdKf) {
            $this->sql->execute("DELETE FROM competitions WHERE id = ?", [['type' => 'i', 'value' => $this->competitionIdKf]]);
        }
        if ($this->competitionIdKh) {
            $this->sql->execute("DELETE FROM competitions WHERE id = ?", [['type' => 'i', 'value' => $this->competitionIdKh]]);
        }
        // Clean club
        if ($this->clubId) {
            $this->sql->execute("DELETE FROM clubs WHERE id = ?", [['type' => 'i', 'value' => $this->clubId]]);
        }
        $this->registryIds = [];
        $this->classementIds = [];
        $this->equipeIds = [];
        $this->competitionIdKh = null;
        $this->competitionIdKf = null;
        $this->clubId = null;
        parent::tearDown();
    }

    /**
     * Test getFinalsDrawRaw returns the draw from registry
     */
    public function test_getFinalsDrawRaw_returns_draw_from_registry()
    {
        $this->createTestData();
        $this->insertDrawRegistry('uf', [
            1 => ['team1' => '1er poule 1', 'team2' => '1er poule 2'],
            2 => ['team1' => '2e poule 1', 'team2' => '2e poule 2'],
        ]);

        $result = $this->rank->getFinalsDrawRaw('uf');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('1er poule 1', $result[1]['team1']);
        $this->assertEquals('1er poule 2', $result[1]['team2']);
        $this->assertEquals('2e poule 1', $result[2]['team1']);
        $this->assertEquals('2e poule 2', $result[2]['team2']);
    }

    /**
     * Test getFinalsDrawRaw returns empty array when no draw exists
     */
    public function test_getFinalsDrawRaw_returns_empty_when_no_draw()
    {
        $result = $this->rank->getFinalsDrawRaw('nonexistent_competition');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test resolveFinalsPosition resolves "1er poule X" to actual team name
     */
    public function test_resolveFinalsPosition_resolves_first_of_pool()
    {
        $this->createTestData();

        // Build a mock cup rank sorted result
        // Pool 1: team1 (rank 1), team2 (rank 2)
        // Pool 2: team3 (rank 1), team4 (rank 2)
        $result = $this->rank->resolveFinalsPosition('1er poule 1', 'uh');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('id_equipe', $result);
        $this->assertArrayHasKey('nom_equipe', $result);
    }

    /**
     * Test resolveFinalsPosition returns null for unresolvable position
     */
    public function test_resolveFinalsPosition_returns_null_for_unknown_position()
    {
        $result = $this->rank->resolveFinalsPosition('unknown position', 'uh');
        $this->assertNull($result);
    }

    /**
     * Test saveFinalsDrawEntry saves a draw entry in registry
     */
    public function test_saveFinalsDrawEntry()
    {
        $this->connect_as_admin();

        $result = $this->rank->saveFinalsDrawEntry('uf', 1, 'team1', '1er poule 1');
        $this->assertTrue($result);

        // Verify it was saved
        $entries = $this->registry->find_by_key('finals_draw.uf.1_8.1.team1');
        $this->assertCount(1, $entries);
        $this->assertEquals('1er poule 1', $entries[0]['registry_value']);

        // Cleanup
        $this->registryIds[] = $entries[0]['id'];
    }

    /**
     * Test saveFullFinalsDraw saves all 8 matches
     */
    public function test_saveFullFinalsDraw()
    {
        $this->connect_as_admin();

        $draw = [];
        for ($i = 1; $i <= 8; $i++) {
            $draw[] = [
                'match' => $i,
                'team1' => "Position A$i",
                'team2' => "Position B$i",
            ];
        }

        $result = $this->rank->saveFullFinalsDraw('uf', json_encode($draw));
        $this->assertTrue($result['success']);
        $this->assertEquals(16, $result['entries_count']);

        // Verify entries exist
        $entries = $this->registry->find_by_key('finals_draw.uf.');
        $this->assertCount(16, $entries);

        // Cleanup
        foreach ($entries as $entry) {
            $this->registryIds[] = $entry['id'];
        }
    }

    /**
     * Test getFinalsDrawResolved returns full bracket structure
     */
    public function test_getFinalsDrawResolved_returns_bracket_structure()
    {
        $this->createTestData();
        $this->insertDrawRegistry('uf', [
            1 => ['team1' => '1er poule 1', 'team2' => '1er poule 2'],
        ]);

        $result = $this->rank->getFinalsDrawResolved('uf');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('rounds', $result);
        $this->assertArrayHasKey('1_8', $result['rounds']);
        $this->assertCount(1, $result['rounds']['1_8']);

        $match = $result['rounds']['1_8'][0];
        $this->assertArrayHasKey('match', $match);
        $this->assertArrayHasKey('team1_label', $match);
        $this->assertArrayHasKey('team2_label', $match);
        $this->assertArrayHasKey('team1_resolved', $match);
        $this->assertArrayHasKey('team2_resolved', $match);
    }
}
