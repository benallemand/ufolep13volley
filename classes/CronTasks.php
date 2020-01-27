<?php

require_once __DIR__ . '/../classes/Emails.php';
require_once __DIR__ . '/../classes/SqlManager.php';

class CronTasks
{
    private $email_manager;
    private $sql_manager;

    /**
     * CronTasks constructor.
     */
    public function __construct()
    {
        $this->email_manager = new Emails();
        $this->sql_manager = new SqlManager();
    }


    /**
     * @param $message
     * @return string
     * @throws Exception
     */
    private function getH1FromHtmlString($message)
    {
        $doc = new DomDocument;
        $doc->validateOnParse = true;
        if (!$doc->loadHTML($message)) {
            throw new Exception("Error while parsing HTML template file");
        }
        $h1NodeList = $doc->getElementsByTagName('h1');
        if ($h1NodeList->length === 0) {
            throw new Exception("Header not found in template");
        }
        return $h1NodeList->item(0)->textContent;
    }

    /**
     * @param $template_file_path
     * @param $array_data_to_replace
     * @param $destination_email
     * @throws Exception
     */
    public function sendGenericEmail($template_file_path, $array_data_to_replace, $destination_email)
    {
        $message = file_get_contents($template_file_path);
        foreach ($array_data_to_replace as $data_to_replace_key => $data_to_replace_value) {
            $message = str_replace("%$data_to_replace_key%", $data_to_replace_value, $message);
        }
        $subject = "[UFOLEP13VOLLEY] " . $this->getH1FromHtmlString($message);
        $serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
        switch ($serverName) {
            case 'localhost':
                $this->email_manager->insert_email(
                    $subject,
                    $message,
                    implode(";", array("benallemand@gmail.com"))
                );
                break;
            default:
                $this->email_manager->insert_email(
                    $subject,
                    $message,
                    $destination_email);
                break;
        }
    }

    /**
     * @throws Exception
     */
    public function sendMailAccountRecap()
    {
        $accounts = $this->sql_manager->sql_get_accounts();
        foreach ($accounts as $current_data) {
            $this->sendGenericEmail(
                __DIR__ . '/../templates/emails/sendMailAccountRecap.fr.html',
                array(
                    'email' => $current_data['email'],
                    'login' => $current_data['login'],
                    'password' => $current_data['password'],
                    'team' => $current_data['nom_equipe'],
                    'competition' => $current_data['competition']
                ),
                $current_data['email']
            );
        }
    }

    /**
     * @throws Exception
     */
    public function sendMailActivity()
    {
        $activities = $this->sql_manager->sql_get_activity();
        if (count($activities) == 0) {
            return;
        }
        $string_activities = "";
        foreach ($activities as $activity) {
            $string_activities .= "<tr>
            <td>" . $activity['date'] . "</td>
            <td>" . $activity['nom_equipe'] . "</td>
            <td>" . $activity['competition'] . "</td>
            <td>" . $activity['description'] . "</td>
            <td>" . $activity['utilisateur'] . "</td>
            <td>" . $activity['email_utilisateur'] . "</td>
        </tr>";
        }
        $this->sendGenericEmail(
            __DIR__ . '/../templates/emails/sendMailActivity.fr.html',
            array(
                'activity' => $string_activities
            ),
            implode(";", array(
                "contact@ufolep13volley.org"
            ))
        );
    }

    /**
     * @throws Exception
     */
    public function sendMailMatchesNotReported()
    {
        $matches_not_reported = $this->sql_manager->sql_get_matches_not_reported();
        if (count($matches_not_reported) == 0) {
            return;
        }
        foreach ($matches_not_reported as $match_not_reported) {
            $email = implode(";", array(
                $match_not_reported['responsable_reception'],
                $match_not_reported['responsable_visiteur']
            ));
            $this->sendGenericEmail(
                __DIR__ . '/../templates/emails/sendMailMatchNotReported.fr.html',
                array(
                    'equipe_reception' => $match_not_reported['equipe_reception'],
                    'equipe_visiteur' => $match_not_reported['equipe_visiteur'],
                    'date_reception' => $match_not_reported['date_reception']
                ),
                $email
            );
        }
    }

