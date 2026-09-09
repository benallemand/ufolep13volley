<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once __DIR__ . '/Configuration.php';
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/SqlManager.php';
require_once __DIR__ . '/Team.php';
require_once __DIR__ . '/Players.php';
require_once __DIR__ . '/Rank.php';
require_once __DIR__ . '/Register.php';
require_once __DIR__ . '/Competition.php';
require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/Survey.php';
require_once __DIR__ . '/Registry.php';

class MatchMgr extends Generic
{
    private Survey $survey;

    private Team $team;
    private Rank $rank;
    private Registry $registry;
    private Configuration $configuration;

    /**
     * Match constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->team = new Team();
        $this->rank = new Rank();
        $this->registry = new Registry();
        $this->survey = new Survey();
        $this->configuration = new Configuration();
        ini_set('max_execution_time', 1200);
        ini_set('memory_limit', '512M');
        ini_set('xdebug.max_nesting_level', 2000);
        $this->table_name = 'matches';
        $this->id_name = 'id_match';
    }

    /**
     * @param string|null $query
     * @param string $order
     * @return string
     */
    private function get_sql(?string $query = "1=1", string $order = "code_competition, division, STR_TO_DATE(date_reception, '%d/%m/%Y')"): string
    {
        return "SELECT m.id_match AS id, m.* FROM matchs_view m WHERE $query ORDER BY $order";
    }

    /**
     * @throws Exception
     */
    public function getMatches(
        $competition = null,
        $division = null,
        $showCertified = null,
        $showNotCertified = null,
        $showForbiddenPlayer = null,
        $showPlayedMatchesOnly = null,
        $showCertifiable = null,
        $showInProgress = null,
        $search = null
    ): array {
        $conditions = ["1=1"];

        if (!empty($competition)) {
            $competition = $this->sql_manager->escape($competition);
            $conditions[] = "m.code_competition = '$competition'";
        }

        if (!empty($division)) {
            $division = $this->sql_manager->escape($division);
            $conditions[] = "m.division = '$division'";
        }

        if ($showCertified === 'true') {
            $conditions[] = "m.certif = 1";
        }

        if ($showNotCertified === 'true') {
            $conditions[] = "m.certif != 1";
        }

        if ($showForbiddenPlayer === 'true') {
            $conditions[] = "m.has_forbidden_player = 1";
        }

        if ($showPlayedMatchesOnly === 'true') {
            $conditions[] = "m.is_match_score_filled = 1";
        }

        if ($showCertifiable === 'true') {
            $conditions[] = "m.certif = 0";
            $conditions[] = "m.has_forbidden_player = 0";
            $conditions[] = "m.is_match_score_filled = 1";
            $conditions[] = "m.is_match_player_filled = 1";
            $conditions[] = "m.is_sign_match_dom = 1";
            $conditions[] = "m.is_sign_match_ext = 1";
            $conditions[] = "m.is_sign_team_dom = 1";
            $conditions[] = "m.is_sign_team_ext = 1";
            $conditions[] = "m.is_survey_filled_dom = 1";
            $conditions[] = "m.is_survey_filled_ext = 1";
        }

        if ($showInProgress === 'true') {
            $conditions[] = "m.match_status != 'ARCHIVED'";
        }

        if (!empty($search)) {
            $search = $this->sql_manager->escape($search);
            $conditions[] = "(m.equipe_dom LIKE '%$search%' 
                OR m.equipe_ext LIKE '%$search%' 
                OR m.libelle_competition LIKE '%$search%' 
                OR m.division LIKE '%$search%' 
                OR m.code_match LIKE '%$search%')";
        }

