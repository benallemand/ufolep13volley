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

    /**
     * Répertoires que `get_photo` a le droit de servir. Seul appelant :
     * la photo d'équipe du tableau de bord responsable (`teams_pics/`).
     */
    const SERVABLE_DIRS = array('teams_pics', 'images');
    const IMAGE_TYPES = array(
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    );

    /**
     * Chemin absolu d'une image servable, ou null.
     *
     * L'endpoint est public et lisait n'importe quel fichier : `.env` (les
     * identifiants de la base), le code source, `../` compris (issue #352).
     * Le chemin est désormais résolu par `realpath` — ce qui neutralise `..`
     * et les liens — puis doit tomber dans un répertoire autorisé et porter
     * une extension d'image.
     */
    public static function resolve_servable_path($path_photo): ?string
    {
        if (!is_string($path_photo) || $path_photo === '' || str_contains($path_photo, "\0")) {
            return null;
        }
        $root = realpath(__DIR__ . '/..');
        $real = realpath($root . '/' . $path_photo);
        if ($real === false || !is_file($real)) {
            return null;
        }
        $extension = strtolower(pathinfo($real, PATHINFO_EXTENSION));
        if (!isset(self::IMAGE_TYPES[$extension])) {
            return null;
        }
        foreach (self::SERVABLE_DIRS as $dir) {
            $allowed = realpath($root . '/' . $dir);
            if ($allowed !== false && str_starts_with($real, $allowed . DIRECTORY_SEPARATOR)) {
                return $real;
            }
        }
        return null;
    }

    function get_photo($path_photo)
    {
        $file = self::resolve_servable_path($path_photo) ?? realpath(__DIR__ . '/../images/unknownTeam.png');
        header('Content-Type: ' . self::IMAGE_TYPES[strtolower(pathinfo($file, PATHINFO_EXTENSION))]);
        readfile($file);
        exit(0);
    }


}