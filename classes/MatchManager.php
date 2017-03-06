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
        DATE_FORMAT(j.start_date + INTERVAL FIELD(cr.jour,
                                'Lundi',
                                'Mardi',
                                'Mercredi',
                                'Jeudi',
                                'Vendredi',
                                'Samedi',
                                'Dimanche') - 1  DAY, '%d/%m/%Y') AS date_reception_originale,
        DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS date_reception,
        UNIX_TIMESTAMP(m.date_reception + INTERVAL 23 HOUR + INTERVAL 59 MINUTE)*1000 AS date_reception_raw,
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
        (
        CASE  WHEN (m.code_competition = 'm') THEN CONCAT('d', CONVERT(m.division, UNSIGNED INTEGER), m.code_competition, '-6x6-ufolep13-volley@googlegroups.com')
              WHEN (m.code_competition = 'f') THEN CONCAT('d', CONVERT(m.division, UNSIGNED INTEGER), m.code_competition, '-4x4-ufolep13-volley@googlegroups.com')
              WHEN (m.code_competition = 'mo') THEN CONCAT('d', CONVERT(m.division, UNSIGNED INTEGER), 'mi', '-4x4-ufolep13-volley@googlegroups.com')
              WHEN (m.code_competition = 'c' AND CONVERT(m.division, UNSIGNED INTEGER) IN (1,2,3))  THEN 'p1a3-isoardi-ufolep13-volley@googlegroups.com'
              WHEN (m.code_competition = 'c' AND CONVERT(m.division, UNSIGNED INTEGER) IN (4,5,6))  THEN 'p4a6-isoardi-ufolep13-volley@googlegroups.com'
              WHEN (m.code_competition = 'c' AND CONVERT(m.division, UNSIGNED INTEGER) IN (7,8,9))  THEN 'p7a9-isoardi-ufolep13-volley@googlegroups.com'
              WHEN (m.code_competition = 'c' AND CONVERT(m.division, UNSIGNED INTEGER) IN (10,11))  THEN 'p10et11-isoardi-ufolep13-volley@googlegroups.com'
              WHEN (m.code_competition = 'cf')  THEN 'isoardi-ufolep13-volley@googlegroups.com'
              WHEN (m.code_competition = 'kh')  THEN 'khanna-ufolep13-volley@googlegroups.com'
              WHEN (m.code_competition = 'kf')  THEN 'khanna-ufolep13-volley@googlegroups.com'
              END
        ) AS email_send_sheets,
        CONCAT('Match ', m.code_match, ' : ', e1.nom_equipe, ' contre ', e2.nom_equipe) AS email_send_sheets_subject,
        'Veuillez trouver ci-joint les fiches équipes ainsi que la feuille de match.' AS email_send_sheets_body,
        IF(mf.id_file IS NOT NULL, '1', '0') AS is_file_attached
        FROM matches m 
        JOIN competitions c ON c.code_competition = m.code_competition
        JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
        JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
        JOIN journees j ON j.id=m.id_journee
        LEFT JOIN creneau cr ON cr.id_equipe = e1.id_equipe AND cr.jour = ELT(WEEKDAY(m.date_reception) + 2,
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

    public function getMatch($id_match)
    {
        $results = $this->getMatches("m.id_match = $id_match");
        if (count($results) !== 1) {
            throw new Exception("Error while retrieving data");
        }
        return $results[0];
    }

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

    public function download()
    {
        setlocale(LC_ALL, 'fr_FR.UTF-8');
        $inputs = filter_input_array(INPUT_GET);
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
        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=$archiveFileName");
        header("Content-length: " . filesize($archiveFileName));
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($archiveFileName);
        unlink($archiveFileName);
        exit;
    }

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
}