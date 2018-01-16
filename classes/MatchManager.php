<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once 'Generic.php';

class MatchManager extends Generic
{
    /**
     * @param null $query
     * @return string
     */
    private function getSql($query = null)
    {
        $sql = "SELECT DISTINCT
        m.id_match,
        m.code_match,
        m.code_competition,
        c.id_compet_maitre AS parent_code_competition,
        c.libelle AS libelle_competition,
        m.division,
        m.id_journee,
        CONCAT(j.nommage, 
          ' : ', 
          'Semaine du ', 
          DATE_FORMAT(j.start_date, '%W %d %M'), 
          ' au ',
          DATE_FORMAT(ADDDATE(j.start_date, INTERVAL 4 DAY), '%W %d %M %Y')) AS journee,
        m.id_equipe_dom,
        e1.nom_equipe AS equipe_dom,
        m.id_equipe_ext,
        e2.nom_equipe AS equipe_ext,
        m.score_equipe_dom+0 AS score_equipe_dom,
        m.score_equipe_ext+0 AS score_equipe_ext,
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
        cr.heure AS heure_reception,
        DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS date_reception,
        UNIX_TIMESTAMP(m.date_reception + INTERVAL 23 HOUR + INTERVAL 59 MINUTE)*1000 AS date_reception_raw,
        DATE_FORMAT(m.date_original, '%d/%m/%Y') AS date_original,
        UNIX_TIMESTAMP(m.date_original + INTERVAL 23 HOUR + INTERVAL 59 MINUTE)*1000 AS date_original_raw,
        m.forfait_dom+0 AS forfait_dom,
        m.forfait_ext+0 AS forfait_ext,
        m.sheet_received+0 AS sheet_received,
        m.note,
        m.certif+0 AS certif,
        m.report_status,
        (
          CASE WHEN (m.score_equipe_dom + m.score_equipe_ext > 0) THEN 0
          WHEN m.date_reception >= curdate() THEN 0
          WHEN curdate() >= DATE_ADD(m.date_reception, INTERVAL 10 DAY) THEN 2
          WHEN curdate() >= DATE_ADD(m.date_reception, INTERVAL 5 DAY) THEN 1
          END
        ) AS retard,
        IF(mf.id_file IS NOT NULL, '1', '0') AS is_file_attached
        FROM matches m 
        JOIN competitions c ON c.code_competition = m.code_competition
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        JOIN journees j ON j.id=m.id_journee
        LEFT JOIN creneau cr ON 
          cr.id_equipe = m.id_equipe_dom AND 
          cr.jour = ELT(WEEKDAY(m.date_reception) + 2,
                                  'Dimanche',
                                  'Lundi',
                                  'Mardi',
                                  'Mercredi',
                                  'Jeudi',
                                  'Vendredi',
                                  'Samedi')
        LEFT JOIN matches_files mf ON mf.id_match = m.id_match
            WHERE 
            1=1";
        if ($query !== NULL) {
            $sql .= " AND $query";
        }
        return $sql;
    }

    /**
     * @param null $query
     * @return string
     */
    private function getSqlMatchFiles($query = null)
    {
        $sql = "SELECT 
        f.id,
        f.path_file
        FROM files f 
        JOIN matches_files mf ON mf.id_file = f.id
        WHERE 1=1";
        if ($query !== NULL) {
            $sql .= " AND $query";
        }
        return $sql;
    }

