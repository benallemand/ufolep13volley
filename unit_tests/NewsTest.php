<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . "/../classes/News.php";
require_once 'UfolepTestCase.php';

class NewsTest extends UfolepTestCase
{
    private ?int $created_news_id = null;

    protected function setUp(): void
    {
        parent::setUp();
        @session_start();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        if ($this->created_news_id !== null) {
            $sql = "DELETE FROM news WHERE id = ?";
            $bindings = [['type' => 'i', 'value' => $this->created_news_id]];
            $this->sql->execute($sql, $bindings);
            $this->created_news_id = null;
        }
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_getAllNews_returns_array()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new News();

        // Act
        $result = $manager->getAllNews();

        // Assert
        $this->assertIsArray($result);
    }

    public function test_getAllNews_includes_disabled_news()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new News();
        
        // Create a disabled news for testing
        $sql = "INSERT INTO news (title, text, file_path, news_date, is_disabled) VALUES (?, ?, '', NOW(), 1)";
        $bindings = [
            ['type' => 's', 'value' => 'Test News Disabled'],
            ['type' => 's', 'value' => 'Test content disabled']
        ];
        $this->created_news_id = $this->sql->execute($sql, $bindings);

        // Act
        $result = $manager->getAllNews();

        // Assert
        $found = false;
        foreach ($result as $news) {
            if ($news['id'] == $this->created_news_id) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "getAllNews should include disabled news");
    }

    public function test_saveNews_creates_new_news()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new News();

        // Act
        $manager->saveNews(null, 'Test New News', 'Test content for new news', '', date('Y-m-d'), 0);
        
        // Get the created news
        $sql = "SELECT id FROM news WHERE title = ? ORDER BY id DESC LIMIT 1";
        $bindings = [['type' => 's', 'value' => 'Test New News']];
        $result = $this->sql->execute($sql, $bindings);
        $this->created_news_id = (int)$result[0]['id'];

        // Assert
        $this->assertNotNull($this->created_news_id);
    }

    public function test_saveNews_updates_existing_news()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new News();
        
        // Create a news first
        $sql = "INSERT INTO news (title, text, file_path, news_date, is_disabled) VALUES (?, ?, '', NOW(), 0)";
        $bindings = [
            ['type' => 's', 'value' => 'Original Title'],
            ['type' => 's', 'value' => 'Original content']
        ];
        $this->created_news_id = $this->sql->execute($sql, $bindings);

        // Act - Update the news
        $manager->saveNews($this->created_news_id, 'Updated Title', 'Updated content', '', date('Y-m-d'), 0);

        // Assert
        $sql = "SELECT title FROM news WHERE id = ?";
        $bindings = [['type' => 'i', 'value' => $this->created_news_id]];
        $result = $this->sql->execute($sql, $bindings);
        $this->assertEquals('Updated Title', $result[0]['title']);
    }

    public function test_deleteNews_removes_news()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new News();
        
        // Create a news first
        $sql = "INSERT INTO news (title, text, file_path, news_date, is_disabled) VALUES (?, ?, '', NOW(), 0)";
        $bindings = [
            ['type' => 's', 'value' => 'To Delete'],
            ['type' => 's', 'value' => 'Content to delete']
        ];
        $news_id = $this->sql->execute($sql, $bindings);

        // Act
        $manager->deleteNews($news_id);

        // Assert
        $sql = "SELECT id FROM news WHERE id = ?";
        $bindings = [['type' => 'i', 'value' => $news_id]];
        $result = $this->sql->execute($sql, $bindings);
        $this->assertEmpty($result);
    }

    public function test_saveNews_fails_for_non_admin()
    {
        // Arrange
        $this->connect_as_team_leader(5);
        $manager = new News();

        // Act & Assert
        $this->expectException(Exception::class);
        $manager->saveNews(null, 'Test News', 'Test content', '', date('Y-m-d'), 0);
    }

    public function test_deleteNews_fails_for_non_admin()
    {
        // Arrange
        $this->connect_as_team_leader(5);
        $manager = new News();

        // Act & Assert
        $this->expectException(Exception::class);
        $manager->deleteNews(1);
    }
}
