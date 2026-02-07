<?php

require_once __DIR__ . '/../classes/LiveScore.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

class LiveScoreTest extends UfolepTestCase
{
    private $liveScore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->liveScore = new LiveScore();
        $this->connect_as_admin();
    }

    public function test_get_live_score_returns_null_when_no_live_score_exists()
    {
        $result = $this->liveScore->getLiveScore(999999);
        $this->assertNull($result);
    }

    public function test_start_live_score_creates_new_entry()
    {
        // Get a valid match id from the database
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        $result = $this->liveScore->startLiveScore($id_match);
        $this->assertNotEmpty($result);

        // Verify entry was created
        $liveScore = $this->liveScore->getLiveScore($id_match);
        $this->assertNotNull($liveScore);
        $this->assertEquals($id_match, $liveScore['id_match']);
        $this->assertEquals(1, $liveScore['set_en_cours']);
        $this->assertEquals(0, $liveScore['score_dom']);
        $this->assertEquals(0, $liveScore['score_ext']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }

    public function test_increment_score_dom()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        // Start a live score
        $this->liveScore->startLiveScore($id_match);

        // Increment dom score
        $this->liveScore->incrementScore($id_match, 'dom');

        $liveScore = $this->liveScore->getLiveScore($id_match);
        $this->assertEquals(1, $liveScore['score_dom']);
        $this->assertEquals(0, $liveScore['score_ext']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }

    public function test_increment_score_ext()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        // Start a live score
        $this->liveScore->startLiveScore($id_match);

        // Increment ext score
        $this->liveScore->incrementScore($id_match, 'ext');

        $liveScore = $this->liveScore->getLiveScore($id_match);
        $this->assertEquals(0, $liveScore['score_dom']);
        $this->assertEquals(1, $liveScore['score_ext']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }

    public function test_next_set_increments_set_and_resets_scores()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        // Start a live score and add some points
        $this->liveScore->startLiveScore($id_match);
        $this->liveScore->incrementScore($id_match, 'dom');
        $this->liveScore->incrementScore($id_match, 'dom');
        $this->liveScore->incrementScore($id_match, 'ext');

        // Go to next set
        $this->liveScore->nextSet($id_match);

        $liveScore = $this->liveScore->getLiveScore($id_match);
        $this->assertEquals(2, $liveScore['set_en_cours']);
        $this->assertEquals(0, $liveScore['score_dom']);
        $this->assertEquals(0, $liveScore['score_ext']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }

    public function test_decrement_score_dom()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        // Start a live score and add points
        $this->liveScore->startLiveScore($id_match);
        $this->liveScore->incrementScore($id_match, 'dom');
        $this->liveScore->incrementScore($id_match, 'dom');

        // Decrement
        $this->liveScore->decrementScore($id_match, 'dom');

        $liveScore = $this->liveScore->getLiveScore($id_match);
        $this->assertEquals(1, $liveScore['score_dom']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }

    public function test_decrement_score_cannot_go_below_zero()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        // Start a live score
        $this->liveScore->startLiveScore($id_match);

        // Try to decrement from 0
        $this->liveScore->decrementScore($id_match, 'dom');

        $liveScore = $this->liveScore->getLiveScore($id_match);
        $this->assertEquals(0, $liveScore['score_dom']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }

    public function test_get_active_live_scores()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 2");
        if (count($matches) < 2) {
            $this->markTestSkipped('Not enough matches in database');
        }

        // Start 2 live scores
        $this->liveScore->startLiveScore($matches[0]['id_match']);
        $this->liveScore->startLiveScore($matches[1]['id_match']);

        $activeLiveScores = $this->liveScore->getActiveLiveScores();
        $this->assertGreaterThanOrEqual(2, count($activeLiveScores));

        // Cleanup
        $this->liveScore->deleteLiveScore($matches[0]['id_match']);
        $this->liveScore->deleteLiveScore($matches[1]['id_match']);
    }

    public function test_upsert_score_updates_full_state()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        // Start a live score
        $this->liveScore->startLiveScore($id_match);
        $liveScore = $this->liveScore->getLiveScore($id_match);
        $version = (int)$liveScore['version'];

        // Upsert with full state
        $scoreData = [
            'score_dom' => 15,
            'score_ext' => 12,
            'sets_dom' => 1,
            'sets_ext' => 0,
            'set_en_cours' => 2,
            'set_1_dom' => 25,
            'set_1_ext' => 20,
        ];
        $result = $this->liveScore->upsertScore($id_match, $scoreData, $version);
        $this->assertTrue($result['success']);

        // Verify state was saved
        $updated = $this->liveScore->getLiveScore($id_match);
        $this->assertEquals(15, $updated['score_dom']);
        $this->assertEquals(12, $updated['score_ext']);
        $this->assertEquals(1, $updated['sets_dom']);
        $this->assertEquals(0, $updated['sets_ext']);
        $this->assertEquals(2, $updated['set_en_cours']);
        $this->assertEquals(25, $updated['set_1_dom']);
        $this->assertEquals(20, $updated['set_1_ext']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }

    public function test_upsert_score_increments_version()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        // Start a live score
        $this->liveScore->startLiveScore($id_match);
        $liveScore = $this->liveScore->getLiveScore($id_match);
        $initialVersion = (int)$liveScore['version'];

        // Upsert
        $scoreData = ['score_dom' => 5, 'score_ext' => 3];
        $result = $this->liveScore->upsertScore($id_match, $scoreData, $initialVersion);

        // Version should have incremented
        $updated = $this->liveScore->getLiveScore($id_match);
        $this->assertEquals($initialVersion + 1, (int)$updated['version']);
        $this->assertEquals($initialVersion + 1, $result['version']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }

    public function test_upsert_score_rejects_stale_version()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        // Start a live score
        $this->liveScore->startLiveScore($id_match);
        $liveScore = $this->liveScore->getLiveScore($id_match);
        $version = (int)$liveScore['version'];

        // First upsert succeeds
        $scoreData = ['score_dom' => 5, 'score_ext' => 3];
        $result1 = $this->liveScore->upsertScore($id_match, $scoreData, $version);
        $this->assertTrue($result1['success']);

        // Second upsert with same (now stale) version should fail
        $scoreData2 = ['score_dom' => 10, 'score_ext' => 8];
        $result2 = $this->liveScore->upsertScore($id_match, $scoreData2, $version);
        $this->assertFalse($result2['success']);
        $this->assertEquals('version_conflict', $result2['error']);

        // Score should remain from first upsert
        $current = $this->liveScore->getLiveScore($id_match);
        $this->assertEquals(5, $current['score_dom']);
        $this->assertEquals(3, $current['score_ext']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }

    public function test_upsert_score_returns_current_state_on_conflict()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        // Start and upsert once
        $this->liveScore->startLiveScore($id_match);
        $liveScore = $this->liveScore->getLiveScore($id_match);
        $version = (int)$liveScore['version'];

        $this->liveScore->upsertScore($id_match, ['score_dom' => 5, 'score_ext' => 3], $version);

        // Conflict: stale version, should return server state
        $result = $this->liveScore->upsertScore($id_match, ['score_dom' => 99, 'score_ext' => 99], $version);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(5, $result['data']['score_dom']);
        $this->assertEquals(3, $result['data']['score_ext']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }

    public function test_start_live_score_initializes_version_to_1()
    {
        $matches = $this->sql->execute("SELECT id_match FROM matches LIMIT 1");
        if (empty($matches)) {
            $this->markTestSkipped('No matches in database');
        }
        $id_match = $matches[0]['id_match'];

        $this->liveScore->startLiveScore($id_match);
        $liveScore = $this->liveScore->getLiveScore($id_match);
        $this->assertEquals(1, (int)$liveScore['version']);

        // Cleanup
        $this->liveScore->deleteLiveScore($id_match);
    }
}
