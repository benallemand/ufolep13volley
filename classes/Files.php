<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/../classes/SqlManager.php';

class Files extends Generic
{
    private $sql_manager;
    private $table_name;

    public function __construct()
    {
        $this->sql_manager = new SqlManager();
        $this->table_name = 'files';
    }

    private function getSql($query = "1=1"): string
    {
        return "SELECT * 
                FROM $this->table_name
                WHERE $query";
    }

    /**
     * @param string $query
     * @return array
     * @throws Exception
     */
    public function get(string $query = "1=1"): array
    {
        $sql = $this->getSql($query);
        return $this->sql_manager->getResults($sql);
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function get_by_id($id): array
    {
        $query = "id = ?";
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $id
        );
        $sql = $this->getSql($query);
        $results = $this->sql_manager->execute($sql, $bindings);
        if (empty($results)) {
            throw new Exception("Unable to find file for id $id !");
        }
        return $results[0];
    }

    /**
     * @param $inputs
     * @return array|int|string|null
     * @throws Exception
     */
    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " $this->table_name SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                default:
                    $bindings[] = array(
                        'type' => 's',
                        'value' => $value
                    );
                    $sql .= "$key = ?,";
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($inputs['id'])) {
            $bindings[] = array(
                'type' => 'i',
                'value' => $inputs['id']
            );
            $sql .= " WHERE id = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $ids
     * @throws Exception
     */
    public function delete($ids)
    {
        $sql = "DELETE FROM $this->table_name WHERE id IN($ids)";
        $this->sql_manager->execute($sql);
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
    function insert_file_in_db($uploadfile, &$id)
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
    function upload_and_insert_file_in_db($fileKey, &$id)
    {
        $uploadfile = null;
        $this->upload_file($fileKey, $uploadfile);
        $id = null;
        $this->insert_file_in_db($uploadfile, $id);
    }
}