    /**
     * @param null $query
     * @return array
     * @throws Exception
     */
    public function getMatches($query = null)
    {
        $db = Database::openDbConnection();
        $sql = $this->getSql($query);
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @param $id_match
     * @return mixed
     * @throws Exception
     */
    public function getMatch($id_match)
    {
        $results = $this->getMatches("m.id_match = $id_match");
        if (count($results) !== 1) {
            throw new Exception("Error while retrieving match data");
        }
        return $results[0];
    }

    /**
     * @param $id_match
     * @return array
     * @throws Exception
     */
    public function getMatchFiles($id_match)
    {
        $db = Database::openDbConnection();
        $sql = $this->getSqlMatchFiles("mf.id_match = $id_match");
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @param $inputs
     * @throws Exception
     */
    public function download($inputs)
    {
        setlocale(LC_ALL, 'fr_FR.UTF-8');
        if (empty($inputs['id'])) {
            throw new Exception("No ID specified\n");
        }
        if (!$this->isDownloadAllowed($inputs['id'])) {
            throw new Exception("User not allowed to download !");
        }
        $match = $this->getMatch($inputs['id']);
        $match_files = $this->getMatchFiles($inputs['id']);
        $archiveFileName = $match['code_match'] . ".zip";
        $zip = new ZipArchive();
        if ($zip->open($archiveFileName, ZIPARCHIVE::CREATE) !== TRUE) {
            throw new Exception("Error during zip file creation !");
        }
        if (count($match_files) === 0) {
            throw new Exception("Pas de fichier attaché (Attention, ceux envoyés par email ne sont pas téléchargeables) !");
        }
        foreach ($match_files as $match_file) {
            $zip->addFile("../" . $match_file['path_file'], basename($match_file['path_file']));
        }
        $zip->close();
        if (empty($inputs['keep_file'])) {
            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=$archiveFileName");
            header("Content-length: " . filesize($archiveFileName));
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile($archiveFileName);
            unlink($archiveFileName);
        }
    }

    /**
     * @param $id_match
     * @return bool
     * @throws Exception
     */
    private function isDownloadAllowed($id_match)
    {
        $userDetails = $this->getCurrentUserDetails();
        $profile = $userDetails['profile_name'];
        $id_team = $userDetails['id_equipe'];
        $match = $this->getMatch($id_match);
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
    private function isMatchUpdateAllowed($id_match)
    {
        $userDetails = $this->getCurrentUserDetails();
        $profile = $userDetails['profile_name'];
        $id_team = $userDetails['id_equipe'];
        $match = $this->getMatch($id_match);
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
                if ($match['certif'] == '1') {
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
    public function saveMatch()
    {
        $db = Database::openDbConnection();
        $inputs = filter_input_array(INPUT_POST);
        if (empty($inputs['id_match'])) {
            $sql = "INSERT INTO";
        } else {
            if (!$this->isMatchUpdateAllowed($inputs['id_match'])) {
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
                    continue;
                case 'id_equipe_dom':
                case 'id_equipe_ext':
                case 'id_journee':
                    $sql .= "$key = $value,";
                    break;
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
                    $sql .= empty($value) ? "$key = 0," : "$key = $value,";
                    break;
                case 'date_reception':
                    $sql .= "$key = DATE(STR_TO_DATE('$value', '%d/%m/%Y')),";
                    break;
                case 'certif':
                case 'sheet_received':
                    $val = ($value === 'on') ? 1 : 0;
                    $sql .= "$key = $val,";
                    break;
                case 'forfait_dom':
                case 'forfait_ext':
                    $val = ($value === 'true') ? 1 : 0;
                    $sql .= "$key = $val,";
                    break;
                default:
                    $sql .= "$key = '$value',";
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (empty($inputs['id_match'])) {

        } else {
            $sql .= " WHERE id_match=" . $inputs['id_match'];
        }
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            $message = mysqli_error($db);
            throw new Exception($message);
        }
        if (empty($inputs['id_match'])) {
            return;
        }
        $this->saveMatchFiles($inputs);
        $code_match = $inputs['code_match'];
        $this->addActivity("Le match $code_match a ete modifie");
        return;
    }

    /**
     * @param $match
     * @throws Exception
     */
    private function saveMatchFiles($match)
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
            $id_file = 0;
            $this->insertFile(substr($uploadfile, 3), $id_file);
            $id_match = $match['id_match'];
            $this->linkMatchToFile($id_match, $id_file);
            if (move_uploaded_file($_FILES[$current_file_iteration]['tmp_name'], $this->accentedToNonAccented($uploadfile))) {
                $this->addActivity("Un nouveau fichier a ete transmis pour le match $code_match.");
            }
        }
        if ($mark_sheet_received) {
            $this->declareSheetReceived($code_match);
            require_once '../classes/Emails.php';
            $emailManager = new Emails();
            $emailManager->sendMailSheetReceived($code_match);
        }
        return;
    }

    /**
     * @param $uploadfile
     * @param $idFile
     * @throws Exception
     */
    private function insertFile($uploadfile, &$idFile)
    {
        $db = Database::openDbConnection();
        $sql = "INSERT INTO files SET path_file = '$uploadfile'";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            $message = mysqli_error($db);
            disconn_db();
            throw new Exception($message);
        }
        $idFile = mysqli_insert_id($db);
    }

    /**
     * @param $idMatch
     * @param $idFile
     * @throws Exception
     */
    private function linkMatchToFile($idMatch, $idFile)
    {
        $db = Database::openDbConnection();
        $sql = "INSERT INTO matches_files SET id_file = $idFile, id_match = $idMatch";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            $message = mysqli_error($db);
            throw new Exception($message);
        }
    }

    /**
     * @param $code_match
     * @return bool
     * @throws Exception
     */
    public function declareSheetReceived($code_match)
    {
        $db = Database::openDbConnection();
        $sql = "UPDATE matches SET sheet_received = 1 WHERE code_match = '$code_match'";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            return false;
        }
        $this->addActivity("La feuille du match $code_match a ete reçue");
        return true;
    }

    /**
     * @param $code_competition
     * @throws Exception
     */
    public function generateDays($code_competition)
    {
        require_once '../classes/RankManager.php';
        $rank_manager = new RankManager();
        $competition = null;
        $competitions = $rank_manager->getCompetitions();
        foreach ($competitions as $current_competition) {
            if ($current_competition['code_competition'] == $code_competition) {
                $competition = $current_competition;
                break;
            }
        }
        if (empty($competition)) {
            throw new Exception("Compétition inconnue");
        }
        if (empty($competition['start_date'])) {
            throw new Exception("Date de début de compétition non renseignée");
        }
        $this->deleteMatches("code_competition = '$code_competition'");
        require_once '../classes/DayManager.php';
        $day_manager = new DayManager();
        $day_manager->deleteDays("code_competition = '$code_competition'");
        $divisions = $rank_manager->getDivisionsFromCompetition($code_competition);
        $rounds_counts = array();
        foreach ($divisions as $division) {
            $teams = $rank_manager->getTeamsFromDivisionAndCompetition($division['division'], $code_competition);
            $teams_count = count($teams);
            if ($teams_count % 2 == 1) {
                $teams_count++;
            }
            $rounds_counts[] = $teams_count - 1;
        }
        for ($round_number = 1; $round_number <= max($rounds_counts); $round_number++) {
            $day_manager->insertDay(
                $code_competition,
                strval($round_number),
                $competition['start_date']
            );
        }
    }

    /**
     * @param $code_competition
     * @throws Exception
     */
    public function generateMatches($code_competition)
    {
        require_once '../classes/RankManager.php';
        $rank_manager = new RankManager();
        $competitions = $rank_manager->getCompetitions();
        foreach ($competitions as $current_competition) {
            if ($current_competition['code_competition'] == $code_competition) {
                $competition = $current_competition;
                break;
            }
        }
        if (empty($competition)) {
            throw new Exception("Compétition inconnue");
        }
        $this->deleteMatches("code_competition = '$code_competition'");
        $divisions = $rank_manager->getDivisionsFromCompetition($competition['code_competition']);
        foreach ($divisions as $division) {
            $teams = $rank_manager->getTeamsFromDivisionAndCompetition($division['division'], $competition['code_competition']);
            $teams_count = count($teams);
            if ($teams_count % 2 == 1) {
                $teams_count++;
            }
            // Generate the fixtures using the cyclic algorithm.
            $totalRounds = $teams_count - 1;
            $matchesPerRound = $teams_count / 2;
            $rounds = array();
            for ($i = 0; $i < $totalRounds; $i++) {
                $rounds[$i] = array();
            }
            for ($round = 0; $round < $totalRounds; $round++) {
                for ($match = 0; $match < $matchesPerRound; $match++) {
                    $home_index = ($round + $match) % ($teams_count - 1);
                    $away_index = ($teams_count - 1 - $match + $round) % ($teams_count - 1);
                    // Last team stays in the same place while the others
                    // rotate around it.
                    if ($match == 0) {
                        $away_index = $teams_count - 1;
                    }
                    $rounds[$round][$match] = strval($home_index)
                        . " v " . strval($away_index);
                }
            }
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
            $round_number = 1;
            foreach ($rounds as $round) {
                require_once '../classes/DayManager.php';
                $day_manager = new DayManager();
                $days = $day_manager->getDays(
                    "j.code_competition = '$code_competition' AND j.numero = $round_number"
                );
                if (count($days) == 0) {
                    throw new Exception("Journée non trouvée pour la competition $code_competition, journée $round_number");
                }
                $id_journee = $days[0]['id'];
                $match_number = 1;
                foreach ($round as $match) {
                    $code_match = strtoupper($competition['code_competition']) .
                        $division['division'] .
                        strval($round_number) .
                        strval($match_number);
                    $match_number++;
                    $index_teams = explode('v', $match);
                    if (empty($teams[intval($index_teams[0])]) || empty($teams[intval($index_teams[1])])) {
                        continue;
                    }
                    $team_dom = $teams[intval($index_teams[0])];
                    $team_ext = $teams[intval($index_teams[1])];
                    if ($team_dom['has_timeslot'] == '0') {
                        if ($team_ext['has_timeslot'] == '0') {
                            $dom_full_name = $team_dom['team_full_name'];
                            $ext_full_name = $team_ext['team_full_name'];
                            throw new Exception("$dom_full_name et $ext_full_name n'ont pas de créneau de réception");
                        }
                        $team_dom = $teams[intval($index_teams[1])];
                        $team_ext = $teams[intval($index_teams[0])];
                    }
                    $computed_date = $this->getComputedDate($id_journee, $team_dom['id_equipe']);
                    if ($this->isDateFilled($computed_date, $team_dom['id_equipe'])) {
                        if ($team_ext['has_timeslot'] == '0') {
                            $dom_full_name = $team_dom['team_full_name'];
                            $ext_full_name = $team_ext['team_full_name'];
                            throw new Exception("$ext_full_name n'a pas de créneau de réception et le créneau de réception de $dom_full_name est plein le $computed_date");
                        }
                        $computed_date = $this->getComputedDate($id_journee, $team_ext['id_equipe']);
                        if ($this->isDateFilled($computed_date, $team_ext['id_equipe'])) {
                            $dom_full_name = $team_dom['team_full_name'];
                            $ext_full_name = $team_ext['team_full_name'];
                            throw new Exception("Les créneaux de réception de $ext_full_name contre $dom_full_name sont pleins le $computed_date");
                        }
                        $this->insertMatch(
                            $code_match,
                            $competition['code_competition'],
                            $division['division'],
                            $team_ext['id_equipe'],
                            $team_dom['id_equipe'],
                            $id_journee
                        );
                    } else {
                        $this->insertMatch(
                            $code_match,
                            $competition['code_competition'],
                            $division['division'],
                            $team_dom['id_equipe'],
                            $team_ext['id_equipe'],
                            $id_journee
                        );
                    }

                }
                $round_number++;
            }
        }
    }

    /**
     * @param $match
     * @return string
     */
    private function flip($match)
    {
        $components = explode(' v ', $match);
        return $components[1] . " v " . $components[0];
    }

    /**
     * @param $code_match
     * @param $code_competition
     * @param $division
     * @param $id_equipe_dom
     * @param $id_equipe_ext
     * @param $id_journee
     * @return int|string
     * @throws Exception
     */
    private function insertMatch($code_match, $code_competition, $division, $id_equipe_dom, $id_equipe_ext, $id_journee)
    {
        $date_match = $this->getComputedDate(
            $id_journee,
            $id_equipe_dom);
        $db = Database::openDbConnection();
        $sql = "INSERT INTO matches SET 
                code_match = '$code_match', 
                code_competition = '$code_competition', 
                division = '$division',
                id_equipe_dom = $id_equipe_dom,
                id_equipe_ext = $id_equipe_ext,
                id_journee = $id_journee,
                date_reception = STR_TO_DATE('$date_match', '%d/%m/%Y')";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            $message = mysqli_error($db);
            throw new Exception($message);
        }
        return mysqli_insert_id($db);
    }

