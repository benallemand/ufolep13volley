<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../includes/fonctions_inc.php';
require_once __DIR__ . '/../classes/Configuration.php';

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
     * @param null $cc
     * @param null $bcc
     * @param null $attachments
     * @throws Exception
     * @throws phpmailerException
     */
    public function sendEmail($subject, $body, $from, $to, $cc = null, $bcc = null, $attachments = null)
    {
        $mail = new PHPMailer();
        $serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
        switch ($serverName) {
            case 'localhost':
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
        if ($cc !== null) {
            foreach (explode(';', $cc) as $ccAddress) {
                $mail->addCC($ccAddress);
            }
        }
        if ($bcc !== null) {
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
     * @throws phpmailerException
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
     * @throws phpmailerException
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
     * @throws phpmailerException
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
     * @throws phpmailerException
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
     * @throws phpmailerException
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
     * @throws phpmailerException
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
     * @throws phpmailerException
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

        $url_host = filter_input(INPUT_SERVER, 'HTTP_HOST');
        $match_manager->download(array(
            'id' => $id_match,
            'keep_file' => "true"
        ));
        $this->sendEmail(
            "[UFOLEP13VOLLEY]Feuilles du match $code_match reçues",
            $message,
            'no-reply@ufolep13volley.org',
            $to,
            null,
            null,
            array(
                "$code_match.zip"
            ));
        unlink("$code_match.zip");
    }
}