<?php
require_once __DIR__ . '/Generic.php';

class Photo extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'photos';
    }

    function insertPhoto($uploadfile)
    {
        $sql = "INSERT INTO photos SET path_photo = '$uploadfile'";
        return $this->sql_manager->execute($sql);
    }

    function get_photo($path_photo)
    {
        header('Content-Type: image/jpeg');
        if(!empty($path_photo)) {
            if(file_exists(__DIR__ . "/../$path_photo")) {
                readfile(__DIR__ . "/../$path_photo");
                exit(0);
            }
        }
        readfile(__DIR__ . "/../images/unknownTeam.png");
        exit(0);
    }


}