<?php
require_once __DIR__ . '/SqlManager.php';
require_once __DIR__ . '/Emails.php';
require_once __DIR__ . '/Rank.php';
require_once __DIR__ . '/Team.php';
require_once __DIR__ . '/Players.php';
require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/TimeSlot.php';
require_once __DIR__ . '/Constants.php';
require_once __DIR__ . '/Competition.php';

class Register extends Generic
{
    private Team $team;
    private Competition $competition;
    private Players $player;
    private UserManager $user;
    private TimeSlot $time_slot;
    private Rank $rank;

    /**
     * Match constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->team = new Team();
        $this->player = new Players();
        $this->user = new UserManager();
        $this->competition = new Competition();
        $this->time_slot = new TimeSlot();
        $this->rank = new Rank();
        $this->table_name = 'register';
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
        $is_paid = null,
        $is_seeding_tournament_requested = null,
        $can_seeding_tournament_setup = null,
        $dirtyFields = null,
        $id = null
    ): void
    {
        if (!$this->competition->is_registration_available($id_competition)) {
            throw new Exception("L'enregistrement à cette compétition n'est pas disponible actuellement !");
        }
        $parameters = array(
            'new_team_name' => trim($new_team_name),
            'id_club' => $id_club,
            'id_competition' => $id_competition,
            'old_team_id' => $old_team_id,
            'leader_name' => trim($leader_name),
            'leader_first_name' => trim($leader_first_name),
            'leader_email' => trim($leader_email),
            'leader_phone' => $leader_phone,
            'id_court_1' => $id_court_1,
            'day_court_1' => $day_court_1,
            'hour_court_1' => $hour_court_1,
            'id_court_2' => $id_court_2,
            'day_court_2' => $day_court_2,
            'hour_court_2' => $hour_court_2,
            'remarks' => trim($remarks),
            'division' => $division,
            'rank_start' => $rank_start,
            'is_paid' => $is_paid,
            'is_seeding_tournament_requested' => $is_seeding_tournament_requested,
            'can_seeding_tournament_setup' => $can_seeding_tournament_setup,
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
                case 'is_paid':
                case 'is_seeding_tournament_requested':
                case 'can_seeding_tournament_setup':
                    if (is_null($value)) {
                        break;
                    }
                    $val = ($value === 'on' || $value === 1) ? 1 : 0;
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $val
                    );
                    $sql .= "$key = ?,";
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
            throw new Exception(MESSAGE_REGISTER_DONE, 201);
        }

    }

    public function getSql($query = "1=1"): string
    {
        return "SELECT 
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
                r.division,
                r.is_paid,
                r.is_seeding_tournament_requested,
                r.can_seeding_tournament_setup
                FROM register r
                JOIN clubs c on c.id = r.id_club
                JOIN competitions c2 on r.id_competition = c2.id
                LEFT JOIN equipes e on r.old_team_id = e.id_equipe
                LEFT JOIN gymnase g on g.id = r.id_court_1
                LEFT JOIN gymnase g2 on g2.id = r.id_court_2
                WHERE $query
                ORDER BY competition, division, rank_start";
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
        $sql = $this->getSql($where);
        $results = $this->sql_manager->execute($sql, $bindings);
        if (!empty($id)) {
            return $results[0];
        }
        return $results;
    }

    /**
     * @throws Exception
     */
    public function set_up_season($id_competition): void
    {
        // make a cleanup before new season
        $this->cleanup_before_start($id_competition);
        // check that all data is ok in register table
        $this->check_data($id_competition);
        // get registered teams
        $registered_teams = $this->get_register_by_competition($id_competition);
        // if automatic registration, only take new teams into account
        if ($this->competition->is_automatic_registration($id_competition)) {
            // if championship and 2nd half, only register new teams
            if ($this->competition->is_championship($id_competition) && !$this->competition->is_first_half($id_competition)) {
                $registered_teams = $this->get_2nd_half_registrations($id_competition);
            } else {
                $registered_teams = $this->get_pending_registrations($id_competition, true);
            }
        }
        foreach ($registered_teams as $registered_team) {
            $id_team = $this->create_or_update_team($registered_team);
            $team = $this->team->getTeam($id_team);
            $this->user->create_leader_account($team['nom_equipe'], $registered_team['leader_email'], $id_team);
            $this->createTimeslots($registered_team, $id_team);
            $this->add_leader_informations($registered_team, $id_team);
        }
        // if competition registration is automatic and not a championship, use specific ranking init
        if ($this->competition->is_automatic_registration($id_competition) && !$this->competition->is_championship($id_competition)) {
            $this->competition->init_classements_isoardi(false);
        } else {
            // init ranks
            $this->init_ranks($id_competition);
        }
    }