        $query = implode(" AND ", $conditions);
        return $this->get_matches($query);
    }

    /**
     * @throws Exception
     */
    public function getToScheduleMatches($competition = null, $division = null): array
    {
        if (empty($competition) && empty($division)) {
            throw new Exception("Il faut renseigner une compétition et une division !");
        }
        return $this->registry->find_by_key("to_schedule." . $competition . "_" . $division . ".");
    }

    /**
     * @param string|null $query
     * @param string $order
     * @return array
     * @throws Exception
     */
    public function get_matches(?string $query = "1=1", string $order = "code_competition, division, STR_TO_DATE(date_reception, '%d/%m/%Y')"): array
    {
        return $this->sql_manager->execute($this->get_sql($query, $order));
    }

    /**
     * @throws Exception
     */
    public function getMesMatches()
    {
        @session_start();
        $team_id = $_SESSION['id_equipe'];
        return $this->get_matches(
            "(m.id_equipe_dom = $team_id OR m.id_equipe_ext = $team_id)
                    AND m.match_status NOT IN ('ARCHIVED')");
    }

    /**
     * Détermine la PROCHAINE action en attente pour un camp donné d'un match,
     * dans l'ordre du workflow : présents -> signer fiche -> score -> signer feuille -> sondage.
     * Reproduit la logique d'affichage de pages/components/match/MatchSummary.js.
     *
     * @param array $match Une ligne de matchs_view
     * @param string $side 'dom' ou 'ext'
     * @param bool $presentsFilled Les joueurs présents de ce camp sont-ils saisis ?
     *        (à calculer par l'appelant : is_match_player_filled de matchs_view n'est
     *        PAS fiable côté camp — il vaut 1 même pour un état d'erreur "fiche non remplie".)
     * @return array|null ['action','label','url'] ou null si plus rien à faire
     */
    public function getNextMatchActionForSide(array $match, string $side, bool $presentsFilled): ?array
    {
        $id = $match['id_match'];
        $signTeam = (int)($match['is_sign_team_' . $side] ?? 0) === 1;
        $scoreFilled = (int)($match['is_match_score_filled'] ?? 0) === 1;
        $signMatch = (int)($match['is_sign_match_' . $side] ?? 0) === 1;
        $surveyFilled = (int)($match['is_survey_filled_' . $side] ?? 0) === 1;

        if (!$presentsFilled) {
            return ['action' => 'fill_players', 'label' => 'Remplir les joueurs présents', 'url' => '/team_sheets.html?id_match=' . $id];
        }
        if (!$signTeam) {
            return ['action' => 'sign_team', 'label' => 'Signer la fiche équipe', 'url' => '/team_sheets.html?id_match=' . $id];
        }
        if (!$scoreFilled) {
            return ['action' => 'fill_score', 'label' => 'Remplir le score', 'url' => '/match.html?id_match=' . $id];
        }
        if (!$signMatch) {
            return ['action' => 'sign_match', 'label' => 'Signer la feuille de match', 'url' => '/match.html?id_match=' . $id];
        }
        if (!$surveyFilled) {
            return ['action' => 'fill_survey', 'label' => 'Remplir le sondage', 'url' => '/survey.html?id_match=' . $id];
        }
        return null;
    }

    /**
     * Actions en attente du responsable connecté, pour ses matchs déjà joués
     * (date <= aujourd'hui) et NON encore certifiés par la commission.
     * Un élément par match, avec sa prochaine action.
     * Utilisé pour afficher des toasts à la connexion (issue #240).
     *
     * @return array
     * @throws Exception
     */
    public function getMyPendingMatchActions(): array
    {
        @session_start();
        $team_id = $_SESSION['id_equipe'] ?? null;
        if (empty($team_id) || !is_numeric($team_id)) {
            return [];
        }
        $team_id = (int)$team_id;
        $matches = $this->get_matches(
            "(m.id_equipe_dom = $team_id OR m.id_equipe_ext = $team_id)
                    AND m.match_status NOT IN ('ARCHIVED')
                    AND m.certif = 0
                    AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') <= CURDATE()");
        if (empty($matches)) {
            return array();
        }
        // Matchs où l'équipe du responsable a déjà saisi des joueurs présents
        // (indicateur fiable par camp, contrairement à is_match_player_filled).
        $ids = array_map(static fn($m) => (int)$m['id_match'], $matches);
        $idList = implode(',', $ids);
        $filledRows = $this->sql_manager->execute(
            "SELECT DISTINCT mp.id_match
             FROM match_player mp
             JOIN joueur_equipe je ON je.id_joueur = mp.id_player
             WHERE je.id_equipe = $team_id AND mp.id_match IN ($idList)");
        $presentsByMatch = array();
        foreach ($filledRows as $row) {
            $presentsByMatch[(int)$row['id_match']] = true;
        }

        $result = array();
        foreach ($matches as $match) {
            $side = ((int)$match['id_equipe_dom'] === $team_id) ? 'dom' : 'ext';
            $presentsFilled = isset($presentsByMatch[(int)$match['id_match']]);
            $action = $this->getNextMatchActionForSide($match, $side, $presentsFilled);
            if ($action === null) {
                continue;
            }
            $result[] = array(
                'id_match' => $match['id_match'],
                'code_match' => $match['code_match'],
                'libelle_competition' => $match['libelle_competition'],
                'division' => $match['division'],
                'date_reception' => $match['date_reception'],
                'equipe_adverse' => $side === 'dom' ? $match['equipe_ext'] : $match['equipe_dom'],
                'action' => $action['action'],
                'label' => $action['label'],
                'url' => $action['url'],
            );
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    public function getMyClubMatches()
    {
        @session_start();
        // Un responsable de club n'a pas d'équipe propre : on résout le club
        // directement depuis la session. Sinon, on déduit le club de l'équipe.
        if (UserManager::isClubLeader() && !empty($_SESSION['id_club'])) {
            $id_club = (int)$_SESSION['id_club'];
            return $this->get_matches(
                "(
                        m.id_equipe_dom IN (SELECT id_equipe FROM equipes WHERE id_club = $id_club)
                        OR
                        m.id_equipe_ext IN (SELECT id_equipe FROM equipes WHERE id_club = $id_club)
                        )
                        AND m.match_status NOT IN ('ARCHIVED')");
        }
        $team_id = $_SESSION['id_equipe'];
        return $this->get_matches(
            "(
                    m.id_equipe_dom IN (SELECT id_equipe
                                        FROM equipes
                                        WHERE id_club IN (
                                            SELECT id_club
                                            FROM equipes
                                            WHERE id_equipe = $team_id))
                    OR
                    m.id_equipe_ext IN (SELECT id_equipe
                                        FROM equipes
                                        WHERE id_club IN (
                                            SELECT id_club
                                            FROM equipes
                                            WHERE id_equipe = $team_id))
                    )
                    AND m.match_status NOT IN ('ARCHIVED')");
    }

    /**
     * Matchs programmés aujourd'hui (date_reception = date du jour), triés par
     * heure. Utilisé par l'encart "Matchs du jour" de la page d'accueil (#230).
     *
     * Endpoint PUBLIC : on ne renvoie qu'un sous-ensemble whitelisté des champs
     * (matchs_view contient des emails d'équipe qu'il ne faut pas exposer).
     * date_reception est stockée au format dd/mm/yyyy → STR_TO_DATE pour comparer
     * à CURDATE().
     *
     * @return array
     * @throws Exception
     */
    public function getMatchesOfTheDay(): array
    {
        $matches = $this->get_matches(
            "STR_TO_DATE(m.date_reception, '%d/%m/%Y') = CURDATE() AND m.match_status != 'ARCHIVED'",
            "heure_reception"
        );
        return array_map(static function ($m) {
            return array(
                'id_match' => $m['id_match'],
                'code_match' => $m['code_match'],
                'code_competition' => $m['code_competition'],
                'libelle_competition' => $m['libelle_competition'] ?? null,
                'division' => $m['division'],
                'equipe_dom' => $m['equipe_dom'],
                'equipe_ext' => $m['equipe_ext'],
                'date_reception' => $m['date_reception'],
                'heure_reception' => $m['heure_reception'],
                'gymnasium' => $m['gymnasium'] ?? null,
                'score_equipe_dom' => $m['score_equipe_dom'] ?? null,
                'score_equipe_ext' => $m['score_equipe_ext'] ?? null,
                'is_match_score_filled' => $m['is_match_score_filled'] ?? null,
            );
        }, $matches);
    }

    /**
     * @param $id_match
     * @return mixed
     * @throws Exception
     */
    public function get_match($id_match)
    {
        // `get_matches()` prend une clause WHERE toute faite : impossible d'y lier
        // un paramètre, donc on valide avant de composer (issue #270).
        if (!is_numeric($id_match)) {
            throw new Exception("Identifiant de match invalide !");
        }
        $id_match = (int)$id_match;
        $results = $this->get_matches("m.id_match = $id_match");
        $count_results = count($results);
        if ($count_results !== 1) {
            throw new Exception("Erreur lors de la récupération des données du match ! Trouvé $count_results match(s) !");
        }
        return $results[0];
    }

    /**
     * Équipes pour lesquelles l'utilisateur connecté peut agir sur un match :
     * celles qui lui sont rattachées (users_teams) ET l'équipe COURANTE de sa
     * session — cette dernière peut venir d'un club géré, donc sans aucune
     * ligne users_teams (cf. UserManager::switchCurrentUserTeam()).
     * @return int[]
     * @throws Exception
     */
    private function getMyAllowedTeamIds(): array
    {
        @session_start();
        $teamIds = array();
        $id_user = $_SESSION['id_user'] ?? null;
        if (!empty($id_user)) {
            $teamIds = array_map('intval', (new UserManager())->getUserTeamIds((int)$id_user));
        }
        if (!empty($_SESSION['id_equipe'])) {
            $teamIds[] = (int)$_SESSION['id_equipe'];
        }
        return array_values(array_unique($teamIds));
    }

    /**
     * @param array $match
     * @return bool
     * @throws Exception
     */
    private function isUserTeamInMatch(array $match): bool
    {
        $teamIds = $this->getMyAllowedTeamIds();
        return in_array((int)$match['id_equipe_dom'], $teamIds, true)
            || in_array((int)$match['id_equipe_ext'], $teamIds, true);
    }

    /**
     * Retourne l'ID de l'équipe de l'utilisateur connecté qui participe au match,
     * ou null si aucune de ses équipes ne participe.
     * @param array $match
     * @return int|null
     * @throws Exception
     */
    private function getUserTeamIdForMatch(array $match): ?int
    {
        $teamIds = $this->getMyAllowedTeamIds();
        if (in_array((int)$match['id_equipe_dom'], $teamIds, true)) {
            return (int)$match['id_equipe_dom'];
        }
        if (in_array((int)$match['id_equipe_ext'], $teamIds, true)) {
            return (int)$match['id_equipe_ext'];
        }
        return null;
    }

    /**
     * @param $id_match
     * @return bool
     * @throws Exception
     */
    public function is_match_read_allowed($id_match): bool
    {
        $this->getCurrentUserDetails();
        if (UserManager::isAdmin()) {
            return true;
        }
        if (UserManager::isTeamLeader()) {
            $match = $this->get_match($id_match);
            return $this->isUserTeamInMatch($match);
        }
        return false;
    }

    /**
     * Version "API REST" de is_match_read_allowed : renvoie un tableau
     * (json-encodable + countable par le routeur rest/action.php) plutôt
     * qu'un booléen. Ne lève jamais d'exception : un utilisateur non
     * connecté ou non autorisé reçoit simplement allowed=false.
     *
     * @param $id_match
     * @return array{allowed: bool}
     */
    public function getMatchReadAccess($id_match): array
    {
        try {
            $allowed = $this->is_match_read_allowed($id_match);
        } catch (Exception $e) {
            $allowed = false;
        }
        return array('allowed' => $allowed);
    }

    /**
     * @param $id_match
     * @return bool
     * @throws Exception
     */
    public function is_match_update_allowed($id_match): bool
    {
        $this->getCurrentUserDetails();
        if (UserManager::isAdmin()) {
            return true;
        }
        if (UserManager::isTeamLeader()) {
            $match = $this->get_match($id_match);
            if (!$this->isUserTeamInMatch($match)) {
                return false;
            }
            if ($match['certif'] == 1) {
                return false;
            }
            if ($match['is_sign_match_dom'] == 1 && $match['is_sign_match_ext'] == 1) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function save_match($id_match,
                               $code_match,
                               $set_1_dom,
                               $set_2_dom,
                               $set_3_dom,
                               $set_4_dom,
                               $set_5_dom,
                               $set_1_ext,
                               $set_2_ext,
                               $set_3_ext,
                               $set_4_ext,
                               $set_5_ext,
                               $referee,
                               $note,
                               $dirtyFields = null)
    {
        $this->save(array(
            'dirtyFields' => $dirtyFields,
            'id_match' => $id_match,
            'code_match' => $code_match,
            'set_1_dom' => $set_1_dom,
            'set_2_dom' => $set_2_dom,
            'set_3_dom' => $set_3_dom,
            'set_4_dom' => $set_4_dom,
            'set_5_dom' => $set_5_dom,
            'set_1_ext' => $set_1_ext,
            'set_2_ext' => $set_2_ext,
            'set_3_ext' => $set_3_ext,
            'set_4_ext' => $set_4_ext,
            'set_5_ext' => $set_5_ext,
            'referee' => $referee,
            'note' => $note,
        ));
    }

    /**
     * Enregistrement d'un match depuis l'administration.
     *
     * Tous les parametres sont optionnels : le routeur REST appelle la methode
     * avec des arguments nommes, et un formulaire n'envoie que les champs qu'il
     * declare. Un parametre obligatoire absent leverait une ArgumentCountError
     * (issue #279).
     *
     * @throws Exception
     */
    public function saveMatch(
        $code_match = null,
        $code_competition = null,
        $division = null,
        $id_equipe_dom = null,
        $id_equipe_ext = null,
        $id_gymnasium = null,
        $date_reception = null,
        $certif = null,
        $is_sign_team_dom = null,
        $is_sign_team_ext = null,
        $is_sign_match_dom = null,
        $is_sign_match_ext = null,
        $note = null,
        $parent_code_competition = null,
        $dirtyFields = null,
        $id_match = null
    )
    {
        $inputs = array(
            'code_match' => $code_match,
            'parent_code_competition' => $parent_code_competition,
            'code_competition' => $code_competition,
            'division' => $division,
            'id_equipe_dom' => $id_equipe_dom,
            'id_equipe_ext' => $id_equipe_ext,
            'id_gymnasium' => $id_gymnasium,
            'date_reception' => $date_reception,
            'certif' => $certif,
            'is_sign_team_dom' => $is_sign_team_dom,
            'is_sign_team_ext' => $is_sign_team_ext,
            'is_sign_match_dom' => $is_sign_match_dom,
            'is_sign_match_ext' => $is_sign_match_ext,
            'note' => $note,
            'dirtyFields' => $dirtyFields,
            'id_match' => $id_match,
        );
        $this->save($inputs);
    }

    /**
     * @throws Exception
     */
    public function save($inputs): void
    {
        $bindings = array();
        if (empty($inputs['id_match'])) {
            $sql = "INSERT INTO";
        } else {
            if (!$this->is_match_update_allowed($inputs['id_match'])) {
                throw new Exception("Vous n'êtes pas autorisé à modifier ce match !");
            }
            $sql = "UPDATE";
        }
        $sql .= " matches SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id_match':
                case 'dirtyFields':
                case 'parent_code_competition':
                case 'equipe_dom':
                case 'equipe_ext':
                    break;
                case 'id_equipe_dom':
                case 'id_equipe_ext':
                case 'id_gymnasium':
                case 'set_1_dom':
                case 'set_1_ext':
                case 'set_2_dom':
                case 'set_2_ext':
                case 'set_3_dom':
                case 'set_3_ext':
                case 'set_4_dom':
                case 'set_4_ext':
                case 'set_5_dom':
                case 'set_5_ext':
                    if ($value === null || $value === '') {
                        $sql .= "$key = NULL,";
                        break;
                    }
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 'i', 'value' => $value);
                    break;
                case 'date_reception':
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    $bindings[] = array('type' => 's', 'value' => $value);
                    break;
                case 'certif':
                case 'is_sign_team_dom':
                case 'is_sign_team_ext':
                case 'is_sign_match_dom':
                case 'is_sign_match_ext':
                    $val = Generic::to_flag($value);
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 'i', 'value' => $val);
                    break;
                default:
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 's', 'value' => $value);
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($inputs['id_match'])) {
            $sql .= " WHERE id_match = ?";
            $bindings[] = array('type' => 'i', 'value' => $inputs['id_match']);
        }
        $this->sql_manager->execute($sql, $bindings);
        if (empty($inputs['id_match'])) {
            return;
        }
        $code_match = $inputs['code_match'];
        $this->addActivity("Le match $code_match a ete modifie");
    }


    /**
     * @param $team_id
     * @param $date_string
     * @return bool
     * @throws Exception
     */
    public function has_match($team_id, $date_string): bool
    {
        // tested ok
        $sql = "SELECT * 
                FROM matches
                WHERE (id_equipe_dom = ? OR id_equipe_ext = ?) 
                  AND date_reception = STR_TO_DATE(?, '%d/%m/%Y')";
        $bindings = array(
            array('type' => 'i', 'value' => $team_id),
            array('type' => 'i', 'value' => $team_id),
            array('type' => 's', 'value' => $date_string)
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return count($results) > 0;
    }

    /**
     * @throws Exception
     */
    public function certify_matchs(string $ids)
    {
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $this->certify_match($id);
        }
    }

    /**
     * @throws Exception
     */
    public function flip_matchs(string $ids)
    {
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $this->flip_match($id);
        }
    }

    /**
     * @throws Exception
     */
    public function certify_match(string $id): void
    {
        $this->is_action_allowed(__FUNCTION__, $id);
        $sql = "UPDATE matches 
                SET certif = 1
                WHERE id_match = ?";
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
    public function add_match_player($id_match, $player_id)
    {
        $sql = "INSERT INTO match_player(id_match, id_player) 
                VALUE (?, ?) 
                ON DUPLICATE KEY UPDATE id_match = id_match, 
                                        id_player = id_player";
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $id_match
        );
        $bindings[] = array(
            'type' => 'i',
            'value' => $player_id
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $id_match
     * @param $id_player
     * @throws Exception
     */
    public function delete_match_player($id_match, $id_player)
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        $sql = "DELETE FROM match_player 
            WHERE id_match = $id_match
            AND id_player = $id_player";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function getLastResults()
    {
        $sql = file_get_contents(__DIR__ . '/../sql/get_last_results.sql');
        $results = $this->sql_manager->execute($sql);
        foreach ($results as $index => $result) {
            $code_competition = $result['code_competition'];
            switch ($code_competition) {
                case 'mo':
                case 'm':
                case 'f':
                case 'kh':
                case 'c':
                case 'po':
                case 'px':
                    $division = $result['division'];
                    $results[$index]['url'] = "championship.php?d=$division&c=$code_competition";
                    break;
                case 'kf':
                case 'cf':
                    $results[$index]['url'] = "cup.php?c=$code_competition";
                    break;
                default :
                    break;
            }
        }
        return $results;
    }

    /**
     * @throws Exception
     */
    public function getWeekMatches(): array|int|string|null
    {
        $sql = file_get_contents(__DIR__ . '/../sql/get_week_matchs.sql');
        $bindings = array();
        $results = $this->sql_manager->execute($sql, $bindings);
        foreach ($results as $index => $result) {
            $code_competition = $result['code_competition'];
            switch ($code_competition) {
                case 'mo':
                case 'm':
                case 'f':
                case 'kh':
                case 'c':
                case 'po':
                case 'px':
                    $division = $result['division'];
                    $results[$index]['url'] = "championship.php?d=$division&c=$code_competition";
                    break;
                case 'kf':
                case 'cf':
                    $results[$index]['url'] = "cup.php?c=$code_competition";
                    break;
                default :
                    break;
            }
        }
        return $results;
    }

    /**
     * @throws Exception
     */
    public function isTeamDomForMatch($id_team, $code_match)
    {
        $sql = "SELECT * FROM matches 
        WHERE id_equipe_dom=$id_team 
        AND code_match='$code_match'
        AND match_status = 'CONFIRMED'";
        $results = $this->sql_manager->execute($sql);
        return count($results) > 0;
    }

    /**
     * @throws Exception
     */
    function archiveMatch($ids)
    {
        // $ids vient de l'extérieur : liste assainie et valeurs liées (issue #268)
        $id_list = Generic::parse_id_list($ids);
        if (empty($id_list)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($id_list), '?'));
        $sql = "UPDATE matches 
            SET match_status = 'ARCHIVED'
            WHERE id_match IN($placeholders)";
        $bindings = array_map(fn($id) => array('type' => 'i', 'value' => $id), $id_list);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    function confirmMatch($ids)
    {
        // $ids vient de l'extérieur : liste assainie et valeurs liées (issue #268)
        $id_list = Generic::parse_id_list($ids);
        if (empty($id_list)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($id_list), '?'));
        $sql = "UPDATE matches 
            SET match_status = 'CONFIRMED'
            WHERE id_match IN($placeholders)";
        $bindings = array_map(fn($id) => array('type' => 'i', 'value' => $id), $id_list);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    function unconfirmMatch($ids)
    {
        // $ids vient de l'extérieur : liste assainie et valeurs liées (issue #268)
        $id_list = Generic::parse_id_list($ids);
        if (empty($id_list)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($id_list), '?'));
        $sql = "UPDATE matches 
            SET match_status = 'NOT_CONFIRMED'
            WHERE id_match IN($placeholders)";
        $bindings = array_map(fn($id) => array('type' => 'i', 'value' => $id), $id_list);
        $this->sql_manager->execute($sql, $bindings);
    }


    /**
     * @throws Exception
     */
    public function getMatchPlayers($id_match = null): int|array|string|null
    {
        $sql = "SELECT  DISTINCT j.*,
                        e.nom_equipe AS equipe,
                        m.date_reception,
                        m.id_match
                FROM matchs_view m
                         JOIN match_player mp on mp.id_match = m.id_match
                         JOIN players_view j on mp.id_player = j.id
                         LEFT JOIN joueur_equipe je ON je.id_joueur = j.id AND (je.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext))
                         LEFT JOIN equipes e ON je.id_equipe = e.id_equipe
                WHERE m.id_match = $id_match
                ORDER BY equipe, sexe, nom, prenom";
        $results = $this->sql_manager->execute($sql);
        return Players::adjust_photo_path_from_results($results);
    }

    /**
     * @throws Exception
     */
    public function getNotMatchPlayers($id_match = null): int|array|string|null
    {
        $sql = "SELECT DISTINCT j.*, e.nom_equipe AS equipe
                FROM joueur_equipe je
                         JOIN matches m ON (m.id_equipe_dom = je.id_equipe OR m.id_equipe_ext = je.id_equipe)
                         JOIN players_view j on j.id = je.id_joueur
                         JOIN equipes e ON e.id_equipe = je.id_equipe
                WHERE m.id_match = $id_match
                  AND je.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext)
                  AND je.id_joueur NOT IN (SELECT id_player FROM match_player where id_match = $id_match)";
        $results = $this->sql_manager->execute($sql);
        return Players::adjust_photo_path_from_results($results);
    }

    /**
     * @param null $id_match
     * @param null $query
     * @return int|array|string|null
     * @throws Exception
     */
    public function getReinforcementPlayers($id_match = null, $query = null): int|array|string|null
    {
        if (empty($query)) {
            throw new Exception("Merci de rechercher un joueur en commençant à taper son nom !");
        } else {
            $query = "j.full_name LIKE '%$query%'";
        }
        $sql = "SELECT DISTINCT j.*
                FROM players_view j
                WHERE $query 
                AND j.id NOT IN (SELECT id_player 
                                   FROM match_player 
                                   WHERE id_match = $id_match)
                AND j.id NOT IN (SELECT id_joueur 
                                 FROM joueur_equipe 
                                 WHERE id_equipe IN (SELECT id_equipe_dom FROM matches WHERE id_match = $id_match)
                                 OR id_equipe IN (SELECT id_equipe_ext FROM matches WHERE id_match = $id_match))";
        $results = $this->sql_manager->execute($sql);
        return Players::adjust_photo_path_from_results($results);
    }


    /**
     * @throws Exception
     */
    public function getTeamsEmailsFromMatch($code_match)
    {
        $sql = "SELECT
                    m.id_equipe_dom,
                    m.id_equipe_ext,
                    m.code_competition,
                    LEFT(m.division, 1) AS division
                FROM matches m
                WHERE m.code_match = '$code_match'
                AND m.match_status = 'CONFIRMED'";
        $results = $this->sql_manager->execute($sql);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer le match $code_match !");
        }
        $data = $results[0];
        $emailDom = $this->team->getTeamEmail($data['id_equipe_dom']);
        $emailExt = $this->team->getTeamEmail($data['id_equipe_ext']);
        // remove ctsd from mails
        return array($emailDom, $emailExt);
    }

    /**
     * @throws Exception
     */
    public function getTeamsEmailsFromMatchReport($code_match)
    {
        $sql = "SELECT
      m.id_equipe_dom,
      m.id_equipe_ext,
      m.code_competition
      FROM matches m
      WHERE m.code_match = '$code_match'
        AND m.match_status = 'CONFIRMED'";
        $results = $this->sql_manager->execute($sql);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer le match $code_match !");
        }
        $data = $results[0];
        $emailDom = $this->team->getTeamEmail($data['id_equipe_dom']);
        $emailExt = $this->team->getTeamEmail($data['id_equipe_ext']);
        $emailReport = 'report@ufolep13volley.org';
        return array($emailDom, $emailExt, $emailReport);
    }

    /**
     * @param $team_id
     * @param $match_code
     * @throws Exception
     */
    public function check_team_allowed_to_ask_report($team_id, $match_code)
    {
        $matches = $this->get_matches("m.code_match = '$match_code'");
        $this_match = $matches[0];
        $code_competition = $this_match['code_competition'];
        $rank = new Rank();
        $report_count = $rank->get_report_count($team_id, $code_competition);
        if (!$this->configuration->covid_mode) {
            if ($report_count > 0) {
                throw new Exception("Demande refusée. Votre équipe a déjà demandé un report pour cette compétition.");
            }
        }
    }

    /**
     * @param $code_match
     * @param $reason
     * @return bool
     * @throws Exception
     */
    public function askForReport($code_match, $reason)
    {
        $match = $this->get_match_by_code_match($code_match);
        if ($match['match_status'] !== 'CONFIRMED') {
            throw new Exception("Seuls les matchs confirmés peuvent faire l'objet d'une demande de report !");
        }
        $sessionIdEquipe = $_SESSION['id_equipe'];
        $this->check_team_allowed_to_ask_report($sessionIdEquipe, $code_match);
        if ($this->isTeamDomForMatch($sessionIdEquipe, $code_match)) {
            $sql = "UPDATE matches SET report_status = 'ASKED_BY_DOM' WHERE code_match = '$code_match'";
        } else {
            $sql = "UPDATE matches SET report_status = 'ASKED_BY_EXT' WHERE code_match = '$code_match'";
        }
        $this->sql_manager->execute($sql);
        $this->addActivity("Report demandé par " . $this->team->getTeamName($sessionIdEquipe) . " pour le match $code_match");
        (new Emails())->sendMailAskForReport($code_match, $reason, $sessionIdEquipe);
        return true;
    }

    /**
     * @param $code_match
     * @param $report_date
     * @throws Exception
     */
    public function giveReportDate($code_match, $report_date)
    {
        $match = $this->get_match_by_code_match($code_match);
        $this->is_action_allowed(__FUNCTION__, $match['id_match']);
        $report_datetime = DateTime::createFromFormat('d/m/Y', $report_date);
        if (!$report_datetime) {
            throw new Exception("Impossible de déterminer la date de report, merci de respecter le format jj/mm/aaaa (exemple: 03/01/2023 pour le 3 Janvier 2023) !");
        }
        $date_string = $report_date;
        if ($this->has_match($match['id_equipe_dom'], $date_string)) {
            throw new Exception("L'équipe " . $match['equipe_dom'] . " a déjà un match ce soir là !");
        }
        if ($this->has_match($match['id_equipe_ext'], $date_string)) {
            throw new Exception("L'équipe " . $match['equipe_ext'] . " a déjà un match ce soir là !");
        }
        $sql = "UPDATE matches 
                SET date_reception = DATE(STR_TO_DATE(?, '%d/%m/%Y')) 
                WHERE code_match = ?";
        $bindings = array();
        $bindings[] = array(
            'type' => 's',
            'value' => $date_string
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $code_match
        );
        $this->sql_manager->execute($sql, $bindings);
        $sessionIdEquipe = $_SESSION['id_equipe'];
        $this->addActivity("Date de report transmise par " . $this->team->getTeamName($sessionIdEquipe) . " pour le match $code_match");
        (new Emails())->sendMailGiveReportDate($code_match, $report_date, $sessionIdEquipe);
    }

    /**
     * @param $code_match
     * @param $reason
     * @return bool
     * @throws Exception
     */
    public function refuseReport($code_match, $reason)
    {
        if (UserManager::isTeamLeader()) {
            $sessionIdEquipe = $_SESSION['id_equipe'];
            if ($this->isTeamDomForMatch($sessionIdEquipe, $code_match)) {
                $report_status = 'REFUSED_BY_DOM';
            } else {
                $report_status = 'REFUSED_BY_EXT';
            }
            $bindings = array();
            $bindings[] = array(
                'type' => 's',
                'value' => $report_status
            );
            $bindings[] = array(
                'type' => 's',
                'value' => $code_match
            );
            $sql = "UPDATE matches SET report_status = ? WHERE code_match = ?";
            $this->sql_manager->execute($sql, $bindings);
            $this->addActivity(
                "Report refusé par " . $this->team->getTeamName($sessionIdEquipe) .
                " pour le match $code_match, raison: " . $reason);
            (new Emails())->sendMailRefuseReport($code_match, $reason, $sessionIdEquipe);
        }
        if (UserManager::isAdmin()) {
            $sql = "UPDATE matches SET report_status = 'REFUSED_BY_ADMIN' WHERE code_match = '$code_match'";
            $this->sql_manager->execute($sql);
            $this->addActivity("Report refusé par la commission" .
                " pour le match $code_match, raison: " . $reason);
            (new Emails())->sendMailRefuseReportAdmin($code_match, $reason);
        }
        return true;
    }

    /**
     * @param $code_match
     * @return bool
     * @throws Exception
     */
    public function acceptReport($code_match)
    {
        // un report s'accepte au nom d'une équipe : il faut le rôle
        // responsable d'équipe (cumulable avec admin — issue #251)
        if (!UserManager::isTeamLeader()) {
            throw new Exception("Seul un responsable d'équipe peut accepter un report !");
        }
        $sessionIdEquipe = $_SESSION['id_equipe'];
        if ($this->isTeamDomForMatch($sessionIdEquipe, $code_match)) {
            $sql = "UPDATE matches SET report_status = 'ACCEPTED_BY_DOM' WHERE code_match = '$code_match'";
        } else {
            $sql = "UPDATE matches SET report_status = 'ACCEPTED_BY_EXT' WHERE code_match = '$code_match'";
        }
        $this->sql_manager->execute($sql);
        $matches = $this->get_matches("m.code_match = '$code_match'");
        $this_match = $matches[0];
        if ($sessionIdEquipe == $this_match['id_equipe_dom']) {
            $this->rank->incrementReportCount($this_match['code_competition'], $this_match['id_equipe_ext']);
        } else {
            $this->rank->incrementReportCount($this_match['code_competition'], $this_match['id_equipe_dom']);
        }
        $this->addActivity("Report accepté par " . $this->team->getTeamName($sessionIdEquipe) . " pour le match $code_match");
        (new Emails())->sendMailAcceptReport($code_match, $sessionIdEquipe);
        return true;
    }

    /**
     * @throws Exception
     */
    public function manage_match_players($id_match, $player_ids, $reinforcement_player_id = null, $dirtyFields = null): void
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        if (!isset($id_match)) {
            throw new Exception("Impossible de trouver id_match !");
        }
        if (empty($id_match)) {
            throw new Exception("id_match vide !");
        }
        $this->delete_match_players($id_match);
        if (!empty($reinforcement_player_id)) {
            $player_ids[] = $reinforcement_player_id;
        }
        if (isset($player_ids)) {
            foreach ($player_ids as $index => $player_id) {
                if (empty($player_id)) {
                    unset($player_ids[$index]);
                    continue;
                }
                $this->add_match_player($id_match, $player_id);
            }
        }
        if (count($player_ids) > 0) {
            $match = $this->get_match($id_match);
            $comment = "Les présents ont été renseignés pour le match " . $match['code_match'];
            $this->addActivity($comment);
        }
    }

    /**
     * @throws Exception
     */
    private function is_action_allowed(string $function_name, $id_match)
    {
        $match_manager = new MatchMgr();
        $match = $match_manager->get_match($id_match);
        $userTeamId = $this->getUserTeamIdForMatch($match);
        if (!UserManager::is_connected()) {
            throw new Exception("Utilisateur non connecté !");
        }
        switch ($function_name) {
            case 'certify_match':
                if (UserManager::isAdmin()) {
                    return;
                }
                throw new Exception("Seule la commission est autorisée à valider un match !");
            case 'giveReportDate':
                // allow admin
                if (UserManager::isAdmin()) {
                    return;
                }
                // allow only playing teams
                if ($userTeamId === null) {
                    throw new Exception("Seules les équipes participant au match peuvent donner une date de report !");
                }
                // allow only team leaders
                if (!UserManager::isTeamLeader()) {
                    throw new Exception("Seuls les responsables d'équipes peuvent donner une date de report !");
                }
                break;
            case 'manage_match_players':
            case 'add_match_player':
            case 'delete_match_player':
                // allow admin
                if (UserManager::isAdmin()) {
                    return;
                }
                // allow only playing teams
                if ($userTeamId === null) {
                    throw new Exception("Seules les équipes ayant participé au match peuvent dire qui était là !");
                }
                // allow only team leaders
                if (!UserManager::isTeamLeader()) {
                    throw new Exception("Seuls les responsables d'équipes peuvent dire qui était là !");
                }
                // allow only CONFIRMED matches
                if ($match['match_status'] !== 'CONFIRMED') {
                    throw new Exception("Il n'est pas possible de renseigner les présents pour ce match, il faut qu'il soit confirmé !");
                }
                // allow only if not yet signed by any team
                if ($match['is_sign_team_dom'] == 1 || $match['is_sign_team_ext'] == 1) {
                    throw new Exception("Déjà signé par une des équipes !");
                }
                break;
            case 'sign_team_sheet':
                // allow admin
                if (UserManager::isAdmin()) {
                    return;
                }
                // allow only playing teams
                if ($userTeamId === null) {
                    throw new Exception("Seules les équipes participant au match peuvent signer les fiches équipes !");
                }
                // allow only team leaders
                if (!UserManager::isTeamLeader()) {
                    throw new Exception("Seuls les responsables d'équipes peuvent signer les fiches équipes !");
                }
                // allow only CONFIRMED matches
                if ($match['match_status'] !== 'CONFIRMED') {
                    throw new Exception("Match non confirmé !");
                }
                // allow only match_player filled matches
                if ($match['is_match_player_filled'] !== 1) {
                    throw new Exception("Les présents des 2 équipes n'ont pas été renseignés !");
                }
                if (!empty($match['count_status'])) {
                    $count_status = $match['count_status'];
                    throw new Exception("Il y a un souci dans la saisie: $count_status !");
                }
                // allow only if not signed yet
                if (($userTeamId == $match['id_equipe_dom'] && $match['is_sign_team_dom'] == 1) ||
                    ($userTeamId == $match['id_equipe_ext'] && $match['is_sign_team_ext'] == 1)) {
                    throw new Exception("Signature déjà effectuée !");
                }
                break;
            case 'sign_match_sheet':
                // allow admin
                if (UserManager::isAdmin()) {
                    return;
                }
                // allow only playing teams
                if ($userTeamId === null) {
                    throw new Exception("Seules les équipes participant au match peuvent signer la feuille de match !");
                }
                // allow only team leaders
                if (!UserManager::isTeamLeader()) {
                    throw new Exception("Seuls les responsables d'équipes signer la feuille de match !");
                }
                // allow only CONFIRMED matches
                if ($match['match_status'] !== 'CONFIRMED') {
                    throw new Exception("Match non confirmé !");
                }
                // allow only score filled matches
                if ($match['score_equipe_dom'] == 0 && $match['score_equipe_ext'] == 0) {
                    throw new Exception("Le score n'a pas été renseigné !");
                }
                // allow only if not signed yet
                if (($userTeamId == $match['id_equipe_dom'] && $match['is_sign_match_dom'] == 1) ||
                    ($userTeamId == $match['id_equipe_ext'] && $match['is_sign_match_ext'] == 1)) {
                    throw new Exception("Signature déjà effectuée !");
                }
                break;
            default:
                break;
        }
    }

    /**
     * @throws Exception
     */
    public function get_match_by_code_match(string $code_match)
    {
        // Contexte de chaîne quotée dans une clause WHERE composée à la main :
        // on échappe, comme le fait déjà getMatches() (issue #270).
        $code_match = $this->sql_manager->escape($code_match);
        $results = $this->get_matches("m.code_match = '$code_match'");
        $count_results = count($results);
        if ($count_results !== 1) {
            throw new Exception("Erreur pendant la réception des données du match! Trouvé $count_results match(s) !");
        }
        return $results[0];
    }

    /**
     * @throws Exception
     */
    public function sign_team_sheet($id_match): void
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        $match = $this->get_match($id_match);
        // if admin, sign for both teams
        if (UserManager::isAdmin()) {
            $sql = "UPDATE matches set is_sign_team_dom = 1, is_sign_team_ext = 1 WHERE id_match = ?";
        } else {
            $userTeamId = $this->getUserTeamIdForMatch($match);
            switch ($userTeamId) {
                case $match['id_equipe_dom']:
                    $sql = "UPDATE matches set is_sign_team_dom = 1 WHERE id_match = ?";
                    break;
                case $match['id_equipe_ext']:
                    $sql = "UPDATE matches set is_sign_team_ext = 1 WHERE id_match = ?";
                    break;
                default:
                    throw new Exception("Equipe non concernée par ce match !");
            }
        }
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_match);
        $this->sql_manager->execute($sql, $bindings);
        $match = $this->get_match($id_match);
        if ($match['is_sign_team_dom'] + $match['is_sign_team_ext'] == 1) {
            (new Emails())->team_sheet_to_be_signed($match['code_match']);
        } elseif ($match['is_sign_team_dom'] + $match['is_sign_team_ext'] == 2) {
            (new Emails())->team_sheet_signed($match['code_match']);
        }
        throw new Exception("Signature prise en compte", 200);
    }

    /**
     * @throws Exception
     */
    public function sign_match_sheet($id_match): void
    {
        $this->is_action_allowed(__FUNCTION__, $id_match);
        $match = $this->get_match($id_match);
        // if admin, sign for both teams
        if (UserManager::isAdmin()) {
            $sql = "UPDATE matches set is_sign_match_dom = 1, is_sign_match_ext = 1 WHERE id_match = ?";
        } else {
            $userTeamId = $this->getUserTeamIdForMatch($match);
            switch ($userTeamId) {
                case $match['id_equipe_dom']:
                    $sql = "UPDATE matches set is_sign_match_dom = 1 WHERE id_match = ?";
                    break;
                case $match['id_equipe_ext']:
                    $sql = "UPDATE matches set is_sign_match_ext = 1 WHERE id_match = ?";
                    break;
                default:
                    throw new Exception("Equipe non concernée par ce match !");
            }
        }
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_match);
        $this->sql_manager->execute($sql, $bindings);
        $match = $this->get_match($id_match);
        if ($match['is_sign_match_dom'] + $match['is_sign_match_ext'] == 1) {
            (new Emails())->match_sheet_to_be_signed($match['code_match']);
        } elseif ($match['is_sign_match_dom'] + $match['is_sign_match_ext'] == 2) {
            (new Emails())->match_sheet_signed($match['code_match']);
        }
        throw new Exception("Signature prise en compte", 200);
    }

    /**
     * @throws Exception
     */
    public function flip_match($id_match)
    {
        $match = $this->get_match($id_match);

        // Créer la date à partir du format français
        $originalDate = DateTime::createFromFormat('d/m/Y', $match['date_reception']);
        if ($originalDate === false) {
            $originalDate = new DateTime($match['date_reception']);
        }
        $weekStart = (clone $originalDate)->modify('monday this week');
        $update_match = [
            'id_match' => $match['id_match'],
            'code_match' => $match['code_match'],
            'id_equipe_dom' => $match['id_equipe_ext'],
            'id_equipe_ext' => $match['id_equipe_dom'],
        ];
        require_once 'TimeSlot.php';
        $tsm = new TimeSlot();
        $timeslots = $tsm->get("c.id_equipe = " . $match['id_equipe_ext']);
        if (count($timeslots) >= 1) {
            $timeslot = $timeslots[0];
            $update_match['id_gymnasium'] = $timeslot['id_gymnase'];
            // Conversion jour français -> offset depuis lundi
            $jourToOffset = [
                'Lundi' => 0,
                'Mardi' => 1,
                'Mercredi' => 2,
                'Jeudi' => 3,
                'Vendredi' => 4,
                'Samedi' => 5,
                'Dimanche' => 6,
            ];
            $dayOffset = $jourToOffset[$timeslot['jour']] ?? 0;
            $newDate = (clone $weekStart)->modify("+{$dayOffset} days");
            $update_match['date_reception'] = $newDate->format('d/m/Y');
            $this->save($update_match);
        }
    }

    /**
     * @throws Exception
     */
    public function get_survey($id_match = null)
    {
        if (empty($id_match)) {
            return $this->survey->get();
        }
        $userDetails = $this->getCurrentUserDetails();
        $id_user = $userDetails['id_user'];
        $results = $this->survey->get("s.id_match = $id_match AND s.user_id = $id_user");
        $count_results = count($results);
        if ($count_results === 0) {
            return array(
                'id' => null,
                'user_id' => $id_user,
                'id_match' => $id_match,
                'on_time' => 0,
                'spirit' => 0,
                'referee' => 0,
                'catering' => 0,
                'global' => 0,
                'comment' => null,
            );
        }
        if ($count_results > 1) {
            throw new Exception("Erreur lors de la récupération des données du sondage ! Trouvé $count_results sondage(s) !");
        }
        return $results[0];
    }

    /**
     * @throws Exception
     */
    public function save_survey($id_match,
                                $on_time,
                                $spirit,
                                $referee,
                                $catering,
                                $global,
                                $comment = null,
                                $dirtyFields = null,
                                $id = null): int|array|string|null
    {
        $ratings = ['on_time' => $on_time, 'spirit' => $spirit, 'referee' => $referee, 'catering' => $catering, 'global' => $global];
        foreach ($ratings as $field => $value) {
            if ($value < 0 || $value > 10) {
                throw new InvalidArgumentException("La note '$field' doit être comprise entre 0 et 10 (reçu: $value)");
            }
        }
        $userDetails = $this->getCurrentUserDetails();
        $id_user = $userDetails['id_user'];
        $inputs = array(
            'dirtyFields' => $dirtyFields,
            'id' => $id,
            'user_id' => $id_user,
            'id_match' => $id_match,
            'on_time' => $on_time,
            'spirit' => $spirit,
            'referee' => $referee,
            'catering' => $catering,
            'global' => $global,
            'comment' => $comment,
        );
        return $this->survey->save($inputs);
    }

    /**
     * @throws Exception
     */
    public function delete_match_players($id_match): void
    {
        $sql = "DELETE FROM match_player 
            WHERE id_match = $id_match";
        $this->sql_manager->execute($sql);

    }

    /**
     * Get available dates for a match with availability checking.
     * Uses batch SQL queries to avoid N+1 performance issues.
     * @param int $id_match
     * @param bool $check_opposite_gymnasium
     * @param bool $force_date
     * @return array
     * @throws Exception
     */
    public function get_available_dates_for_match(int $id_match, bool $check_opposite_gymnasium = false, bool $force_date = false): array
    {
        $match = $this->get_match($id_match);
        $competition = $this->get_competition_details($match['code_competition']);

        $start_date = $this->parse_date_dmY($competition['start_date_formatted']);
        $end_date = $this->get_competition_end_date($competition);
        $today = new DateTime('today');

        // Skip past dates: start from today if competition already started
        if ($start_date < $today) {
            $start_date = clone $today;
        }

        // Determine receiving team and gymnasium
        $receiving_team_id = $match['id_equipe_dom'];
        $gymnasium_id = $match['id_gymnasium'];
        if ($check_opposite_gymnasium) {
            $receiving_team_id = $match['id_equipe_ext'];
            $opposite_gymnasium = $this->get_team_gymnasium($receiving_team_id);
            if ($opposite_gymnasium) {
                $gymnasium_id = $opposite_gymnasium;
            }
        }

        // Get the receiving team's reception day(s) from créneau
        $reception_days = $this->get_team_reception_days($receiving_team_id);

        // Batch: fetch all team matches in the period (excluding current match)
        $team_busy_dates = $this->get_team_busy_dates(
            $match['id_equipe_dom'],
            $match['id_equipe_ext'],
            $start_date->format('d/m/Y'),
            $end_date->format('d/m/Y'),
            $id_match
        );

        // Batch: fetch gymnasium usage per date in the period (excluding current match)
        $gymnasium_usage = $this->get_gymnasium_usage_in_period(
            $gymnasium_id,
            $start_date->format('d/m/Y'),
            $end_date->format('d/m/Y'),
            $id_match
        );

        // Batch: fetch all blacklisted dates in the period
        $blacklisted_dates = $this->get_blacklisted_dates_in_period(
            $start_date->format('d/m/Y'),
            $end_date->format('d/m/Y')
        );

        // Batch: fetch all matches for week conflict detection (excluding current match)
        $all_team_matches = $this->get_team_matches_in_period(
            $match['id_equipe_dom'],
            $match['id_equipe_ext'],
            $start_date->format('d/m/Y'),
            $end_date->format('d/m/Y'),
            $id_match
        );

        // Batch: fetch public holidays and school holidays Zone B
        $start_year = (int)$start_date->format('Y');
        $end_year = (int)$end_date->format('Y');
        $public_holidays = $this->get_french_public_holidays($start_year);
        if ($end_year !== $start_year) {
            $public_holidays = array_merge($public_holidays, $this->get_french_public_holidays($end_year));
        }
        $school_holidays = $this->get_school_holidays_zone_b($start_date, $end_date);

        $available_dates = array();
        $current_date = clone $start_date;
        while ($current_date <= $end_date) {
            $date_str = $current_date->format('d/m/Y');
            $date_ymd = $current_date->format('Y-m-d');

            // Skip dates that don't match the receiving team's créneau day
            $day_name = $this->get_french_day_name($current_date);
            $matches_reception_day = empty($reception_days) || in_array($day_name, $reception_days);

            if (!$matches_reception_day && !$force_date) {
                $current_date->modify('+1 day');
                continue;
            }

            // Skip holidays and school vacations (unless force_date)
            $is_holiday = $this->is_holiday_or_vacation($date_ymd, $public_holidays, $school_holidays);

            if ($is_holiday && !$force_date) {
                $current_date->modify('+1 day');
                continue;
            }

            if ($force_date) {
                $available_dates[] = array(
                    'date' => $date_str,
                    'available' => true,
                    'gymnasium_available' => true,
                    'teams_available' => true,
                    'matches_reception_day' => $matches_reception_day,
                    'is_holiday' => $is_holiday,
                    'week_conflicts' => array('home_team_conflicts' => array(), 'away_team_conflicts' => array())
                );
                $current_date->modify('+1 day');
                continue;
            }

            $home_busy = in_array($date_ymd, $team_busy_dates['home']);
            $away_busy = in_array($date_ymd, $team_busy_dates['away']);
            $teams_available = !$home_busy && !$away_busy;

            $gym_count = $gymnasium_usage['dates'][$date_ymd] ?? 0;
            $gymnasium_available = $gym_count < $gymnasium_usage['capacity'];

            $is_blacklisted = in_array($date_ymd, $blacklisted_dates);

            $week_conflicts = $this->compute_week_conflicts_from_matches(
                $all_team_matches,
                $match['id_equipe_dom'],
                $match['id_equipe_ext'],
                $current_date
            );

            $available_dates[] = array(
                'date' => $date_str,
                'available' => $teams_available && $gymnasium_available && !$is_blacklisted,
                'gymnasium_available' => $gymnasium_available,
                'teams_available' => $teams_available,
                'matches_reception_day' => true,
                'is_holiday' => false,
                'week_conflicts' => $week_conflicts
            );
            $current_date->modify('+1 day');
        }

        return $available_dates;
    }

    /**
     * @param string $date format d/m/Y
     * @param string $code_competition
     * @return bool
     * @throws Exception
     */
    public function is_date_within_competition_period(string $date, string $code_competition): bool
    {
        $competition = $this->get_competition_details($code_competition);
        $check_date = DateTime::createFromFormat('d/m/Y', $date);
        $start_date = $this->parse_date_dmY($competition['start_date_formatted']);
        $end_date = $this->get_competition_end_date($competition);

        return $check_date >= $start_date && $check_date <= $end_date;
    }

    /**
     * Check teams availability for a specific date.
     * Single SQL query for both teams, excludes the match being moved.
     * @param int $home_team_id
     * @param int $away_team_id
     * @param string $date format d/m/Y
     * @param int|null $exclude_match_id match being moved (to exclude from busy check)
     * @return array
     * @throws Exception
     */
    public function check_teams_availability_for_date(int $home_team_id, int $away_team_id, string $date, ?int $exclude_match_id = null): array
    {
        $sql = "SELECT id_equipe_dom, id_equipe_ext FROM matches 
                WHERE date_reception = STR_TO_DATE(?, '%d/%m/%Y')
                AND (id_equipe_dom IN (?, ?) OR id_equipe_ext IN (?, ?))
                AND match_status NOT IN ('ARCHIVED')";
        $bindings = array(
            array('type' => 's', 'value' => $date),
            array('type' => 'i', 'value' => $home_team_id),
            array('type' => 'i', 'value' => $away_team_id),
            array('type' => 'i', 'value' => $home_team_id),
            array('type' => 'i', 'value' => $away_team_id),
        );
        if ($exclude_match_id) {
            $sql .= " AND id_match != ?";
            $bindings[] = array('type' => 'i', 'value' => $exclude_match_id);
        }

        $results = $this->sql_manager->execute($sql, $bindings);
        $home_busy = false;
        $away_busy = false;
        foreach ($results as $row) {
            if ($row['id_equipe_dom'] == $home_team_id || $row['id_equipe_ext'] == $home_team_id) {
                $home_busy = true;
            }
            if ($row['id_equipe_dom'] == $away_team_id || $row['id_equipe_ext'] == $away_team_id) {
                $away_busy = true;
            }
        }

        return array(
            'home_team_available' => !$home_busy,
            'away_team_available' => !$away_busy
        );
    }

    /**
     * Check if gymnasium is available for a specific date.
     * Excludes the match being moved.
     * @param int $gymnasium_id
     * @param string $date format d/m/Y
     * @param int|null $exclude_match_id
     * @return bool
     * @throws Exception
     */
    public function is_gymnasium_available_for_date(int $gymnasium_id, string $date, ?int $exclude_match_id = null): bool
    {
        $sql = "SELECT g.nb_terrain, COUNT(m.id_match) as scheduled_matches 
                FROM gymnase g 
                LEFT JOIN matches m ON g.id = m.id_gymnasium 
                    AND m.date_reception = STR_TO_DATE(?, '%d/%m/%Y')
                    AND m.match_status NOT IN ('ARCHIVED')";
        $bindings = array(
            array('type' => 's', 'value' => $date),
        );
        if ($exclude_match_id) {
            $sql .= " AND m.id_match != ?";
            $bindings[] = array('type' => 'i', 'value' => $exclude_match_id);
        }
        $sql .= " WHERE g.id = ? GROUP BY g.id, g.nb_terrain";
        $bindings[] = array('type' => 'i', 'value' => $gymnasium_id);

        $result = $this->sql_manager->execute($sql, $bindings);
        if (empty($result)) {
            return true;
        }
        return $result[0]['scheduled_matches'] < $result[0]['nb_terrain'];
    }

    /**
     * Get week conflicts for teams (matches already scheduled the same week).
     * Excludes the match being moved.
     * @param int $home_team_id
     * @param int $away_team_id
     * @param string $date format d/m/Y
     * @param int|null $exclude_match_id
     * @return array
     * @throws Exception
     */
    public function get_week_conflicts_for_teams(int $home_team_id, int $away_team_id, string $date, ?int $exclude_match_id = null): array
    {
        $check_date = DateTime::createFromFormat('d/m/Y', $date);
        $week_start = clone $check_date;
        $week_start->modify('monday this week');
        $week_end = clone $week_start;
        $week_end->modify('sunday this week');

        $sql = "SELECT m.id_match, m.id_equipe_dom, m.id_equipe_ext, 
                       DATE_FORMAT(m.date_reception, '%d/%m/%Y') as match_date,
                       e1.nom_equipe as nom_dom, e2.nom_equipe as nom_ext
                FROM matches m
                JOIN equipes e1 ON m.id_equipe_dom = e1.id_equipe
                JOIN equipes e2 ON m.id_equipe_ext = e2.id_equipe
                WHERE m.date_reception BETWEEN STR_TO_DATE(?, '%d/%m/%Y') AND STR_TO_DATE(?, '%d/%m/%Y')
                AND m.match_status NOT IN ('ARCHIVED')
                AND (m.id_equipe_dom IN (?, ?) OR m.id_equipe_ext IN (?, ?))";
        $bindings = array(
            array('type' => 's', 'value' => $week_start->format('d/m/Y')),
            array('type' => 's', 'value' => $week_end->format('d/m/Y')),
            array('type' => 'i', 'value' => $home_team_id),
            array('type' => 'i', 'value' => $away_team_id),
            array('type' => 'i', 'value' => $home_team_id),
            array('type' => 'i', 'value' => $away_team_id),
        );
        if ($exclude_match_id) {
            $sql .= " AND m.id_match != ?";
            $bindings[] = array('type' => 'i', 'value' => $exclude_match_id);
        }

        $matches = $this->sql_manager->execute($sql, $bindings);
        return $this->build_week_conflicts($matches, $home_team_id, $away_team_id);
    }

    /**
     * @param int $id_match
     * @param string $new_date format d/m/Y
     * @param int $gymnasium_id
     * @param bool $invert_reception
     * @param string|null $comment
     * @throws Exception
     */
    public function modify_match_date(int $id_match, string $new_date, int $gymnasium_id = 0, bool $invert_reception = false, ?string $comment = null): void
    {
        if (!$this->is_match_date_modification_allowed($id_match)) {
            throw new Exception("Vous n'êtes pas autorisé à modifier la date de ce match !");
        }

        $match = $this->get_match($id_match);
        $old_date = $match['date_reception'];

        if ($invert_reception) {
            $opposite_gym = $this->get_team_gymnasium($match['id_equipe_ext']);
            if ($opposite_gym) {
                $gymnasium_id = $opposite_gym;
            }
        }
        if ($gymnasium_id <= 0) {
            $gymnasium_id = $match['id_gymnasium'];
        }

        $bindings = array();
        $sql = "UPDATE matches SET date_reception = STR_TO_DATE(?, '%d/%m/%Y'), id_gymnasium = ?";
        $bindings[] = array('type' => 's', 'value' => $new_date);
        $bindings[] = array('type' => 'i', 'value' => $gymnasium_id);

        if ($invert_reception) {
            $sql .= ", id_equipe_dom = ?, id_equipe_ext = ?";
            $bindings[] = array('type' => 'i', 'value' => $match['id_equipe_ext']);
            $bindings[] = array('type' => 'i', 'value' => $match['id_equipe_dom']);
        }

        if ($comment) {
            $sql .= ", note = ?";
            $bindings[] = array('type' => 's', 'value' => trim(($match['note'] ?? '') . "\n" . $comment));
        }

        $sql .= " WHERE id_match = ?";
        $bindings[] = array('type' => 'i', 'value' => $id_match);

        $this->sql_manager->execute($sql, $bindings);
        $this->addActivity("Le match {$match['code_match']} a vu sa date modifiée pour le $new_date");

        // Re-fetch updated match for email (teams may have been inverted)
        $updated_match = $this->get_match($id_match);
        $this->send_date_change_notification($updated_match, $old_date, $new_date, $invert_reception, $comment);
    }

    /**
     * @param int $id_match
     * @return bool
     * @throws Exception
     */
    public function is_match_date_modification_allowed(int $id_match): bool
    {
        $match = $this->get_match($id_match);

        if ($match['match_status'] !== 'NOT_CONFIRMED') {
            return false;
        }

        $this->getCurrentUserDetails();
        if (UserManager::isAdmin()) {
            return true;
        }
        if (UserManager::isTeamLeader()) {
            return $this->isUserTeamInMatch($match);
        }
        return false;
    }

    /**
     * @param string $code_competition
     * @return array
     * @throws Exception
     */
    private function get_competition_details(string $code_competition): array
    {
        $sql = "SELECT c.*, DATE_FORMAT(c.start_date, '%d/%m/%Y') AS start_date_formatted, d.date_limite AS limit_date 
                FROM competitions c 
                LEFT JOIN dates_limite d ON d.code_competition = c.code_competition 
                WHERE c.code_competition = ?";
        $bindings = array(array('type' => 's', 'value' => $code_competition));
        $results = $this->sql_manager->execute($sql, $bindings);

        if (empty($results)) {
            throw new Exception("Compétition non trouvée : $code_competition");
        }
        return $results[0];
    }

    /**
     * @param array $competition
     * @return DateTime
     */
    private function get_competition_end_date(array $competition): DateTime
    {
        if (!empty($competition['limit_date'])) {
            $end = DateTime::createFromFormat('d/m/Y', $competition['limit_date']);
            if ($end) {
                return $end;
            }
        }
        return $this->parse_date_dmY($competition['start_date_formatted'])->modify('+1 year');
    }

    /**
     * @param int $team_id
     * @return array List of French day names (e.g. ['Lundi', 'Mercredi'])
     * @throws Exception
     */
    private function get_team_reception_days(int $team_id): array
    {
        $sql = "SELECT DISTINCT c.jour FROM creneau c WHERE c.id_equipe = ? ORDER BY c.usage_priority";
        $bindings = array(array('type' => 'i', 'value' => $team_id));
        $results = $this->sql_manager->execute($sql, $bindings);
        return array_column($results, 'jour');
    }

    /**
     * @param DateTime $date
     * @return string French day name
     */
    private function get_french_day_name(DateTime $date): string
    {
        $days = array(1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche');
        return $days[(int)$date->format('N')];
    }

    /**
     * @param string $date_string format d/m/Y
     * @return DateTime
     * @throws Exception
     */
    private function parse_date_dmY(string $date_string): DateTime
    {
        $date = DateTime::createFromFormat('d/m/Y', $date_string);
        if (!$date) {
            throw new Exception("Format de date invalide : $date_string (attendu d/m/Y)");
        }
        $date->setTime(0, 0, 0);
        return $date;
    }

    /**
     * Get French public holidays for a given year.
     * @param int $year
     * @return array of Y-m-d strings
     */
    private function get_french_public_holidays(int $year): array
    {
        $url = "https://calendrier.api.gouv.fr/jours-feries/metropole/$year.json";
        $context = stream_context_create(array('http' => array('timeout' => 5)));
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            error_log("Failed to fetch public holidays from API for year $year");
            return array();
        }
        $data = json_decode($response, true);
        if (empty($data)) {
            return array();
        }
        return array_keys($data);
    }

    /**
     * Get Zone B school holidays from the French government API.
     * Returns an array of [start_date, end_date] periods in Y-m-d format.
     * @param DateTime $start_date
     * @param DateTime $end_date
     * @return array of ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     */
    private function get_school_holidays_zone_b(DateTime $start_date, DateTime $end_date): array
    {
        $start_month = (int)$start_date->format('m');
        $start_year = (int)$start_date->format('Y');
        if ($start_month >= 8) {
            $annee_scolaire = $start_year . '-' . ($start_year + 1);
        } else {
            $annee_scolaire = ($start_year - 1) . '-' . $start_year;
        }

        $url = 'https://data.education.gouv.fr/api/explore/v2.1/catalog/datasets/fr-en-calendrier-scolaire/records'
            . '?where=' . urlencode("zones=\"Zone B\" AND annee_scolaire=\"$annee_scolaire\"")
            . '&limit=100';

        $context = stream_context_create(array('http' => array('timeout' => 5)));
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            error_log("Failed to fetch school holidays from API for $annee_scolaire");
            return array();
        }

        $data = json_decode($response, true);
        if (empty($data['results'])) {
            return array();
        }

        $periods = array();
        $seen = array();
        foreach ($data['results'] as $record) {
            $desc = $record['description'] ?? '';
            if ($desc === "Vacances d'Été") {
                continue;
            }
            $s = substr($record['start_date'] ?? '', 0, 10);
            $e = substr($record['end_date'] ?? '', 0, 10);
            $key = "$s|$e";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $periods[] = array('start' => $s, 'end' => $e, 'description' => $desc);
        }
        return $periods;
    }

    /**
     * Check if a date falls on a French public holiday or during Zone B school holidays.
     * @param string $date_ymd Y-m-d format
     * @param array $public_holidays array of Y-m-d strings
     * @param array $school_holiday_periods array of ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     * @return bool
     */
    private function is_holiday_or_vacation(string $date_ymd, array $public_holidays, array $school_holiday_periods): bool
    {
        if (in_array($date_ymd, $public_holidays)) {
            return true;
        }
        foreach ($school_holiday_periods as $period) {
            if ($date_ymd >= $period['start'] && $date_ymd <= $period['end']) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $team_id
     * @return int|null
     * @throws Exception
     */
    private function get_team_gymnasium(int $team_id): ?int
    {
        $sql = "SELECT c.id_gymnase FROM creneau c WHERE c.id_equipe = ? ORDER BY c.usage_priority LIMIT 1";
        $bindings = array(array('type' => 'i', 'value' => $team_id));
        $results = $this->sql_manager->execute($sql, $bindings);
        return empty($results) ? null : (int)$results[0]['id_gymnase'];
    }

    /**
     * Batch: get dates where each team is busy in the period (excluding a match).
     * @param int $home_team_id
     * @param int $away_team_id
     * @param string $start format d/m/Y
     * @param string $end format d/m/Y
     * @param int $exclude_match_id
     * @return array{home: string[], away: string[]} dates in Y-m-d format
     * @throws Exception
     */
    private function get_team_busy_dates(int $home_team_id, int $away_team_id, string $start, string $end, int $exclude_match_id): array
    {
        $sql = "SELECT DISTINCT DATE_FORMAT(m.date_reception, '%Y-%m-%d') as d, m.id_equipe_dom, m.id_equipe_ext
                FROM matches m
                WHERE m.date_reception BETWEEN STR_TO_DATE(?, '%d/%m/%Y') AND STR_TO_DATE(?, '%d/%m/%Y')
                AND m.match_status NOT IN ('ARCHIVED')
                AND m.id_match != ?
                AND (m.id_equipe_dom IN (?, ?) OR m.id_equipe_ext IN (?, ?))";
        $bindings = array(
            array('type' => 's', 'value' => $start),
            array('type' => 's', 'value' => $end),
            array('type' => 'i', 'value' => $exclude_match_id),
            array('type' => 'i', 'value' => $home_team_id),
            array('type' => 'i', 'value' => $away_team_id),
            array('type' => 'i', 'value' => $home_team_id),
            array('type' => 'i', 'value' => $away_team_id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);

        $home_dates = array();
        $away_dates = array();
        foreach ($results as $row) {
            if ($row['id_equipe_dom'] == $home_team_id || $row['id_equipe_ext'] == $home_team_id) {
                $home_dates[] = $row['d'];
            }
            if ($row['id_equipe_dom'] == $away_team_id || $row['id_equipe_ext'] == $away_team_id) {
                $away_dates[] = $row['d'];
            }
        }
        return array('home' => array_unique($home_dates), 'away' => array_unique($away_dates));
    }

    /**
     * Batch: get gymnasium usage count per date in the period (excluding a match).
     * @param int $gymnasium_id
     * @param string $start format d/m/Y
     * @param string $end format d/m/Y
     * @param int $exclude_match_id
     * @return array{capacity: int, dates: array<string, int>} dates keyed by Y-m-d
     * @throws Exception
     */
    private function get_gymnasium_usage_in_period(int $gymnasium_id, string $start, string $end, int $exclude_match_id): array
    {
        $sql = "SELECT g.nb_terrain,
                       DATE_FORMAT(m.date_reception, '%Y-%m-%d') as d,
                       COUNT(m.id_match) as cnt
                FROM gymnase g
                LEFT JOIN matches m ON g.id = m.id_gymnasium
                    AND m.date_reception BETWEEN STR_TO_DATE(?, '%d/%m/%Y') AND STR_TO_DATE(?, '%d/%m/%Y')
                    AND m.match_status NOT IN ('ARCHIVED')
                    AND m.id_match != ?
                WHERE g.id = ?
                GROUP BY g.nb_terrain, d";
        $bindings = array(
            array('type' => 's', 'value' => $start),
            array('type' => 's', 'value' => $end),
            array('type' => 'i', 'value' => $exclude_match_id),
            array('type' => 'i', 'value' => $gymnasium_id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);

        $capacity = 999;
        $dates = array();
        foreach ($results as $row) {
            $capacity = (int)$row['nb_terrain'];
            if ($row['d'] !== null) {
                $dates[$row['d']] = (int)$row['cnt'];
            }
        }
        return array('capacity' => $capacity, 'dates' => $dates);
    }

    /**
     * Batch: get all blacklisted dates (global) in the period.
     * @param string $start format d/m/Y
     * @param string $end format d/m/Y
     * @return string[] dates in Y-m-d format
     * @throws Exception
     */
    private function get_blacklisted_dates_in_period(string $start, string $end): array
    {
        $sql = "SELECT DATE_FORMAT(closed_date, '%Y-%m-%d') as d 
                FROM blacklist_date 
                WHERE closed_date BETWEEN STR_TO_DATE(?, '%d/%m/%Y') AND STR_TO_DATE(?, '%d/%m/%Y')";
        $bindings = array(
            array('type' => 's', 'value' => $start),
            array('type' => 's', 'value' => $end),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return array_column($results, 'd');
    }

    /**
     * Batch: get all matches involving the two teams in the period (excluding a match).
     * @param int $home_team_id
     * @param int $away_team_id
     * @param string $start format d/m/Y
     * @param string $end format d/m/Y
     * @param int $exclude_match_id
     * @return array
     * @throws Exception
     */
    private function get_team_matches_in_period(int $home_team_id, int $away_team_id, string $start, string $end, int $exclude_match_id): array
    {
        $sql = "SELECT m.id_match, m.id_equipe_dom, m.id_equipe_ext,
                       DATE_FORMAT(m.date_reception, '%Y-%m-%d') as match_date_ymd,
                       DATE_FORMAT(m.date_reception, '%d/%m/%Y') as match_date,
                       e1.nom_equipe as nom_dom, e2.nom_equipe as nom_ext
                FROM matches m
                JOIN equipes e1 ON m.id_equipe_dom = e1.id_equipe
                JOIN equipes e2 ON m.id_equipe_ext = e2.id_equipe
                WHERE m.date_reception BETWEEN STR_TO_DATE(?, '%d/%m/%Y') AND STR_TO_DATE(?, '%d/%m/%Y')
                AND m.match_status NOT IN ('ARCHIVED')
                AND m.id_match != ?
                AND (m.id_equipe_dom IN (?, ?) OR m.id_equipe_ext IN (?, ?))";
        $bindings = array(
            array('type' => 's', 'value' => $start),
            array('type' => 's', 'value' => $end),
            array('type' => 'i', 'value' => $exclude_match_id),
            array('type' => 'i', 'value' => $home_team_id),
            array('type' => 'i', 'value' => $away_team_id),
            array('type' => 'i', 'value' => $home_team_id),
            array('type' => 'i', 'value' => $away_team_id),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Compute week conflicts from pre-fetched matches (no additional SQL).
     * @param array $all_matches
     * @param int $home_team_id
     * @param int $away_team_id
     * @param DateTime $check_date
     * @return array
     */
    private function compute_week_conflicts_from_matches(array $all_matches, int $home_team_id, int $away_team_id, DateTime $check_date): array
    {
        $week_start = (clone $check_date)->modify('monday this week');
        $week_end = (clone $week_start)->modify('sunday this week');

        $home_conflicts = array();
        $away_conflicts = array();

        foreach ($all_matches as $m) {
            $match_dt = new DateTime($m['match_date_ymd']);
            if ($match_dt < $week_start || $match_dt > $week_end) {
                continue;
            }
            if ($m['id_equipe_dom'] == $home_team_id || $m['id_equipe_ext'] == $home_team_id) {
                $home_conflicts[] = array(
                    'team_name' => $m['id_equipe_dom'] == $home_team_id ? $m['nom_dom'] : $m['nom_ext'],
                    'match_date' => $m['match_date'],
                    'opponent' => $m['id_equipe_dom'] == $home_team_id ? $m['nom_ext'] : $m['nom_dom']
                );
            }
            if ($m['id_equipe_dom'] == $away_team_id || $m['id_equipe_ext'] == $away_team_id) {
                $away_conflicts[] = array(
                    'team_name' => $m['id_equipe_dom'] == $away_team_id ? $m['nom_dom'] : $m['nom_ext'],
                    'match_date' => $m['match_date'],
                    'opponent' => $m['id_equipe_dom'] == $away_team_id ? $m['nom_ext'] : $m['nom_dom']
                );
            }
        }

        return array('home_team_conflicts' => $home_conflicts, 'away_team_conflicts' => $away_conflicts);
    }

    /**
     * Build week conflicts array from query results.
     * @param array $matches
     * @param int $home_team_id
     * @param int $away_team_id
     * @return array
     */
    private function build_week_conflicts(array $matches, int $home_team_id, int $away_team_id): array
    {
        $home_conflicts = array();
        $away_conflicts = array();

        foreach ($matches as $m) {
            if ($m['id_equipe_dom'] == $home_team_id || $m['id_equipe_ext'] == $home_team_id) {
                $home_conflicts[] = array(
                    'team_name' => $m['id_equipe_dom'] == $home_team_id ? $m['nom_dom'] : $m['nom_ext'],
                    'match_date' => $m['match_date'],
                    'opponent' => $m['id_equipe_dom'] == $home_team_id ? $m['nom_ext'] : $m['nom_dom']
                );
            }
            if ($m['id_equipe_dom'] == $away_team_id || $m['id_equipe_ext'] == $away_team_id) {
                $away_conflicts[] = array(
                    'team_name' => $m['id_equipe_dom'] == $away_team_id ? $m['nom_dom'] : $m['nom_ext'],
                    'match_date' => $m['match_date'],
                    'opponent' => $m['id_equipe_dom'] == $away_team_id ? $m['nom_ext'] : $m['nom_dom']
                );
            }
        }

        return array('home_team_conflicts' => $home_conflicts, 'away_team_conflicts' => $away_conflicts);
    }

    /**
     * Send email notification for date change using the Emails system.
     * @param array $match updated match data
     * @param string $old_date
     * @param string $new_date
     * @param bool $invert_reception
     * @param string|null $comment
     * @throws Exception
     */
    private function send_date_change_notification(array $match, string $old_date, string $new_date, bool $invert_reception, ?string $comment): void
    {
        try {
            $email_dom = $this->team->getTeamEmail($match['id_equipe_dom']);
        } catch (Exception $e) {
            $email_dom = '';
        }
        try {
            $email_ext = $this->team->getTeamEmail($match['id_equipe_ext']);
        } catch (Exception $e) {
            $email_ext = '';
        }
        if (empty($email_dom) && empty($email_ext)) {
            return;
        }

        $emails = new Emails();
        $emails->insert_generic_email(
            __DIR__ . '/../templates/emails/sendMailDateModification.fr.html',
            array(
                'code_match' => $match['code_match'],
                'equipe_dom' => $match['equipe_dom'] ?? '',
                'equipe_ext' => $match['equipe_ext'] ?? '',
                'old_date' => $old_date,
                'new_date' => $new_date,
                'gymnasium' => $match['gymnasium'] ?? '',
                'inversion' => $invert_reception ? "<strong>Attention : La réception a été inversée !</strong><br>" : '',
                'commentaire' => $comment ? "Commentaire : $comment<br>" : '',
            ),
            $email_dom,
            $email_ext
        );
    }
}
