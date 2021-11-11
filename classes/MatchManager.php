<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once __DIR__ . '/Generic.php';

class MatchManager extends Generic
{
    /**
     * MatchManager constructor.
     */
    public function __construct()
    {
        ini_set('max_execution_time', 1200);
        ini_set('memory_limit', '512M');
        ini_set('xdebug.max_nesting_level', 2000);
    }

    /**
     * @param null $query
     * @return string
     */
    private function getSql($query = "1=1")
    {
        return "SELECT DISTINCT
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
        IF(mf.id_file IS NOT NULL, '1', '0') AS is_file_attached,
        m.match_status
        FROM matches m 
        JOIN competitions c ON c.code_competition = m.code_competition
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        LEFT JOIN journees j ON j.id=m.id_journee
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
            WHERE $query";
    }

    /**
     * @param null $query
     * @return string
     */
    private function getSqlMatchFiles($query = "1=1")
    {
        // group by to avoid duplicate files in zip file
        $sql = "SELECT 
        f.id,
        f.path_file,
        f.hash
        FROM files f 
        JOIN matches_files mf ON mf.id_file = f.id
        WHERE $query GROUP BY f.hash";
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
                    $value = mysqli_real_escape_string($db, $value);
                    $sql .= "$key = '$value',";
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($inputs['id_match'])) {
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
            $file_hash = md5_file($_FILES[$current_file_iteration]['tmp_name']);
            $this->insertFile(substr($uploadfile, 3), $file_hash, $id_file);
            $id_match = $match['id_match'];
            $this->linkMatchToFile($id_match, $id_file);
            if (move_uploaded_file($_FILES[$current_file_iteration]['tmp_name'], $this->accentedToNonAccented($uploadfile))) {
                $this->addActivity("Un nouveau fichier a ete transmis pour le match $code_match.");
            }
        }
        if ($mark_sheet_received) {
            $this->declareSheetReceived($code_match);
            require_once __DIR__ . '/../classes/Emails.php';
            $emailManager = new Emails();
            $emailManager->sendMailSheetReceived($code_match);
        }
        return;
    }

    /**
     * @param $uploadfile
     * @param $file_hash
     * @param $idFile
     * @throws Exception
     */
    private function insertFile($uploadfile, $file_hash, &$idFile)
    {
        $db = Database::openDbConnection();
        $sql = "INSERT INTO files SET path_file = '$uploadfile', hash = '$file_hash'";
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
     * @param $query
     * @throws Exception
     */
    public function unsetDayMatches($query = "1=1")
    {
        $db = Database::openDbConnection();
        $sql = "UPDATE matches SET id_journee = NULL WHERE $query";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            throw new Exception("Erreur durant unsetDayMatches: " . mysqli_error($db));
        }
    }

    /**
     * @param $competition
     * @throws Exception
     */
    public function generateMatches($competition)
    {
        if (empty($competition)) {
            throw new Exception("Compétition non trouvée !");
        }
        // aller/retour pour le championnat 4x4 mixte
        $code_competition = $competition['code_competition'];
        $is_mirror_needed = ($competition['is_home_and_away'] === '1');
        // supprimer les matchs générés et non confirmés pour la compétition (on préserver les matchs archivés)
        $this->deleteMatches("code_competition = '$code_competition' AND match_status = 'NOT_CONFIRMED'");
        require_once __DIR__ . '/../classes/RankManager.php';
        $rank_manager = new RankManager();
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
            // Generate the fixtures using the cyclic algorithm.
            // source: http://bluebones.net/fixtures.php
            $totalRounds = $teams_count - 1;
            $matchesPerRound = $teams_count / 2;
            if($is_mirror_needed) {
                $message .= "Nombre de journées : " . (2 * $totalRounds) . PHP_EOL;
            }
            else {
                $message .= "Nombre de journées : " . $totalRounds . PHP_EOL;
            }
            $message .= "Nombre de matches par journée : " . $matchesPerRound . PHP_EOL;
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
                    // on remplit le tableau avec les matchs à insérer
                    $to_be_inserted_matches[] = array(
                        'dom' => $team_dom,
                        'ext' => $team_ext,
                        'competition' => $competition,
                        'division' => $division
                    );
                }
            }
            $message .= "Nombre de matchs à créer : " . count($to_be_inserted_matches) . PHP_EOL;
            $count_to_be_inserted_matches += count($to_be_inserted_matches);
            $this->insert_matches($to_be_inserted_matches, $code_competition, $division['division'], 0, 0);
        }
        $count_inserted_matches = count($this->getMatches("m.code_competition = '$code_competition' AND m.match_status = 'NOT_CONFIRMED'"));
        $message .= "Nombre de matchs à créer : $count_to_be_inserted_matches" . PHP_EOL;
        $message .= "Nombre de matchs créés : $count_inserted_matches" . PHP_EOL;
        throw new Exception($message);
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
     * @param string $date_match , format '%d/%m/%Y'
     * @param $note
     * @return int|string
     * @throws Exception
     */
    private function insertMatch($code_match,
                                 $code_competition,
                                 $division,
                                 $id_equipe_dom,
                                 $id_equipe_ext,
                                 $id_journee,
                                 $date_match,
                                 $note)
    {
        $db = Database::openDbConnection();
        $sql = "INSERT INTO matches SET 
                code_match = " . (is_null($code_match) ? "NULL" : "'$code_match'") . ", 
                code_competition = '$code_competition', 
                division = '$division',
                id_equipe_dom = $id_equipe_dom,
                id_equipe_ext = $id_equipe_ext,
                id_journee = " . (is_null($id_journee) ? "NULL" : "$id_journee") . ",
                date_reception = " . (is_null($date_match) ? "NULL" : "STR_TO_DATE('$date_match', '%d/%m/%Y')") . ",
                note = " . (is_null($note) ? "NULL" : "'" . mysqli_real_escape_string($db, $note) . "'");
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            $message = mysqli_error($db);
            throw new Exception($message . ", SQL : " . $sql);
        }
        return mysqli_insert_id($db);
    }

    /**
     * @param $code_competition
     * @param $id_equipe_dom
     * @return mixed
     * @throws Exception
     */
    private function getComputedDates($code_competition, $id_equipe_dom)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT DATE_FORMAT(j.start_date + INTERVAL FIELD(cr.jour,
                                                                 'Lundi',
                                                                 'Mardi',
                                                                 'Mercredi',
                                                                 'Jeudi',
                                                                 'Vendredi',
                                                                 'Samedi',
                                                                 'Dimanche') - 1 DAY, '%d/%m/%Y') AS computed_date,
                       j.numero                                                                   AS week_number,
                       j.id                                                                       AS week_id
                FROM journees j
                         JOIN creneau cr ON cr.id IN (SELECT creneau.id
                                                      FROM creneau
                                                      WHERE creneau.id_equipe = $id_equipe_dom)
                         JOIN classements c on cr.id_equipe = c.id_equipe
                WHERE cr.id_equipe = $id_equipe_dom
                  AND j.code_competition = '$code_competition'
                ORDER BY week_number, cr.usage_priority";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @param $computed_date
     * @param $id_equipe
     * @return bool
     * @throws Exception
     */
    private function isDateFilled($computed_date, $id_equipe)
    {
        // Chercher le gymnase de réception
        $db = Database::openDbConnection();
        $sql = "SELECT * FROM gymnase WHERE id IN (
                    SELECT id_gymnase 
                    FROM creneau 
                    WHERE id_equipe = $id_equipe
                    AND jour = ELT(WEEKDAY(STR_TO_DATE('$computed_date', '%d/%m/%Y')) + 2,
                                           'Dimanche',
                                           'Lundi',
                                           'Mardi',
                                           'Mercredi',
                                           'Jeudi',
                                           'Vendredi',
                                           'Samedi'))";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        $nb_terrain = 0;
        foreach ($results as $result) {
            $nb_terrain += $result['nb_terrain'];
        }
        // Trouver les matchs déjà joués ce soir là
        $sql = "SELECT cr.id_gymnase
                FROM matches m
                  JOIN equipes e ON e.id_equipe = m.id_equipe_dom
                  JOIN creneau cr ON cr.id_equipe = e.id_equipe
                WHERE m.date_reception = STR_TO_DATE('$computed_date', '%d/%m/%Y')
                      AND cr.id_gymnase IN (SELECT id_gymnase
                                            FROM creneau
                                            WHERE creneau.id_equipe = $id_equipe)
                      AND m.match_status != 'ARCHIVED'";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return (count($results) >= $nb_terrain);
    }

    /**
     * @param $query
     * @throws Exception
     */
    public function deleteMatches($query = "1=1")
    {
        $db = Database::openDbConnection();
        $sql = "DELETE FROM matches WHERE $query";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            throw new Exception("Erreur durant l'effacement: " . mysqli_error($db));
        }
    }

    /**
     * @param $computed_date
     * @param $id_equipe
     * @return bool
     * @throws Exception
     */
    private function isDateBlacklisted($computed_date, $id_equipe = null)
    {
        $db = Database::openDbConnection();
        if ($id_equipe === null) {
            $sql = "SELECT * 
                FROM blacklist_date 
                WHERE closed_date = STR_TO_DATE('$computed_date', '%d/%m/%Y')";
        } else {
            $sql = "SELECT * 
                FROM blacklist_gymnase 
                WHERE closed_date = STR_TO_DATE('$computed_date', '%d/%m/%Y')
                AND id_gymnase IN (
                    SELECT id_gymnase 
                    FROM creneau 
                    WHERE id_equipe = $id_equipe
                    AND jour = ELT(WEEKDAY(closed_date) + 2,
                                           'Dimanche',
                                           'Lundi',
                                           'Mardi',
                                           'Mercredi',
                                           'Jeudi',
                                           'Vendredi',
                                           'Samedi'))";
        }
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return (count($results) > 0);
    }

    /**
     * @param $computed_date
     * @param $id_equipe
     * @return bool
     * @throws Exception
     */
    private function isTeamBlacklisted($computed_date, $id_equipe)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT * 
                FROM blacklist_team
                WHERE closed_date = STR_TO_DATE('$computed_date', '%d/%m/%Y')
                AND id_team = $id_equipe";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return (count($results) > 0);
    }

    /**
     * @param $competition
     * @param $division
     * @param $week_id
     * @return int
     * @throws Exception
     */
    private function get_count_matches_per_day($competition, $division, $week_id)
    {
        $db = Database::openDbConnection();
        $code_competition = $competition['code_competition'];
        $division_number = $division['division'];
        $sql = "SELECT * 
                FROM matches
                WHERE code_competition = '$code_competition' 
                  AND division = '$division_number' 
                  AND id_journee = $week_id
                  AND match_status != 'ARCHIVED'";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return count($results);
    }

    /**
     * @param $to_be_inserted_match
     * @param bool $try_flip
     * @return bool
     * @throws Exception
     */
    private function insert_match($to_be_inserted_match, $try_flip = false)
    {
        $team_dom = $to_be_inserted_match['dom'];
        $team_ext = $to_be_inserted_match['ext'];
        $competition = $to_be_inserted_match['competition'];
        $division = $to_be_inserted_match['division'];
        $code_competition = $competition['code_competition'];
        $is_date_found = false;
        $computed_dates = $this->getComputedDates($code_competition, $team_dom['id_equipe']);
        $found_date = null;
        foreach ($computed_dates as $computed_date) {
            // computed date is full (too many matches in same gymnasium)
            if ($this->isDateFilled($computed_date['computed_date'], $team_dom['id_equipe'])) {
                continue;
            }
            // computed date is not allowed (holiday)
            if ($this->isDateBlacklisted($computed_date['computed_date'])) {
                continue;
            }
            // computed date is not allowed (gymnasium is not available)
            if ($this->isDateBlacklisted($computed_date['computed_date'], $team_dom['id_equipe'])) {
                continue;
            }
            // computed date is not allowed (home team already has a match)
            if ($this->isWeekAvailable($computed_date['week_id'], $team_dom['id_equipe'])) {
                continue;
            }
            // computed date is not allowed (away team already has a match)
            if ($this->isWeekAvailable($computed_date['week_id'], $team_ext['id_equipe'])) {
                continue;
            }
            $is_date_found = true;
            $found_date = $computed_date['computed_date'];
            $round_number = $computed_date['week_number'];
            $match_number = $this->get_count_matches_per_day($competition, $division, $computed_date['week_id']) + 1;
            $year_month = date('ym');
            $code_match = strtoupper($competition['code_competition']) .
                $year_month .
                $division['division'] .
                strval($round_number) .
                strval($match_number);
            $this->insertMatch(
                $code_match,
                $competition['code_competition'],
                $division['division'],
                $team_dom['id_equipe'],
                $team_ext['id_equipe'],
                $computed_date['week_id'],
                $found_date,
                null);
            break;
        }
        if (!$is_date_found) {
            if ($try_flip) {
                return $this->insert_match(array(
                    'dom' => $to_be_inserted_match['ext'],
                    'ext' => $to_be_inserted_match['dom'],
                    'competition' => $to_be_inserted_match['competition'],
                    'division' => $to_be_inserted_match['division']
                ), false);
            }
        }
        return $is_date_found;
    }

    /**
     * @param $week_id
     * @param $id_equipe
     * @return bool
     * @throws Exception
     */
    private
    function isWeekAvailable($week_id, $id_equipe)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT m.* FROM matches m 
                WHERE m.id_journee = $week_id
                AND (m.id_equipe_dom = $id_equipe OR m.id_equipe_ext = $id_equipe)";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
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
     * @return void
     * @throws Exception
     */
    private
    function insert_matches(array $to_be_inserted_matches,
                            $code_competition,
                            $division,
                            $index_match,
                            $index_place)
    {
        $matches = $this->getMatches("m.code_competition = '$code_competition' 
                                             AND m.division = '$division' 
                                             AND m.match_status = 'NOT_CONFIRMED'");
        if (count($matches) === count($to_be_inserted_matches)) {
            return;
        }
        $this->deleteMatches("code_competition = '$code_competition' 
                                             AND division = '$division' 
                                             AND match_status = 'NOT_CONFIRMED'");
        if ($index_match === count($to_be_inserted_matches)) {
            return;
        }
        if ($index_place === count($to_be_inserted_matches)) {
            $this->insert_matches($to_be_inserted_matches, $code_competition, $division, $index_match + 1, 0);
            return;
        }
        $to_be_inserted_matches = $this->move_element($to_be_inserted_matches, $index_match, $index_place);
        $is_successful = true;
        foreach ($to_be_inserted_matches as $to_be_inserted_match) {
            if (!$this->insert_match($to_be_inserted_match, true)) {
                $is_successful = false;
                break;
            }
        }
        if (!$is_successful) {
            $this->insert_matches($to_be_inserted_matches, $code_competition, $division, $index_match, $index_place + 1);
        }
    }

    /**
     * @param $team
     * @return bool
     * @throws Exception
     */
    private function is_flip_allowed($team)
    {
        $db = Database::openDbConnection();
        $id_team = $team['id_equipe'];
        $sql = "SELECT 
                       SUM(IF(m.id_equipe_dom = e.id_equipe, 1, 0)) AS domicile,
                       SUM(IF(m.id_equipe_ext = e.id_equipe, 1, 0)) AS exterieur,
                       m.code_competition                           AS competition,
                       e.nom_equipe                                 AS equipe
                FROM matches m
                         JOIN equipes e on m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe
                WHERE m.match_status != 'ARCHIVED'
                AND e.id_equipe = $id_team
                GROUP BY competition, equipe
                HAVING ABS(domicile - exterieur) > 2
                ORDER BY competition, equipe";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return count($results) === 0;
    }

    /**
     * @param $computed_date
     * @param $id_equipe
     * @return bool
     * @throws Exception
     */
    private function isTeamsBlacklisted($computed_date, $id_equipe)
    {
        $blacklistedTeamIds = $this->getBlackListedTeamIds($id_equipe);
        foreach ($blacklistedTeamIds as $blacklistedTeamId) {
            if ($this->has_match($blacklistedTeamId, $computed_date)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $id_equipe
     * @return array
     * @throws Exception
     */
    private function getBlackListedTeamIds($id_equipe)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT * 
                FROM blacklist_teams
                WHERE id_team_1 = $id_equipe
                OR id_team_2 = $id_equipe";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        $blacklisted_teams_ids = array();
        foreach ($results as $result) {
            if ($result['id_team_1'] == $id_equipe) {
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
    private function has_match($team_id, $date_string)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT * 
                FROM matches
                WHERE
                      (id_equipe_dom = $team_id OR id_equipe_ext = $team_id) 
                  AND date_reception = STR_TO_DATE('$date_string', '%d/%m/%Y')";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return count($results) > 0;
    }

}