    /**
     * @param $id_journee
     * @param $id_equipe_dom
     * @return mixed
     * @throws Exception
     */
    private function getComputedDate($id_journee, $id_equipe_dom)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT DATE_FORMAT(j.start_date + INTERVAL FIELD(cr.jour,
                                                 'Lundi',
                                                 'Mardi',
                                                 'Mercredi',
                                                 'Jeudi',
                                                 'Vendredi',
                                                 'Samedi',
                                                 'Dimanche') DAY, '%d/%m/%Y') AS computed_date
                FROM journees j
                  JOIN creneau cr ON cr.id = (SELECT MIN(creneau.id)
                                              FROM creneau
                                              WHERE creneau.id_equipe = $id_equipe_dom)
                WHERE cr.id_equipe = $id_equipe_dom
                      AND j.id = $id_journee";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        if (count($results) == 0) {
            require_once 'TeamManager.php';
            $team_manager = new TeamManager();
            $team = $team_manager->getTeam($id_equipe_dom);
            $team_full_name = $team['team_full_name'];
            throw new Exception("Créneau non défini pour l'équipe $team_full_name");
        }
        return $results[0]['computed_date'];
    }

    /**
     * @param $computed_date
     * @param $id_equipe
     * @return bool
     * @throws Exception
     */
    private function isDateFilled($computed_date, $id_equipe)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT cr.id_gymnase
                FROM matches m
                  JOIN equipes e ON e.id_equipe = m.id_equipe_dom
                  JOIN creneau cr ON cr.id_equipe = e.id_equipe
                WHERE m.date_reception = STR_TO_DATE('$computed_date', '%d/%m/%Y')
                      AND cr.id_gymnase IN (SELECT id_gymnase
                                            FROM creneau
                                            WHERE creneau.id_equipe = $id_equipe)";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return (count($results) > 2);
    }

    /**
     * @param $query
     * @throws Exception
     */
    private function deleteMatches($query)
    {
        $db = Database::openDbConnection();
        $sql = "DELETE FROM matches WHERE 1=1";
        if ($query !== NULL) {
            $sql .= " AND $query";
        }
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            throw new Exception("Erreur durant l'effacement: " . mysqli_error($db));
        }
    }


}