    /**
     * @throws Exception
     */
    public function sendMailNextMatches()
    {
        $teams = $this->sql_manager->sql_get_ids_team_requesting_next_matches();
        if (count($teams) == 0) {
            return;
        }
        foreach ($teams as $team) {
            $next_matches = $this->sql_manager->sql_get_next_matches_for_team($team['team_id']);
            if (count($next_matches) == 0) {
                continue;
            }
            $html_next_matches = "";
            foreach ($next_matches as $next_match) {
                $html_next_matches .= "<tr>";
                $html_next_matches .= "<td>";
                $html_next_matches .= $next_match['equipe_domicile'];
                $html_next_matches .= "</td>";
                $html_next_matches .= "<td>";
                $html_next_matches .= $next_match['equipe_exterieur'];
                $html_next_matches .= "</td>";
                $html_next_matches .= "<td>";
                $html_next_matches .= $next_match['code_match'];
                $html_next_matches .= "</td>";
                $html_next_matches .= "<td>";
                $html_next_matches .= $next_match['date'];
                $html_next_matches .= "</td>";
                $html_next_matches .= "<td>";
                $html_next_matches .= $next_match['heure'];
                $html_next_matches .= "</td>";
                $html_next_matches .= "<td>";
                $html_next_matches .= $next_match['responsable'];
                $html_next_matches .= "</td>";
                $html_next_matches .= "<td>";
                $html_next_matches .= $next_match['telephone'];
                $html_next_matches .= "</td>";
                $html_next_matches .= "<td>";
                $html_next_matches .= $next_match['email'];
                $html_next_matches .= "</td>";
                $html_next_matches .= "<td>";
                $html_next_matches .= $next_match['creneaux'];
                $html_next_matches .= "</td>";
                $html_next_matches .= "</tr>";
            }
            $team_emails = $this->sql_manager->sql_get_email_from_team_id($team['team_id']);
            if (count($team_emails) == 0) {
                continue;
            }
            $this->sendGenericEmail(
                __DIR__ . '/../templates/emails/sendMailNextMatches.fr.html',
                array(
                    'next_matches' => $html_next_matches
                ),
                $team_emails[0]['email']
            );
        }
    }

    /**
     * @throws Exception
     */
    public function sendMailPlayersWithoutLicenceNumber()
    {
        $players_without_licence_number = $this->sql_manager->sql_get_players_without_licence_number();
        if (count($players_without_licence_number) == 0) {
            return;
        }
        foreach ($players_without_licence_number as $players_without_licence_number_per_leader) {
            $this->sendGenericEmail(
                __DIR__ . '/../templates/emails/sendMailPlayersWithoutLicenceNumber.fr.html',
                array(
                    'joueurs' => $players_without_licence_number_per_leader['joueurs'],
                    'club' => $players_without_licence_number_per_leader['club'],
                    'equipe' => $players_without_licence_number_per_leader['equipe'],
                    'responsable' => $players_without_licence_number_per_leader['responsable']
                ),
                $players_without_licence_number_per_leader['responsable']
            );
        }
    }

    /**
     * @throws Exception
     */
    public function sendMailTeamLeadersWithoutEmail()
    {
        $team_leaders_without_email = $this->sql_manager->sql_get_team_leaders_without_email();
        if (count($team_leaders_without_email) == 0) {
            return;
        }
        $string_team_leaders_without_email = "";
        foreach ($team_leaders_without_email as $team_leader_without_email) {
            $string_team_leaders_without_email .= "<tr>
            <td>" . $team_leader_without_email['prenom'] . "</td>
            <td>" . $team_leader_without_email['nom'] . "</td>
            <td>" . $team_leader_without_email['competition'] . "</td>
            <td>" . $team_leader_without_email['equipe'] . "</td>
        </tr>";
        }
        $this->sendGenericEmail(
            __DIR__ . '/../templates/emails/sendMailTeamLeadersWithoutEmail.fr.html',
            array(
                'team_leaders_without_email' => $string_team_leaders_without_email
            ),
            'contact@ufolep13volley.org'
        );
    }

