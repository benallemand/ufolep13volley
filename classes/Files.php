<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/../classes/SqlManager.php';

class Files extends Generic
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'files';
    }

    /**
     * @throws Exception
     */
    public function cleanup_files()
    {
        // Detect files without hash written in database
        $results_select = $this->get("hash IS NULL");
        foreach ($results_select as $result_select) {
            $file_path = __DIR__ . "/../" . $result_select['path_file'];
            if (file_exists($file_path)) {
                // compute md5 if file exists and save in database
                $hash = md5_file($file_path);
                $this->save(array(
                    'id' => $result_select['id'],
                    'hash' => $hash));
            } else {
                // if file does not exist, delete from database
                $this->delete($result_select['id']);
            }
        }
        // clean duplicate files in db
        $this->delete_duplicates();
        // list db files
        $results_select = $this->get();
        $db_file_paths = array_column($results_select, 'path_file');
        // list files under directory match_files
        $existing_files = scandir(__DIR__ . "/../match_files");
        foreach ($existing_files as $current_existing_file) {
            if (in_array($current_existing_file, array('.', '..'))) {
                continue;
            }
            if (in_array("match_files/$current_existing_file", $db_file_paths)) {
                continue;
            }
            // if file is not found in database, delete it
            unlink(__DIR__ . "/../match_files/$current_existing_file");
        }

    }

    /**
     * @throws Exception
     */
    private function delete_duplicates()
    {
        $sql = "DELETE f1
                FROM files f1,
                    files f2
                WHERE f1.id > f2.id
                 AND f1.hash = f2.hash
                 AND f1.hash IS NOT NULL
                 AND f2.hash IS NOT NULL";
        $this->sql_manager->execute($sql);
    }

    /**
     * @param $fileKey
     * @param $uploadfile
     * @throws Exception
     */
    function upload_file($fileKey, &$uploadfile)
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
    function insert_file_in_db($uploadfile)
    {
        $path_photo = str_replace('../', '', $uploadfile);
        $sql = "INSERT INTO photos SET path_photo = '$path_photo'";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $fileKey
     * @param $id
     * @throws Exception
     */
    function upload_and_insert_file_in_db($fileKey)
    {
        $uploadfile = null;
        $this->upload_file($fileKey, $uploadfile);
        return $this->insert_file_in_db($uploadfile);
    }
}