    /**
     * @param $id_competition
     * @return void
     * @throws Exception
     */
    public function cleanup_before_start($id_competition): void
    {
        // archive any active match
        $this->archive_confirmed_matches($id_competition);
        // remove matches_files when archived
        $this->cleanup_files($id_competition);
        $this->cleanup_matches_files($id_competition);
        // remove matches_players when archived
        $this->cleanup_matches_players($id_competition);
        // if championship and 2nd half, nothing else is needed
        if ($this->competition->is_championship($id_competition) && !$this->competition->is_first_half($id_competition)) {
            return;
        }
        // remove all leader accounts
        $this->cleanup_accounts($id_competition);
        // remove all timeslots
        $this->cleanup_timeslots($id_competition);
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

    /**
     * @throws Exception
     */
    public function check_data($id_competition)
    {
        $sql = "SELECT * 
                FROM register 
                WHERE id_competition = ?
                AND (
                    new_team_name IS NULL
                    OR (id_court_1 IS NOT NULL AND day_court_1 IS NULL)
                    OR (id_court_2 IS NOT NULL AND day_court_2 IS NULL)
                    OR (id_court_2 IS NOT NULL AND id_court_1 IS NULL))";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        if (count($this->sql_manager->execute($sql, $bindings)) > 0) {
            throw new Exception("Au moins une condition est manquante !");
        }
    }

    /**
     * @throws Exception
     */
    private function init_ranks($id_competition)
    {
        $competition_manager = new Competition();
        // first, remove all ranks for competition
        $this->rank->delete_competition($id_competition);
        // if needed, make a group draw: init register.rank_start and register.division
        if ($competition_manager->is_group_draw_needed($id_competition)) {
            $this->group_draw($id_competition);
        }
        // insert teams in ranks (find by name and competition) with division/rank_start as defined in register
        $this->rank->insert_from_register($id_competition);
    }

