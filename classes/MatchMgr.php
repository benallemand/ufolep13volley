<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once __DIR__ . '/Configuration.php';
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/SqlManager.php';
require_once __DIR__ . '/Team.php';
require_once __DIR__ . '/Players.php';
require_once __DIR__ . '/Rank.php';
require_once __DIR__ . '/Register.php';
require_once __DIR__ . '/Competition.php';
require_once __DIR__ . '/Day.php';
require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/Survey.php';

class MatchMgr extends Generic
{
    private Survey $survey;

    private Team $team;
    private Rank $rank;
    private Configuration $configuration;

    /**
     * Match constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->team = new Team();
        $this->rank = new Rank();
        $this->survey = new Survey();
        $this->configuration = new Configuration();
        ini_set('max_execution_time', 1200);
        ini_set('memory_limit', '512M');
        ini_set('xdebug.max_nesting_level', 2000);
        $this->table_name = 'matches';
        $this->id_name = 'id_match';
    }

    /**
     * @param string|null $query
     * @param string $order
     * @return string
     */
    private function get_sql(?string $query = "1=1", string $order = "code_competition, division, numero_journee, code_match"): string
    {
        return "SELECT m.id_match AS id, m.* FROM matchs_view m WHERE $query ORDER BY $order";
    }

    /**
     * @param string|null $query
     * @return string
     */
    private function get_sql_match_files(?string $query = "1=1"): string
    {
        // group by to avoid duplicate files in zip file
        return "SELECT 
        f.id,
        f.path_file,
        f.hash
        FROM files f 
        JOIN matches_files mf ON mf.id_file = f.id
        WHERE $query GROUP BY f.hash";
    }

