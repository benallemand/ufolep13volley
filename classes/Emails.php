<?php

require_once '../includes/fonctions_inc.php';
require_once '../classes/Configuration.php';
require_once '../libs/php/PHPMailer/PHPMailerAutoload.php';

class Emails
{

    public function sendEmail($subject, $body, $from, $to, $cc = null, $bcc = null, $attachments = null)
    {
        $mail = new PHPMailer();
        $serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
        switch ($serverName) {
            case 'localhost':
                $mail->isSMTP();
                break;
            default:
                $mail->isSendmail();
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
            foreach ($attachments as $fileName => $stringAttachment) {
                $mail->addStringAttachment($stringAttachment, $fileName, 'base64', 'text/plain');
            }
        }
        $mail->WordWrap = 50;
        $mail->Subject = $subject;
        $mail->Body = $mail->msgHTML($body);
        if (!$mail->send()) {
            throw new Exception("Send email error : " . $mail->ErrorInfo);
        }
    }


    public function sendMailNewUser($email, $login, $password, $idTeam)
    {
        $teamName = getTeamName($idTeam);

        $message = file_get_contents('../templates/emails/sendMailNewUser.fr.html');
        $message = str_replace('%login%', $login, $message);
        $message = str_replace('%password%', $password, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->sendEmail("[UFOLEP13VOLLEY]Identifiants de connexion", $message, 'no-reply@ufolep13volley.org', $email);
    }

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

    public function sendMailRefuseReport($code_match, $id_team)
    {
        $teamName = getTeamName($id_team);
        $teams_emails = getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailRefuseReport.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);
        $message = str_replace('%team_name%', $teamName, $message);

        $this->sendEmail("[UFOLEP13VOLLEY]Refus de report de $teamName pour le match $code_match", $message, 'no-reply@ufolep13volley.org', $to);
    }

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

    public function sendMailRefuseReportAdmin($code_match)
    {
        $teams_emails = getTeamsEmailsFromMatchReport($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailRefuseReportAdmin.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);

        $this->sendEmail("[UFOLEP13VOLLEY]Refus de report par la commission pour le match $code_match", $message, 'no-reply@ufolep13volley.org', $to);
    }

    public function sendMailSheetReceived($code_match)
    {
        $teams_emails = getTeamsEmailsFromMatch($code_match);
        $to = implode(';', $teams_emails);

        $message = file_get_contents('../templates/emails/sendMailSheetReceived.fr.html');
        $message = str_replace('%code_match%', $code_match, $message);

        $this->sendEmail("[UFOLEP13VOLLEY]Feuilles du match $code_match reçues", $message, 'no-reply@ufolep13volley.org', $to);
    }
}