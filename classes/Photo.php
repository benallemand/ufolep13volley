<?php
require_once __DIR__ . '/Generic.php';

class Photo extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'photos';
    }

    /**
     * @throws Exception
     */
    function insertPhoto($uploadfile)
    {
        $sql = "INSERT INTO photos SET path_photo = ?";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $uploadfile);
        return $this->sql_manager->execute($sql, $bindings);
    }

    function get_photo($path_photo)
    {
        header('Content-Type: image/jpeg');
        if (!empty($path_photo)) {
            if (file_exists(__DIR__ . "/../$path_photo")) {
                readfile(__DIR__ . "/../$path_photo");
                exit(0);
            }
        }
        readfile(__DIR__ . "/../images/unknownTeam.png");
        exit(0);
    }


}