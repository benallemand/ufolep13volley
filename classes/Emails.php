<?php
require_once __DIR__ . '/../vendor/autoload.php';

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

    private Configuration $configuration;

    /**
     * Emails constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'emails';
        $this->file_manager = new Files();
        $this->match = new MatchMgr();
        $this->team = new Team();
        $this->configuration = new Configuration();
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
                                 array $file_ids = array()): int|string
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
            'value' => $this->configuration->mail_username
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
    public function sendEmail($subject, $body, $to, $cc = null, $bcc = null, $attachments = null): void
    {
        if (empty($to)) {
            error_log("Email does not have any TO, subject: $subject, body: $body");
            return;
        }
        $mail = new PHPMailer();
        $this->configuration->mail_function === 'mail' ? $mail->isMail() : $mail->isSMTP();
        $mail->CharSet = "UTF-8";
        $mail->Host = $this->configuration->mail_host;
        $mail->SMTPAuth = $this->configuration->mail_smtpauth;
        $mail->Username = $this->configuration->mail_username;
        // force email recipients to me, as this is not prod server
        if ($mail->Username === 'benallemand@gmail.com') {
            $to = "benallemand@gmail.com";
            $cc = "benallemand@gmail.com";
            $bcc = "benallemand@gmail.com";
        }
        $mail->Password = $this->configuration->mail_password;
        $mail->SMTPSecure = $this->configuration->mail_smtpsecure;
        $mail->Port = $this->configuration->mail_port;
        $mail->setFrom($this->configuration->mail_username, 'UFOLEP 13 Volleyball');
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
        if (is_array($attachments)) {
            foreach ($attachments as $fileName) {
                $mail->addAttachment($fileName, basename($fileName));
            }
        }
        $mail->addReplyTo('contact@ufolep13volley.org', 'UFOLEP 13 Volleyball');
        $mail->WordWrap = 50;
        $mail->Subject = $subject;
        $mail->Body = $mail->msgHTML($body);
        if ($mail->Username === 'benallemand@gmail.com') {
            return;
        }
        if (!$mail->send()) {
            throw new Exception("Erreur dans l'envoi de mail : " . $mail->ErrorInfo);
        }
    }


    /**
     * @param $email
     * @param $login
     * @param $password
     * @throws Exception
     */
    public function sendMailNewUser($email, $login, $password): void
    {
        $message = file_get_contents(__DIR__ . '/../templates/emails/sendMailNewUser.fr.html');
        $message = str_replace('%login%', $login, $message);
        $message = str_replace('%password%', $password ?: '', $message);
        $this->insert_email(
            "[UFOLEP13VOLLEY]Identifiants de connexion",
            $message,
            $email);
    }

    /**
     * @throws Exception
     */
    public function send_reset_password($email, $login, $url): void
    {
        $user_teams = $this->team->get_user_team_by_user($login);
        $team_names = array();
        foreach ($user_teams as $user_team) {
            $team_names[] = $this->team->getTeamName($user_team['team_id']);
        }

        $message = file_get_contents('../templates/emails/send_reset_password.fr.html');
        $message = str_replace('%login%', $login, $message);
        $message = str_replace('%team_name%', implode(',', $team_names), $message);
        $message = str_replace('%url%', $url, $message);

        $this->insert_email(
            "[UFOLEP13VOLLEY]Réinitialisation de mot de passe",
            $message,
            $email);
        $this->send_pending_emails();
    }

    /**
     * @param $code_match
     * @param $reason
     * @param $id_team
     * @throws Exception
     */
    public function sendMailAskForReport($code_match, $reason, $id_team): void
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
    public function sendMailGiveReportDate($code_match, $report_date, $id_team): void
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
    public function sendMailRefuseReport($code_match, $reason, $id_team): void
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
    public function sendMailAcceptReport($code_match, $id_team): void
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
     * @param $reason
     * @throws Exception
     */
    public function sendMailRefuseReportAdmin($code_match, $reason): void
    {
        $teams_emails = $this->match->getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailRefuseReportAdmin.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        $message = str_replace('%reason%', $reason, $message);

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
    public function team_sheet_to_be_signed($code_match): void
    {
        $match = $this->match->get_match_by_code_match($code_match);
        if ($match['is_sign_team_dom'] == 1) {
            $to = $match['email_ext'];
            $cc = $match['email_dom'];
            $team_name = $match['equipe_dom'];
        } elseif ($match['is_sign_team_ext'] == 1) {
            $to = $match['email_dom'];
            $cc = $match['email_ext'];
            $team_name = $match['equipe_ext'];
        } else {
            throw new Exception("Les fiches équipes n'ont pas été signées !");
        }
        $url_match = 'https://www.ufolep13volley.org/team_sheets.php?id_match=' . $match['id_match'];
        // insert for sending
        $this->insert_generic_email(
            __DIR__ . '/../templates/emails/team_sheet_to_be_signed.fr.html',
            array(
                'code_match' => $code_match,
                'team_name' => $team_name,
                'url_match' => $url_match,
            ),
            $to,
            $cc
        );
        $this->send_pending_emails();
    }

    /**
     * @param $code_match
     * @throws Exception
     * @throws Exception
     */
    public function match_sheet_to_be_signed($code_match): void
    {
        $match = $this->match->get_match_by_code_match($code_match);
        if ($match['is_sign_match_dom'] == 1) {
            $to = $match['email_ext'];
            $cc = $match['email_dom'];
            $team_name = $match['equipe_dom'];
        } elseif ($match['is_sign_match_ext'] == 1) {
            $to = $match['email_dom'];
            $cc = $match['email_ext'];
            $team_name = $match['equipe_ext'];
        } else {
            throw new Exception("Les feuilles de match n'ont pas été signées !");
        }
        $url_match = 'https://www.ufolep13volley.org/match.php?id_match=' . $match['id_match'];
        // insert for sending
        $this->insert_generic_email(
            __DIR__ . '/../templates/emails/match_sheet_to_be_signed.fr.html',
            array(
                'code_match' => $code_match,
                'team_name' => $team_name,
                'url_match' => $url_match,
            ),
            $to,
            $cc
        );
        $this->send_pending_emails();
    }

    /**
     * @param $code_match
     * @throws Exception
     * @throws Exception
     */
    public function team_sheet_signed($code_match): void
    {
        $match = $this->match->get_match_by_code_match($code_match);
        if ($match['is_sign_team_dom'] == 1 && $match['is_sign_team_ext'] == 1) {
            $to = implode(';', array($match['email_ext'], $match['email_dom']));
        } else {
            throw new Exception("Les fiches équipes n'ont pas été signées par les 2 équipes !");
        }
        $url_match = 'https://www.ufolep13volley.org/match.php?id_match=' . $match['id_match'];
        // insert for sending
        $this->insert_generic_email(
            __DIR__ . '/../templates/emails/team_sheet_signed.fr.html',
            array(
                'code_match' => $code_match,
                'url_match' => $url_match,
            ),
            $to
        );
        $this->send_pending_emails();
    }

    /**
     * @param $code_match
     * @throws Exception
     * @throws Exception
     */
    public function match_sheet_signed($code_match): void
    {
        $match = $this->match->get_match_by_code_match($code_match);
        if ($match['is_sign_match_dom'] == 1) {
            $to = implode(';', array($match['email_ext'], $match['email_dom']));
        } else {
            throw new Exception("Les feuilles de match n'ont pas été signées par les 2 équipes !");
        }
        $url_survey = 'https://www.ufolep13volley.org/survey.php?id_match=' . $match['id_match'];
        // insert for sending
        $this->insert_generic_email(
            __DIR__ . '/../templates/emails/match_sheet_signed.fr.html',
            array(
                'code_match' => $code_match,
                'url_survey' => $url_survey,
            ),
            $to
        );
        $this->send_pending_emails();
    }

    /**
     * @param int $id
     * @throws Exception
     */
    public function delete_email(int $id): void
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
    public function delete_emails($where = "1=1"): void
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
    public function insert_email_notify_activated_player(int $player_id): int|string
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
        $message = str_replace('%teams_list%', $player['teams_list'] === null ? '' : $player['teams_list'], $message);
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
    public function set_email_status($id, string $status): void
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
    public function set_sent_date($id): void
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
    public function insert_email_notify_registration(int $register_id): int|string
    {
        require_once __DIR__ . "/Register.php";
        $manager = new Register();
        $register = $manager->get_register($register_id);
        $message = file_get_contents(__DIR__ . '/../templates/emails/notify_registration.fr.html');
        $params = array(
            'new_team_name',
            'club',
            'competition',
            'old_team',
            'leader_name',
            'leader_first_name',
            'leader_email',
            'leader_phone',
            'court_1',
            'day_court_1',
            'hour_court_1',
            'court_2',
            'day_court_2',
            'hour_court_2',
            'remarks',
        );
        foreach ($params as $param) {
            if (empty($register[$param])) {
                $register[$param] = '';
            }
            $message = str_replace("%$param%", $register[$param], $message);
        }
        return $this->insert_email(
            "[UFOLEP13VOLLEY]L'inscription de l'équipe " . $register['new_team_name'] . " a bien été prise en compte",
            $message,
            implode(';', array($register['leader_email'])),
            "contact@ufolep13volley.org");
    }

    /**
     * @throws Exception|Exception
     */
    public function send_pending_emails(): void
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
            $this->set_email_status($pending_email['id'], 'DONE');
            $this->set_sent_date($pending_email['id']);
        }
    }

    /**
     * @throws Exception|Exception
     */
    public function retry_error_emails(): void
    {
        $sql = file_get_contents(__DIR__ . '/../sql/retry_error_emails.sql');
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
            throw new Exception("Erreur de lecture du modèle HTML !");
        }
        $h1NodeList = $doc->getElementsByTagName('h1');
        if ($h1NodeList->length === 0) {
            throw new Exception("Pas d'en-tête dans le modèle HTML !");
        }
        return $h1NodeList->item(0)->textContent;
    }

    /**
     * @param $template_file_path
     * @param $array_data_to_replace
     * @param $destination_email
     * @param string $cc
     * @param string $bcc
     * @throws Exception
     */
    public function insert_generic_email($template_file_path, $array_data_to_replace, $destination_email, string $cc = "", string $bcc = ""): void
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
                    implode(";", array("benallemand@gmail.com")),
                    $cc,
                    $bcc
                );
                break;
            default:
                $this->insert_email(
                    $subject,
                    $message,
                    $destination_email,
                    $cc,
                    $bcc);
                break;
        }
    }

    /**
     * @throws Exception
     */
    public function insert_email_activity(): void
    {
        $activities = $this->sql_manager->sql_get_last_day_activity();
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
    public function insert_email_matches_not_reported(): void
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
    public function insert_email_next_matches(): void
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
    public function insert_email_players_without_licence_number(): void
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
    public function insert_mail_match_not_fully_signed(): void
    {
        $result = $this->sql_manager->execute(file_get_contents(__DIR__ . '/../sql/match_missing_sheets.sql'));
        if (count($result) == 0) {
            return;
        }
        foreach ($result as $data) {
            $target_mails = array();
            if ($data['is_sign_team_dom'] != 'ok'
                || $data['is_sign_match_dom'] != 'ok'
                || $data['is_survey_filled_dom'] != 'ok') {
                $target_mails[] = $data['email_dom'];
            }
            if ($data['is_sign_team_ext'] != 'ok'
                || $data['is_sign_match_ext'] != 'ok'
                || $data['is_survey_filled_ext'] != 'ok'
            ) {
                $target_mails[] = $data['email_ext'];
            }
            $email = implode(';', $target_mails);
            if (empty($email)) {
                continue;
            }
            $this->insert_generic_email(
                __DIR__ . '/../templates/emails/send_mail_match_not_fully_signed.fr.html',
                array(
                    'code_match' => $data['code_match'],
                    'equipe_dom' => $data['equipe_dom'],
                    'equipe_ext' => $data['equipe_ext'],
                    'is_sign_team_dom' => $data['is_sign_team_dom'],
                    'is_sign_match_dom' => $data['is_sign_match_dom'],
                    'is_survey_filled_dom' => $data['is_survey_filled_dom'],
                    'is_sign_team_ext' => $data['is_sign_team_ext'],
                    'is_sign_match_ext' => $data['is_sign_match_ext'],
                    'is_survey_filled_ext' => $data['is_survey_filled_ext'],
                ),
                $email
            );
        }
    }

    /**
     * @throws Exception
     */
    public function insert_email_team_leaders_without_email(): void
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
    public function insert_email_team_recap(): void
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
    public function insert_email_alert_report(): void
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
    public function insert_email_missing_licences(): void
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

    /**
     * @throws Exception
     */
    public function insert_email_register_not_paid(): void
    {
        $result = $this->sql_manager->execute(file_get_contents(__DIR__ . '/../sql/register_not_paid.sql'));
        if (count($result) == 0) {
            return;
        }
        $destination = array();
        $trs_club_compet_cout = "";
        foreach ($result as $data) {
            if (empty($data['email_club']) && empty($data['emails_equipes'])) {
                continue;
            }
            $destination = array_merge($destination, explode(',', $data['emails_equipes']));
            $destination[] = $data['email_club'];
            $club = $data['club'];
            $compet = $data['competitions'];
            $cout = $data['cout'];
            $trs_club_compet_cout .= "<tr><td>$club</td><td>$compet</td><td>$cout</td></tr>";
        }
        $destination = array_unique($destination);
        $coordonnees_ufolep13 = "UFOLEP13 81 RUE DE LA MAURELLE 13013 MARSEILLE";
        $url_rib = "https://www.ufolep13volley.org/infos_utiles/Media/rib.pdf";
        $this->insert_generic_email(
            __DIR__ . '/../templates/emails/register_not_paid.fr.html',
            array(
                'trs_club_compet_cout' => $trs_club_compet_cout,
                'coordonnees_ufolep13' => $coordonnees_ufolep13,
                'url_rib' => $url_rib,
            ),
            "contact@ufolep13volley.org",
            "",
            implode(';', $destination)
        );
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get_last(): mixed
    {
        $emails = $this->get_emails("id IN (SELECT MAX(id) FROM emails)");
        return $emails[0];
    }

    public function getSql($query = "1=1"): string
    {
        return "SELECT 
                    id,
                    from_email,
                    to_email,
                    cc,
                    bcc,
                    subject,
                    body,
                    DATE_FORMAT(creation_date, '%d/%m/%Y %H:%i:%s') AS creation_date,
                    DATE_FORMAT(sent_date, '%d/%m/%Y %H:%i:%s') AS sent_date,
                    sending_status
                FROM emails
                WHERE $query
                ORDER BY id DESC";
    }
}