    /**
     * @throws Exception
     */
    private function archive_confirmed_matches($id_competition)
    {
        $sql = "UPDATE matches 
                SET match_status = 'ARCHIVED' 
                WHERE match_status NOT IN ('NOT_CONFIRMED', 'ARCHIVED')
                AND code_competition IN (SELECT code_competition 
                                         FROM competitions 
                                         WHERE id = ?)";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    private function cleanup_accounts($id_competition)
    {
        // delete accounts leader
        // do not delete accounts if competition is not a parent competition
        $sql = "DELETE 
                FROM comptes_acces 
                WHERE id IN (SELECT user_id
                             FROM users_profiles
                             WHERE profile_id IN (SELECT id 
                                                  FROM profiles 
                                                  WHERE name IN ('RESPONSABLE_EQUIPE')))
                AND id_equipe IN (SELECT id_equipe 
                                  FROM equipes 
                                  WHERE code_competition IN (SELECT code_competition 
                                                             FROM competitions 
                                                             WHERE id = ? AND code_competition = id_compet_maitre))";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    private function cleanup_timeslots($id_competition)
    {
        // delete timeslots
        // do not delete timeslots if competition is not a parent competition
        $sql = "DELETE 
                FROM creneau 
                WHERE id_equipe IN (SELECT old_team_id
                                    FROM register)
                AND id_equipe IN (SELECT id_equipe 
                                  FROM equipes 
                                  WHERE code_competition IN (SELECT code_competition 
                                                             FROM competitions 
                                                             WHERE id = ? AND code_competition = id_compet_maitre))";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
        $sql = "DELETE FROM creneau 
                WHERE id_equipe IN (SELECT e.id_equipe
                                    FROM register r
                                    JOIN competitions c on r.id_competition = c.id
                                    JOIN equipes e on r.old_team_id IS NULL 
                                                    AND r.new_team_name = e.nom_equipe 
                                                    AND e.code_competition = c.code_competition)
                AND id_equipe IN (SELECT id_equipe 
                                  FROM equipes 
                                  WHERE code_competition IN (SELECT code_competition 
                                                             FROM competitions 
                                                             WHERE id = ? AND code_competition = id_compet_maitre))";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    private function cleanup_matches_files($id_competition)
    {
        $sql = "DELETE FROM matches_files 
                WHERE id_match IN (SELECT id_match 
                                   FROM matches 
                                   WHERE match_status IN ('ARCHIVED')
                                   AND code_competition IN (SELECT code_competition
                                                            FROM competitions 
                                                            WHERE id = ?))";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    private function cleanup_matches_players($id_competition)
    {
        $sql = "DELETE FROM match_player 
                WHERE id_match IN (SELECT id_match 
                                   FROM matches 
                                   WHERE match_status IN ('ARCHIVED')
                                   AND code_competition IN (SELECT code_competition 
                                                            FROM competitions 
                                                            WHERE id = ?))";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    private function cleanup_files($id_competition)
    {
        $sql = "DELETE FROM files 
                WHERE id IN (SELECT id_file 
                             FROM matches_files 
                             WHERE id_match IN (SELECT id_match  
                                                FROM matches 
                                                WHERE match_status IN ('ARCHIVED')
                                                AND code_competition IN (SELECT code_competition
                                                                         FROM competitions 
                                                                         WHERE id = ?)))";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    private function get_register_by_competition($id_competition): array|int|string|null
    {
        $where = "r.id_competition = ? 
                  AND c2.code_competition = c2.id_compet_maitre";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $sql = $this->getSql($where);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function get_pending_registrations($id_competition, $check_parent_competition = false): array|int|string|null
    {
        $competition = $this->competition->get_by_id($id_competition);
        if ($check_parent_competition) {
            $competition = $this->competition->getCompetition($competition['id_compet_maitre']);
            $where = "(r.rank_start IS NULL AND r.division IS NULL) AND r.id_competition = ?";
        } else {
            $where = "(r.rank_start IS NULL AND r.division IS NULL) AND r.id_competition = ?";
        }
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $competition['id']);
        $sql = $this->getSql($where);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * make a group draw to set 'register.division' and 'register.rank_start' for a dedicated competition
     * @param $id_competition
     * @return void
     * @throws Exception
     */
    private function group_draw($id_competition): void
    {
        if (!$this->competition->is_group_draw_needed($id_competition)) {
            throw new Exception("Pas de tirage au sort pour cette compétition !");
        }
        $pending_registrations = $this->get_register_by_competition($id_competition);
        shuffle($pending_registrations);
        $pools = Competition::make_pools_of_3($pending_registrations);
        foreach ($pools as $pool_index => $pool) {
            foreach ($pool as $pending_registration_index => $pending_registration) {
                $this->save(array(
                    'id' => $pending_registration['id'],
                    'division' => $pool_index + 1,
                    'rank_start' => $pending_registration_index + 1,
                ));
            }
        }
    }

    /**
     * @throws Exception
     */
    public function get_2nd_half_registrations($id_competition): array
    {
        $where = "new_team_name NOT IN (SELECT nom_equipe
                            FROM equipes
                            WHERE code_competition IN (SELECT code_competition
                                                       FROM competitions
                                                       WHERE id = ?))
                  AND id_competition = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id_competition),
            array('type' => 'i', 'value' => $id_competition),
        );
        return $this->get($where, $bindings);
    }

    /**
     * @throws Exception
     */
    public function fill_ranks(string $ids): void
    {
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $this->fill_rank($id);
        }
    }

    /**
     * @throws Exception
     */
    private function fill_rank(string $id): void
    {
        $register = $this->get_register($id);
        if (empty($register['old_team_id'])) {
            return;
        }
        try {
            $competition = $this->competition->get_by_id($register['id_competition']);
            $division = $this->rank->getTeamDivision(
                $competition['code_competition'],
                $register['old_team_id']
            );
            $rank = $this->rank->getTeamRank(
                $competition['code_competition'],
                $division,
                $register['old_team_id']
            );
        } catch (Exception) {
            return;
        }
        $update_register = array(
            'id' => $register['id'],
            'division' => $division,
            'rank_start' => $rank,
        );
        $this->save($update_register);
    }

}