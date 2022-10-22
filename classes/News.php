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
            ORDER BY n.news_date DESC";
        return $this->sql_manager->execute($sql);
    }


}