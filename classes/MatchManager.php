<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/SqlManager.php';

class MatchManager extends Generic
{
    /**
     * @var SqlManager
     */
    private $sql_manager;

    /**
     * MatchManager constructor.
     */
    public function __construct()
    {
        $this->sql_manager = new SqlManager();
        ini_set('max_execution_time', 1200);
        ini_set('memory_limit', '512M');
        ini_set('xdebug.max_nesting_level', 2000);
    }

    /**
     * @param string|null $query
     * @return string
     */
    private function get_sql(?string $query = "1=1"): string
    {
        return "SELECT DISTINCT m.id_match,
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
                                m.id_journee,
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
                JOIN competitions c ON c.code_competition = m.code_competition
                JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
                JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
                LEFT JOIN journees j ON j.id = m.id_journee
                LEFT JOIN creneau cr ON cr.id_equipe = m.id_equipe_dom 
                                        AND cr.jour = ELT(WEEKDAY(m.date_reception) + 2,
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
     * @param null $query
     * @return array
     * @throws Exception
     */
    public function get_matches($query = null): array
    {
        return $this->sql_manager->execute($this->get_sql($query));
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
     * @param $inputs
     * @throws Exception
     */
    public function download($inputs)
    {
        setlocale(LC_ALL, 'fr_FR.UTF-8');
        if (empty($inputs['id'])) {
            throw new Exception("No ID specified\n");
        }
        if (!$this->is_download_allowed($inputs['id'])) {
            throw new Exception("User not allowed to download !");
        }
        $match = $this->get_match($inputs['id']);
        $match_files = $this->get_match_files($inputs['id']);
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
    private function is_match_update_allowed($id_match): bool
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
    public function save_match()
    {
        $inputs = filter_input_array(INPUT_POST);
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
                    $val = ($value === 'on') ? 1 : 0;
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
            $id_file = 0;
            $file_hash = md5_file($_FILES[$current_file_iteration]['tmp_name']);
            $id_file = $this->insert_file(substr($uploadfile, 3), $file_hash);
            $id_match = $match['id_match'];
            $this->link_match_to_file($id_match, $id_file);
            if (move_uploaded_file($_FILES[$current_file_iteration]['tmp_name'], $this->accentedToNonAccented($uploadfile))) {
                $this->addActivity("Un nouveau fichier a ete transmis pour le match $code_match.");
            }
        }
        if ($mark_sheet_received) {
            $this->declare_sheet_received($code_match);
            require_once __DIR__ . '/../classes/Emails.php';
            $emailManager = new Emails();
            $emailManager->sendMailSheetReceived($code_match);
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
    public function declare_sheet_received($code_match): bool
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
    public function generate_matches($competition)
    {
        if (empty($competition)) {
            throw new Exception("Compétition non trouvée !");
        }
        // aller/retour pour le championnat 4x4 mixte
        $code_competition = $competition['code_competition'];
        $is_mirror_needed = ($competition['is_home_and_away'] === '1');
        // supprimer les matchs générés et non confirmés pour la compétition (on préserver les matchs archivés)
        $this->delete_matches("code_competition = '$code_competition' AND match_status = 'NOT_CONFIRMED'");
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
            // Generate the fixtures using the round robin cyclic algorithm.
            $totalRounds = $teams_count - 1;
            $matchesPerRound = $teams_count / 2;
            if ($is_mirror_needed) {
                $message .= "Nombre de journées : " . (2 * $totalRounds) . PHP_EOL;
            } else {
                $message .= "Nombre de journées : " . $totalRounds . PHP_EOL;
            }
            $message .= "Nombre de matches par journée : " . $matchesPerRound . PHP_EOL;
            $rounds = array();
            for ($i = 0; $i < $totalRounds; $i++) {
                $rounds[$i] = array();
            }
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
                    // on remplit le tableau avec les matchs à insérer
                    $home_id = $team_dom['id_equipe'];
                    $away_id = $team_ext['id_equipe'];
                    if ($this->is_last_match_same_home($home_id, $away_id)) {
                        // si il y a déjà eu une rencontre dom vs ext la dernière fois, inverser la réception
                        $to_be_inserted_matches[] = array(
                            'dom' => $team_ext,
                            'ext' => $team_dom,
                            'competition' => $competition,
                            'division' => $division
                        );
                    } else {
                        // sinon garder tel quel
                        $to_be_inserted_matches[] = array(
                            'dom' => $team_dom,
                            'ext' => $team_ext,
                            'competition' => $competition,
                            'division' => $division
                        );
                    }
                }
            }
            $message .= "Nombre de matchs à créer : " . count($to_be_inserted_matches) . PHP_EOL;
            $count_to_be_inserted_matches += count($to_be_inserted_matches);
            $this->insert_matches($to_be_inserted_matches, $code_competition, $division['division'], 0, 0);
        }
        $count_inserted_matches = count($this->get_matches("m.code_competition = '$code_competition' AND m.match_status = 'NOT_CONFIRMED'"));
        $message .= "Nombre de matchs à créer : $count_to_be_inserted_matches" . PHP_EOL;
        $message .= "Nombre de matchs créés : $count_inserted_matches" . PHP_EOL;
        throw new Exception($message);
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
    private function insert_db_match($code_match,
                                     $code_competition,
                                     $division,
                                     $id_equipe_dom,
                                     $id_equipe_ext,
                                     $id_journee,
                                     $date_match,
                                     $note)
    {
        $bindings = array();
        $code_match_string = "code_match = NULL";
        if (!is_null($code_match)) {
            $bindings[] = array('type' => 's', 'value' => $code_match);
            $code_match_string = "code_match = ?";
        }
        $bindings[] = array('type' => 's', 'value' => $code_competition);
        $bindings[] = array('type' => 's', 'value' => $division);
        $bindings[] = array('type' => 'i', 'value' => $id_equipe_dom);
        $bindings[] = array('type' => 'i', 'value' => $id_equipe_ext);
        $day_string = "id_journee = NULL";
        if (!is_null($id_journee)) {
            $bindings[] = array('type' => 'i', 'value' => $id_journee);
            $day_string = "id_journee = ?";
        }
        $date_reception_string = "date_reception = NULL";
        if (!is_null($date_match)) {
            $bindings[] = array('type' => 's', 'value' => $date_match);
            $date_reception_string = "date_reception = STR_TO_DATE(?, '%d/%m/%Y')";
        }
        $note_string = "note = NULL";
        if (!is_null($note)) {
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
                       j.id                                                                       AS week_id
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
     * @param $id_equipe
     * @return bool
     * @throws Exception
     */
    private function is_date_filled($computed_date, $id_equipe): bool
    {
        // Chercher le gymnase de réception
        $sql = "SELECT * FROM gymnase WHERE id IN (
                    SELECT id_gymnase 
                    FROM creneau 
                    WHERE id_equipe = ?
                    AND jour = ELT(WEEKDAY(STR_TO_DATE(?, '%d/%m/%Y')) + 2,
                                           'Dimanche',
                                           'Lundi',
                                           'Mardi',
                                           'Mercredi',
                                           'Jeudi',
                                           'Vendredi',
                                           'Samedi'))";
        $bindings = array(
            array('type' => 'i', 'value' => $id_equipe),
            array('type' => 's', 'value' => $computed_date),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        $nb_terrain = 0;
        foreach ($results as $result) {
            $nb_terrain += $result['nb_terrain'];
        }
        // Trouver les matchs déjà joués ce soir là
        $sql = "SELECT cr.id_gymnase
                FROM matches m
                  JOIN equipes e ON e.id_equipe = m.id_equipe_dom
                  JOIN creneau cr ON cr.id_equipe = e.id_equipe
                WHERE m.date_reception = STR_TO_DATE(?, '%d/%m/%Y')
                      AND cr.id_gymnase IN (SELECT id_gymnase
                                            FROM creneau
                                            WHERE creneau.id_equipe = ?)
                      AND m.match_status != 'ARCHIVED'";
        $bindings = array(
            array('type' => 's', 'value' => $computed_date),
            array('type' => 'i', 'value' => $id_equipe),
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
     * @param $team_id
     * @return bool
     * @throws Exception
     */
    public function is_date_blacklisted($computed_date, $team_id = null): bool
    {
        // tested ok
        $bindings = array(
            array('type' => 's', 'value' => $computed_date),
        );
        if ($team_id === null) {
            $sql = "SELECT * 
                FROM blacklist_date 
                WHERE closed_date = STR_TO_DATE(?, '%d/%m/%Y')";
        } else {
            $bindings[] = array('type' => 'i', 'value' => $team_id);
            $sql = "SELECT * 
                FROM blacklist_gymnase 
                WHERE closed_date = STR_TO_DATE(?, '%d/%m/%Y')
                AND id_gymnase IN (
                    SELECT id_gymnase 
                    FROM creneau 
                    WHERE id_equipe = ?
                    AND jour = ELT(WEEKDAY(closed_date) + 2,
                                           'Dimanche',
                                           'Lundi',
                                           'Mardi',
                                           'Mercredi',
                                           'Jeudi',
                                           'Vendredi',
                                           'Samedi'))";
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
            if ($this->is_date_filled($computed_date['computed_date'], $team_dom['id_equipe'])) {
                continue;
            }
            // computed date is not allowed (holiday)
            if ($this->is_date_blacklisted($computed_date['computed_date'])) {
                continue;
            }
            // computed date is not allowed (gymnasium is not available)
            if ($this->is_date_blacklisted($computed_date['computed_date'], $team_dom['id_equipe'])) {
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
     * @return void
     * @throws Exception
     */
    public function insert_matches(array $to_be_inserted_matches,
                                         $code_competition,
                                         $division,
                                         $index_match,
                                         $index_place)
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

}
