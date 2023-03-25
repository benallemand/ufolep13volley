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
            throw new Exception("Impossible de trouver le fichier envoyé !");
        }
        $uploaddir = '../teams_pics/';
        $uploadfile = $uploaddir . time() . '.' . pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadfile) !== TRUE) {
            throw new Exception("Impossible de déplacer/renommer le fichier envoyé !");
        }
    }

    /**
     * @throws Exception
     */
    private function check_action_allowed(string $function_name, $file_path)
    {
        if (!UserManager::is_connected()) {
            throw new Exception("Connectez-vous pour télécharger ce(s) fichier(s) !", 401);
        }
        switch ($function_name) {
            case 'download_match_file':
                $code_match = $this->get_code_match_from_file_path($file_path);
                $match_manager = new MatchMgr();
                $match = $match_manager->get_match_by_code_match($code_match);
                // allow admin
                if (UserManager::isAdmin()) {
                    return;
                }
                if (!UserManager::isTeamLeader()) {
                    throw new Exception("Seuls les responsables d'équipes peuvent télécharger ce fichier !", 401);
                }
                // allow only playing teams
                @session_start();
                if (!in_array($_SESSION['id_equipe'], array($match['id_equipe_dom'], $match['id_equipe_ext']))) {
                    throw new Exception("Seules les équipes ayant participé au match peuvent dire qui était là !", 401);
                }
                break;
            default:
                break;
        }
    }

    /**
     * @param $file_path
     * @throws Exception
     */
    function download_match_file($file_path)
    {
        $this->check_action_allowed(__FUNCTION__, $file_path);
        $dir = __DIR__ . '/../match_files';
        $name = basename($file_path);
        $file_path = "$dir/$name";
        if (!file_exists($file_path)) {
            throw new Exception("Fichier $name introuvable !");
        }
        header("Content-type: " . mime_content_type($file_path));
        header("Content-Disposition: filename=$name");
        header("Content-length: " . filesize($file_path));
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($file_path);
        die();
    }

    /**
     * @param $uploadfile
     * @return array|int|string|null
     * @throws Exception
     */
    function insert_file_in_db($uploadfile): array|int|string|null
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

    private function get_code_match_from_file_path($file_path): string
    {
        $file_name = pathinfo($file_path, PATHINFO_FILENAME);
        return substr($file_name, 0, strpos($file_name, "file"));
    }
}