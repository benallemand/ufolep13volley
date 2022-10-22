<?php
require_once __DIR__ . '/Generic.php';

class Activity extends Generic
{
    /**
     * @throws Exception
     */
    function add($comment): int|array|string|null
    {
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $comment);
        if (!empty($_SESSION['id_user'])) {
            $bindings[] = array('type' => 's', 'value' => $_SESSION['id_user']);
            $sql = "INSERT activity SET 
                        comment=?, 
                        activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s'), 
                        user_id=?";
        } else {
            $sql = "INSERT activity SET 
                        comment=?, 
                        activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s')";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }
}