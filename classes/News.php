<?php
require_once __DIR__ . '/Generic.php';
/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 26/02/2018
 * Time: 17:02
 */

class News extends Generic
{

    /**
     * LimitDateManager constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getLastNews()
    {
        $sql = "SELECT 
                n.id,
                n.title,
                n.text,
                n.file_path,
                DATE_FORMAT(n.news_date, ' %a %d/%m/%Y') AS news_date
            FROM news n
            WHERE is_disabled = 0 
            ORDER BY n.news_date DESC";
        return $this->sql_manager->execute($sql);
    }

    public function getAllNews(): array
    {
        $sql = "SELECT 
                n.id,
                n.title,
                n.text,
                n.file_path,
                DATE_FORMAT(n.news_date, '%Y-%m-%d') AS news_date,
                n.is_disabled
            FROM news n
            ORDER BY n.news_date DESC";
        return $this->sql_manager->execute($sql);
    }

    public function saveNews(): void
    {
        @session_start();
        if (!UserManager::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent modifier les news");
        }

        $id = $_POST['id'] ?? null;
        $title = $_POST['title'] ?? '';
        $text = $_POST['text'] ?? '';
        $file_path = $_POST['file_path'] ?? '';
        $news_date = $_POST['news_date'] ?? date('Y-m-d');
        $is_disabled = $_POST['is_disabled'] ?? 0;

        if (!empty($id) && is_numeric($id)) {
            $sql = "UPDATE news SET title = ?, text = ?, file_path = ?, news_date = ?, is_disabled = ? WHERE id = ?";
            $bindings = [
                ['type' => 's', 'value' => $title],
                ['type' => 's', 'value' => $text],
                ['type' => 's', 'value' => $file_path],
                ['type' => 's', 'value' => $news_date],
                ['type' => 'i', 'value' => $is_disabled],
                ['type' => 'i', 'value' => $id]
            ];
        } else {
            $sql = "INSERT INTO news (title, text, file_path, news_date, is_disabled) VALUES (?, ?, ?, ?, ?)";
            $bindings = [
                ['type' => 's', 'value' => $title],
                ['type' => 's', 'value' => $text],
                ['type' => 's', 'value' => $file_path],
                ['type' => 's', 'value' => $news_date],
                ['type' => 'i', 'value' => $is_disabled]
            ];
        }
        $this->sql_manager->execute($sql, $bindings);
    }

    public function deleteNews(): void
    {
        @session_start();
        if (!UserManager::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent supprimer les news");
        }

        $id = $_POST['id'] ?? null;
        if (empty($id) || !is_numeric($id)) {
            throw new Exception("ID de news invalide");
        }

        $sql = "DELETE FROM news WHERE id = ?";
        $bindings = [['type' => 'i', 'value' => $id]];
        $this->sql_manager->execute($sql, $bindings);
    }

}