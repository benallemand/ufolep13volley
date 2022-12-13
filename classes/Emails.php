<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../classes/Configuration.php';
require_once __DIR__ . '/../classes/Files.php';
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/Generic.php';

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

class Emails extends Generic
{
    private Files $file_manager;
    private MatchMgr $match;
    private Team $team;

    /**
     * Emails constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->file_manager = new Files();
        $this->match = new MatchMgr();
        $this->team = new Team();
    }

    /**
     * @param $subject
     * @param string $body
     * @param string $to
     * @param string $cc
     * @param string $bcc
     * @param array $file_ids
     * @return int|string
     * @throws Exception
     */
    public function insert_email($subject,
                                 string $body,
                                 string $to,
                                 string $cc = "",
                                 string $bcc = "",
                                 array $file_ids = array())
    {
        $sql = "INSERT INTO emails (
                    from_email, 
                    to_email, 
                    cc, 
                    bcc, 
                    subject, 
                    body) VALUES (?,
                                  ?, 
                                  ?,
                                  ?,
                                  ?,
                                  ?)";
        $bindings = array();
        $bindings[] = array(
            'type' => 's',
            'value' => Configuration::MAIL_USERNAME
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $to
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $cc
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $bcc
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $subject
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $body
        );
        $email_id = $this->sql_manager->execute($sql, $bindings);
        foreach ($file_ids as $file_id) {
            $bindings = array();
            $bindings[] = array(
                'type' => 'i',
                'value' => $email_id
            );
            $bindings[] = array(
                'type' => 'i',
                'value' => $file_id
            );
            $sql = "INSERT INTO emails_files (id_email, id_file) VALUES (?, ?)";
            $this->sql_manager->execute($sql, $bindings);
        }
        return $email_id;
    }

    /**
     * @param $subject
     * @param $body
     * @param $to
     * @param null $cc
     * @param null $bcc
     * @param null $attachments
     * @throws Exception
     */
    public function sendEmail($subject, $body, $to, $cc = null, $bcc = null, $attachments = null)
    {
        if (empty($to)) {
            error_log("Email does not have any TO, subject: $subject, body: $body");
            return;
        }
        $mail = new PHPMailer();
        $serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
        switch ($serverName) {
            case 'localhost':
                $to = "benallemand@gmail.com";
                $cc = "benallemand@gmail.com";
                $bcc = "benallemand@gmail.com";
                break;
            default:
                break;
        }
        $mail->isMail();
        $mail->CharSet = "UTF-8";
        $mail->Host = Configuration::MAIL_HOST;
        $mail->SMTPAuth = Configuration::MAIL_SMTPAUTH;
        $mail->Username = Configuration::MAIL_USERNAME;
        $mail->Password = Configuration::MAIL_PASSWORD;
        $mail->SMTPSecure = Configuration::MAIL_SMTPSECURE;
        $mail->Port = Configuration::MAIL_PORT;
        $mail->setFrom(Configuration::MAIL_USERNAME);
        foreach (explode(';', $to) as $toAddress) {
            $mail->addAddress($toAddress);
        }
        if (!empty($cc)) {
            foreach (explode(';', $cc) as $ccAddress) {
                $mail->addCC($ccAddress);
            }
        }
        if (!empty($bcc)) {
            foreach (explode(';', $bcc) as $bccAddress) {
                $mail->addBCC($bccAddress);
            }
        }
        $mail->addBCC("benallemand@gmail.com");
        if (is_array($attachments)) {
            foreach ($attachments as $fileName) {
                $mail->addAttachment($fileName, basename($fileName));
            }
        }
        $mail->WordWrap = 50;
        $mail->Subject = $subject;
        $mail->Body = $mail->msgHTML($body);
        if (empty($serverName)) {
            if (Generic::ends_with(filter_input(INPUT_SERVER, 'PHP_SELF'), 'phpunit')) {
                return;
            }
        }
        if (!$mail->send()) {
            throw new Exception("Send email error : " . $mail->ErrorInfo);
        }
    }


    /**
     * @param $email
     * @param $login
     * @param $password
     * @param $idTeam
     * @throws Exception
     */
    public function sendMailNewUser($email, $login, $password, $idTeam)
    {
        $teamName = $this->team->getTeamName($idTeam);

        $message = file_get_contents('../templates/emails/sendMailNewUser.fr.html');
        $message = str_replace('%login%', $login, $message);
        $message = str_replace('%password%', $password, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->insert_email(
            "[UFOLEP13VOLLEY]Identifiants de connexion",
            $message,
            $email);
    }

    /**
     * @param $code_match
     * @param $reason
     * @param $id_team
     * @throws Exception
     */
    public function sendMailAskForReport($code_match, $reason, $id_team)
    {
        $teamName = $this->team->getTeamName($id_team);
        $teams_emails = $this->match->getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailAskForReport.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        $message = str_replace('%reason%', $reason, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->insert_email(
            "[UFOLEP13VOLLEY]Demande de report de $teamName pour le match $code_match",
            $message,
            $to);
    }

    /**
     * @param $code_match
     * @param $report_date
     * @param $id_team
     * @throws Exception
     */
    public function sendMailGiveReportDate($code_match, $report_date, $id_team)
    {
        $teamName = $this->team->getTeamName($id_team);
        $teams_emails = $this->match->getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailGiveReportDate.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        $message = str_replace('%report_date%', $report_date, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->insert_email(
            "[UFOLEP13VOLLEY]Transmission de date de report de $teamName pour le match $code_match",
            $message,
            $to);
    }

    /**
     * @param $code_match
     * @param $reason
     * @param $id_team
     * @throws Exception
     */
    public function sendMailRefuseReport($code_match, $reason, $id_team)
    {
        $teamName = $this->team->getTeamName($id_team);
        $teams_emails = $this->match->getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailRefuseReport.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        $message = str_replace('%reason%', $reason, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->insert_email(
            "[UFOLEP13VOLLEY]Refus de report de $teamName pour le match $code_match",
            $message,
            $to);
    }

    /**
     * @param $code_match
     * @param $id_team
     * @throws Exception
     */
    public function sendMailAcceptReport($code_match, $id_team)
    {
        $teamName = $this->team->getTeamName($id_team);
        $teams_emails = $this->match->getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailAcceptReport.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->insert_email(
            "[UFOLEP13VOLLEY]Report accepté par $teamName pour le match $code_match",
            $message,
            $to);
    }

    /**
     * @param $code_match
     * @throws Exception
     */
    public function sendMailRefuseReportAdmin($code_match)
    {
        $teams_emails = $this->match->getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailRefuseReportAdmin.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);

        $this->insert_email(
            "[UFOLEP13VOLLEY]Refus de report par la commission pour le match $code_match",
            $message,
            $to);
    }

    /**
     * @param $code_match
     * @throws Exception
     * @throws Exception
     */
    public function sendMailSheetReceived($code_match)
    {
        $teams_emails = $this->match->getTeamsEmailsFromMatch($code_match);
        require_once __DIR__ . "/MatchMgr.php";
        $to = implode(';', $teams_emails);
        // fill code match
        $message = file_get_contents('../templates/emails/sendMailSheetReceived.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        // fill files
        $files_paths_html = '';
        $matches = $this->match->get_matches("m.code_match = '$code_match'");
        $files_paths = $matches[0]['files_paths'];
        $root = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        if (!empty($files_paths)) {
            $files_paths_list = explode('|', $files_paths);
            foreach ($files_paths_list as $file_path) {
                $files_paths_html .= "<a href='$root/rest/action.php/files/download_match_file?file_path=$file_path' target='_blank'>" . basename($file_path) . "</a><br/>" . PHP_EOL;
            }
        }
        $message = str_replace('%files_paths%', $files_paths_html, $message);
        // insert for sending
        $this->insert_email(
            "[UFOLEP13VOLLEY]Feuilles du match $code_match reçues",
            $message,
            $to
        );
    }

    /**
     * @param int $id
     * @throws Exception
     */
    public function delete_email(int $id)
    {
        $sql = "DELETE FROM emails WHERE id = ?";
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
    public function delete_emails($where = "1=1")
    {
        $sql = "DELETE FROM emails 
                WHERE $where";
        $bindings = array();
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param int $email_id
     * @return array
     * @throws Exception
     */
    public function get_email_files(int $email_id): array
    {
        $sql = "SELECT * FROM emails_files WHERE id_email = $email_id";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param int $player_id
     * @return int|string
     * @throws Exception
     */
    public function insert_email_notify_activated_player(int $player_id)
    {
        require_once __DIR__ . "/Players.php";
        $players_manager = new Players();
        $player = $players_manager->get_player($player_id);
        $player_related_emails = $players_manager->get_related_emails($player_id);
        if (count($player_related_emails) === 0) {
            return 0;
        }
        $message = file_get_contents(__DIR__ . '/../templates/emails/notify_activated_player.fr.html');
        $message = str_replace('%full_name%', $player['full_name'], $message);
        $message = str_replace('%date_homologation%', $player['date_homologation'], $message);
        $message = str_replace('%teams_list%', $player['teams_list'], $message);
        return $this->insert_email(
            "[UFOLEP13VOLLEY]La licence de " . $player['full_name'] . " a été validée par la commission",
            $message,
            implode(';', $player_related_emails),
            "contact@ufolep13volley.org");
    }

    /**
     * @param string $where
     * @return array
     * @throws Exception
     */
    public function get_emails(string $where = "1=1"): array
    {
        $sql = "SELECT * FROM emails WHERE $where";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $id
     * @param string $status
     * @throws Exception
     */
    public function set_email_status($id, string $status)
    {
        $sql = "UPDATE emails SET sending_status = ? WHERE id = ?";
        $bindings = array();
        $bindings[] = array(
            'type' => 's',
            'value' => $status
        );
        $bindings[] = array(
            'type' => 'i',
            'value' => $id
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $id
     * @throws Exception
     */
    public function set_sent_date($id)
    {
        $sql = "UPDATE emails SET sent_date = NOW() WHERE id = ?";
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $id
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param int $register_id
     * @return int|string
     * @throws Exception|Exception
     */
    public function insert_email_notify_registration(int $register_id)
    {
        require_once __DIR__ . "/Register.php";
        $manager = new Register();
        $register = $manager->get_register($register_id);
        $message = file_get_contents('../templates/emails/notify_registration.fr.html');
        $message = str_replace('%new_team_name%', $register['new_team_name'], $message);
        $message = str_replace('%club%', $register['club'], $message);
        $message = str_replace('%competition%', $register['competition'], $message);
        $message = str_replace('%old_team%', $register['old_team'], $message);
        $message = str_replace('%leader_name%', $register['leader_name'], $message);
        $message = str_replace('%leader_first_name%', $register['leader_first_name'], $message);
        $message = str_replace('%leader_email%', $register['leader_email'], $message);
        $message = str_replace('%leader_phone%', $register['leader_phone'], $message);
        $message = str_replace('%court_1%', $register['court_1'], $message);
        $message = str_replace('%day_court_1%', $register['day_court_1'], $message);
        $message = str_replace('%hour_court_1%', $register['hour_court_1'], $message);
        $message = str_replace('%court_2%', $register['court_2'], $message);
        $message = str_replace('%day_court_2%', $register['day_court_2'], $message);
        $message = str_replace('%hour_court_2%', $register['hour_court_2'], $message);
        $message = str_replace('%remarks%', $register['remarks'], $message);
        return $this->insert_email(
            "[UFOLEP13VOLLEY]L'inscription de l'équipe " . $register['new_team_name'] . " a bien été prise en compte",
            $message,
            implode(';', array($register['leader_email'])),
            "contact@ufolep13volley.org");
    }

    /**
     * @throws Exception|Exception
     */
    public function send_pending_emails()
    {
        $pending_emails = $this->get_emails("sending_status = 'TO_DO' AND creation_date > CURDATE() - INTERVAL 10 DAY LIMIT 50");
        foreach ($pending_emails as $pending_email) {
            $email_files = $this->get_email_files($pending_email['id']);
            $attachments = array();
            foreach ($email_files as $email_file) {
                $file = $this->file_manager->get_by_id($email_file['id_file']);
                $attachments[] = __DIR__ . "/../" . $file['path_file'];
            }
            try {
                $this->sendEmail(
                    $pending_email['subject'],
                    $pending_email['body'],
                    $pending_email['to_email'],
                    $pending_email['cc'],
                    $pending_email['bcc'],
                    $attachments
                );
            } catch (Exception $exception) {
                print_r("ERROR");
                $this->set_email_status($pending_email['id'], 'ERROR');
                print_r($exception->getMessage());
                continue;
            }
            print_r("DONE");
            $this->set_email_status($pending_email['id'], 'DONE');
            $this->set_sent_date($pending_email['id']);
        }
    }

    /**
     * @throws Exception|Exception
     */
    public function retry_error_emails()
    {
        $sql = "UPDATE emails 
                SET sending_status = 'TO_DO' 
                WHERE sending_status = 'ERROR'";
        $this->sql_manager->execute($sql);
    }

    /**
     * @param $message
     * @return string
     * @throws Exception
     */
    private function getH1FromHtmlString($message): string
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
    public function insert_generic_email($template_file_path, $array_data_to_replace, $destination_email)
    {
        $message = file_get_contents($template_file_path);
        foreach ($array_data_to_replace as $data_to_replace_key => $data_to_replace_value) {
            $message = str_replace(
                "%$data_to_replace_key%",
                empty($data_to_replace_value) ? '' : $data_to_replace_value,
                $message);
        }
        $subject = "[UFOLEP13VOLLEY] " . $this->getH1FromHtmlString($message);
        $serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
        switch ($serverName) {
            case 'localhost':
                $this->insert_email(
                    $subject,
                    $message,
                    implode(";", array("benallemand@gmail.com"))
                );
                break;
            default:
                $this->insert_email(
                    $subject,
                    $message,
                    $destination_email);
                break;
        }
    }

    /**
     * @throws Exception
     */
    public function insert_email_activity()
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
        $this->insert_generic_email(
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
    public function insert_email_matches_not_reported()
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
            if (empty($email)) {
                continue;
            }
            $this->insert_generic_email(
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
    public function insert_email_next_matches()
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
            $email = $team_emails[0]['email'];
            if (empty($email)) {
                continue;
            }
            $this->insert_generic_email(
                __DIR__ . '/../templates/emails/sendMailNextMatches.fr.html',
                array(
                    'next_matches' => $html_next_matches
                ),
                $email
            );
        }
    }

    /**
     * @throws Exception
     */
    public function insert_email_players_without_licence_number()
    {
        $players_without_licence_number = $this->sql_manager->sql_get_players_without_licence_number();
        if (count($players_without_licence_number) == 0) {
            return;
        }
        foreach ($players_without_licence_number as $players_without_licence_number_per_leader) {
            $email = $players_without_licence_number_per_leader['responsable'];
            if (empty($email)) {
                continue;
            }
            $this->insert_generic_email(
                __DIR__ . '/../templates/emails/sendMailPlayersWithoutLicenceNumber.fr.html',
                array(
                    'joueurs' => $players_without_licence_number_per_leader['joueurs'],
                    'club' => $players_without_licence_number_per_leader['club'],
                    'equipe' => $players_without_licence_number_per_leader['equipe'],
                    'responsable' => $players_without_licence_number_per_leader['responsable']
                ),
                $email
            );
        }
    }

    /**
     * @throws Exception
     */
    public function insert_email_team_leaders_without_email()
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
        $this->insert_generic_email(
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
    public function insert_email_team_recap()
    {
        $team_recaps = $this->sql_manager->sql_get_team_recaps();
        if (count($team_recaps) == 0) {
            return;
        }
        foreach ($team_recaps as $team_recap) {
            $email = $team_recap['club_email'];
            if (empty($email)) {
                continue;
            }
            $this->insert_generic_email(
                __DIR__ . '/../templates/emails/sendMailTeamRecap.fr.html',
                array(
                    'team_name' => $team_recap['team_name'],
                    'team_leader' => $team_recap['team_leader'],
                    'championship_name' => $team_recap['championship_name'],
                    'division' => $team_recap['division'],
                    'creneaux' => $team_recap['creneaux'],
                ),
                $email
            );
        }
    }

    /**
     * @throws Exception
     */
    public function insert_email_alert_report()
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
            if (empty($email)) {
                continue;
            }
            $this->insert_generic_email(
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
    public function insert_email_missing_licences()
    {
        $teams_with_missing_licences = $this->sql_manager->get_teams_with_missing_licences();
        if (count($teams_with_missing_licences) == 0) {
            return;
        }
        foreach ($teams_with_missing_licences as $teams_with_missing_licence) {
            $email = implode(';', array($teams_with_missing_licence['responsable'], $teams_with_missing_licence['email']));
            if (empty($email)) {
                continue;
            }
            $this->insert_generic_email(
                __DIR__ . '/../templates/emails/sendMailTeamWithMissingLicences.fr.html',
                array(
                    'joueurs' => $teams_with_missing_licence['joueurs'],
                    'club' => $teams_with_missing_licence['club'],
                    'responsable' => $teams_with_missing_licence['responsable'],
                    'equipe' => $teams_with_missing_licence['equipe'],
                    'email' => $teams_with_missing_licence['email'],
                ),
                $email
            );
        }
    }
}