    /**
     * @throws Exception
     */
    public function getMatches($competition = null, $division = null): array
    {
        if (empty($competition) && empty($division)) {
            return $this->get_matches();
        }
        return $this->get_matches("m.code_competition = '$competition' 
                                    AND m.division = '$division' 
                                    AND m.match_status IN ('CONFIRMED', 'NOT_CONFIRMED')");
    }

    /**
     * @param string|null $query
     * @param string $order
     * @return array
     * @throws Exception
     */
    public function get_matches(?string $query = "1=1", string $order = "code_competition, division, numero_journee, code_match"): array
    {
        return $this->sql_manager->execute($this->get_sql($query, $order));
    }

    /**
     * @throws Exception
     */
    public function getMesMatches()
    {
        @session_start();
        $team_id = $_SESSION['id_equipe'];
        return $this->get_matches(
            "(m.id_equipe_dom = $team_id OR m.id_equipe_ext = $team_id) 
                    AND m.match_status NOT IN ('ARCHIVED')");
    }

    /**
     * @throws Exception
     */
    public function getMyClubMatches()
    {
        @session_start();
        $team_id = $_SESSION['id_equipe'];
        return $this->get_matches(
            "(
                    m.id_equipe_dom IN (SELECT id_equipe 
                                        FROM equipes 
                                        WHERE id_club IN (
                                            SELECT id_club 
                                            FROM equipes 
                                            WHERE id_equipe = $team_id)) 
                    OR 
                    m.id_equipe_ext IN (SELECT id_equipe 
                                        FROM equipes 
                                        WHERE id_club IN (
                                            SELECT id_club 
                                            FROM equipes 
                                            WHERE id_equipe = $team_id))
                    ) 
                    AND m.match_status NOT IN ('ARCHIVED')");
    }

    /**
     * @param $id_match
     * @return mixed
     * @throws Exception
     */
    public function get_match($id_match)
    {
        $results = $this->get_matches("m.id_match = $id_match");
        $count_results = count($results);
        if ($count_results !== 1) {
            throw new Exception("Erreur lors de la récupération des données du match ! Trouvé $count_results match(s) !");
        }
        return $results[0];
    }

    /**
     * @param $id_match
     * @return array
     * @throws Exception
     */
    public function get_match_files($id_match): array
    {
        return $this->sql_manager->execute($this->get_sql_match_files("mf.id_match = $id_match"));
    }

    /**
     * @param $id
     * @throws Exception
     */
    public function download($id)
    {
        setlocale(LC_ALL, 'fr_FR.UTF-8');
        if (empty($id)) {
            throw new Exception("Pas d'id spécifié !");
        }
        if (!$this->is_download_allowed($id)) {
            throw new Exception("Utilisateur non autorisé à télécharger !");
        }
        $match = $this->get_match($id);
        $match_files = $this->get_match_files($id);
        $archiveFileName = $match['code_match'] . ".zip";
        $zip = new ZipArchive();
        if ($zip->open($archiveFileName, ZIPARCHIVE::CREATE) !== TRUE) {
            throw new Exception("Erreur pendant la création du fichier ZIP !");
        }
        if (count($match_files) === 0) {
            throw new Exception("Pas de fichier attaché (Attention, ceux envoyés par email ne sont pas téléchargeables) !");
        }
        foreach ($match_files as $match_file) {
            $file_path = __DIR__ . "/../" . $match_file['path_file'];
            if (file_exists($file_path))
                $zip->addFile($file_path, basename($file_path));
        }
        $zip->close();
        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=$archiveFileName");
        header("Content-length: " . filesize($archiveFileName));
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($archiveFileName);
        unlink($archiveFileName);
    }

    /**
     * @param $id_match
     * @return bool
     * @throws Exception
     */
    private function is_download_allowed($id_match): bool
    {
        $userDetails = $this->getCurrentUserDetails();
        $profile = $userDetails['profile_name'];
        $id_team = $userDetails['id_equipe'];
        $match = $this->get_match($id_match);
        switch ($profile) {
            case 'ADMINISTRATEUR':
                return true;
            case 'RESPONSABLE_EQUIPE':
                if (
                    ($id_team != $match['id_equipe_dom'])
                    &&
                    ($id_team != $match['id_equipe_ext'])
                ) {
                    throw new Exception("Equipe non autorisée à télécharger ce match !");
                }
                return true;
            default:
                throw new Exception("Profil utilisateur non autorisé à télécharger !");
        }
    }

    /**
     * @param $id_match
     * @return bool
     * @throws Exception
     */
    public function is_match_read_allowed($id_match): bool
    {
        // if localhost, presume it is for test purpose
        switch (filter_input(INPUT_SERVER, 'SERVER_NAME')) {
            case 'localhost':
            case null:
                return true;
            default:
                break;
        }
        $userDetails = $this->getCurrentUserDetails();
        $profile = $userDetails['profile_name'];
        $id_team = $userDetails['id_equipe'];
        $match = $this->get_match($id_match);
        switch ($profile) {
            case 'ADMINISTRATEUR':
            case 'SUPPORT':
                return true;
            case 'RESPONSABLE_EQUIPE':
                if (
                    ($id_team != $match['id_equipe_dom'])
                    &&
                    ($id_team != $match['id_equipe_ext'])
                ) {
                    return false;
                }
                return true;
            default:
                return false;
        }
    }

    /**
     * @param $id_match
     * @return bool
     * @throws Exception
     */
    public function is_match_update_allowed($id_match): bool
    {
        // if localhost, presume it is for test purpose
        switch (filter_input(INPUT_SERVER, 'SERVER_NAME')) {
            case 'localhost':
            case null:
                return true;
            default:
                break;
        }
        $userDetails = $this->getCurrentUserDetails();
        $profile = $userDetails['profile_name'];
        $id_team = $userDetails['id_equipe'];
        $match = $this->get_match($id_match);
        switch ($profile) {
            case 'ADMINISTRATEUR':
            case 'SUPPORT':
                return true;
            case 'RESPONSABLE_EQUIPE':
                if (
                    ($id_team != $match['id_equipe_dom'])
                    &&
                    ($id_team != $match['id_equipe_ext'])
                ) {
                    return false;
                }
                if ($match['certif'] == 1) {
                    return false;
                }
                if ($match['is_sign_match_dom'] == 1 && $match['is_sign_match_ext'] == 1) {
                    return false;
                }
                return true;
            default:
                return false;
        }
    }

    /**
     * @throws Exception
     */
    public function save_match($id_match,
                               $code_match,
                               $set_1_dom,
                               $set_2_dom,
                               $set_3_dom,
                               $set_4_dom,
                               $set_5_dom,
                               $set_1_ext,
                               $set_2_ext,
                               $set_3_ext,
                               $set_4_ext,
                               $set_5_ext,
                               $referee,
                               $note,
                               $dirtyFields = null)
    {
        $this->save(array(
            'dirtyFields' => $dirtyFields,
            'id_match' => $id_match,
            'code_match' => $code_match,
            'set_1_dom' => $set_1_dom,
            'set_2_dom' => $set_2_dom,
            'set_3_dom' => $set_3_dom,
            'set_4_dom' => $set_4_dom,
            'set_5_dom' => $set_5_dom,
            'set_1_ext' => $set_1_ext,
            'set_2_ext' => $set_2_ext,
            'set_3_ext' => $set_3_ext,
            'set_4_ext' => $set_4_ext,
            'set_5_ext' => $set_5_ext,
            'referee' => $referee,
            'note' => $note,
        ));
    }

    /**
     * @param $code_match
     * @param $parent_code_competition
     * @param $code_competition
     * @param $division
     * @param $id_equipe_dom
     * @param $id_equipe_ext
     * @param $id_gymnasium
     * @param $id_journee
     * @param $date_reception
     * @param $certif
     * @param $is_sign_team_dom
     * @param $is_sign_team_ext
     * @param $is_sign_match_dom
     * @param $is_sign_match_ext
     * @param $note
     * @param null $dirtyFields
     * @param null $id_match
     * @throws Exception
     */
    public function saveMatch(
        $code_match,
        $parent_code_competition,
        $code_competition,
        $division,
        $id_equipe_dom,
        $id_equipe_ext,
        $id_gymnasium,
        $id_journee,
        $date_reception,
        $certif,
        $is_sign_team_dom,
        $is_sign_team_ext,
        $is_sign_match_dom,
        $is_sign_match_ext,
        $note,
        $dirtyFields = null,
        $id_match = null
    )
    {
        $inputs = array(
            'code_match' => $code_match,
            'parent_code_competition' => $parent_code_competition,
            'code_competition' => $code_competition,
            'division' => $division,
            'id_equipe_dom' => $id_equipe_dom,
            'id_equipe_ext' => $id_equipe_ext,
            'id_gymnasium' => $id_gymnasium,
            'id_journee' => $id_journee,
            'date_reception' => $date_reception,
            'certif' => $certif,
            'is_sign_team_dom' => $is_sign_team_dom,
            'is_sign_team_ext' => $is_sign_team_ext,
            'is_sign_match_dom' => $is_sign_match_dom,
            'is_sign_match_ext' => $is_sign_match_ext,
            'note' => $note,
            'dirtyFields' => $dirtyFields,
            'id_match' => $id_match,
        );
        $this->save($inputs);
    }

    /**
     * @throws Exception
     */
    public function save($inputs): void
    {
        $bindings = array();
        if (empty($inputs['id_match'])) {
            $sql = "INSERT INTO";
        } else {
            if (!$this->is_match_update_allowed($inputs['id_match'])) {
                throw new Exception("Vous n'êtes pas autorisé à modifier ce match !");
            }
            $sql = "UPDATE";
        }
        $sql .= " matches SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id_match':
                case 'dirtyFields':
                case 'parent_code_competition':
                case 'equipe_dom':
                case 'equipe_ext':
                    break;
                case 'id_equipe_dom':
                case 'id_equipe_ext':
                case 'id_gymnasium':
                case 'id_journee':
                case 'set_1_dom':
                case 'set_1_ext':
                case 'set_2_dom':
                case 'set_2_ext':
                case 'set_3_dom':
                case 'set_3_ext':
                case 'set_4_dom':
                case 'set_4_ext':
                case 'set_5_dom':
                case 'set_5_ext':
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 'i', 'value' => $value);
                    break;
                case 'date_reception':
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    $bindings[] = array('type' => 's', 'value' => $value);
                    break;
                case 'certif':
                case 'is_sign_team_dom':
                case 'is_sign_team_ext':
                case 'is_sign_match_dom':
                case 'is_sign_match_ext':
                    $val = ($value === 'on' || $value === 1) ? 1 : 0;
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 'i', 'value' => $val);
                    break;
                default:
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 's', 'value' => $value);
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($inputs['id_match'])) {
            $sql .= " WHERE id_match = ?";
            $bindings[] = array('type' => 'i', 'value' => $inputs['id_match']);
        }
        $this->sql_manager->execute($sql, $bindings);
        if (empty($inputs['id_match'])) {
            return;
        }
        $code_match = $inputs['code_match'];
        $this->addActivity("Le match $code_match a ete modifie");
    }

    /**
     * @param $uploadfile
     * @param $file_hash
     * @return array|int|string|null
     * @throws Exception
     */
    private function insert_file($uploadfile, $file_hash)
    {
        $sql = "INSERT INTO files SET path_file = ?, hash = ?";
        $bindings = array(
            array('type' => 's', 'value' => $uploadfile),
            array('type' => 's', 'value' => $file_hash),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $idMatch
     * @param $idFile
     * @return array|int|string|null
     * @throws Exception
     */
    private function link_match_to_file($idMatch, $idFile)
    {
        $sql = "INSERT INTO matches_files SET id_file = ?, id_match = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $idFile),
            array('type' => 'i', 'value' => $idMatch),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param string $query
     * @throws Exception
     */
    public function unset_day_matches(string $query = "1=1")
    {
        $this->sql_manager->execute("UPDATE matches SET id_journee = NULL WHERE $query");
    }

    /**
     * @param $N
     * @return array
     */
    function generate_round_robin_rounds($N): array
    {
        assert($N % 2 === 0);
        $rounds = array();
        for ($i = 0; $i < $N - 1; $i++) {
            // round
            // first match
            $matches = array();
            $matches[] = (0) . " v " . ($N - 1 - $i);
            for ($j = 1; $j < $N / 2; $j++) {
                // 2..n match
                $home = (1 + (($N - $i + $j - 2) % ($N - 1)));
                $away = (1 + ((2 * $N - $i - $j - 3) % ($N - 1)));
                $matches[] = $home . " v " . $away;
            }
            $rounds[] = $matches;
        }
        return $rounds;
    }

    /**
     * @throws Exception
     */
    public function generate_matches_v2($competition): void
    {
        $code_competition = $competition['code_competition'];
        $this->delete_matches("code_competition = '$code_competition' AND match_status = 'NOT_CONFIRMED'");
        $message = "";
        $expected_matches = $this->get_expected_matches($competition, null, $message);
        foreach ($expected_matches as $index => $match) {
            error_log($index + 1 . " / " . count($expected_matches));
            $this->insert_match($match);
        }
    }

    /**
     * @param $competition
     * @throws Exception
     */
    public function generate_matches($competition)
    {
        if (empty($competition)) {
            throw new Exception("Compétition non trouvée !");
        }
        // delete previous generation attempts
        $code_competition = $competition['code_competition'];
        $this->delete_matches("code_competition = '$code_competition' AND match_status = 'NOT_CONFIRMED'");
        // home and away if needed
        $divisions = $this->rank->getDivisionsFromCompetition($competition['code_competition']);
        $message = "Nombre de divisions : " . count($divisions) . PHP_EOL;
        $count_to_be_inserted_matches = 0;
        $all_expected_matches = array();
        foreach ($divisions as $division) {
            $message_division = "";
            $expected_matches = $this->get_expected_matches($competition, $division, $message_division);
            $all_expected_matches[] = $expected_matches;
            $message .= $message_division;
            $count_to_be_inserted_matches += count($expected_matches);
        }
        $all_expected_matches = array_merge(...$all_expected_matches);
        foreach ($all_expected_matches as $expected_matches) {
            if (count($expected_matches) === 0) {
                continue;
            }
            $this->insert_matches($expected_matches, 0, 0);
        }
        $count_inserted_matches = count($this->get_matches("m.code_competition = '$code_competition' AND m.match_status = 'NOT_CONFIRMED'"));
        $message .= "Nombre de matchs à créer : $count_to_be_inserted_matches" . PHP_EOL;
        $message .= "Nombre de matchs créés : $count_inserted_matches" . PHP_EOL;
        throw new Exception($message, 201);
    }

    /**
     * @param $match
     * @return string
     */
    private function flip($match): string
    {
        $components = explode(' v ', $match);
        return $components[1] . " v " . $components[0];
    }

    /**
     * @param string $code_competition
     * @param $division
     * @param int $id_equipe_dom
     * @param int $id_equipe_ext
     * @param string|null $code_match
     * @param int|null $id_journee
     * @param string|null $date_match , format '%d/%m/%Y'
     * @param int|null $id_gymnase
     * @param string|null $note
     * @return void
     * @throws Exception
     */
    public function insert_db_match(string $code_competition,
                                            $division,
                                     int    $id_equipe_dom,
                                     int    $id_equipe_ext,
                                     string $code_match = null,
                                     int    $id_journee = null,
                                     string $date_match = null,
                                     int    $id_gymnase = null,
                                     string $note = null): void
    {
        $bindings = array();
        $code_match_string = "code_match = NULL";
        if (!empty($code_match)) {
            $bindings[] = array('type' => 's', 'value' => $code_match);
            $code_match_string = "code_match = ?";
        }
        $bindings[] = array('type' => 's', 'value' => $code_competition);
        $bindings[] = array('type' => 's', 'value' => $division);
        $bindings[] = array('type' => 'i', 'value' => $id_equipe_dom);
        $bindings[] = array('type' => 'i', 'value' => $id_equipe_ext);
        $day_string = "id_journee = NULL";
        if (!empty($id_journee)) {
            $bindings[] = array('type' => 'i', 'value' => $id_journee);
            $day_string = "id_journee = ?";
        }
        $gymnasium_string = "id_gymnasium = NULL";
        if (!empty($id_gymnase)) {
            $bindings[] = array('type' => 'i', 'value' => $id_gymnase);
            $gymnasium_string = "id_gymnasium = ?";
        }
        $date_reception_string = "date_reception = NULL";
        if (!empty($date_match)) {
            $bindings[] = array('type' => 's', 'value' => $date_match);
            $date_reception_string = "date_reception = STR_TO_DATE(?, '%d/%m/%Y')";
        }
        $note_string = "note = NULL";
        if (!empty($note)) {
            $bindings[] = array('type' => 's', 'value' => $note);
            $note_string = "note = ?";
        }
        $sql = "INSERT INTO matches SET 
                $code_match_string, 
                code_competition = ?, 
                division = ?,
                id_equipe_dom = ?,
                id_equipe_ext = ?,
                $day_string,
                $gymnasium_string,
                $date_reception_string,
                $note_string";
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $id_equipe
     * @param $code_competition
     * @return array|int|string|null
     * @throws Exception
     */
    private function get_computed_dates($id_equipe, $code_competition): array|int|string|null
    {
        $sql = "SELECT DATE_FORMAT(j.start_date + INTERVAL FIELD(c.jour,
                                                 'Lundi',
                                                 'Mardi',
                                                 'Mercredi',
                                                 'Jeudi',
                                                 'Vendredi',
                                                 'Samedi',
                                                 'Dimanche') - 1 DAY, '%d/%m/%Y') AS computed_date,
                       j.numero                                                   AS week_number,
                       j.id                                                       AS week_id,
                       c.id_gymnase,
                       e.code_competition,
                       e.nom_equipe
                FROM journees j, equipes e
                         JOIN creneau c on e.id_equipe = c.id_equipe
                WHERE e.id_equipe = ?
                AND j.code_competition = ?
                ORDER BY c.usage_priority, week_number";
        $bindings = array(
            array('type' => 'i', 'value' => $id_equipe),
            array('type' => 's', 'value' => $code_competition),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $computed_date
     * @param $id_gymnase
     * @return bool
     * @throws Exception
     */
    private function is_date_filled($computed_date, $id_gymnase): bool
    {
        // si le gymnase est complet, retourner true
        $sql = file_get_contents(__DIR__ . '/../sql/is_gymnasium_full.sql');
        $bindings = array(
            array('type' => 'i', 'value' => $id_gymnase),
            array('type' => 's', 'value' => $computed_date),
        );
        return count($this->sql_manager->execute($sql, $bindings)) > 0;
    }

    /**
     * @param string $query
     * @throws Exception
     */
    public function delete_matches(string $query = "1=1"): void
    {
        $sql = "DELETE FROM matches WHERE $query";
        $this->sql_manager->execute($sql);
    }

    /**
     * @param $computed_date
     * @param null $id_gymnase
     * @return bool
     * @throws Exception
     */
    public function is_date_blacklisted($computed_date, $id_gymnase = null): bool
    {
        // tested ok
        $bindings = array(
            array('type' => 's', 'value' => $computed_date),
        );
        if ($id_gymnase === null) {
            $sql = "SELECT * 
                FROM blacklist_date 
                WHERE closed_date = STR_TO_DATE(?, '%d/%m/%Y')";
        } else {
            $bindings[] = array('type' => 'i', 'value' => $id_gymnase);
            $sql = "SELECT * 
                FROM blacklist_gymnase 
                WHERE closed_date = STR_TO_DATE(?, '%d/%m/%Y')
                AND id_gymnase = ?";
        }
        $results = $this->sql_manager->execute($sql, $bindings);
        return (count($results) > 0);
    }

    /**
     * @param $code_competition
     * @param $division_number
     * @param $week_id
     * @return int
     * @throws Exception
     */
    public function get_count_matches_per_day($code_competition, $division_number, $week_id): int
    {
        // tested ok
        $sql = "SELECT * 
                FROM matches
                WHERE code_competition = ? 
                  AND division = ? 
                  AND id_journee = ?
                  AND match_status != 'ARCHIVED'";
        $bindings = array(
            array('type' => 's', 'value' => $code_competition),
            array('type' => 's', 'value' => $division_number),
            array('type' => 'i', 'value' => $week_id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return count($results);
    }

    /**
     * @param $to_be_inserted_match
     * @param bool $is_flip_requested
     * @return bool
     * @throws Exception
     */
    public function insert_match($to_be_inserted_match, bool $is_flip_requested = false): bool
    {
        $team_dom = $to_be_inserted_match['dom'];
        $team_ext = $to_be_inserted_match['ext'];
        $competition = $to_be_inserted_match['competition'];
        $division = $to_be_inserted_match['division'];
        $code_competition = $competition['code_competition'];
        $computed_dates = $this->get_computed_dates($team_dom['id_equipe'], $code_competition);
        foreach ($computed_dates as $computed_date) {
            // computed date is full (too many matches in same gymnasium)
            if ($this->is_date_filled(
                $computed_date['computed_date'],
                $computed_date['id_gymnase'])) {
                continue;
            }
            // computed date is not allowed (holiday)
            if ($this->is_date_blacklisted($computed_date['computed_date'])) {
                continue;
            }
            // computed date is not allowed (gymnasium is not available)
            if ($this->is_date_blacklisted(
                $computed_date['computed_date'],
                $computed_date['id_gymnase'])) {
                continue;
            }
            // computed date is not allowed (home team cannot play when another one is playing)
            if ($this->is_date_blacklisted_team(
                $computed_date['computed_date'],
                $team_dom['id_equipe'])) {
                continue;
            }
            // computed date is not allowed (away team cannot play when another one is playing)
            if ($this->is_date_blacklisted_team(
                $computed_date['computed_date'],
                $team_ext['id_equipe'])) {
                continue;
            }
            // computed date is not allowed (home team already has a match this week)
            if ($this->is_team_busy_for_week($computed_date['week_id'], $team_dom['id_equipe'])) {
                continue;
            }
            // computed date is not allowed (away team already has a match this week)
            if ($this->is_team_busy_for_week($computed_date['week_id'], $team_ext['id_equipe'])) {
                continue;
            }
            $found_date = $computed_date['computed_date'];
            $round_number = $computed_date['week_number'];
            $match_number = $this->get_count_matches_per_day($competition['code_competition'], $division['division'], $computed_date['week_id']) + 1;
            $year_month = date('ym');
            $code_match =
                strtoupper($competition['code_competition']) .
                $year_month .
                $division['division'] .
                $round_number .
                $match_number;
            $this->insert_db_match(
                $competition['code_competition'],
                $division['division'],
                $team_dom['id_equipe'],
                $team_ext['id_equipe'],
                $code_match,
                $computed_date['week_id'],
                $found_date,
                $computed_date['id_gymnase']);
            return true;
        }
        // pas de date trouvée
        // si le dernier match entre les 2 équipes n'est pas récent
        if (!$is_flip_requested
            && !$this->is_last_match_recent($team_dom['id_equipe'], $team_ext['id_equipe'])
            && $this->has_timeslot($team_ext['id_equipe'])) {
            // inverser la réception, et trouver une nouvelle date
            return $this->insert_match(array(
                'dom' => $to_be_inserted_match['ext'],
                'ext' => $to_be_inserted_match['dom'],
                'competition' => $to_be_inserted_match['competition'],
                'division' => $to_be_inserted_match['division']
            ),
                true);
        }
        // pas de date trouvée
        // créer le match sans date
        $this->insert_db_match(
            $competition['code_competition'],
            $division['division'],
            $team_dom['id_equipe'],
            $team_ext['id_equipe'],
            null,
            null,
            null,
            null,
            "date non trouvée");
        return false;
    }

    /**
     * @param $week_id
     * @param $team_id
     * @return bool
     * @throws Exception
     */
    public function is_team_busy_for_week($week_id, $team_id): bool
    {
        // tested ok
        $sql = "SELECT m.* FROM matches m 
                WHERE DATE_FORMAT(m.date_reception, '%v%Y') IN (SELECT DATE_FORMAT(start_date, '%v%Y') 
                                                                FROM journees 
                                                                WHERE id = ?)
                AND (m.id_equipe_dom = ? OR m.id_equipe_ext = ?)";
        $bindings = array(
            array('type' => 'i', 'value' => $week_id),
            array('type' => 'i', 'value' => $team_id),
            array('type' => 'i', 'value' => $team_id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return count($results) > 0;
    }

    function move_element($array, $index_from, $index_to)
    {
        $new_array = $array;
        $out = array_splice($new_array, $index_from, 1);
        array_splice($new_array, $index_to, 0, $out);
        return $new_array;
    }

    /**
     * @param array $to_be_inserted_matches
     * @param $index_match
     * @param $index_place
     * @return void
     * @throws Exception
     */
    public function insert_matches(array $to_be_inserted_matches,
                                         $index_match,
                                         $index_place
    ): void
    {
        if (count($to_be_inserted_matches) === 0) {
            throw new Exception("Il n'y a pas de match à insérer !");
        }
        $code_competition = $to_be_inserted_matches[0]['competition']['code_competition'];
        $division = $to_be_inserted_matches[0]['division']['division'];
        $matches = $this->get_matches("m.code_competition = '$code_competition' 
                                             AND m.division = '$division' 
                                             AND m.match_status = 'NOT_CONFIRMED'");
        if (count($matches) === count($to_be_inserted_matches)) {
            return;
        }
        $this->delete_matches("code_competition = '$code_competition' 
                                             AND division = '$division' 
                                             AND match_status = 'NOT_CONFIRMED'");
        if ($index_match === count($to_be_inserted_matches)) {
            return;
        }
        if ($index_place === count($to_be_inserted_matches)) {
            $this->insert_matches($to_be_inserted_matches, $index_match + 1, 0);
            return;
        }
        $to_be_inserted_matches = $this->move_element($to_be_inserted_matches, $index_match, $index_place);
        $is_successful = true;
        foreach ($to_be_inserted_matches as $to_be_inserted_match) {
            if (!$this->insert_match($to_be_inserted_match)) {
                $is_successful = false;
                break;
            }
        }
        if (!$is_successful) {
            $this->insert_matches($to_be_inserted_matches, $index_match, $index_place + 1);
        }
    }


    /**
     * @param $team_id
     * @return array
     * @throws Exception
     */
    public function get_blacklisted_team_ids($team_id): array
    {
        // tested ok
        $sql = "SELECT * 
                FROM blacklist_teams
                WHERE id_team_1 = ?
                OR id_team_2 = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $team_id),
            array('type' => 'i', 'value' => $team_id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        $blacklisted_teams_ids = array();
        foreach ($results as $result) {
            if ($result['id_team_1'] == $team_id) {
                $blacklisted_teams_ids[] = $result['id_team_2'];
            } else {
                $blacklisted_teams_ids[] = $result['id_team_1'];
            }
        }
        return $blacklisted_teams_ids;
    }

    /**
     * @param $team_id
     * @param $date_string
     * @return bool
     * @throws Exception
     */
    public function has_match($team_id, $date_string): bool
    {
        // tested ok
        $sql = "SELECT * 
                FROM matches
                WHERE (id_equipe_dom = ? OR id_equipe_ext = ?) 
                  AND date_reception = STR_TO_DATE(?, '%d/%m/%Y')";
        $bindings = array(
            array('type' => 'i', 'value' => $team_id),
            array('type' => 'i', 'value' => $team_id),
            array('type' => 's', 'value' => $date_string)
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return count($results) > 0;
    }

    /**
     * @param $home_id
     * @param $away_id
     * @return bool
     * @throws Exception
     */
    public function is_last_match_same_home($home_id, $away_id): bool
    {
        // tested ok
        $sql = "SELECT  MAX(date_reception), 
                        id_equipe_dom, 
                        id_equipe_ext 
                FROM matches 
                WHERE (id_equipe_dom = ? AND id_equipe_ext = ?) 
                   OR (id_equipe_dom = ? AND id_equipe_ext = ?)";
        $bindings = array(
            array('type' => 'i', 'value' => $home_id),
            array('type' => 'i', 'value' => $away_id),
            array('type' => 'i', 'value' => $away_id),
            array('type' => 'i', 'value' => $home_id)
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        $count_results = count($results);
        if ($count_results !== 1) {
            throw new Exception("Impossible de trouver le dernier match entre les ids $home_id et $away_id ! La requête sql a retourné $count_results ligne(s) !");
        }
        return $results[0]['id_equipe_dom'] === $home_id;
    }

    /**
     * @throws Exception
     */
    public function certify_matchs(string $ids)
    {
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $this->certify_match($id);
        }
    }

    /**
     * @throws Exception
     */
    public function flip_matchs(string $ids)
    {
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $this->flip_match($id);
        }
    }

    /**
     * @throws Exception
     */
    public function certify_match(string $id): void
    {
        $this->is_action_allowed(__FUNCTION__, $id);
        $sql = "UPDATE matches 
                SET certif = 1
                WHERE id_match = ?";
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $id
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function add_match_player($id_match, $player_id)
    {
        $sql = "INSERT INTO match_player(id_match, id_player) 
                VALUE (?, ?) 
                ON DUPLICATE KEY UPDATE id_match = id_match, 
                                        id_player = id_player";
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $id_match
        );
        $bindings[] = array(
            'type' => 'i',
            'value' => $player_id
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $id_match
     * @param $id_player
     * @throws Exception
     */
    public function delete_match_player($id_match, $id_player)
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        $sql = "DELETE FROM match_player 
            WHERE id_match = $id_match
            AND id_player = $id_player";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function getLastResults()
    {
        $sql = file_get_contents(__DIR__ . '/../sql/get_last_results.sql');
        $results = $this->sql_manager->execute($sql);
        foreach ($results as $index => $result) {
            $code_competition = $result['code_competition'];
            switch ($code_competition) {
                case 'mo':
                case 'm':
                case 'f':
                case 'kh':
                case 'c':
                case 'po':
                case 'px':
                    $division = $result['division'];
                    $results[$index]['url'] = "championship.php?d=$division&c=$code_competition";
                    break;
                case 'kf':
                case 'cf':
                    $results[$index]['url'] = "cup.php?c=$code_competition";
                    break;
                default :
                    break;
            }
        }
        return $results;
    }

    /**
     * @throws Exception
     */
    public function getWeekMatches(): array|int|string|null
    {
        $sql = file_get_contents(__DIR__ . '/../sql/get_week_matchs.sql');
        $bindings = array();
        $results = $this->sql_manager->execute($sql, $bindings);
        foreach ($results as $index => $result) {
            $code_competition = $result['code_competition'];
            switch ($code_competition) {
                case 'mo':
                case 'm':
                case 'f':
                case 'kh':
                case 'c':
                case 'po':
                case 'px':
                    $division = $result['division'];
                    $results[$index]['url'] = "championship.php?d=$division&c=$code_competition";
                    break;
                case 'kf':
                case 'cf':
                    $results[$index]['url'] = "cup.php?c=$code_competition";
                    break;
                default :
                    break;
            }
        }
        return $results;
    }

    /**
     * @throws Exception
     */
    public function isTeamDomForMatch($id_team, $code_match)
    {
        $sql = "SELECT * FROM matches 
        WHERE id_equipe_dom=$id_team 
        AND code_match='$code_match'
        AND match_status = 'CONFIRMED'";
        $results = $this->sql_manager->execute($sql);
        return count($results) > 0;
    }

    /**
     * @throws Exception
     */
    function archiveMatch($ids)
    {
        $sql = "UPDATE matches 
            SET match_status = 'ARCHIVED',
                id_journee = NULL
            WHERE id_match IN($ids)";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    function confirmMatch($ids)
    {
        $sql = "UPDATE matches 
            SET match_status = 'CONFIRMED' 
            WHERE id_match IN($ids)";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    function unconfirmMatch($ids)
    {
        $sql = "UPDATE matches 
            SET match_status = 'NOT_CONFIRMED' 
            WHERE id_match IN($ids)";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function invalidateMatch($code_match)
    {
        $sql = "UPDATE matches SET certif = 0 WHERE code_match = '$code_match'";
        $this->sql_manager->execute($sql);
        $this->addActivity("La certification du match $code_match a ete annulee");
        return true;
    }

    /**
     * @throws Exception
     */
    public function certifyMatch($code_match)
    {
        $sql = "UPDATE matches SET certif = 1 WHERE code_match = '$code_match'";
        $this->sql_manager->execute($sql);
        $this->addActivity("Le match $code_match a ete certifie");
        return true;
    }


    /**
     * @throws Exception
     */
    public function generateMatches($ids): void
    {
        if (empty($ids)) {
            throw new Exception("Aucune compétition sélectionnée !");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            require_once __DIR__ . '/Competition.php';
            $competition_manager = new Competition();
            $competitions = $competition_manager->getCompetitions("c.id = $id");
            if (count($competitions) !== 1) {
                throw new Exception("Une seule compétition doit être trouvée !");
            }
            if ($competition_manager->isCompetitionStarted($competitions[0]['id'])) {
                throw new Exception("La compétition a déjà commencé !!!");
            }
            $competition = $competitions[0];
            $this->generate_matches_v2($competition);
        }
    }


    /**
     * @throws Exception
     */
    public function getMatchPlayers($id_match = null): int|array|string|null
    {
        $sql = "SELECT  DISTINCT j.*,
                        e.nom_equipe AS equipe,
                        m.date_reception,
                        m.id_match
                FROM matchs_view m
                         JOIN match_player mp on mp.id_match = m.id_match
                         JOIN players_view j on mp.id_player = j.id
                         LEFT JOIN joueur_equipe je ON je.id_joueur = j.id AND (je.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext))
                         LEFT JOIN equipes e ON je.id_equipe = e.id_equipe
                WHERE m.id_match = $id_match
                ORDER BY equipe, sexe, nom, prenom";
        $results = $this->sql_manager->execute($sql);
        return Players::adjust_photo_path_from_results($results);
    }

    /**
     * @throws Exception
     */
    public function getNotMatchPlayers($id_match = null): int|array|string|null
    {
        $sql = "SELECT DISTINCT j.*, e.nom_equipe AS equipe
                FROM joueur_equipe je
                         JOIN matches m ON (m.id_equipe_dom = je.id_equipe OR m.id_equipe_ext = je.id_equipe)
                         JOIN players_view j on j.id = je.id_joueur
                         JOIN equipes e ON e.id_equipe = je.id_equipe
                WHERE m.id_match = $id_match
                  AND je.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext)
                  AND je.id_joueur NOT IN (SELECT id_player FROM match_player where id_match = $id_match)";
        $results = $this->sql_manager->execute($sql);
        return Players::adjust_photo_path_from_results($results);
    }

    /**
     * @param null $id_match
     * @param null $query
     * @return int|array|string|null
     * @throws Exception
     */
    public function getReinforcementPlayers($id_match = null, $query = null): int|array|string|null
    {
        if (empty($query)) {
            throw new Exception("Merci de rechercher un joueur en commençant à taper son nom !");
        } else {
            $query = "j.full_name LIKE '%$query%'";
        }
        $sql = "SELECT DISTINCT j.*
                FROM players_view j
                WHERE $query 
                AND j.id NOT IN (SELECT id_player 
                                   FROM match_player 
                                   WHERE id_match = $id_match)
                AND j.id NOT IN (SELECT id_joueur 
                                 FROM joueur_equipe 
                                 WHERE id_equipe IN (SELECT id_equipe_dom FROM matches WHERE id_match = $id_match)
                                 OR id_equipe IN (SELECT id_equipe_ext FROM matches WHERE id_match = $id_match))";
        $results = $this->sql_manager->execute($sql);
        return Players::adjust_photo_path_from_results($results);
    }


    /**
     * @throws Exception
     */
    public function getTeamsEmailsFromMatch($code_match)
    {
        $sql = "SELECT
                    m.id_equipe_dom,
                    m.id_equipe_ext,
                    m.code_competition,
                    LEFT(m.division, 1) AS division
                FROM matches m
                WHERE m.code_match = '$code_match'
                AND m.match_status = 'CONFIRMED'";
        $results = $this->sql_manager->execute($sql);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer le match $code_match !");
        }
        $data = $results[0];
        $emailDom = $this->team->getTeamEmail($data['id_equipe_dom']);
        $emailExt = $this->team->getTeamEmail($data['id_equipe_ext']);
        // remove ctsd from mails
        return array($emailDom, $emailExt);
    }

    /**
     * @throws Exception
     */
    public function getTeamsEmailsFromMatchReport($code_match)
    {
        $sql = "SELECT
      m.id_equipe_dom,
      m.id_equipe_ext,
      m.code_competition
      FROM matches m
      WHERE m.code_match = '$code_match'
        AND m.match_status = 'CONFIRMED'";
        $results = $this->sql_manager->execute($sql);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer le match $code_match !");
        }
        $data = $results[0];
        $emailDom = $this->team->getTeamEmail($data['id_equipe_dom']);
        $emailExt = $this->team->getTeamEmail($data['id_equipe_ext']);
        $emailReport = 'report@ufolep13volley.org';
        return array($emailDom, $emailExt, $emailReport);
    }

    /**
     * @param $team_id
     * @param $match_code
     * @throws Exception
     */
    public function check_team_allowed_to_ask_report($team_id, $match_code)
    {
        $matches = $this->get_matches("m.code_match = '$match_code'");
        $this_match = $matches[0];
        $code_competition = $this_match['code_competition'];
        $rank = new Rank();
        $report_count = $rank->get_report_count($team_id, $code_competition);
        if (!$this->configuration->covid_mode) {
            if ($report_count > 0) {
                throw new Exception("Demande refusée. Votre équipe a déjà demandé un report pour cette compétition.");
            }
        }
    }

    /**
     * @param $code_match
     * @param $reason
     * @return bool
     * @throws Exception
     */
    public function askForReport($code_match, $reason)
    {
        $sessionIdEquipe = $_SESSION['id_equipe'];
        $this->check_team_allowed_to_ask_report($sessionIdEquipe, $code_match);
        if ($this->isTeamDomForMatch($sessionIdEquipe, $code_match)) {
            $sql = "UPDATE matches SET report_status = 'ASKED_BY_DOM' WHERE code_match = '$code_match'";
        } else {
            $sql = "UPDATE matches SET report_status = 'ASKED_BY_EXT' WHERE code_match = '$code_match'";
        }
        $this->sql_manager->execute($sql);
        $this->addActivity("Report demandé par " . $this->team->getTeamName($sessionIdEquipe) . " pour le match $code_match");
        (new Emails())->sendMailAskForReport($code_match, $reason, $sessionIdEquipe);
        return true;
    }

    /**
     * @param $code_match
     * @param $report_date
     * @throws Exception
     */
    public function giveReportDate($code_match, $report_date)
    {
        $match = $this->get_match_by_code_match($code_match);
        $this->is_action_allowed(__FUNCTION__, $match['id_match']);
        $report_datetime = DateTime::createFromFormat('d/m/Y', $report_date);
        if (!$report_datetime) {
            throw new Exception("Impossible de déterminer la date de report, merci de respecter le format jj/mm/aaaa (exemple: 03/01/2023 pour le 3 Janvier 2023) !");
        }
        $date_string = $report_date;
        if ($this->has_match($match['id_equipe_dom'], $date_string)) {
            throw new Exception("L'équipe " . $match['equipe_dom'] . " a déjà un match ce soir là !");
        }
        if ($this->has_match($match['id_equipe_ext'], $date_string)) {
            throw new Exception("L'équipe " . $match['equipe_ext'] . " a déjà un match ce soir là !");
        }
        $sql = "UPDATE matches 
                SET date_reception = DATE(STR_TO_DATE(?, '%d/%m/%Y')) 
                WHERE code_match = ?";
        $bindings = array();
        $bindings[] = array(
            'type' => 's',
            'value' => $date_string
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $code_match
        );
        $this->sql_manager->execute($sql, $bindings);
        $sessionIdEquipe = $_SESSION['id_equipe'];
        $this->addActivity("Date de report transmise par " . $this->team->getTeamName($sessionIdEquipe) . " pour le match $code_match");
        (new Emails())->sendMailGiveReportDate($code_match, $report_date, $sessionIdEquipe);
    }

    /**
     * @param $code_match
     * @param $reason
     * @return bool
     * @throws Exception
     */
    public function refuseReport($code_match, $reason)
    {
        if (UserManager::isTeamLeader()) {
            $sessionIdEquipe = $_SESSION['id_equipe'];
            if ($this->isTeamDomForMatch($sessionIdEquipe, $code_match)) {
                $report_status = 'REFUSED_BY_DOM';
            } else {
                $report_status = 'REFUSED_BY_EXT';
            }
            $bindings = array();
            $bindings[] = array(
                'type' => 's',
                'value' => $report_status
            );
            $bindings[] = array(
                'type' => 's',
                'value' => $code_match
            );
            $sql = "UPDATE matches SET report_status = ? WHERE code_match = ?";
            $this->sql_manager->execute($sql, $bindings);
            $this->addActivity(
                "Report refusé par " . $this->team->getTeamName($sessionIdEquipe) .
                " pour le match $code_match, raison: " . $reason);
            (new Emails())->sendMailRefuseReport($code_match, $reason, $sessionIdEquipe);
        }
        if (UserManager::isAdmin()) {
            $sql = "UPDATE matches SET report_status = 'REFUSED_BY_ADMIN' WHERE code_match = '$code_match'";
            $this->sql_manager->execute($sql);
            $this->addActivity("Report refusé par la commission" .
                " pour le match $code_match, raison: " . $reason);
            (new Emails())->sendMailRefuseReportAdmin($code_match, $reason);
        }
        return true;
    }

    /**
     * @param $code_match
     * @return bool
     * @throws Exception
     */
    public function acceptReport($code_match)
    {
        if (UserManager::isAdmin()) {
            throw new Exception("Un administrateur ne peut pas accepter un report !");
        }
        if (!UserManager::isTeamLeader()) {
            throw new Exception("Seul un responsable d'équipe peut accepter un report !");
        }
        $sessionIdEquipe = $_SESSION['id_equipe'];
        if ($this->isTeamDomForMatch($sessionIdEquipe, $code_match)) {
            $sql = "UPDATE matches SET report_status = 'ACCEPTED_BY_DOM' WHERE code_match = '$code_match'";
        } else {
            $sql = "UPDATE matches SET report_status = 'ACCEPTED_BY_EXT' WHERE code_match = '$code_match'";
        }
        $this->sql_manager->execute($sql);
        $matches = $this->get_matches("m.code_match = '$code_match'");
        $this_match = $matches[0];
        if ($sessionIdEquipe == $this_match['id_equipe_dom']) {
            $this->rank->incrementReportCount($this_match['code_competition'], $this_match['id_equipe_ext']);
        } else {
            $this->rank->incrementReportCount($this_match['code_competition'], $this_match['id_equipe_dom']);
        }
        $this->addActivity("Report accepté par " . $this->team->getTeamName($sessionIdEquipe) . " pour le match $code_match");
        (new Emails())->sendMailAcceptReport($code_match, $sessionIdEquipe);
        return true;
    }

    /**
     * @throws Exception
     */
    public function manage_match_players($id_match, $player_ids, $reinforcement_player_id = null, $dirtyFields = null): void
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        if (!isset($id_match)) {
            throw new Exception("Impossible de trouver id_match !");
        }
        if (empty($id_match)) {
            throw new Exception("id_match vide !");
        }
        $this->delete_match_players($id_match);
        if (!empty($reinforcement_player_id)) {
            $player_ids[] = $reinforcement_player_id;
        }
        if (isset($player_ids)) {
            foreach ($player_ids as $index => $player_id) {
                if (empty($player_id)) {
                    unset($player_ids[$index]);
                    continue;
                }
                $this->add_match_player($id_match, $player_id);
            }
        }
        if (count($player_ids) > 0) {
            $match = $this->get_match($id_match);
            $comment = "Les présents ont été renseignés pour le match " . $match['code_match'];
            $this->addActivity($comment);
        }
    }

    /**
     * @throws Exception
     */
    private function is_action_allowed(string $function_name, $id_match)
    {
        $match_manager = new MatchMgr();
        $match = $match_manager->get_match($id_match);
        if (!UserManager::is_connected()) {
            throw new Exception("Utilisateur non connecté !");
        }
        switch ($function_name) {
            case 'certify_match':
                if (in_array($_SESSION['profile_name'], array('ADMINISTRATEUR', 'SUPPORT'))) {
                    return;
                }
                throw new Exception("Seule la commission est autorisée à valider un match !");
            case 'giveReportDate':
                // allow admin
                if ($_SESSION['profile_name'] === 'ADMINISTRATEUR') {
                    return;
                }
                // allow only playing teams
                if (!in_array(
                    $_SESSION['id_equipe'],
                    array($match['id_equipe_dom'],
                        $match['id_equipe_ext']))) {
                    throw new Exception("Seules les équipes participant au match peuvent donner une date de report !");
                }
                // allow only RESPONSABLE_EQUIPE
                if ($_SESSION['profile_name'] !== 'RESPONSABLE_EQUIPE') {
                    throw new Exception("Seuls les responsables d'équipes peuvent donner une date de report !");
                }
                break;
            case 'manage_match_players':
            case 'add_match_player':
            case 'delete_match_player':
                // allow admin
                if ($_SESSION['profile_name'] === 'ADMINISTRATEUR') {
                    return;
                }
                // allow only playing teams
                if (!in_array(
                    $_SESSION['id_equipe'],
                    array($match['id_equipe_dom'],
                        $match['id_equipe_ext']))) {
                    throw new Exception("Seules les équipes ayant participé au match peuvent dire qui était là !");
                }
                // allow only RESPONSABLE_EQUIPE
                if ($_SESSION['profile_name'] !== 'RESPONSABLE_EQUIPE') {
                    throw new Exception("Seuls les responsables d'équipes peuvent dire qui était là !");
                }
                // allow only CONFIRMED matches
                if ($match['match_status'] !== 'CONFIRMED') {
                    throw new Exception("Il n'est pas possible de renseigner les présents pour ce match, il faut qu'il soit confirmé !");
                }
                // allow only if not yet signed by any team
                if ($match['is_sign_team_dom'] == 1 || $match['is_sign_team_ext'] == 1) {
                    throw new Exception("Déjà signé par une des équipes !");
                }
                break;
            case 'sign_team_sheet':
                // allow if ADMINISTRATEUR
                if (in_array($_SESSION['profile_name'], array('ADMINISTRATEUR'))) {
                    return;
                }
                // allow only playing teams
                if (!in_array(
                    $_SESSION['id_equipe'],
                    array($match['id_equipe_dom'],
                        $match['id_equipe_ext']))) {
                    throw new Exception("Seules les équipes participant au match peuvent signer les fiches équipes !");
                }
                // allow only RESPONSABLE_EQUIPE
                if (!in_array($_SESSION['profile_name'], array('RESPONSABLE_EQUIPE'))) {
                    throw new Exception("Seuls les responsables d'équipes peuvent signer les fiches équipes !");
                }
                // allow only CONFIRMED matches
                if ($match['match_status'] !== 'CONFIRMED') {
                    throw new Exception("Match non confirmé !");
                }
                // allow only match_player filled matches
                if ($match['is_match_player_filled'] !== 1) {
                    throw new Exception("Les présents des 2 équipes n'ont pas été renseignés !");
                }
                if (!empty($match['count_status'])) {
                    $count_status = $match['count_status'];
                    throw new Exception("Il y a un souci dans la saisie: $count_status !");
                }
                // allow only if not signed yet
                if (($_SESSION['id_equipe'] == $match['id_equipe_dom'] && $match['is_sign_team_dom'] == 1) ||
                    ($_SESSION['id_equipe'] == $match['id_equipe_ext'] && $match['is_sign_team_ext'] == 1)) {
                    throw new Exception("Signature déjà effectuée !");
                }
                break;
            case 'sign_match_sheet':
                // allow if ADMINISTRATEUR
                if (in_array($_SESSION['profile_name'], array('ADMINISTRATEUR'))) {
                    return;
                }
                // allow only playing teams
                if (!in_array(
                    $_SESSION['id_equipe'],
                    array($match['id_equipe_dom'],
                        $match['id_equipe_ext']))) {
                    throw new Exception("Seules les équipes participant au match peuvent signer la feuille de match !");
                }
                // allow only RESPONSABLE_EQUIPE
                if ($_SESSION['profile_name'] !== 'RESPONSABLE_EQUIPE') {
                    throw new Exception("Seuls les responsables d'équipes signer la feuille de match !");
                }
                // allow only CONFIRMED matches
                if ($match['match_status'] !== 'CONFIRMED') {
                    throw new Exception("Match non confirmé !");
                }
                // allow only score filled matches
                if ($match['score_equipe_dom'] == 0 && $match['score_equipe_ext'] == 0) {
                    throw new Exception("Le score n'a pas été renseigné !");
                }
                // allow only if not signed yet
                if (($_SESSION['id_equipe'] == $match['id_equipe_dom'] && $match['is_sign_match_dom'] == 1) ||
                    ($_SESSION['id_equipe'] == $match['id_equipe_ext'] && $match['is_sign_match_ext'] == 1)) {
                    throw new Exception("Signature déjà effectuée !");
                }
                break;
            default:
                break;
        }
    }

    /**
     * @param mixed $computed_date
     * @param $id_equipe
     * @return bool
     * @throws Exception
     */
    private function is_date_blacklisted_team(mixed $computed_date, $id_equipe): bool
    {
        $sql = "SELECT *
                FROM blacklist_teams bt
                WHERE (
                    id_team_1 = ? 
                    AND (
                        id_team_1 IN (  SELECT id_equipe_dom 
                                        FROM matches 
                                        WHERE date_reception = STR_TO_DATE(?, '%d/%m/%Y'))                   
                        OR 
                        id_team_1 IN (  SELECT id_equipe_ext 
                                        FROM matches 
                                        WHERE date_reception = STR_TO_DATE(?, '%d/%m/%Y'))))
                OR (
                    id_team_2 = ? 
                    AND (
                        id_team_2 IN (  SELECT id_equipe_dom 
                                        FROM matches 
                                        WHERE date_reception = STR_TO_DATE(?, '%d/%m/%Y'))                   
                        OR
                        id_team_2 IN (  SELECT id_equipe_ext 
                                        FROM matches 
                                        WHERE date_reception = STR_TO_DATE(?, '%d/%m/%Y'))))";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_equipe);
        $bindings[] = array('type' => 's', 'value' => $computed_date);
        $bindings[] = array('type' => 's', 'value' => $computed_date);
        $bindings[] = array('type' => 'i', 'value' => $id_equipe);
        $bindings[] = array('type' => 's', 'value' => $computed_date);
        $bindings[] = array('type' => 's', 'value' => $computed_date);
        return count($this->sql_manager->execute($sql, $bindings)) > 0;
    }

    /**
     * @throws Exception
     */
    public function get_match_by_code_match(string $code_match)
    {
        $results = $this->get_matches("m.code_match = '$code_match'");
        $count_results = count($results);
        if ($count_results !== 1) {
            throw new Exception("Erreur pendant la réception des données du match! Trouvé $count_results match(s) !");
        }
        return $results[0];
    }

    /**
     * @throws Exception
     */
    public function generateAll($ids = null, $do_reinit = 'on', $generate_days = 'on', $generate_matches = 'on'): void
    {
        $competition_mgr = new Competition();
        $do_reinit = $do_reinit === 'on';
        $generate_days = $generate_days === 'on';
        $generate_matches = $generate_matches === 'on';
        if (empty($ids)) {
            throw new Exception("Il faut sélectionner une ou plusieurs compétitions pour démarrer la génération !");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            if ($do_reinit) {
                // init all gymnasiums etc. from register table
                (new Register())->set_up_season($id);
                // reset competition
                $competition_mgr->resetCompetition($id);
            }
            if ($generate_days) {
                // generate days
                (new Day())->generateDays($id);
            }
            if ($generate_matches) {
                // delete previously generated matchs
                $competition = $competition_mgr->get_by_id($id);
                $code_competition = $competition['code_competition'];
                $this->delete_matches("match_status = 'NOT_CONFIRMED' AND code_competition = '$code_competition'");
                // generate matches
                $this->generate_matches_v2($competition);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function sign_team_sheet($id_match): void
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        $match = $this->get_match($id_match);
        // if admin, sign for both teams
        if (UserManager::isAdmin()) {
            $sql = "UPDATE matches set is_sign_team_dom = 1, is_sign_team_ext = 1 WHERE id_match = ?";
        } else {
            switch ($_SESSION['id_equipe']) {
                case $match['id_equipe_dom']:
                    $sql = "UPDATE matches set is_sign_team_dom = 1 WHERE id_match = ?";
                    break;
                case $match['id_equipe_ext']:
                    $sql = "UPDATE matches set is_sign_team_ext = 1 WHERE id_match = ?";
                    break;
                default:
                    throw new Exception("Equipe non concernée par ce match !");
            }
        }
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_match);
        $this->sql_manager->execute($sql, $bindings);
        $match = $this->get_match($id_match);
        if ($match['is_sign_team_dom'] + $match['is_sign_team_ext'] == 1) {
            (new Emails())->team_sheet_to_be_signed($match['code_match']);
        } elseif ($match['is_sign_team_dom'] + $match['is_sign_team_ext'] == 2) {
            (new Emails())->team_sheet_signed($match['code_match']);
        }
        throw new Exception("Signature prise en compte", 200);
    }

    /**
     * @throws Exception
     */
    public function sign_match_sheet($id_match): void
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        $match = $this->get_match($id_match);
        // if admin, sign for both teams
        if (UserManager::isAdmin()) {
            $sql = "UPDATE matches set is_sign_match_dom = 1, is_sign_match_ext = 1 WHERE id_match = ?";
        } else {
            switch ($_SESSION['id_equipe']) {
                case $match['id_equipe_dom']:
                    $sql = "UPDATE matches set is_sign_match_dom = 1 WHERE id_match = ?";
                    break;
                case $match['id_equipe_ext']:
                    $sql = "UPDATE matches set is_sign_match_ext = 1 WHERE id_match = ?";
                    break;
                default:
                    throw new Exception("Equipe non concernée par ce match !");
            }
        }
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_match);
        $this->sql_manager->execute($sql, $bindings);
        $match = $this->get_match($id_match);
        if ($match['is_sign_match_dom'] + $match['is_sign_match_ext'] == 1) {
            (new Emails())->match_sheet_to_be_signed($match['code_match']);
        } elseif ($match['is_sign_match_dom'] + $match['is_sign_match_ext'] == 2) {
            (new Emails())->match_sheet_signed($match['code_match']);
        }
        throw new Exception("Signature prise en compte", 200);
    }

    /**
     * @throws Exception
     */
    private function is_last_match_recent(mixed $home_id, mixed $away_id): bool
    {
        // tested ok
        $sql = "SELECT a.*
                FROM (SELECT MAX(date_reception) AS max_date_reception,
                             id_equipe_dom,
                             id_equipe_ext
                      FROM matches
                      WHERE ((id_equipe_dom = ? AND id_equipe_ext = ?)
                          OR (id_equipe_dom = ? AND id_equipe_ext = ?))
                        AND match_status IN ('CONFIRMED', 'ARCHIVED')) a
                WHERE a.max_date_reception IS NOT NULL
                  AND a.max_date_reception > DATE_SUB(NOW(), INTERVAL 9 MONTH)";
        $bindings = array(
            array('type' => 'i', 'value' => $home_id),
            array('type' => 'i', 'value' => $away_id),
            array('type' => 'i', 'value' => $away_id),
            array('type' => 'i', 'value' => $home_id)
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return count($results) === 1;
    }

    /**
     * @throws Exception
     */
    public function adjust_home_away(array $competition)
    {
        // get candidates for flip
        $matches_home_away_adjust_needed = $this->get_matches_home_away_adjust_needed($competition['code_competition']);
        $offset = 0;
        while (count($matches_home_away_adjust_needed) > 0 && $offset < count($matches_home_away_adjust_needed)) {
            $match = $matches_home_away_adjust_needed[$offset];
            // if match flip is allowed, flip it
            if (!$this->is_last_match_recent($match['id_equipe_ext'], $match['id_equipe_dom'])) {
                $this->flip_match($match['id_match']);
                $matches_home_away_adjust_needed = $this->get_matches_home_away_adjust_needed($competition['code_competition']);
            }
            $offset++;
        }
    }

    /**
     * @throws Exception
     */
    private function get_matches_home_away_adjust_needed(mixed $code_competition): array|int|string|null
    {
        $sql = "SELECT mv.*
                FROM matchs_view mv
                         JOIN (SELECT SUM(IF(m.id_equipe_dom = e.id_equipe, 1, 0)) AS domicile,
                                      SUM(IF(m.id_equipe_ext = e.id_equipe, 1, 0)) AS exterieur,
                                      c.code_competition                           AS competition,
                                      c.division                                   AS division,
                                      e.id_equipe                                  AS id_equipe
                               FROM matches m
                                        JOIN equipes e on m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe
                                        JOIN classements c on e.id_equipe = c.id_equipe
                               WHERE m.match_status IN ('NOT_CONFIRMED')
                                 AND m.code_competition = ?
                               GROUP BY c.code_competition, c.division, e.nom_equipe
                               HAVING ABS(domicile - exterieur) > 2
                               ORDER BY competition, division) adjust
                WHERE mv.code_competition = ?
                  AND mv.id_equipe_dom = adjust.id_equipe
                AND mv.match_status IN ('NOT_CONFIRMED')";
        $bindings = array(
            array('type' => 's', 'value' => $code_competition),
            array('type' => 's', 'value' => $code_competition),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    private function flip_match($id_match)
    {
        $match = $this->get_match($id_match);
        $update_match = array(
            // mandatory for any update
            'id_match' => $match['id_match'],
            'code_match' => $match['code_match'],
            // flip reception
            'id_equipe_dom' => $match['id_equipe_ext'],
            'id_equipe_ext' => $match['id_equipe_dom'],
        );
        require_once 'TimeSlot.php';
        $tsm = new TimeSlot();
        $timeslots = $tsm->get("c.id_equipe = " . $match['id_equipe_ext']);
        if (count($timeslots) >= 1) {
            // compute reception date and gymnasium
            $update_match['id_gymnasium'] = $timeslots[0]['id_gymnase'];
            $update_match['date_reception'] = $this->get_date($timeslots[0]['id'], $match['id_journee']);
            $this->save($update_match);
        }
    }

    /**
     * @throws Exception
     */
    private function get_date(mixed $id_timeslot, $id_journee)
    {
        $sql = "SELECT DATE_FORMAT(j.start_date + INTERVAL FIELD(c.jour,
                                                 'Lundi',
                                                 'Mardi',
                                                 'Mercredi',
                                                 'Jeudi',
                                                 'Vendredi',
                                                 'Samedi',
                                                 'Dimanche') - 1 DAY, '%d/%m/%Y') AS computed_date
                FROM journees j, creneau c
                WHERE j.id = ?
                AND c.id = ?
                ORDER BY c.usage_priority";
        $bindings = array(
            array('type' => 'i', 'value' => $id_journee),
            array('type' => 'i', 'value' => $id_timeslot),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (count($results) == 0) {
            throw new Exception("Impossible de trouver une date pour ce créneau et cette journée !");
        }
        return $results[0]['computed_date'];
    }

    /**
     * @throws Exception
     */
    public function draw_matches($code_competition, $division, $id_journee): void
    {
        $comp_mgr = new Competition();
        $day_mgr = new Day();
        $competition = $comp_mgr->getCompetition($code_competition);
        $day = $day_mgr->get_by_id($id_journee);
        if ($day['numero'] == 1) {
            $teams = $this->rank->getTeamsFromDivisionAndCompetition($division, $code_competition);
        } else {
            $previous_day = $day_mgr->get_one(
                "j.code_competition = ? AND j.numero = ?",
                array(
                    array('type' => 's', 'value' => $code_competition),
                    array('type' => 'i', 'value' => $day['numero'] - 1),
                ));
            $teams = $this->rank->get_winner_teams_from_previous_day($division, $code_competition, $previous_day['nommage']);
        }
        if (count($teams) % 2 != 0) {
            throw new Exception("Impossible de tirer au sort les matchs, il faut un nombre pair d'équipes !");
        }
        shuffle($teams);
        $match_number = 1;
        while (count($teams) > 0) {
            $team_dom = array_pop($teams);
            $team_ext = array_pop($teams);
            $year_month = date('ym');
            $code_match =
                strtoupper($competition['code_competition']) .
                $year_month .
                $division .
                $day['numero'] .
                $match_number;
            $this->insert_db_match($code_competition,
                $division,
                $team_dom['id_equipe'],
                $team_ext['id_equipe'],
                $code_match,
                $id_journee,
                '',
                0,
            );
            $match_number++;
        }
    }

    /**
     * @throws Exception
     */
    public function get_survey($id_match = null)
    {
        if (empty($id_match)) {
            return $this->survey->get();
        }
        $userDetails = $this->getCurrentUserDetails();
        $id_user = $userDetails['id_user'];
        $results = $this->survey->get("s.id_match = $id_match AND s.user_id = $id_user");
        $count_results = count($results);
        if ($count_results === 0) {
            return array(
                'id' => null,
                'user_id' => $id_user,
                'id_match' => $id_match,
                'on_time' => 0,
                'spirit' => 0,
                'referee' => 0,
                'catering' => 0,
                'global' => 0,
                'comment' => null,
            );
        }
        if ($count_results > 1) {
            throw new Exception("Erreur lors de la récupération des données du sondage ! Trouvé $count_results sondage(s) !");
        }
        return $results[0];
    }

    /**
     * @throws Exception
     */
    public function save_survey($id_match,
                                $on_time,
                                $spirit,
                                $referee,
                                $catering,
                                $global,
                                $comment = null,
                                $dirtyFields = null,
                                $id = null): int|array|string|null
    {
        $userDetails = $this->getCurrentUserDetails();
        $id_user = $userDetails['id_user'];
        $inputs = array(
            'dirtyFields' => $dirtyFields,
            'id' => $id,
            'user_id' => $id_user,
            'id_match' => $id_match,
            'on_time' => $on_time,
            'spirit' => $spirit,
            'referee' => $referee,
            'catering' => $catering,
            'global' => $global,
            'comment' => $comment,
        );
        return $this->survey->save($inputs);
    }

    /**
     * @throws Exception
     */
    public function has_timeslot($id_equipe): bool
    {
        $sql = "SELECT id 
                FROM creneau 
                WHERE id_equipe = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id_equipe),
        );
        return count($this->sql_manager->execute($sql, $bindings)) > 0;

    }

    /**
     * @param array $competition
     * @param array|null $division
     * @param string|null $message
     * @return array
     * @throws Exception
     */
    public function get_expected_matches(array $competition, array $division = null, string &$message = null): array
    {
        $is_mirror_needed = ($competition['is_home_and_away'] === 1);
        if (is_null($division)) {
            $divisions = $this->rank->getDivisionsFromCompetition($competition['code_competition']);
        } else {
            $divisions[] = $division;
        }
        $all_expected_matches = array();
        foreach ($divisions as $division) {
            $message .= "Division : " . $division['division'] . PHP_EOL;
            $teams = $this->rank->getTeamsFromDivisionAndCompetition(
                $division['division'],
                $competition['code_competition']);
            $teams_count = count($teams);
            $message .= "Nombre d'équipes : " . $teams_count . PHP_EOL;
            if ($teams_count % 2 == 1) {
                $teams_count++;
            }
            // Generate the fixtures using the round-robin cyclic algorithm.
            $totalRounds = $teams_count - 1;
            $matchesPerRound = $teams_count / 2;
            if ($is_mirror_needed) {
                $message .= "Nombre de journées : " . (2 * $totalRounds) . PHP_EOL;
            } else {
                $message .= "Nombre de journées : " . $totalRounds . PHP_EOL;
            }
            $message .= "Nombre de matches par journée : " . $matchesPerRound . PHP_EOL;
            $rounds = $this->generate_round_robin_rounds($teams_count);
            // Last team can't be away for every game so flip them
            // to home on odd rounds.
            for ($round = 0; $round < sizeof($rounds); $round++) {
                if ($round % 2 == 1) {
                    $rounds[$round][0] = $this->flip($rounds[$round][0]);
                }
            }
            // si matchs aller/retour, on prend le tableau et on inverse chaque élément
            if ($is_mirror_needed) {
                $mirror_rounds = array();
                foreach ($rounds as $round) {
                    $mirror_matchs = array();
                    foreach ($round as $match) {
                        $mirror_matchs[] = $this->flip($match);
                    }
                    $mirror_rounds[] = $mirror_matchs;
                }
                foreach ($mirror_rounds as $mirror_round) {
                    $rounds[] = $mirror_round;
                }
            }
            $to_be_inserted_matches = array();
            foreach ($rounds as $round) {
                foreach ($round as $match) {
                    $index_teams = explode('v', $match);
                    if (empty($teams[intval($index_teams[0])]) || empty($teams[intval($index_teams[1])])) {
                        continue;
                    }
                    // on récupère les équipes correspondantes selon leur position de départ dans la division
                    $team_dom = $teams[intval($index_teams[0])];
                    $team_ext = $teams[intval($index_teams[1])];
                    $dom = $team_dom;
                    $ext = $team_ext;
                    // on remplit le tableau avec les matchs à insérer
                    // s'il y a déjà eu une rencontre dom vs ext la dernière fois, et que ça date de moins de 9 mois
                    $note = '';
                    if ($this->is_last_match_same_home($team_dom['id_equipe'], $team_ext['id_equipe'])
                        && $this->is_last_match_recent($team_dom['id_equipe'], $team_ext['id_equipe'])
                        && $this->has_timeslot($team_ext['id_equipe'])) {
                        // inverser la réception
                        $dom = $team_ext;
                        $ext = $team_dom;
                        $note = "inversion de réception nécessaire";
                    }
                    $to_be_inserted_matches[] = array(
                        'dom' => $dom,
                        'ext' => $ext,
                        'competition' => $competition,
                        'division' => $division,
                        'note' => $note
                    );
                }
            }
            $message .= "Nombre de matchs à créer : " . count($to_be_inserted_matches) . PHP_EOL;
            $all_expected_matches[] = $to_be_inserted_matches;
        }
        return array_merge(...$all_expected_matches);
    }

    /**
     * @throws Exception
     */
    public function delete_match_players($id_match): void
    {
        $sql = "DELETE FROM match_player 
            WHERE id_match = $id_match";
        $this->sql_manager->execute($sql);

    }
}
