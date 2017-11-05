<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once 'Generic.php';

class DayManager extends Generic
{
    public function insertDay($code_competition, $numero)
    {
        $db = Database::openDbConnection();
        $numero_padded = str_pad($numero, 2, '0', STR_PAD_LEFT);
        $sql = "INSERT INTO journees SET code_competition = '$code_competition', numero = $numero, nommage = 'Journee $numero_padded'";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            $message = mysqli_error($db);
            disconn_db();
            throw new Exception($message);
        }
        return mysqli_insert_id($db);
    }

}