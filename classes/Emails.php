<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../includes/fonctions_inc.php';
require_once __DIR__ . '/../classes/Configuration.php';
require_once __DIR__ . '/../classes/SqlManager.php';

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

class Emails
{
    /**
     * @param $subject
     * @param $body
     * @param $from
     * @param $to
     * @param string $cc
     * @param string $bcc
     * @param array $file_ids
     * @return int|string
     * @throws \Exception
     */
    public function insert_email($subject,
                                 $body,
                                 $from,
                                 $to,
                                 $cc = "",
                                 $bcc = "",
                                 $file_ids = array())
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
        $sql_manager = new SqlManager();
        $bindings = array();
        $bindings[] = array(
            'type' => 's',
            'value' => $from
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
        $email_id = $sql_manager->execute($sql, $bindings);
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
            $sql_manager->execute($sql, $bindings);
        }
        return $email_id;
    }

    /**
     * @param $subject
     * @param $body
     * @param $from
     * @param $to
     * @param null $cc
     * @param null $bcc
     * @param null $attachments
     * @throws Exception
     */
    public function sendEmail($subject, $body, $from, $to, $cc = null, $bcc = null, $attachments = null)
    {
        if (empty($to)) {
            error_log("Email does not have any TO, subject: $subject, body: $body");
            return;
        }
        $mail = new PHPMailer();
        $serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
        switch ($serverName) {
            case 'localhost':
                print_r($subject);
                print_r($body);
                print_r($from);
                print_r($to);
                print_r($cc);
                print_r($bcc);
                print_r($attachments);
                return;
            default:
                $mail->isSMTP();
                break;
        }
        $mail->CharSet = "UTF-8";
        $mail->Host = Configuration::MAIL_HOST;
        $mail->SMTPAuth = Configuration::MAIL_SMTPAUTH;
        $mail->Username = Configuration::MAIL_USERNAME;
        $mail->Password = Configuration::MAIL_PASSWORD;
        $mail->SMTPSecure = Configuration::MAIL_SMTPSECURE;
        $mail->Port = Configuration::MAIL_PORT;
        $mail->setFrom($from);
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
        $teamName = getTeamName($idTeam);

        $message = file_get_contents('../templates/emails/sendMailNewUser.fr.html');
        $message = str_replace('%login%', $login, $message);
        $message = str_replace('%password%', $password, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->sendEmail("[UFOLEP13VOLLEY]Identifiants de connexion", $message, 'no-reply@ufolep13volley.org', $email);
    }

    /**
     * @param $code_match
     * @param $reason
     * @param $id_team
     * @throws Exception
     */
    public function sendMailAskForReport($code_match, $reason, $id_team)
    {
        $teamName = getTeamName($id_team);
        $teams_emails = getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailAskForReport.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        $message = str_replace('%reason%', $reason, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->sendEmail("[UFOLEP13VOLLEY]Demande de report de $teamName pour le match $code_match", $message, 'no-reply@ufolep13volley.org', $to);
    }

    /**
     * @param $code_match
     * @param $report_date
     * @param $id_team
     * @throws Exception
     */
    public function sendMailGiveReportDate($code_match, $report_date, $id_team)
    {
        $teamName = getTeamName($id_team);
        $teams_emails = getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailGiveReportDate.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        $message = str_replace('%report_date%', $report_date, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->sendEmail("[UFOLEP13VOLLEY]Transmission de date de report de $teamName pour le match $code_match", $message, 'no-reply@ufolep13volley.org', $to);
    }

    /**
     * @param $code_match
     * @param $reason
     * @param $id_team
     * @throws Exception
     */
    public function sendMailRefuseReport($code_match, $reason, $id_team)
    {
        $teamName = getTeamName($id_team);
        $teams_emails = getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailRefuseReport.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        $message = str_replace('%reason%', $reason, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->sendEmail("[UFOLEP13VOLLEY]Refus de report de $teamName pour le match $code_match", $message, 'no-reply@ufolep13volley.org', $to);
    }

    /**
     * @param $code_match
     * @param $id_team
     * @throws Exception
     */
    public function sendMailAcceptReport($code_match, $id_team)
    {
        $teamName = getTeamName($id_team);
        $teams_emails = getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailAcceptReport.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->sendEmail("[UFOLEP13VOLLEY]Report accepté par $teamName pour le match $code_match", $message, 'no-reply@ufolep13volley.org', $to);
    }

    /**
     * @param $code_match
     * @throws Exception
     */
    public function sendMailRefuseReportAdmin($code_match)
    {
        $teams_emails = getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailRefuseReportAdmin.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);

        $this->sendEmail("[UFOLEP13VOLLEY]Refus de report par la commission pour le match $code_match", $message, 'no-reply@ufolep13volley.org', $to);
    }

    /**
     * @param $code_match
     * @throws Exception
     * @throws \Exception
     */
    public function sendMailSheetReceived($code_match)
    {
        $teams_emails = getTeamsEmailsFromMatch($code_match);
        require_once __DIR__ . "/MatchManager.php";
        $match_manager = new MatchManager();
        $matches = $match_manager->getMatches("m.code_match = '$code_match'");
        $id_match = $matches[0]['id_match'];
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailSheetReceived.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);

        $match_manager = new MatchManager();
        $match_files = $match_manager->getMatchFiles($id_match);
        $attached_files = array();
        foreach ($match_files as $match_file) {
            $attached_files[] = "../" . $match_file['path_file'];
        }
        $this->sendEmail(
            "[UFOLEP13VOLLEY]Feuilles du match $code_match reçues",
            $message,
            'no-reply@ufolep13volley.org',
            $to,
            null,
            null,
            $attached_files
        );
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete_email(int $id)
    {
        $sql = "DELETE FROM emails WHERE id = ?";
        $sql_manager = new SqlManager();
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $id
        );
        $sql_manager->execute($sql, $bindings);
    }

    /**
     * @param int $email_id
     * @return array
     * @throws \Exception
     */
    public function get_email_files(int $email_id)
    {
        $sql = "SELECT * FROM emails_files WHERE id_email = $email_id";
        $sql_manager = new SqlManager();
        return $sql_manager->getResults($sql);
    }

    /**
     * @param int $player_id
     * @return int|string
     * @throws \Exception
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
        $message = file_get_contents('../templates/emails/notify_activated_player.fr.html');
        $message = str_replace('%full_name%', $player['full_name'], $message);
        $message = str_replace('%date_homologation%', $player['date_homologation'], $message);
        $message = str_replace('%teams_list%', $player['teams_list'], $message);
        return $this->insert_email(
            "[UFOLEP13VOLLEY]La licence de " . $player['full_name'] . " a été validée par la commission",
            $message,
            "no-reply@ufolep13volley.org",
            implode(';', $player_related_emails),
            "contact@ufolep13volley.org");
    }

    /**
     * @param string $where
     * @return array
     * @throws \Exception
     */
    public function get_emails(string $where = "1=1")
    {
        $sql = "SELECT * FROM emails WHERE $where";
        $sql_manager = new SqlManager();
        return $sql_manager->getResults($sql);
    }

    /**
     * @param $id
     * @param string $status
     * @throws \Exception
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
        $sql_manager = new SqlManager();
        $sql_manager->execute($sql, $bindings);
    }
}