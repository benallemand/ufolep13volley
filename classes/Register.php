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
        $is_cup_registered = null,
        $is_seeding_tournament_requested = null,
        $can_seeding_tournament_setup = null,
        $id = null
    ): void
    {
        // issue #249 : la demande d'inscription est réservée aux responsables
        // de club (et aux admins, qui peuvent corriger n'importe quelle demande)
        if (!UserManager::isClubLeader() && !UserManager::isAdmin()) {
            throw new Exception("Seuls les responsables de club peuvent gérer les inscriptions !", 403);
        }
        require_once __DIR__ . '/Club.php';
        if (!UserManager::isAdmin()) {
            // le club de session fait foi, quel que soit le club posté
            $id_club = (new Club())->getMyClubId();
            if (!empty($id)) {
                $this->assertMyClubPendingRegistration($id);
            }
            if (!$this->competition->is_registration_available($id_competition)) {
                throw new Exception("L'enregistrement à cette compétition n'est pas disponible actuellement !");
            }
        } elseif (empty($id_club) && UserManager::isClubLeader()) {
            // les rôles se cumulent : un admin aussi responsable de club qui
            // poste depuis son espace club (sans id_club) inscrit pour SON club
            $id_club = (new Club())->getMyClubId();
        }
        if (empty($id_club) && !empty($id)) {
            // mise à jour sans club posté : on conserve celui de la demande
            $id_club = $this->get_register($id)['id_club'];
        }
        if (empty($id_club)) {
            throw new Exception("Le club de l'équipe à inscrire n'est pas déterminé !", 400);
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
            'is_cup_registered' => $is_cup_registered,
            'is_seeding_tournament_requested' => $is_seeding_tournament_requested,
            'can_seeding_tournament_setup' => $can_seeding_tournament_setup,
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
                case 'is_cup_registered':
                case 'is_seeding_tournament_requested':
                case 'can_seeding_tournament_setup':
                    if (is_null($value)) {
                        break;
                    }
                    $val = Generic::to_flag($value);
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
        if (!UserManager::isAdmin() && !empty($parameters['id'])) {
            // Le club corrige sa demande : une demande refusée repasse en
            // attente d'une nouvelle décision, motif effacé (issue #376).
            $sql .= "status = 'PENDING', refusal_reason = NULL, refusal_date = NULL,";
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

    /**
     * Vérifie qu'une inscription appartient au club du responsable connecté
     * et qu'elle est encore modifiable (statut PENDING).
     * @throws Exception
     */
    private function assertMyClubPendingRegistration($id): array
    {
        require_once __DIR__ . '/Club.php';
        $registration = $this->get_one("r.id = ?", array(array('type' => 'i', 'value' => $id)));
        if (empty($registration)) {
            throw new Exception("Inscription introuvable !", 404);
        }
        if ((int)$registration['id_club'] !== (new Club())->getMyClubId()) {
            throw new Exception("Cette inscription n'appartient pas à votre club !", 403);
        }
        if ($registration['status'] === 'VALIDATED') {
            throw new Exception("Cette inscription a été validée, elle n'est plus modifiable !", 403);
        }
        return $registration;
    }

    /** Seules colonnes de la liste publique : rien sur les personnes (issue #379). */
    public const PUBLIC_REGISTRATION_FIELDS = array('club', 'equipe', 'status', 'type', 'ancien_nom');

    /**
     * Liste publique des inscriptions, affichée en page d'accueil (issue #379).
     *
     * Une compétition y figure dès l'ouverture de ses inscriptions et jusqu'à
     * son démarrage. `start_date` est celle de la saison précédente tant que la
     * commission n'a pas saisi la nouvelle : une date antérieure à l'ouverture
     * des inscriptions ne masque donc pas la liste.
     *
     * Pour chaque compétition : les demandes déposées depuis l'ouverture (tous
     * statuts, refus compris, sans motif), et en championnat les équipes du
     * classement actuel qui ne se sont pas réinscrites (`NOT_REGISTERED`).
     *
     * **Public** : chaque ligne ne porte que PUBLIC_REGISTRATION_FIELDS — ni
     * responsable, ni gymnase, ni remarque, ni paiement, ni motif de refus.
     *
     * @return array<int, array{libelle: string, code_competition: string,
     *               limit_register_date: ?string, teams: array}>
     * @throws Exception
     */
    public function getPublicRegistrations(): array
    {
        $competitions = $this->sql_manager->execute(
            "SELECT id, code_competition, libelle,
                    DATE_FORMAT(limit_register_date, '%d/%m/%Y') AS limit_register_date
             FROM competitions
             WHERE start_register_date IS NOT NULL
               AND start_register_date <= CURDATE()
               AND (start_date IS NULL OR start_date <= start_register_date OR start_date > CURDATE())
             ORDER BY libelle");
        if (empty($competitions)) {
            return array();
        }
        $ids = array_map(static fn($c) => (int)$c['id'], $competitions);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_map(static fn($id) => array('type' => 'i', 'value' => $id), $ids);
        $championships = implode(',', array_map(static fn($code) => "'$code'", Competition::CHAMPIONSHIPS));

        $registered = $this->sql_manager->execute(
            "SELECT r.id_competition, cl.nom AS club, r.new_team_name AS equipe, r.status,
                    IF(r.old_team_id IS NULL, 'new', 'renewal') AS type,
                    IF(e.nom_equipe <> r.new_team_name, e.nom_equipe, NULL) AS ancien_nom
             FROM register r
                      JOIN competitions c ON c.id = r.id_competition
                      JOIN clubs cl ON cl.id = r.id_club
                      LEFT JOIN equipes e ON e.id_equipe = r.old_team_id
             WHERE r.id_competition IN ($placeholders)
               AND r.creation_date >= c.start_register_date",
            $bindings);
        // Réinscription reconnue à `old_team_id`, quelle que soit la
        // compétition demandée : une équipe passée du féminin au mixte n'est
        // pas « non réinscrite ».
        $not_registered = $this->sql_manager->execute(
            "SELECT comp.id AS id_competition, cl.nom AS club, e.nom_equipe AS equipe,
                    'NOT_REGISTERED' AS status, NULL AS type, NULL AS ancien_nom
             FROM classements c
                      JOIN competitions comp ON comp.code_competition = c.code_competition
                      JOIN equipes e ON e.id_equipe = c.id_equipe
                      LEFT JOIN clubs cl ON cl.id = e.id_club
             WHERE comp.id IN ($placeholders)
               AND comp.code_competition IN ($championships)
               AND NOT EXISTS (SELECT 1
                               FROM register r
                               WHERE r.old_team_id = c.id_equipe
                                 AND r.creation_date >= comp.start_register_date)",
            $bindings);

        $teams = array();
        foreach (array_merge($registered, $not_registered) as $row) {
            $teams[(int)$row['id_competition']][] = array_intersect_key($row, array_flip(self::PUBLIC_REGISTRATION_FIELDS));
        }
        $result = array();
        foreach ($competitions as $competition) {
            $list = $teams[(int)$competition['id']] ?? array();
            usort($list, static fn($a, $b) => array((string)$a['club'], (string)$a['equipe'])
                <=> array((string)$b['club'], (string)$b['equipe']));
            $result[] = array(
                'libelle' => $competition['libelle'],
                'code_competition' => $competition['code_competition'],
                'limit_register_date' => $competition['limit_register_date'],
                'teams' => $list,
            );
        }
        return $result;
    }

    /**
     * Liste les inscriptions du club du responsable connecté.
     * @throws Exception
     */
    public function getMyClubRegistrations(): array
    {
        if (!UserManager::isClubLeader()) {
            throw new Exception("Seul un responsable de club peut faire ça !", 403);
        }
        require_once __DIR__ . '/Club.php';
        $id_club = (new Club())->getMyClubId();
        $sql = $this->getSql("r.id_club = ?");
        return $this->sql_manager->execute($sql, array(array('type' => 'i', 'value' => $id_club)));
    }

    /**
     * Suppression d'une demande par le club, tant qu'elle n'est pas validée.
     * @throws Exception
     */
    public function deleteMyClubRegistration($id): void
    {
        if (!UserManager::isClubLeader()) {
            throw new Exception("Seul un responsable de club peut faire ça !", 403);
        }
        $registration = $this->assertMyClubPendingRegistration($id);
        $this->sql_manager->execute(
            "DELETE FROM register WHERE id = ?",
            array(array('type' => 'i', 'value' => $id)));
        $this->addActivity("Inscription supprimée par le club : " . $registration['new_team_name'] . " (" . $registration['competition'] . ")");
    }

    /**
     * Validation d'une demande par l'admin : elle devient non modifiable par
     * le club et éligible à l'engagement (set_up_season). Une demande refusée
     * peut être validée directement, si la commission revient sur sa décision.
     * @throws Exception 409 si elle est déjà validée (pas d'email en double)
     */
    public function validateRegistration($id): void
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent valider une inscription !", 403);
        }
        $registration = $this->find_registration($id);
        if ($registration['status'] === 'VALIDATED') {
            throw new Exception("L'inscription « " . $registration['new_team_name'] . " » est déjà validée !", 409);
        }
        $this->sql_manager->execute(
            "UPDATE register SET status = 'VALIDATED', validation_date = NOW(), refusal_reason = NULL, refusal_date = NULL
             WHERE id = ?",
            array(array('type' => 'i', 'value' => $id)));
        $this->notifyClub($registration, 'notify_registration_validated.fr.html', "Inscription validée");
        $this->addActivity("Inscription validée : " . $registration['new_team_name'] . " (" . $registration['competition'] . ")");
    }

    /**
     * Retour d'une demande validée au statut PENDING (admin).
     * @throws Exception 409 si elle n'est pas validée : il n'y a rien à dévalider
     */
    public function unvalidateRegistration($id): void
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent dévalider une inscription !", 403);
        }
        $registration = $this->find_registration($id);
        if ($registration['status'] !== 'VALIDATED') {
            throw new Exception("L'inscription « " . $registration['new_team_name'] . " » n'est pas validée : rien à dévalider !", 409);
        }
        $this->sql_manager->execute(
            "UPDATE register SET status = 'PENDING', validation_date = NULL WHERE id = ?",
            array(array('type' => 'i', 'value' => $id)));
        $this->addActivity("Inscription dévalidée : " . $registration['new_team_name'] . " (" . $registration['competition'] . ")");
    }

    /**
     * Refus d'une demande en attente par l'admin, avec un motif envoyé au club
     * (issue #376 : une équipe « volante », sans gymnase, est désormais
     * interdite). Le club peut corriger sa demande : elle repasse en attente.
     * Une demande validée se dévalide d'abord : on ne refuse pas une équipe
     * peut-être déjà engagée.
     * @throws Exception
     */
    public function refuseRegistration($id, $reason = null): void
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent refuser une inscription !", 403);
        }
        $reason = trim((string)$reason);
        if ($reason === '') {
            throw new Exception("Le motif du refus est obligatoire : il est envoyé au club.", 400);
        }
        if (mb_strlen($reason) > 1000) {
            throw new Exception("Le motif du refus ne doit pas dépasser 1000 caractères.", 400);
        }
        $registration = $this->find_registration($id);
        if ($registration['status'] === 'VALIDATED') {
            throw new Exception("L'inscription « " . $registration['new_team_name'] . " » est validée : la dévalider avant de la refuser.", 409);
        }
        if ($registration['status'] === 'REFUSED') {
            throw new Exception("L'inscription « " . $registration['new_team_name'] . " » est déjà refusée !", 409);
        }
        $this->sql_manager->execute(
            "UPDATE register SET status = 'REFUSED', refusal_reason = ?, refusal_date = NOW() WHERE id = ?",
            array(
                array('type' => 's', 'value' => $reason),
                array('type' => 'i', 'value' => $id),
            ));
        $this->notifyClub($registration, 'notify_registration_refused.fr.html', "Inscription refusée",
            array('%reason%' => $reason));
        $this->addActivity("Inscription refusée : " . $registration['new_team_name'] . " (" . $registration['competition'] . "), motif : $reason");
    }

    /**
     * @throws Exception 404 si l'inscription n'existe pas
     */
    private function find_registration($id): array
    {
        $registration = $this->get_one("r.id = ?", array(array('type' => 'i', 'value' => $id)));
        if (empty($registration)) {
            throw new Exception("Inscription introuvable !", 404);
        }
        return $registration;
    }

    /**
     * Notifie par email les responsables du club d'une décision sur leur
     * demande. Les valeurs sont échappées : le nom d'équipe et le motif sont
     * saisis librement (issue #292).
     * @throws Exception
     */
    private function notifyClub(array $registration, string $template, string $subject, array $extra = array()): void
    {
        $sql = "SELECT ca.email
                FROM users_clubs uc
                JOIN comptes_acces ca ON ca.id = uc.user_id
                WHERE uc.club_id = ?";
        $bindings = array(array('type' => 'i', 'value' => $registration['id_club']));
        $leaders = $this->sql_manager->execute($sql, $bindings);
        if (count($leaders) === 0) {
            return;
        }
        $values = array_merge(array(
            '%new_team_name%' => $registration['new_team_name'],
            '%competition%' => $registration['competition'],
        ), $extra);
        $message = file_get_contents(__DIR__ . '/../templates/emails/' . $template);
        foreach ($values as $placeholder => $value) {
            $message = str_replace($placeholder, nl2br(htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')), $message);
        }
        $email_manager = new Emails();
        foreach ($leaders as $leader) {
            $email_manager->insert_email(
                "[UFOLEP13VOLLEY]$subject : " . $registration['new_team_name'],
                $message,
                $leader['email']);
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
                r.status,
                DATE_FORMAT(r.validation_date, '%d/%m/%Y %H:%i:%s') AS validation_date,
                r.refusal_reason,
                DATE_FORMAT(r.refusal_date, '%d/%m/%Y %H:%i:%s') AS refusal_date,
                r.rank_start,
                r.division,
                r.is_paid,
                r.is_cup_registered,
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
    public function set_up_season($ids): void
    {
        if (empty($ids)) {
            throw new Exception("Aucune compétition sélectionnée !");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id_competition) {
            // make a cleanup before new season
            $this->cleanup_before_start($id_competition);
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
                // init de saison : autant de comptes crees que d'inscriptions, on
                // laisse les identifiants au cron horaire (issue #305)
                $this->user->create_or_update_leader_account($registered_team['leader_email'], $id_team, false);
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
                $player['sexe'],
                $player['departement_affiliation'],
                $player['id_club'],
                $player['num_licence'],
                $player['date_homologation'],
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
                error_log("l'équipe n'existe pas, création ok");
            } else {
                // if new team has already been created, get team id
                $team = $this->team->get_by_name($registered_team['code_competition'],
                    $registered_team['new_team_name'],
                    $registered_team['id_club']);
                $id_team = $team['id_equipe'];
                error_log("l'équipe existe déjà, ok");
            }
        } else {
            $old_team = $this->team->get_by_id($registered_team['old_team_id']);
            if($old_team['nom_equipe'] !== $registered_team['new_team_name']) {
                // if team already exists, get team id
                $id_team = $registered_team['old_team_id'];
                // rename existing team with new team name
                $this->team->save(array(
                    'id_equipe' => $id_team,
                    'nom_equipe' => $registered_team['new_team_name'],
                ));
                error_log("l'équipe existe sous un autre nom, modification ok");
            }
            else {
                $id_team = $registered_team['old_team_id'];
                error_log("l'équipe existe déjà, ok");
            }
        }
        return $id_team;
    }

    /**
     * @throws Exception
     */
    private function init_ranks($id_competition): void
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
        // delete accounts only if competition is a parent competition
        $sql = file_get_contents(__DIR__ . '/../sql/cleanup_accounts.sql');
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
    private function get_register_by_competition($id_competition): array|int|string|null
    {
        $where = "r.id_competition = ?
                  AND r.status = 'VALIDATED'
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
        }
        $where = "(r.rank_start IS NULL AND r.division IS NULL) AND r.status = 'VALIDATED' AND r.id_competition = ?";
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
        $pools = Competition::make_pools_of_3(array($pending_registrations));
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
                  AND r.status = 'VALIDATED'
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

    public function create_teams_and_accounts(string $ids): void
    {
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $this->create_team_and_account($id);
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

    private function create_team_and_account(string $id)
    {
        $register = $this->get_register($id);
        error_log($register['new_team_name']);
        $id_team = $this->create_or_update_team($register);
        // traitement en lot (l'admin selectionne N inscriptions dans la grille) :
        // les identifiants restent en file et partent au cron horaire (issue #305)
        $this->user->create_or_update_leader_account($register['leader_email'], $id_team, false);
    }

}