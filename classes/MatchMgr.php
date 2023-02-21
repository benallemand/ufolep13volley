<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/SqlManager.php';
require_once __DIR__ . '/Team.php';
require_once __DIR__ . '/Players.php';
require_once __DIR__ . '/Rank.php';
require_once __DIR__ . '/Register.php';
require_once __DIR__ . '/Competition.php';
require_once __DIR__ . '/Day.php';
require_once __DIR__ . '/UserManager.php';

class MatchMgr extends Generic
{
    private Team $team;
    private Rank $rank;

    /**
     * Match constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->team = new Team();
        $this->rank = new Rank();
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
        return "SELECT m.* FROM matchs_view m WHERE $query ORDER BY $order";
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
            throw new Exception("Error while retrieving match data ! Found $count_results match(s) !");
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
            throw new Exception("No ID specified\n");
        }
        if (!$this->is_download_allowed($id)) {
            throw new Exception("User not allowed to download !");
        }
        $match = $this->get_match($id);
        $match_files = $this->get_match_files($id);
        $archiveFileName = $match['code_match'] . ".zip";
        $zip = new ZipArchive();
        if ($zip->open($archiveFileName, ZIPARCHIVE::CREATE) !== TRUE) {
            throw new Exception("Error during zip file creation !");
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
                    throw new Exception("Team not allowed to download this match !");
                }
                return true;
            default:
                throw new Exception("User role not allowed to download !");
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
                               $score_equipe_dom,
                               $set_1_dom,
                               $set_2_dom,
                               $set_3_dom,
                               $set_4_dom,
                               $set_5_dom,
                               $score_equipe_ext,
                               $set_1_ext,
                               $set_2_ext,
                               $set_3_ext,
                               $set_4_ext,
                               $set_5_ext,
                               $note,
                               $forfait_dom = false,
                               $forfait_ext = false,
                               $dirtyFields = null)
    {
        return $this->save(array(
            'dirtyFields' => $dirtyFields,
            'id_match' => $id_match,
            'code_match' => $code_match,
            'forfait_dom' => $forfait_dom,
            'score_equipe_dom' => $score_equipe_dom,
            'set_1_dom' => $set_1_dom,
            'set_2_dom' => $set_2_dom,
            'set_3_dom' => $set_3_dom,
            'set_4_dom' => $set_4_dom,
            'set_5_dom' => $set_5_dom,
            'forfait_ext' => $forfait_ext,
            'score_equipe_ext' => $score_equipe_ext,
            'set_1_ext' => $set_1_ext,
            'set_2_ext' => $set_2_ext,
            'set_3_ext' => $set_3_ext,
            'set_4_ext' => $set_4_ext,
            'set_5_ext' => $set_5_ext,
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
     * @param $sheet_received
     * @param $certif
     * @param $note
     * @param null $dirtyFields
     * @param null $id_match
     * @return array|int|string|null
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
        $sheet_received,
        $certif,
        $note,
        $dirtyFields = null,
        $id_match = null
    ): array|int|string|null
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
            'sheet_received' => $sheet_received,
            'certif' => $certif,
            'note' => $note,
            'dirtyFields' => $dirtyFields,
            'id_match' => $id_match,
        );
        return $this->save($inputs);
    }

    /**
     * @throws Exception
     */
    public function save($inputs)
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
                case 'score_equipe_dom':
                case 'score_equipe_ext':
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 'i', 'value' => $value);
                    break;
                case 'date_reception':
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    $bindings[] = array('type' => 's', 'value' => $value);
                    break;
                case 'certif':
                case 'sheet_received':
                case 'forfait_dom':
                case 'forfait_ext':
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
        $this->save_match_files($inputs);
        $code_match = $inputs['code_match'];
        $this->addActivity("Le match $code_match a ete modifie");
    }

    /**
     * @param $match
     * @throws Exception
     */
    private function save_match_files($match)
    {
        $uploaddir = '../match_files/';
        $code_match = $match['code_match'];
        $file_iteration = array('file1', 'file2', 'file3', 'file4');
        $mark_sheet_received = false;
        foreach ($file_iteration as $current_file_iteration) {
            if (empty($_FILES[$current_file_iteration]['name'])) {
                continue;
            }
            $mark_sheet_received = true;
            $iteration = 1;
            $extension = pathinfo($_FILES[$current_file_iteration]['name'], PATHINFO_EXTENSION);
            $uploadfile = "$uploaddir$code_match$current_file_iteration$iteration.$extension";
            while (file_exists($uploadfile)) {
                $iteration++;
                $uploadfile = "$uploaddir$code_match$current_file_iteration$iteration.$extension";
            }
            $file_hash = md5_file($_FILES[$current_file_iteration]['tmp_name']);
            $id_file = $this->insert_file(substr($uploadfile, 3), $file_hash);
            $id_match = $match['id_match'];
            $this->link_match_to_file($id_match, $id_file);
            if (move_uploaded_file($_FILES[$current_file_iteration]['tmp_name'], Generic::accentedToNonAccented($uploadfile))) {
                $this->addActivity("Un nouveau fichier a ete transmis pour le match $code_match.");
            }
        }
        if ($mark_sheet_received) {
            $this->declare_sheet_received($code_match);
            (new Emails())->sendMailSheetReceived($code_match);
        }
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
     * @param $code_match
     * @throws Exception
     */
    public function declare_sheet_received($code_match)
    {
        $sql = "UPDATE matches SET sheet_received = 1 WHERE code_match = ?";
        $bindings = array(
            array('type' => 's', 'value' => $code_match),
        );
        $this->sql_manager->execute($sql, $bindings);
        $this->addActivity("La feuille du match $code_match a ete reçue");
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
     * @param $competition
     * @param bool $try_flip
     * @param bool $forbid_same_home
     * @throws Exception
     */
    public function generate_matches($competition, bool $try_flip = false, bool $forbid_same_home = false)
    {
        if (empty($competition)) {
            throw new Exception("Compétition non trouvée !");
        }
        // aller/retour pour le championnat 4x4 mixte
        $code_competition = $competition['code_competition'];
        $is_mirror_needed = ($competition['is_home_and_away'] === 1);
        // supprimer les matchs générés et non confirmés pour la compétition (on préserve les matchs archivés)
        $this->delete_matches("code_competition = '$code_competition' AND match_status = 'NOT_CONFIRMED'");
        require_once __DIR__ . '/Rank.php';
        $rank_manager = new Rank();
        $divisions = $rank_manager->getDivisionsFromCompetition($competition['code_competition']);
        $message = "Nombre de divisions : " . count($divisions) . PHP_EOL;
        $count_to_be_inserted_matches = 0;
        foreach ($divisions as $division) {
            $message .= "Division : " . $division['division'] . PHP_EOL;
            $teams = $rank_manager->getTeamsFromDivisionAndCompetition(
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
                    // si l'option de regarder le dernier match est active
                    if ($forbid_same_home) {
                        // si il y a déjà eu une rencontre dom vs ext la dernière fois
                        if ($this->is_last_match_same_home($team_dom['id_equipe'], $team_ext['id_equipe'])) {
                            // si le dernier match est assez récent
                            if ($this->is_last_match_recent($team_dom['id_equipe'], $team_ext['id_equipe'])) {
                                // inverser la réception
                                $dom = $team_ext;
                                $ext = $team_dom;
                            }
                        }
                    }
                    $to_be_inserted_matches[] = array(
                        'dom' => $dom,
                        'ext' => $ext,
                        'competition' => $competition,
                        'division' => $division
                    );
                }
            }
            $message .= "Nombre de matchs à créer : " . count($to_be_inserted_matches) . PHP_EOL;
            $count_to_be_inserted_matches += count($to_be_inserted_matches);
            error_log($division['division']);
            $this->insert_matches($to_be_inserted_matches, $code_competition, $division['division'], 0, 0, $try_flip);
        }
        $count_inserted_matches = count($this->get_matches("m.code_competition = '$code_competition' AND m.match_status = 'NOT_CONFIRMED'"));
        $message .= "Nombre de matchs à créer : $count_to_be_inserted_matches" . PHP_EOL;
        $message .= "Nombre de matchs créés : $count_inserted_matches" . PHP_EOL;
        error_log($message);
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
     * @param string $code_match
     * @param string $code_competition
     * @param $division
     * @param int $id_equipe_dom
     * @param int $id_equipe_ext
     * @param int $id_journee
     * @param string $date_match , format '%d/%m/%Y'
     * @param int $id_gymnase
     * @param string|null $note
     * @return int|string
     * @throws Exception
     */
    private function insert_db_match(string $code_match,
                                     string $code_competition,
                                            $division,
                                     int    $id_equipe_dom,
                                     int    $id_equipe_ext,
                                     int    $id_journee,
                                     string $date_match,
                                     int    $id_gymnase,
                                     string $note = null)
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
        return $this->sql_manager->execute($sql, $bindings);
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
        $sql = "SELECT id_gymnasium, date_reception, COUNT(*)
                FROM matches m
                         JOIN gymnase g on m.id_gymnasium = g.id
                WHERE id_gymnasium = ?
                  AND m.date_reception = STR_TO_DATE(?, '%d/%m/%Y')
                  AND m.match_status != 'ARCHIVED'
                GROUP BY id_gymnasium, g.nb_terrain
                HAVING COUNT(*) >= g.nb_terrain";
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
    public function delete_matches(string $query = "1=1")
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

//    TODO dead code ?
//    /**
//     * @param $computed_date
//     * @param $team_id
//     * @return bool
//     * @throws Exception
//     */
//    private function is_team_blacklisted($computed_date, $team_id): bool
//    {
//        $sql = "SELECT *
//                FROM blacklist_team
//                WHERE closed_date = STR_TO_DATE(?, '%d/%m/%Y')
//                AND id_team = ?";
//        $bindings = array(
//            array('type' => 's', 'value' => $computed_date),
//            array('type' => 'i', 'value' => $team_id),
//        );
//        $results = $this->sql_manager->execute($sql, $bindings);
//        return (count($results) > 0);
//    }

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
     * @param bool $try_flip
     * @return bool
     * @throws Exception
     */
    private function insert_match($to_be_inserted_match, bool $try_flip = false): bool
    {
        $team_dom = $to_be_inserted_match['dom'];
        $team_ext = $to_be_inserted_match['ext'];
        $competition = $to_be_inserted_match['competition'];
        $division = $to_be_inserted_match['division'];
        $code_competition = $competition['code_competition'];
        $is_date_found = false;
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
            $is_date_found = true;
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
                $code_match,
                $competition['code_competition'],
                $division['division'],
                $team_dom['id_equipe'],
                $team_ext['id_equipe'],
                $computed_date['week_id'],
                $found_date,
                $computed_date['id_gymnase']);
            break;
        }
        if (!$is_date_found) {
            if ($try_flip) {
                return $this->insert_match(array(
                    'dom' => $to_be_inserted_match['ext'],
                    'ext' => $to_be_inserted_match['dom'],
                    'competition' => $to_be_inserted_match['competition'],
                    'division' => $to_be_inserted_match['division']
                ));
            }
        }
        return $is_date_found;
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
     * @param $code_competition
     * @param $division
     * @param $index_match
     * @param $index_place
     * @param $try_flip
     * @return void
     * @throws Exception
     */
    public function insert_matches(array $to_be_inserted_matches,
                                         $code_competition,
                                         $division,
                                         $index_match,
                                         $index_place,
                                         $try_flip): void
    {
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
            $this->insert_matches($to_be_inserted_matches, $code_competition, $division, $index_match + 1, 0, $try_flip);
            return;
        }
        $to_be_inserted_matches = $this->move_element($to_be_inserted_matches, $index_match, $index_place);
        $is_successful = true;
        foreach ($to_be_inserted_matches as $to_be_inserted_match) {
            if (!$this->insert_match($to_be_inserted_match, $try_flip)) {
                $is_successful = false;
                break;
            }
        }
        if (!$is_successful) {
            $this->insert_matches($to_be_inserted_matches, $code_competition, $division, $index_match, $index_place + 1, $try_flip);
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
            throw new Exception("Unable to find last match between ids $home_id and $away_id ! sql returned $count_results line(s) !");
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
    public function certify_match(string $id)
    {
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
        $sql = "SELECT DISTINCT 
    c.libelle AS competition, 
    IF(c.code_competition='f' OR c.code_competition='m' OR c.code_competition='mo', CONCAT('Division ', m.division, ' - ', j.nommage), CONCAT('Poule ', m.division, ' - ', j.nommage)) AS division_journee, 
    c.code_competition AS code_competition,
    m.division AS division,
    e1.id_equipe AS id_dom,
    e1.nom_equipe AS equipe_domicile,
    m.score_equipe_dom+0 AS score_equipe_dom, 
    m.score_equipe_ext+0 AS score_equipe_ext, 
    e2.id_equipe AS id_ext,
    e2.nom_equipe AS equipe_exterieur, 
    CONCAT(m.set_1_dom, '-', set_1_ext) AS set1, 
    CONCAT(m.set_2_dom, '-', set_2_ext) AS set2, 
    CONCAT(m.set_3_dom, '-', set_3_ext) AS set3, 
    CONCAT(m.set_4_dom, '-', set_4_ext) AS set4, 
    CONCAT(m.set_5_dom, '-', set_5_ext) AS set5, 
    m.date_reception
    FROM matches m
    LEFT JOIN activity a_modif ON (a_modif.comment LIKE 'Le match % a ete modifie' AND SPLIT_STRING(a_modif.comment, ' ', 3) = m.code_match)
    LEFT JOIN activity a_sheet_received ON (a_sheet_received.comment LIKE 'La feuille du match % a ete reçue' AND SPLIT_STRING(a_sheet_received.comment, ' ', 5) = m.code_match)
    JOIN journees j ON j.id=m.id_journee
    JOIN competitions c ON c.code_competition =  m.code_competition
    JOIN equipes e1 ON e1.id_equipe =  m.id_equipe_dom
    JOIN equipes e2 ON e2.id_equipe =  m.id_equipe_ext
    WHERE (
    (m.score_equipe_dom!=0 OR m.score_equipe_ext!=0)
    AND m.match_status = 'CONFIRMED'
    AND (m.date_reception <= CURDATE())
    AND (m.date_reception >= DATE_ADD(CURDATE(), INTERVAL -10 DAY) )
    AND (a_modif.activity_date >= m.date_reception OR a_sheet_received.activity_date >= m.date_reception)
    )
    ORDER BY c.libelle , m.division , j.nommage , m.date_reception DESC";
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
                    $results[$index]['rang_dom'] = $this->rank->getTeamRank(
                        $result['code_competition'],
                        $result['division'],
                        $result['id_dom']);
                    $results[$index]['rang_ext'] = $this->rank->getTeamRank(
                        $result['code_competition'],
                        $result['division'],
                        $result['id_ext']);
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
    public function getWeekMatches($date_string = null)
    {
        if (empty($date_string)) {
            $date_string = date('d/m/Y');
        }
        $sql = "SELECT DISTINCT c.libelle                                                                                             AS competition,
                IF(c.code_competition = 'f' OR c.code_competition = 'm' OR c.code_competition = 'mo',
                   CONCAT('Division ', m.division, ' - ', j.nommage),
                   CONCAT('Poule ', m.division, ' - ', j.nommage))                                                    AS division_journee,
                c.code_competition                                                                                    AS code_competition,
                m.division                                                                                            AS division,
                e1.id_equipe                                                                                          AS id_dom,
                e1.nom_equipe                                                                                         AS equipe_domicile,
                m.score_equipe_dom + 0                                                                                AS score_equipe_dom,
                m.score_equipe_ext + 0                                                                                AS score_equipe_ext,
                e2.id_equipe                                                                                          AS id_ext,
                e2.nom_equipe                                                                                         AS equipe_exterieur,
                CONCAT(m.set_1_dom, '-', set_1_ext)                                                                   AS set1,
                CONCAT(m.set_2_dom, '-', set_2_ext)                                                                   AS set2,
                CONCAT(m.set_3_dom, '-', set_3_ext)                                                                   AS set3,
                CONCAT(m.set_4_dom, '-', set_4_ext)                                                                   AS set4,
                CONCAT(m.set_5_dom, '-', set_5_ext)                                                                   AS set5,
                m.date_reception
FROM matches m
         LEFT JOIN activity a_modif ON (a_modif.comment LIKE 'Le match % a ete modifie' AND
                                        SPLIT_STRING(a_modif.comment, ' ', 3) = m.code_match)
         LEFT JOIN activity a_sheet_received ON (a_sheet_received.comment LIKE 'La feuille du match % a ete reçue' AND
                                                 SPLIT_STRING(a_sheet_received.comment, ' ', 5) = m.code_match)
         JOIN journees j ON j.id = m.id_journee
         JOIN competitions c ON c.code_competition = m.code_competition
         JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
         JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
WHERE WEEK(m.date_reception) = WEEK(STR_TO_DATE('$date_string', '%d/%m/%Y'))
AND m.match_status = 'CONFIRMED'
ORDER BY c.libelle , m.division , j.nommage , m.date_reception DESC";
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
                    $results[$index]['rang_dom'] = $this->rank->getTeamRank(
                        $result['code_competition'], $result['division'], $result['id_dom']);
                    $results[$index]['rang_ext'] = $this->rank->getTeamRank(
                        $result['code_competition'], $result['division'], $result['id_ext']);
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
    public function generateMatches($ids)
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
            $this->generate_matches($competition);
        }
    }


    /**
     * @throws Exception
     */
    public function getMatchPlayers($id_match = null)
    {
        $match = $this->get_match($id_match);
        $results = (new Players())->get_players(
            "j.id IN (SELECT id_player FROM match_player WHERE id_match = $id_match)",
            "club, sexe, nom, prenom");
        foreach ($results as $index => $result) {
            $results[$index]['date_reception'] = $match['date_reception'];
            $results[$index]['id_match'] = $match['id_match'];
        }
        return $results;
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
        $emailReport = '';
        switch ($data['code_competition']) {
            case 'm':
                $emailReport = 'report-6x6-mmx@ufolep13volley.org';
                break;
            case 'f':
                $emailReport = 'report-4x4-fem@ufolep13volley.org';
                break;
            case 'mo':
                $emailReport = 'report-4x4-mxt@ufolep13volley.org';
                break;
            case 'kh':
                $emailReport = 'report-4x4-ckh@ufolep13volley.org';
                break;
        }
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
        require_once __DIR__ . '/../classes/Configuration.php';
        if (!Configuration::COVID_MODE) {
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
        $report_datetime = DateTime::createFromFormat('Y-m-d', $report_date);
        if (!$report_datetime) {
            throw new Exception("Impossible de déterminer la date de report, merci de respecter le format jj/mm/aaaa (exemple: 03/01/2023 pour le 3 Janvier 2023) !");
        }
        $date_string = $report_datetime->format('d/m/Y');
        if ($this->has_match($match['id_equipe_dom'], $date_string)) {
            throw new Exception("L'équipe " . $match['equipe_dom'] . " a déjà un match ce soir là !");
        }
        if ($this->has_match($match['id_equipe_ext'], $date_string)) {
            throw new Exception("L'équipe " . $match['equipe_ext'] . " a déjà un match ce soir là !");
        }
        $sql = "UPDATE matches 
                SET date_reception = DATE(STR_TO_DATE(?, '%Y-%m-%d')) 
                WHERE code_match = ?";
        $bindings = array();
        $bindings[] = array(
            'type' => 's',
            'value' => $report_date
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
            (new Emails())->sendMailRefuseReportAdmin($code_match);
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
    public function manage_match_players($id_match, $player_ids, $dirtyFields = null): void
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        if (!isset($id_match)) {
            throw new Exception("Cannot find id_match !");
        }
        if (!isset($player_ids)) {
            throw new Exception("Cannot find player_ids !");
        }
        if (empty($id_match)) {
            throw new Exception("id_match is empty !");
        }
        foreach ($player_ids as $index => $player_id) {
            if (empty($player_id)) {
                unset($player_ids[$index]);
                continue;
            }
            $this->add_match_player($id_match, $player_id);
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
                //TODO à décommenter quand ça sera implémenté complètement
//                if ($match['is_sign_team_dom'] == 1 || $match['is_sign_team_ext'] == 1) {
//                    throw new Exception("Déjà signé par une des équipes !");
//                }
                break;
            case 'sign_team_sheet':
                // allow only playing teams
                if (!in_array(
                    $_SESSION['id_equipe'],
                    array($match['id_equipe_dom'],
                        $match['id_equipe_ext']))) {
                    throw new Exception("Seules les équipes participant au match peuvent signer les fiches équipes !");
                }
                // allow only RESPONSABLE_EQUIPE
                if ($_SESSION['profile_name'] !== 'RESPONSABLE_EQUIPE') {
                    throw new Exception("Seuls les responsables d'équipes signer les fiches équipes !");
                }
                // allow only CONFIRMED matches
                if ($match['match_status'] !== 'CONFIRMED') {
                    throw new Exception("Match non confirmé !");
                }
                // allow only match_player filled matches
                if ($match['is_match_player_filled'] !== 1) {
                    throw new Exception("Les présents des 2 équipes n'ont pas été renseignés !");
                }
                break;
            case 'sign_match_sheet':
                // allow only playing teams
                if (!in_array(
                    $_SESSION['id_equipe'],
                    array($match['id_equipe_dom'],
                        $match['id_equipe_ext']))) {
                    throw new Exception("Seules les équipes participant au match peuvent signer la feuille de match !");
                }
                // allow only RESPONSABLE_EQUIPE
                if ($_SESSION['profile_name'] !== 'RESPONSABLE_EQUIPE') {
                    throw new Exception("Seuls les responsables d'équipes signer lla feuille de match !");
                }
                // allow only CONFIRMED matches
                if ($match['match_status'] !== 'CONFIRMED') {
                    throw new Exception("Match non confirmé !");
                }
                // allow only match_player filled matches
                if ($match['is_match_player_filled'] !== 1) {
                    throw new Exception("Les présents des 2 équipes n'ont pas été renseignés !");
                }
                // allow only score filled matches
                if ($match['score_equipe_dom'] == 0 && $match['score_equipe_ext'] == 0) {
                    throw new Exception("Le score n'a pas été renseigné !");
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
            throw new Exception("Error while retrieving match data ! Found $count_results match(s) !");
        }
        return $results[0];
    }

    /**
     * @throws Exception
     */
    public function generateAll($ids = null)
    {
        if (empty($ids)) {
            throw new Exception("Il faut sélectionner une ou plusieurs compétitions pour démarrer la génération !");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            // init all gymnasiums etc. from register table
            (new Register())->set_up_season($id);
            // get competitions
            $competition_mgr = new Competition();
            // reset competition
            $competition_mgr->resetCompetition($id);
            // generate days
            (new Day())->generateDays($id);
            $competition = $competition_mgr->get_by_id($id);
            $code_competition = $competition['code_competition'];
            $this->delete_matches("match_status = 'NOT_CONFIRMED' AND code_competition = '$code_competition'");
            $this->generate_matches($competition, true, true);
        }
    }

    /**
     * @throws Exception
     */
    public function sign_team_sheet($id_match)
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        $match = $this->get_match($id_match);
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
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_match);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function sign_match_sheet($id_match)
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        $match = $this->get_match($id_match);
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
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_match);
        $this->sql_manager->execute($sql, $bindings);
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
                  AND a.max_date_reception > DATE_SUB(NOW(), INTERVAL 1 YEAR)";
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
        while(count($matches_home_away_adjust_needed) > 0) {
            $match = $matches_home_away_adjust_needed[$offset];
            // if match flip is allowed, flip it
            if (!$this->is_last_match_recent($match['id_equipe_ext'], $match['id_equipe_dom'])) {
                $this->flip_match($match);
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
    private function flip_match($match)
    {
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
            $update_match['id_gymnasium'] = $timeslots[0];
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
}
