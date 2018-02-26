<?php
/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 26/02/2018
 * Time: 14:28
 */
require_once 'Generic.php';

class HallOfFameManager extends Generic
{

    /**
     * @param $title
     * @param $team_name
     * @param $period
     * @param $league
     * @return int|string
     * @throws Exception
     */
    public function insert($title, $team_name, $period, $league)
    {
        $db = Database::openDbConnection();
        $sql = "INSERT INTO hall_of_fame SET 
                title = '$title', 
                team_name = '$team_name', 
                period = '$period',
                league = '$league'";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            $message = mysqli_error($db);
            throw new Exception($message);
        }
        return mysqli_insert_id($db);
    }
}