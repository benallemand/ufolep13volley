<?php
require_once __DIR__ . '/SqlManager.php';
require_once __DIR__ . '/Emails.php';
require_once __DIR__ . '/Team.php';
require_once __DIR__ . '/Players.php';
require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/TimeSlot.php';

class Register extends Generic
{
    private Team $team;
    private Players $player;
    private UserManager $user;
    private TimeSlot $time_slot;

    /**
     * Match constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->team = new Team();
        $this->player = new Players();
        $this->user = new UserManager();
        $this->time_slot = new TimeSlot();
    }

    /**
     * @throws Exception
     */
    public function register(
        $new_team_name,
        $id_club,
        $id_competition,
        $old_team_id,
        $leader_name,
        $leader_first_name,
        $leader_email,
        $leader_phone,
        $id_court_1,
        $day_court_1,
        $hour_court_1,
        $id_court_2,
        $day_court_2,
        $hour_court_2,
        $remarks,
        $division = null,
        $rank_start = null,
        $dirtyFields = null,
        $id = null
    ): void
    {
        $parameters = array(
            'new_team_name' => $new_team_name,
            'id_club' => $id_club,
            'id_competition' => $id_competition,
            'old_team_id' => $old_team_id,
            'leader_name' => $leader_name,
            'leader_first_name' => $leader_first_name,
            'leader_email' => $leader_email,
            'leader_phone' => $leader_phone,
            'id_court_1' => $id_court_1,
            'day_court_1' => $day_court_1,
            'hour_court_1' => $hour_court_1,
            'id_court_2' => $id_court_2,
            'day_court_2' => $day_court_2,
            'hour_court_2' => $hour_court_2,
            'remarks' => $remarks,
            'division' => $division,
            'rank_start' => $rank_start,
            'dirtyFields' => $dirtyFields,
            'id' => $id,
        );
        $bindings = array();
        if (empty($parameters['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " register SET ";
        foreach ($parameters as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'id_club':
                case 'old_team_id':
                case 'id_court_1':
                case 'id_court_2':
                case 'id_competition':
                case 'rank_start':
                    if (empty($value) || $value == 'null') {
                        $sql .= "$key = NULL,";
                    } else {
                        $sql .= "$key = ?,";
                        $bindings[] = array('type' => 'i', 'value' => $value);
                    }
                    break;
                default:
                    if (empty($value) || $value == 'null') {
                        $sql .= "$key = NULL,";
                    } else {
                        $sql .= "$key = ?,";
                        $bindings[] = array('type' => 's', 'value' => $value);
                    }
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($parameters['id'])) {
            $sql .= " WHERE id = ?";
            $bindings[] = array('type' => 'i', 'value' => $parameters['id']);
        }
        $id = $this->sql_manager->execute($sql, $bindings);
        if (!empty($id)) {
            $email_manager = new Emails();
            $email_manager->insert_email_notify_registration($id);
        }

    }

    /**
     * @throws Exception
     */
    public function get_register($id = null)
    {
        $where = "1=1";
        $bindings = array();
        if (!empty($id)) {
            $where .= " AND r.id = ?";
            $bindings[] = array('type' => 'i', 'value' => $id);
        }
        $sql = "SELECT 
                r.id,
                r.new_team_name,
                r.id_club,
                c.nom AS club,
                r.id_competition,
                c2.code_competition AS code_competition,
                c2.libelle AS competition,
                r.old_team_id,
                e.nom_equipe AS old_team,
                r.leader_name,
                r.leader_first_name,
                r.leader_email,
                r.leader_phone,
                r.id_court_1,
                g.nom AS court_1,
                r.day_court_1,
                r.hour_court_1,
                r.id_court_2,
                g2.nom AS court_2,
                r.day_court_2,
                r.hour_court_2,
                r.remarks,
                DATE_FORMAT(r.creation_date, '%d/%m/%Y %H:%i:%s') AS creation_date,
                r.rank_start,
                r.division
                FROM register r
                JOIN clubs c on c.id = r.id_club
                JOIN competitions c2 on r.id_competition = c2.id
                LEFT JOIN equipes e on r.old_team_id = e.id_equipe
                LEFT JOIN gymnase g on g.id = r.id_court_1
                LEFT JOIN gymnase g2 on g2.id = r.id_court_2
                WHERE $where
                ORDER BY competition, division, rank_start";
        $results = $this->sql_manager->execute($sql, $bindings);
        if (!empty($id)) {
            return $results[0];
        }
        return $results;
    }

    /**
     * @throws Exception
     */
    public function set_up_season(): void
    {
        // make a cleanup before new season
        $this->cleanup_before_start();
        // check that all data is ok  in register table
        $this->check_data();
        // get registered teams
        $registered_teams = $this->get_register();
        foreach ($registered_teams as $registered_team) {
            $id_team = $this->create_or_update_team($registered_team);
            $team = $this->team->getTeam($id_team);
            $this->user->create_leader_account($team['nom_equipe'], $registered_team['leader_email'], $id_team);
            $this->createTimeslots($registered_team, $id_team);
            $this->add_leader_informations($registered_team, $id_team);
        }
        // init ranks
        $this->init_ranks();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function cleanup_before_start(): void
    {
        // remove all standard and leader accounts
//        $this->cleanup_accounts();
        // remove all timeslots
        $this->cleanup_timeslots();
        // archive any active match
        $this->archive_confirmed_matches();
        // remove matches_files when archived
        $this->cleanup_files();
        $this->cleanup_matches_files();
        // remove matches_players when archived
        $this->cleanup_matches_players();
    }

    /**
     * @param mixed $registered_team
     * @param mixed $id_team
     * @throws Exception
     */
    public function createTimeslots(mixed $registered_team, mixed $id_team)
    {
        // create timeslots
        if (!empty($registered_team['id_court_1'])) {
            $this->time_slot->create(
                $registered_team['id_court_1'],
                $registered_team['day_court_1'],
                $registered_team['hour_court_1'],
                $id_team,
                0,
                1);
        }
        if (!empty($registered_team['id_court_2'])) {
            $this->time_slot->create(
                $registered_team['id_court_2'],
                $registered_team['day_court_2'],
                $registered_team['hour_court_2'],
                $id_team,
                0,
                2);
        }
    }

    /**
     * @param mixed $registered_team
     * @param mixed $id_team
     * @throws Exception
     */
    public function add_leader_informations(mixed $registered_team, mixed $id_team)
    {
// update leader player with email and phone
        $first_name = $registered_team['leader_first_name'];
        $last_name = $registered_team['leader_name'];
        $players = $this->player->get_players("UPPER(j.prenom)=UPPER('$first_name') 
                                                             AND UPPER(j.nom)=UPPER('$last_name')");
        // if many players found, throw exception
        if (count($players) > 1) {
            throw new Exception("Plusieurs homonymes trouvés, impossible de trouver $first_name $last_name");
        }
        // if player not found, create it
        if (count($players) == 0) {
            $id_player = $this->player->create(
                $first_name,
                $last_name,
                $registered_team['leader_phone'],
                $registered_team['leader_email'],
                $registered_team['id_club'],
            );
            $player = $this->player->get_player($id_player);
        } else {
            $player = $players[0];
            $player['telephone'] = $registered_team['leader_phone'];
            $player['email'] = $registered_team['leader_email'];
            $player['id_club'] = $registered_team['id_club'];
            $this->player->update_player(
                $id_team,
                $player['prenom'],
                $player['nom'],
                $player['num_licence'],
                $player['date_homologation'],
                $player['sexe'],
                $player['departement_affiliation'],
                $player['id_club'],
                $player['show_photo'],
                $player['telephone'],
                $player['email'],
                $player['telephone2'],
                $player['email2'],
                $player['id']);
        }
        // add leader to team
        if (!$this->player->is_player_in_team($player['id'], $id_team)) {
            $this->player->add_to_team(array($player['id']), $id_team);
        }
        // set as team leader for team
        $this->player->set_leader(array($player['id']), $id_team);
    }

    /**
     * @param mixed $registered_team
     * @return int
     * @throws Exception
     */
    public function create_or_update_team(mixed $registered_team): int
    {
        if (empty($registered_team['old_team_id'])) {
            // if team is new, create team
            if (!$this->team->team_exists($registered_team['code_competition'],
                $registered_team['new_team_name'],
                $registered_team['id_club'])) {
                $id_team = $this->team->create_team(
                    $registered_team['code_competition'],
                    $registered_team['new_team_name'],
                    $registered_team['id_club']);
            } else {
                // if new team has already been created, get team id
                $team = $this->team->get_by_name($registered_team['code_competition'],
                    $registered_team['new_team_name'],
                    $registered_team['id_club']);
                $id_team = $team['id_equipe'];
            }
        } else {
            // if team already exists, get team id
            $id_team = $registered_team['old_team_id'];
            // rename existing team with new team name
            $this->team->save(array(
                'id_equipe' => $id_team,
                'nom_equipe' => $registered_team['new_team_name'],
            ));
        }
        return $id_team;
    }

    private function check_data()
    {
        $sql = "SELECT * 
                FROM register 
                WHERE new_team_name IS NULL
                OR division IS NULL
                OR rank_start < 1
                OR (id_court_1 IS NOT NULL AND day_court_1 IS NULL)
                OR (id_court_2 IS NOT NULL AND day_court_2 IS NULL)
                OR (id_court_2 IS NOT NULL AND id_court_1 IS NULL)";
        if (count($this->sql_manager->execute($sql)) > 0) {
            throw new Exception("At least one required condition is missing !");
        }
    }

    /**
     * @throws Exception
     */
    private function init_ranks()
    {
        // first, remove all ranks
        $sql = "DELETE FROM classements";
        $this->sql_manager->execute($sql);
        // then, insert ranks from register table with new teams
        $sql = "INSERT INTO classements(code_competition, division, id_equipe, rank_start) 
                SELECT c.code_competition, r.division, e.id_equipe, r.rank_start
                FROM register r 
                JOIN competitions c on r.id_competition = c.id
                JOIN equipes e on 
                    r.old_team_id IS NULL 
                        AND r.new_team_name = e.nom_equipe 
                        AND e.code_competition = c.code_competition";
        $this->sql_manager->execute($sql);
        // then, insert ranks from register table with old teams
        $sql = "INSERT INTO classements(code_competition, division, id_equipe, rank_start) 
                SELECT c.code_competition, r.division, e.id_equipe, r.rank_start 
                FROM register r 
                JOIN competitions c on r.id_competition = c.id
                JOIN equipes e on 
                    r.old_team_id = e.id_equipe
                    AND e.code_competition = c.code_competition";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    private function archive_confirmed_matches()
    {
        $sql = "UPDATE matches SET match_status = 'ARCHIVED' WHERE match_status NOT IN ('NOT_CONFIRMED', 'ARCHIVED')";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    private function cleanup_accounts()
    {
        // delete accounts standard and leader
        $sql = "DELETE FROM comptes_acces 
                  WHERE id IN (SELECT user_id 
                             FROM users_profiles 
                             WHERE profile_id IN (SELECT id 
                                                  FROM profiles 
                                                  WHERE name IN ('RESPONSABLE_EQUIPE', 'STANDARD')))";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    private function cleanup_timeslots()
    {
        // delete timeslots
        $sql = "DELETE FROM creneau 
                WHERE id_equipe IN (SELECT old_team_id
                                    FROM register)";
        $this->sql_manager->execute($sql);
        $sql = "DELETE FROM creneau 
                WHERE id_equipe IN (SELECT e.id_equipe
                                    FROM register r
                                    JOIN competitions c on r.id_competition = c.id
                                    JOIN equipes e on r.old_team_id IS NULL 
                                                    AND r.new_team_name = e.nom_equipe 
                                                    AND e.code_competition = c.code_competition)";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    private function cleanup_matches_files()
    {
        $sql = "DELETE FROM matches_files 
                WHERE id_match IN (SELECT id_match 
                                   FROM matches 
                                   WHERE match_status IN ('ARCHIVED'))";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    private function cleanup_matches_players()
    {
        $sql = "DELETE FROM match_player 
                WHERE id_match IN (SELECT id_match 
                                   FROM matches 
                                   WHERE match_status IN ('ARCHIVED'))";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    private function cleanup_files()
    {
        $sql = "DELETE FROM files 
                WHERE id IN (SELECT id_file 
                             FROM matches_files 
                             WHERE id_match IN (SELECT id_match  
                                                FROM matches 
                                                WHERE match_status IN ('ARCHIVED')))";
        $this->sql_manager->execute($sql);
    }

}