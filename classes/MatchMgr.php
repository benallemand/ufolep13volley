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
    private function get_sql(?string $query = "1=1", string $order = "numero_journee"): string
    {
        return "SELECT  m.id_match,
                        IF(m.forfait_dom + m.forfait_ext > 0, 1, 0)                                     AS is_forfait,
                        IF(m.id_match IN (SELECT DISTINCT id_match FROM match_player), 1, 0)            AS is_match_player_filled,
                        IF((m.id_match NOT IN (SELECT DISTINCT id_match FROM match_player)) 
                            AND (m.forfait_dom + m.forfait_ext = 0)
                            AND (m.sheet_received > 0)
                            AND (m.certif = 0)
                            , 1, 0)                                                                     AS is_match_player_requested,
                        IF(m.id_match IN (  SELECT id_match 
                                            FROM match_player
                                            JOIN joueurs j2 on match_player.id_player = j2.id
                                            WHERE (j2.date_homologation > m.date_reception 
                                                   OR j2.date_homologation IS NULL 
                                                   OR j2.num_licence IS NULL)
                                         )
                            , 1, 0)                                                                     AS has_forbidden_player,
                        m.code_match,
                        m.code_competition,
                        c.id_compet_maitre                                                              AS parent_code_competition,
                        c.libelle                                                                       AS libelle_competition,
                        m.division,
                        j.numero                                                                        AS numero_journee,
                        j.id                                                                            AS id_journee,
                        CONCAT(j.nommage,
                               ' : ',
                               'Semaine du ',
                               DATE_FORMAT(j.start_date, '%W %d %M'),
                               ' au ',
                               DATE_FORMAT(ADDDATE(j.start_date, INTERVAL 4 DAY), '%W %d %M %Y'))       AS journee,
                        m.id_equipe_dom,
                        e1.nom_equipe                                                                   AS equipe_dom,
                        m.id_equipe_ext,
                        e2.nom_equipe                                                                   AS equipe_ext,
                        m.score_equipe_dom + 0                                                          AS score_equipe_dom,
                        m.score_equipe_ext + 0                                                          AS score_equipe_ext,
                        m.set_1_dom,
                        m.set_1_ext,
                        m.set_2_dom,
                        m.set_2_ext,
                        m.set_3_dom,
                        m.set_3_ext,
                        m.set_4_dom,
                        m.set_4_ext,
                        m.set_5_dom,
                        m.set_5_ext,
                        cr.heure                                                                        AS heure_reception, 
                        m.id_gymnasium,
                        g.nom                                                                           AS gymnasium,
                        DATE_FORMAT(m.date_reception, '%d/%m/%Y')                                       AS date_reception,
                        UNIX_TIMESTAMP(m.date_reception + INTERVAL 23 HOUR + INTERVAL 59 MINUTE) * 1000 AS date_reception_raw,
                        DATE_FORMAT(m.date_original, '%d/%m/%Y')                                        AS date_original,
                        UNIX_TIMESTAMP(m.date_original + INTERVAL 23 HOUR + INTERVAL 59 MINUTE) * 1000  AS date_original_raw,
                        m.forfait_dom + 0                                                               AS forfait_dom,
                        m.forfait_ext + 0                                                               AS forfait_ext,
                        m.sheet_received + 0                                                            AS sheet_received,
                        m.note,
                        m.certif + 0                                                                    AS certif,
                        m.report_status,
                        (
                            CASE
                                WHEN (m.score_equipe_dom + m.score_equipe_ext > 0) THEN 0
                                WHEN m.date_reception >= curdate() THEN 0
                                WHEN curdate() >= DATE_ADD(m.date_reception, INTERVAL 10 DAY) THEN 2
                                WHEN curdate() >= DATE_ADD(m.date_reception, INTERVAL 5 DAY) THEN 1
                                END
                            )                                                                           AS retard,
                        IF(mf.id_file IS NOT NULL, '1', '0')                                            AS is_file_attached,
                        m.match_status
                FROM matches m
                JOIN      competitions c ON c.code_competition = m.code_competition
                JOIN      equipes e1 ON e1.id_equipe = m.id_equipe_dom
                JOIN      equipes e2 ON e2.id_equipe = m.id_equipe_ext
                LEFT JOIN journees j ON m.id_journee = j.id
                LEFT JOIN creneau cr ON cr.id_equipe = m.id_equipe_dom 
                                        AND cr.jour = ELT(WEEKDAY(m.date_reception) + 2,
                                                        'Dimanche',
                                                        'Lundi',
                                                        'Mardi',
                                                        'Mercredi',
                                                        'Jeudi',
                                                        'Vendredi',
                                                        'Samedi')
                                        AND cr.id_gymnase = m.id_gymnasium
                LEFT JOIN gymnase g ON m.id_gymnasium = g.id    
                LEFT JOIN matches_files mf ON mf.id_match = m.id_match
                WHERE $query
                ORDER BY $order";
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
    public function get_matches(?string $query = "1=1", string $order = "numero_journee"): array
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
            error_log($file_path);
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
                               $dirtyFields = null)
    {
        return $this->save(array(
            'dirtyFields' => $dirtyFields,
            'id_match' => $id_match,
            'code_match' => $code_match,
            'score_equipe_dom' => $score_equipe_dom,
            'set_1_dom' => $set_1_dom,
            'set_2_dom' => $set_2_dom,
            'set_3_dom' => $set_3_dom,
            'set_4_dom' => $set_4_dom,
            'set_5_dom' => $set_5_dom,
            'score_equipe_ext' => $score_equipe_ext,
            'set_1_ext' => $set_1_ext,
            'set_2_ext' => $set_2_ext,
            'set_3_ext' => $set_3_ext,
            'set_4_ext' => $set_4_ext,
            'set_5_ext' => $set_5_ext,
        ));
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
                    $val = ($value === 'on' || $value === 1) ? 1 : 0;
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 'i', 'value' => $val);
                    break;
                case 'forfait_dom':
                case 'forfait_ext':
                    $val = ($value === 'true') ? 1 : 0;
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
     * @throws Exception
     */
    public function generate_matches($competition, $try_flip, $forbid_same_home = false)
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
            // Interleave so that home and away games are fairly evenly dispersed.
            $interleaved = array();
            for ($i = 0; $i < $totalRounds; $i++) {
                $interleaved[$i] = array();
            }
            $evn = 0;
            $odd = ($teams_count / 2);
            for ($i = 0; $i < sizeof($rounds); $i++) {
                if ($i % 2 == 0) {
                    $interleaved[$i] = $rounds[$evn++];
                } else {
                    $interleaved[$i] = $rounds[$odd++];
                }
            }
            $rounds = $interleaved;
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
                    if ($forbid_same_home) {
                        // si l'option de regarder le dernier match est active
                        if ($this->is_last_match_same_home($team_dom['id_equipe'], $team_ext['id_equipe'])) {
                            // si il y a déjà eu une rencontre dom vs ext la dernière fois, inverser la réception
                            $dom = $team_ext;
                            $ext = $team_dom;
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
     * @param $code_competition
     * @param $id_equipe_dom
     * @return array|int|string|null
     * @throws Exception
     */
    private function get_computed_dates($code_competition, $id_equipe_dom)
    {
        $sql = "SELECT DATE_FORMAT(j.start_date + INTERVAL FIELD(cr.jour,
                                                                 'Lundi',
                                                                 'Mardi',
                                                                 'Mercredi',
                                                                 'Jeudi',
                                                                 'Vendredi',
                                                                 'Samedi',
                                                                 'Dimanche') - 1 DAY, '%d/%m/%Y') AS computed_date,
                       j.numero                                                                   AS week_number,
                       j.id                                                                       AS week_id,
                       cr.id_gymnase
                FROM journees j
                         JOIN creneau cr ON cr.id IN (SELECT creneau.id
                                                      FROM creneau
                                                      WHERE creneau.id_equipe = ?)
                         JOIN classements c on cr.id_equipe = c.id_equipe
                WHERE cr.id_equipe = ?
                  AND j.code_competition = ?
                ORDER BY week_number, cr.usage_priority";
        $bindings = array(
            array('type' => 'i', 'value' => $id_equipe_dom),
            array('type' => 'i', 'value' => $id_equipe_dom),
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
        // Chercher le gymnase de réception
        $sql = "SELECT nb_terrain FROM gymnase WHERE id = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id_gymnase),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        $nb_terrain = 0;
        if (count($results) > 0) {
            $nb_terrain = $results[0]['nb_terrain'];
        }
        // Trouver les matchs déjà joués ce soir là
        $sql = "SELECT *
                FROM matches m
                WHERE id_gymnasium = ?
                    AND m.date_reception = STR_TO_DATE(?, '%d/%m/%Y')
                    AND m.match_status != 'ARCHIVED'";
        $bindings = array(
            array('type' => 'i', 'value' => $id_gymnase),
            array('type' => 's', 'value' => $computed_date),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return (count($results) >= $nb_terrain);
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
        $computed_dates = $this->get_computed_dates($code_competition, $team_dom['id_equipe']);
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
            // computed date is not allowed (home team already has a match)
            if ($this->is_team_busy_for_week($computed_date['week_id'], $team_dom['id_equipe'])) {
                continue;
            }
            // computed date is not allowed (away team already has a match)
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
                WHERE m.id_journee = ?
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

//    TODO dead code ?
//    /**
//     * @param $team
//     * @return bool
//     * @throws Exception
//     */
//    private function is_flip_allowed($team)
//    {
//        $id_team = $team['id_equipe'];
//        $sql = "SELECT
//                       SUM(IF(m.id_equipe_dom = e.id_equipe, 1, 0)) AS domicile,
//                       SUM(IF(m.id_equipe_ext = e.id_equipe, 1, 0)) AS exterieur,
//                       m.code_competition                           AS competition,
//                       e.nom_equipe                                 AS equipe
//                FROM matches m
//                         JOIN equipes e on m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe
//                WHERE m.match_status != 'ARCHIVED'
//                AND e.id_equipe = $id_team
//                GROUP BY competition, equipe
//                HAVING ABS(domicile - exterieur) > 2
//                ORDER BY competition, equipe";
//        $results = $this->sql_manager->execute($sql);
//        return count($results) === 0;
//    }
//
//    /**
//     * @param $computed_date
//     * @param $team_id
//     * @return bool
//     * @throws Exception
//     */
//    public function are_teams_blacklisted($computed_date, $team_id)
//    {
//        $blacklistedTeamIds = $this->get_blacklisted_team_ids($team_id);
//        foreach ($blacklistedTeamIds as $blacklistedTeamId) {
//            if ($this->has_match($blacklistedTeamId, $computed_date)) {
//                return true;
//            }
//        }
//        return false;
//    }

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
            $this->generate_matches($competition, false, false);
        }
    }


    /**
     * @throws Exception
     */
    public function getMatchPlayers($id_match = null)
    {
        return (new Players())->get_players(
            "j.id IN (SELECT id_player FROM match_player WHERE id_match = $id_match)",
            "club");
//        $where = "1=1";
//        if (!empty($id_match)) {
//            $where = "mp.id_match = $id_match";
//        }
//        $sql = "SELECT
//                    CONCAT(j.nom, ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')') AS full_name,
//                    j.prenom,
//                    j.nom,
//                    j.num_licence,
//                    p.path_photo,
//                    j.sexe,
//                    j.departement_affiliation,
//                    j.est_actif+0 AS est_actif,
//                    c.nom AS club,
//                    j.show_photo+0 AS show_photo,
//                    j.id,
//                    DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS date_reception,
//                    DATE_FORMAT(j.date_homologation, '%d/%m/%Y') AS date_homologation,
//                    mp.id_match
//                FROM match_player mp
//                LEFT JOIN joueurs j ON mp.id_player = j.id
//                LEFT JOIN matches m ON mp.id_match = m.id_match
//                LEFT JOIN joueur_equipe je ON je.id_joueur = j.id
//                LEFT JOIN equipes e ON e.id_equipe=je.id_equipe AND e.id_equipe IN (SELECT id_equipe FROM classements)
//                LEFT JOIN clubs c ON c.id = j.id_club
//                LEFT JOIN photos p ON p.id = j.id_photo
//                WHERE $where
//                GROUP BY CONCAT(j.nom, ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')'), j.prenom, j.nom, j.num_licence, p.path_photo, j.sexe, j.departement_affiliation, j.est_actif+0, c.nom, j.show_photo+0, j.id, DATE_FORMAT(j.date_homologation, '%d/%m/%Y')
//                ORDER BY club, sexe, j.nom";
//        $results = $this->sql_manager->execute($sql);
//        foreach ($results as $index => $result) {
//            $results[$index]['path_photo'] = Generic::accentedToNonAccented($results[$index]['path_photo']);
//        }
//        return $results;
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
        $emailCtsd = '';
        $division = $data['division'];
        switch ($data['code_competition']) {
            case 'm':
                $emailCtsd = 'd' . $division . 'm-6x6@ufolep13volley.org';
                break;
            case 'f':
                $emailCtsd = 'd' . $division . 'f-4x4@ufolep13volley.org';
                break;
            case 'mo':
                $emailCtsd = 'd' . $division . 'mi-4x4@ufolep13volley.org';
                break;
            case 'kh':
            case 'kf':
                $emailCtsd = 'khanna@ufolep13volley.org';
                break;
            case 'c':
            case 'cf':
                $emailCtsd = 'isoardi@ufolep13volley.org';
                break;
        }
        return array($emailDom, $emailExt, $emailCtsd);
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
     * @return bool
     * @throws Exception
     */
    public function giveReportDate($code_match, $report_date)
    {
        $sessionIdEquipe = $_SESSION['id_equipe'];
        $sql = "UPDATE matches SET date_reception = DATE(STR_TO_DATE('$report_date', '%Y-%m-%d')) WHERE code_match = '$code_match'";
        $this->sql_manager->execute($sql);
        $this->addActivity("Date de report transmise par " . $this->team->getTeamName($sessionIdEquipe) . " pour le match $code_match");
        (new Emails())->sendMailGiveReportDate($code_match, $report_date, $sessionIdEquipe);
        return true;
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
        switch ($function_name) {
            case 'manage_match_players':
                $match_manager = new MatchMgr();
                $match = $match_manager->get_match($id_match);
                @session_start();
                // allow admin
                if ($_SESSION['profile_name'] === 'ADMINISTRATEUR') {
                    return;
                }
                // allow only playing teams
                if (!in_array($_SESSION['id_equipe'], array($match['id_equipe_dom'], $match['id_equipe_ext']))) {
                    throw new Exception("Seules les équipes ayant participé au match peuvent dire qui était là !");
                }
                // allow only RESPONSABLE_EQUIPE
                if ($_SESSION['profile_name'] !== 'RESPONSABLE_EQUIPE') {
                    throw new Exception("Seuls les responables d'équipes peuvent dire qui était là !");
                }
                // allow only RESPONSABLE_EQUIPE
                if (intval($match['sheet_received']) > 0) {
                    throw new Exception("La feuille de match a déjà été envoyée, il n'est plus possible de renseigner les présents !");
                }
                break;
            default:
                break;
        }
    }

}