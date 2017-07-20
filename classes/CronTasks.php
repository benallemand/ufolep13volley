<?php

require_once '../classes/Emails.php';
require_once '../classes/SqlManager.php';

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

    private function sendGenericEmail($template_file_path, $array_data_to_replace, $destination_email)
    {
        $message = file_get_contents($template_file_path);
        foreach ($array_data_to_replace as $data_to_replace_key => $data_to_replace_value) {
            $message = str_replace("%$data_to_replace_key%", $data_to_replace_value, $message);
        }
        $subject = $this->getH1FromHtmlString($message);
        $this->email_manager->sendEmail($subject, $message, 'no-reply@ufolep13volley.org', $destination_email);
    }

    public function sendMailAccountRecap()
    {
        $accounts = $this->sql_manager->sql_get_accounts();
        foreach ($accounts as $current_data) {
            $this->sendGenericEmail(
                '../templates/emails/sendMailAccountRecap.fr.html',
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

    public function sendMailActivity()
    {
        $activities = $this->sql_manager->sql_get_activity();
        if(count($activities) == 0) {
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
            '../templates/emails/sendMailActivity.fr.html',
            array(
                'activity' => $string_activities
            ),
            implode(";", array(
                'benallemand@gmail.com',
                'philipvolley@free.fr'
            ))
        );
    }

    public function sendMailMatchNotReported($email, $equipe_reception, $equipe_visiteur, $date_reception)
    {
        $this->sendGenericEmail(
            '../templates/emails/sendMailMatchNotReported.fr.html',
            array(
                'equipe_reception' => $equipe_reception,
                'equipe_visiteur' => $equipe_visiteur,
                'date_reception' => $date_reception
            ),
            $email
        );
    }

    public function sendMailNextMatches($email, $next_matches)
    {
        $this->sendGenericEmail(
            '../templates/emails/sendMailNextMatches.fr.html',
            array(
                'next_matches' => $next_matches
            ),
            $email
        );
    }

    public function sendMailPlayersWithoutLicenceNumber($email, $joueurs, $club, $equipe, $responsable)
    {
        $this->sendGenericEmail(
            '../templates/emails/sendMailPlayersWithoutLicenceNumber.fr.html',
            array(
                'email' => $email,
                'joueurs' => $joueurs,
                'club' => $club,
                'equipe' => $equipe,
                'responsable' => $responsable
            ),
            $email
        );
    }

    public function sendMailTeamLeadersWithoutEmail($email, $team_leaders_without_email)
    {
        $this->sendGenericEmail(
            '../templates/emails/sendMailTeamLeadersWithoutEmail.fr.html',
            array(
                'team_leaders_without_email' => $team_leaders_without_email
            ),
            $email
        );
    }


}