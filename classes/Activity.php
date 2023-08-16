<?php
require_once __DIR__ . '/Generic.php';

class Activity extends Generic
{
    /**
     * @throws Exception
     */
    function add($comment, $id_user = null): int|array|string|null
    {
        @session_start();
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $comment);
        $is_user_set = false;
        if (!empty($id_user)) {
            $is_user_set = true;
            $bindings[] = array('type' => 'i', 'value' => $id_user);
        } elseif (!empty($_SESSION['id_user'])) {
            $is_user_set = true;
            $bindings[] = array('type' => 'i', 'value' => $_SESSION['id_user']);
        }
        if ($is_user_set) {
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