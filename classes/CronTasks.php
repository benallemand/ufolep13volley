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
                $this->email_manager->sendEmail(
                    $subject,
                    $message,
                    'no-reply@ufolep13volley.org',
                    implode(";", array("benallemand@gmail.com"))
                );
                break;
            default:
                $this->email_manager->sendEmail(
                    $subject,
                    $message,
                    'no-reply@ufolep13volley.org',
                    $destination_email);
                break;
        }
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
            '../templates/emails/sendMailActivity.fr.html',
            array(
                'activity' => $string_activities
            ),
            implode(";", array(
                "philipvolley@free.fr"
            ))
        );
    }

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
                '../templates/emails/sendMailMatchNotReported.fr.html',
                array(
                    'equipe_reception' => $match_not_reported['equipe_reception'],
                    'equipe_visiteur' => $match_not_reported['equipe_visiteur'],
                    'date_reception' => $match_not_reported['date_reception']
                ),
                $email
            );
        }
    }

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
            $team_email = $this->sql_manager->sql_get_email_from_team_id($team['team_id']);
            $this->sendGenericEmail(
                '../templates/emails/sendMailNextMatches.fr.html',
                array(
                    'next_matches' => $next_matches
                ),
                $team_email['email']
            );
        }
    }

    public function sendMailPlayersWithoutLicenceNumber()
    {
        $players_without_licence_number = $this->sql_manager->sql_get_players_without_licence_number();
        if (count($players_without_licence_number) == 0) {
            return;
        }
        foreach ($players_without_licence_number as $players_without_licence_number_per_leader) {
            $this->sendGenericEmail(
                '../templates/emails/sendMailPlayersWithoutLicenceNumber.fr.html',
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
            '../templates/emails/sendMailTeamLeadersWithoutEmail.fr.html',
            array(
                'team_leaders_without_email' => $string_team_leaders_without_email
            ),
            'laurent.gorlier@ufolep13volley.org'
        );
    }

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
                '../templates/emails/sendMailAlertReport.fr.html',
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
}