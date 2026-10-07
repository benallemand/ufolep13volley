<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:54
 */
require_once __DIR__ . '/SqlManager.php';
require_once __DIR__ . '/UserManager.php';

class Generic
{
    protected SqlManager $sql_manager;
    protected string $table_name;
    protected string $id_name;

    public function __construct()
    {
        @session_start();
        $this->sql_manager = new SqlManager();
        $this->id_name = 'id';
    }

    public static function starts_with($string, $startString): bool
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    public static function ends_with($string, $endString): bool
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCurrentUserDetails(): array
    {
        if (!(isset($_SESSION['login']))) {
            @session_start();
        }
        if (!(isset($_SESSION['login']))) {
            throw new Exception("Utilisateur non connecté !");
        }
        return $_SESSION;
    }

    /**
     * @param $comment
     * @throws Exception
     */
    protected function addActivity($comment): void
    {
        try{
            $userDetails = $this->getCurrentUserDetails();
        }
        catch (Exception $exception) {
        }
        $bindings = array(
            array('type' => 's', 'value' => $comment),
        );
        if (!empty($userDetails['id_user'])) {
            $bindings[] = array('type' => 'i', 'value' => $userDetails['id_user']);
            $sql = "INSERT activity SET comment = ?, activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s'), user_id = ?";
        } else {
            $sql = "INSERT activity SET comment = ?, activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s')";
        }
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $str
     * @return string
     */
    public static function accentedToNonAccented($str): string
    {
        $unwanted_array = array('?' => 'S', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y',
            '-' => '', ' ' => '', '\'' => '');
        return empty($str) ? '' : strtr($str, $unwanted_array);
    }

    public static function randomPassword(): string
    {
        $alphabet = "abcdefghjkmnpqrstuwxyzABCDEFGHJKMNPQRSTUWXYZ23456789@";
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }

    /**
     * @param $id_team
     * @return array
     * @throws Exception
     */
    public function getActivity($id_team=null, $my_team=null): array
    {
        // Historique de l'espace responsable (`my_team`) : l'équipe courante,
        // y compris pour un admin qui en est responsable (#419) ; sans quoi il
        // recevait le journal de tout le site. L'écran Activité de l'admin ne
        // le passe pas.
        if (UserManager::is_connected()
            && (!UserManager::isAdmin() || (Generic::to_flag($my_team) === 1 && UserManager::isTeamLeader()))) {
            $id_team = $_SESSION['id_equipe'];
        }
        $sql = "SELECT 
                DATE_FORMAT(a.activity_date, '%d/%m/%Y %H:%i:%s') AS date, 
                GROUP_CONCAT(DISTINCT e.nom_equipe) AS nom_equipe, 
                GROUP_CONCAT(DISTINCT c.libelle) AS competition, 
                a.comment AS description, 
                ca.login AS utilisateur, 
                ca.email AS email_utilisateur 
            FROM activity a
            LEFT JOIN comptes_acces ca ON ca.id=a.user_id
            LEFT JOIN users_teams ut ON ca.id = ut.user_id
            LEFT JOIN equipes e ON e.id_equipe=ut.team_id
            LEFT JOIN competitions c ON c.code_competition=e.code_competition";
        // `$id_team` vient de la session pour un non-admin, mais un admin peut
        // le poster : on lie la valeur au lieu de la concatener (issue #270).
        $bindings = array();
        if (!empty($id_team)) {
            $sql .= " WHERE e.id_equipe = ?";
            $bindings[] = array('type' => 'i', 'value' => $id_team);
        }
        $sql .= " GROUP BY a.activity_date, a.comment, ca.login, ca.email";
        $sql .= " ORDER BY a.activity_date DESC";
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Ligne en base avant une écriture, pour que le journal d'activité dise ce
     * qui a réellement changé (issue #377). Null pour une création.
     * @throws Exception
     */
    protected function row_before($id, ?string $table = null, ?string $id_name = null): ?array
    {
        if (empty($id)) {
            return null;
        }
        $table = $table ?? $this->table_name;
        $id_name = $id_name ?? $this->id_name;
        $rows = $this->sql_manager->execute("SELECT * FROM $table WHERE $id_name = ?",
            array(array('type' => 'i', 'value' => (int)$id)));
        return $rows[0] ?? null;
    }

    /**
     * Ligne d'activité décrivant une écriture : création, ou liste des champs
     * modifiés (« - nom : ancien → nouveau »), calculée en comparant la ligne
     * d'avant (`row_before`) aux valeurs écrites. Remplace `dirtyFields`, que
     * seul ExtJS envoyait (issue #377). Null si rien n'a changé.
     */
    protected function build_activity(string $subject, ?array $before, array $inputs): ?string
    {
        if ($before === null) {
            return "Création : $subject";
        }
        $changes = array();
        foreach ($inputs as $field => $value) {
            if (!array_key_exists($field, $before)) {
                continue; // paramètre qui n'est pas une colonne (id_team…)
            }
            $old = self::comparable_value($before[$field]);
            $new = self::comparable_value($value);
            if ($old !== $new) {
                $changes[] = "- $field : " . ($old === '' ? '(vide)' : $old) . ' → ' . ($new === '' ? '(vide)' : $new);
            }
        }
        if (empty($changes)) {
            return null;
        }
        return "$subject : <br/>" . implode('<br/>', $changes);
    }

    /**
     * Valeur ramenée à une forme comparable entre la base et un formulaire :
     * bit(1) lu en binaire, date jj/mm/aaaa, « null » posté en texte.
     */
    private static function comparable_value(mixed $value): string
    {
        if (is_string($value) && strlen($value) === 1 && ord($value) < 2) {
            return (string)ord($value);
        }
        $value = trim((string)$value);
        if ($value === 'null') {
            return '';
        }
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $value, $m)) {
            return "$m[3]-$m[2]-$m[1]";
        }
        return $value;
    }

    public function getSql($query = "1=1"): string
    {
        return "SELECT * 
                FROM $this->table_name
                WHERE $query";
    }

    /**
     * @param string $query
     * @param array $bindings
     * @return array
     * @throws Exception
     */
    public function get(string $query = "1=1", array $bindings=array()): array
    {
        $sql = $this->getSql($query);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function get_one(string $query = "1=1", array $bindings=array()): ?array
    {
        $results = $this->get($query, $bindings);
        return count($results) == 1 ? $results[0] : null;
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function get_by_id($id): array
    {
        $query = "$this->id_name = ?";
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $id
        );
        $sql = $this->getSql($query);
        $results = $this->sql_manager->execute($sql, $bindings);
        if (empty($results)) {
            throw new Exception("Pas de donnée dispo pour l'id $id !");
        }
        return $results[0];
    }

    /**
     * @param $ids
     * @throws Exception
     */
    public function delete($ids): void
    {
        // $ids arrive de l'extérieur sous la forme "1,2,3" (les grilles admin
        // joignent les ids sélectionnés). Il était concaténé tel quel dans le SQL,
        // ce qui l'exposait à une injection (issue #268) : on le découpe, on ne
        // garde que des entiers, et on lie chaque valeur.
        $id_list = self::parse_id_list($ids);
        if (empty($id_list)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($id_list), '?'));
        $sql = "DELETE FROM $this->table_name WHERE $this->id_name IN($placeholders)";
        $bindings = array_map(
            fn($id) => array('type' => 'i', 'value' => $id),
            $id_list
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Normalise une valeur de case à cocher en 0 ou 1.
     *
     * Les formulaires n'envoient pas tous la même chose : une case ExtJS cochée
     * postait `on` et une case décochée ne postait rien du tout, alors que le
     * formulaire modal de l'admin Vue envoie toujours la clé, avec `1` ou `0`.
     * Les comparaisons strictes `$value === 'on' || $value === 1` semées dans
     * les classes ne voyaient donc pas le `'1'` de l'admin Vue : **toute case
     * cochée y était enregistrée à 0** (issue #265, lot 4).
     *
     * @param mixed $value
     * @return int 0 ou 1
     */
    public static function to_flag(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_null($value)) {
            return 0;
        }
        $normalized = strtolower(trim((string)$value));
        if ($normalized === 'on' || $normalized === 'true' || $normalized === 'yes') {
            return 1;
        }
        return is_numeric($normalized) && (float)$normalized != 0 ? 1 : 0;
    }

    /**
     * Normalise une liste d'ids reçue de l'extérieur ("1,2,3", un tableau, un
     * entier) en un tableau d'entiers, sans doublon ni valeur non numérique.
     *
     * @param mixed $ids
     * @return int[]
     */
    public static function parse_id_list(mixed $ids): array
    {
        if (is_null($ids)) {
            return array();
        }
        $raw = is_array($ids) ? $ids : explode(',', (string)$ids);
        $parsed = array();
        foreach ($raw as $value) {
            if (is_numeric(trim((string)$value))) {
                $parsed[] = (int)trim((string)$value);
            }
        }
        return array_values(array_unique($parsed));
    }

    /**
     * Un identifiant reçu de l'extérieur, en entier — ou une exception.
     * `is_numeric` refuse `12 OR 1=1`, là où un cast `(int)` seul le
     * ramènerait silencieusement à 12 (issue #355).
     *
     * @throws Exception
     */
    public static function parse_id(mixed $id, string $label = 'identifiant'): int
    {
        if (is_int($id)) {
            return $id;
        }
        if (!is_string($id) || !preg_match('/^\s*\d+\s*$/', $id)) {
            throw new Exception(ucfirst($label) . " invalide !");
        }
        return (int)trim($id);
    }

    /**
     * @param $inputs
     * @return array|int|string|null
     * @throws Exception
     */
    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs[$this->id_name])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " $this->table_name SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case $this->id_name:
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
        if (!empty($inputs[$this->id_name])) {
            $bindings[] = array(
                'type' => 'i',
                'value' => $inputs[$this->id_name]
            );
            $sql .= " WHERE $this->id_name = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param ...$args
     * @return array|int|string|null
     * @throws Exception
     */
    public function save_with_args(...$args) {
        if (count($args) === 1 && is_array($args[0])) {
            $inputs = $args[0]; // Les arguments sont déjà sous forme de tableau associatif
        } else {
            $inputs = $args; // Sinon, utilisez directement les arguments
        }
        return $this->save($inputs);
    }
}