    /**
     * @throws Exception
     */
    public function sendMailAlertReport()
    {
        $pending_reports = $this->sql_manager->sql_get_pending_reports();
        if (count($pending_reports) == 0) {
            return;
        }
        foreach ($pending_reports as $pending_report) {
            $email = implode(";", array(
                $pending_report['email_home'],
                $pending_report['email_guest']
            ));
            $this->sendGenericEmail(
                __DIR__ . '/../templates/emails/sendMailAlertReport.fr.html',
                array(
                    'match_reference' => $pending_report['match_reference'],
                    'team_home' => $pending_report['team_home'],
                    'team_guest' => $pending_report['team_guest'],
                    'original_match_date' => $pending_report['original_match_date']
                ),
                $email
            );
        }
    }

    /**
     * @throws Exception
     */
    public function cleanupFiles()
    {
        require_once __DIR__ . "/Database.php";
        $db = Database::openDbConnection();
        // Detect files without hash written in database
        $sql_select = "SELECT * FROM files WHERE hash IS NULL";
        $req_select = mysqli_query($db, $sql_select);
        if ($req_select === FALSE) {
            throw new Exception(mysqli_error($db));
        }
        $results_select = array();
        while ($data_select = mysqli_fetch_assoc($req_select)) {
            $results_select[] = $data_select;
        }
        foreach ($results_select as $result_select) {
            $file_path = __DIR__ . "/../" . $result_select['path_file'];
            if (file_exists($file_path)) {
                // compute md5 if file exists and save in database
                $hash = md5_file($file_path);
                $id = $result_select['id'];
                $sql_update = "UPDATE files SET hash = '$hash' WHERE id = $id";
                $req_update = mysqli_query($db, $sql_update);
                if ($req_update === FALSE) {
                    throw new Exception(mysqli_error($db));
                }
            } else {
                // if file does not exist, delete from database
                $id = $result_select['id'];
                $sql_delete = "DELETE FROM files WHERE id = $id";
                $req_delete = mysqli_query($db, $sql_delete);
                if ($req_delete === FALSE) {
                    throw new Exception(mysqli_error($db));
                }
            }
        }
        // clean duplicate files in db
        $sql_clean = "DELETE f1
                       FROM files f1,
                            files f2
                       WHERE f1.id > f2.id
                         AND f1.hash = f2.hash
                         AND f1.hash IS NOT NULL
                         AND f2.hash IS NOT NULL";
        $req_clean = mysqli_query($db, $sql_clean);
        if ($req_clean === FALSE) {
            throw new Exception(mysqli_error($db));
        }
        // list db files
        $sql_select = "SELECT * FROM files";
        $req_select = mysqli_query($db, $sql_select);
        if ($req_select === FALSE) {
            throw new Exception(mysqli_error($db));
        }
        $results_select = array();
        while ($data_select = mysqli_fetch_assoc($req_select)) {
            $results_select[] = $data_select;
        }
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
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws Exception
     */
    public function send_pending_emails()
    {
        $sql = "SELECT * FROM emails WHERE sending_status = 'TO_DO' AND creation_date > CURDATE() - INTERVAL 5 DAY LIMIT 10";
        $pending_emails = $this->sql_manager->getResults($sql);
        foreach ($pending_emails as $pending_email) {
            $sql = "SELECT f.path_file FROM emails_files ef JOIN files f on ef.id_file = f.id WHERE id_email = ?";
            $bindings = array();
            $bindings[] = array(
                'type' => 'i',
                'value' => $pending_email['id']
            );
            $email_files = $this->sql_manager->execute($sql, $bindings);
            $attachments = array();
            foreach ($email_files as $email_file) {
                $attachments[] = "../" . $email_file['path_file'];
            }
            try {
                $this->email_manager->sendEmail(
                    $pending_email['subject'],
                    $pending_email['body'],
                    $pending_email['to_email'],
                    $pending_email['cc'],
                    $pending_email['bcc'],
                    $attachments
                );
            } catch (Exception $exception) {
                print_r("ERROR");
                $this->email_manager->set_email_status($pending_email['id'], 'ERROR');
                continue;
            }
            print_r("DONE");
            $this->email_manager->set_email_status($pending_email['id'], 'DONE');
            $this->email_manager->set_sent_date($pending_email['id']);
        }
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws Exception
     */
    public function retry_error_emails()
    {
        $sql = "UPDATE emails SET sending_status = 'TO_DO' WHERE sending_status = 'ERROR'";
        $this->sql_manager->execute($sql);
    }
}