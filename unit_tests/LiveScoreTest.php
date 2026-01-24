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
}
