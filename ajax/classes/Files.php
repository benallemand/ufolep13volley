<?php

require_once __DIR__ . '/../../includes/db_inc.php';

class Files
{

    /**
     * @param $fileKey
     * @param $uploadfile
     * @throws Exception
     */
    function uploadFile($fileKey, &$uploadfile)
    {
        if (empty($_FILES[$fileKey]['name'])) {
            throw new Exception("Unable to find uploaded file");
        }
        $uploaddir = '../teams_pics/';
        $uploadfile = $uploaddir . time() . '.' . pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadfile) !== TRUE) {
            throw new Exception("Unable to move and rename uploaded file");
        }
    }

    /**
     * @param $uploadfile
     * @param $id
     * @throws Exception
     */
    function insertFileInDb($uploadfile, &$id)
    {
        global $db;
        conn_db();
        $path_photo = str_replace('../', '', $uploadfile);
        $sql = "INSERT INTO photos SET path_photo = '$path_photo'";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            disconn_db();
            throw new Exception("SQL error : " . mysqli_error($db));
        }
        $id = mysqli_insert_id($db);
        disconn_db();
    }

    /**
     * @param $fileKey
     * @param $id
     * @throws Exception
     */
    function uploadAndInsertFileInDb($fileKey, &$id)
    {
        $uploadfile = null;
        $this->uploadFile($fileKey, $uploadfile);
        $id = null;
        $this->insertFileInDb($uploadfile, $id);
    }
}
