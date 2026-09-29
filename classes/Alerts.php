<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/UserManager.php';

/**
 * Alertes du tableau de bord responsable (issue #346).
 *
 * Une alerte porte : l'équipe concernée, un texte, une criticité
 * (error / warning / info), un code d'aide (`expected_action`, décliné en
 * texte par TeamLeaderAlerts.js) et le lien de l'écran où la corriger.
 *
 * Portée : l'équipe courante de la session (responsable d'équipe) et, pour un
 * responsable de club, les équipes engagées de ses clubs. Trois familles,
 * retenues par la commission : actions de match en attente (et pénalités
 * reçues), joueurs (licence, photo, validation), effectif et rôles.
 */
class Alerts extends Generic
{
    const LINK_PLAYERS = '/pages/my_page.html#/players';
    const LINK_TIMESLOTS = '/pages/my_page.html#/timeslots';
    const LINK_MATCHES = '/pages/my_page.html#/team_matchs';

    /** Effectif minimal et nombre minimal de femmes / d'hommes, par compétition. */
    const MIN_PLAYERS = array('m' => 6, 'c' => 6, 'cf' => 6, 'mo' => 4, 'f' => 4, 't' => 4, 'ff' => 4, 'kh' => 4, 'kf' => 4);
    const MIN_WOMEN = array('f' => 4, 't' => 4, 'ff' => 4, 'kh' => 2, 'kf' => 2, 'mo' => 1);
    const MIN_MEN = array('mo' => 1);

    public function __construct()
    {
        parent::__construct();
    }

    public function getInfos()
    {
        return array(
            array(
                'description' => "Rejoindre le groupe WhatsApp pour se tenir informé tout au long de la saison",
                'img_src' => '/images/teams-whatsapp.jpeg',),
        );
    }

    /**
     * @throws Exception
     */
    public function getAlerts(): array
    {
        @session_start();
        if (UserManager::isAdmin()) {
            return array();
        }
        $results = array();
        foreach ($this->alert_teams() as $id_equipe => $team_name) {
            foreach ($this->team_alerts($id_equipe) as $alert) {
                $results[] = $alert + array('id_equipe' => $id_equipe, 'team' => $team_name);
            }
        }
        foreach ($this->pending_match_alerts() as $alert) {
            $results[] = $alert;
        }
        foreach ($results as $index => $alert) {
            $results[$index]['owner'] = $_SESSION['login'] ?? null;
        }
        return $results;
    }

    /**
     * Équipes dont le compte connecté voit les alertes : id_equipe => nom.
     * @return array<int, string>
     * @throws Exception
     */
    private function alert_teams(): array
    {
        $teams = array();
        if (UserManager::isTeamLeader() && !empty($_SESSION['id_equipe'])) {
            $id = Generic::parse_id($_SESSION['id_equipe'], "identifiant d'équipe");
            $teams[$id] = $this->team_name($id);
        }
        if (UserManager::isClubLeader()) {
            require_once __DIR__ . '/Club.php';
            $club_ids = (new Club())->getMyClubIds();
            $placeholders = implode(',', array_fill(0, count($club_ids), '?'));
            // équipes engagées cette saison seulement : une équipe sans
            // classement n'a ni match ni effectif à surveiller
            $rows = $this->sql_manager->execute(
                "SELECT DISTINCT e.id_equipe, e.nom_equipe
                 FROM equipes e JOIN classements c ON c.id_equipe = e.id_equipe
                 WHERE e.id_club IN ($placeholders)
                 ORDER BY e.nom_equipe",
                array_map(static fn($id) => array('type' => 'i', 'value' => $id), $club_ids));
            foreach ($rows as $row) {
                $teams[(int)$row['id_equipe']] = $row['nom_equipe'];
            }
        }
        return $teams;
    }

    private function team_name(int $id_equipe): string
    {
        $rows = $this->sql_manager->execute("SELECT nom_equipe FROM equipes WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $id_equipe)));
        return $rows[0]['nom_equipe'] ?? '';
    }

    private function alert(string $issue, string $criticity, string $expected_action, string $link): array
    {
        return array('issue' => $issue, 'criticity' => $criticity, 'expected_action' => $expected_action, 'link' => $link);
    }

    /**
     * Effectif, rôles et joueurs d'une équipe.
     * @throws Exception
     */
    public function team_alerts(int $id_equipe): array
    {
        $alerts = array();
        $squad = $this->squad($id_equipe);
        $code = $squad['code_competition'];
        if ($squad['total'] < (self::MIN_PLAYERS[$code] ?? 0)) {
            $alerts[] = $this->alert("Pas assez de joueurs dans l'équipe ({$squad['total']})", 'error', 'showHelpAddPlayer', self::LINK_PLAYERS);
        }
        if ($squad['women'] < (self::MIN_WOMEN[$code] ?? 0)) {
            $alerts[] = $this->alert("Pas assez de filles dans l'équipe", 'error', 'showHelpAddPlayer', self::LINK_PLAYERS);
        }
        if ($squad['men'] < (self::MIN_MEN[$code] ?? 0)) {
            $alerts[] = $this->alert("Pas assez de garçons dans l'équipe", 'error', 'showHelpAddPlayer', self::LINK_PLAYERS);
        }
        if (!$this->has_role($id_equipe, 'is_leader')) {
            $alerts[] = $this->alert("Responsable d'équipe non défini", 'error', 'showHelpSelectLeader', self::LINK_PLAYERS);
        }
        if (!$this->has_role($id_equipe, 'is_vice_leader')) {
            $alerts[] = $this->alert("Responsable suppléant d'équipe non défini", 'warning', 'showHelpSelectViceLeader', self::LINK_PLAYERS);
        }
        if (!$this->has_role($id_equipe, 'is_captain')) {
            $alerts[] = $this->alert("Capitaine d'équipe non défini", 'error', 'showHelpSelectCaptain', self::LINK_PLAYERS);
        }
        if (!$this->hasTimeSlot($id_equipe)) {
            $alerts[] = $this->alert("Pas de gymnase de réception", 'info', 'showHelpSelectTimeSlot', self::LINK_TIMESLOTS);
        }
        if (!$this->has_leader_contact($id_equipe, 'telephone', 'telephone2')) {
            $alerts[] = $this->alert("Pas de numéro de téléphone", 'error', 'showHelpAddPhoneNumber', self::LINK_PLAYERS);
        }
        if (!$this->has_leader_contact($id_equipe, 'email', 'email2')) {
            $alerts[] = $this->alert("Pas d'email", 'error', 'showHelpAddEmail', self::LINK_PLAYERS);
        }
        $without_photo = $this->playing_names($id_equipe, "NULLIF(TRIM(j.path_photo), '') IS NULL");
        if (!empty($without_photo)) {
            $alerts[] = $this->alert("Joueurs sans photo : " . $this->name_list($without_photo), 'error',
                'showHelpPlayersWithoutPhoto', self::LINK_PLAYERS);
        }
        $without_licence = $this->playing_names($id_equipe, "NULLIF(TRIM(j.num_licence), '') IS NULL");
        if (!empty($without_licence)) {
            $alerts[] = $this->alert("Joueurs sans licence : " . $this->name_list($without_licence), 'error',
                'showHelpPlayersWithoutLicenceNumber', self::LINK_PLAYERS);
        }
        $inactive = $this->playing_names($id_equipe, "j.est_actif = 0 AND NULLIF(TRIM(j.num_licence), '') IS NOT NULL");
        if (!empty($inactive)) {
            $alerts[] = $this->alert("Licence non encore validée : " . $this->name_list($inactive), 'info',
                'showHelpInactivePlayers', self::LINK_PLAYERS);
        }
        foreach ($this->recent_penalties($id_equipe) as $penalty) {
            $alerts[] = $this->alert("Pénalité automatique (-1 pt) : feuille de match {$penalty['code_match']} "
                . "non signée à 48 h", 'warning', 'showHelpPenalty', self::LINK_MATCHES);
        }
        return $alerts;
    }

    /**
     * Actions en attente sur les matchs joués de l'équipe courante (#240) :
     * présents, fiche équipe, score, feuille de match, sondage.
     * @throws Exception
     */
    private function pending_match_alerts(): array
    {
        if (empty($_SESSION['id_equipe'])) {
            return array();
        }
        require_once __DIR__ . '/MatchMgr.php';
        $team_name = $this->team_name((int)$_SESSION['id_equipe']);
        $alerts = array();
        foreach ((new MatchMgr())->getMyPendingMatchActions() as $action) {
            $alerts[] = $this->alert("Match du {$action['date_reception']} contre {$action['equipe_adverse']} : {$action['label']}",
                    'warning', 'showHelpMatchAction', $action['url'])
                + array('id_equipe' => (int)$_SESSION['id_equipe'], 'team' => $team_name);
        }
        return $alerts;
    }

    /** Effectif jouant (#325) : total, femmes, hommes, et compétition de l'équipe. */
    private function squad(int $id_equipe): array
    {
        $rows = $this->sql_manager->execute(
            "SELECT e.code_competition,
                    COUNT(j.id) AS total,
                    COALESCE(SUM(j.sexe = 'F'), 0) AS women,
                    COALESCE(SUM(j.sexe = 'M'), 0) AS men
             FROM equipes e
             LEFT JOIN joueur_equipe je ON je.id_equipe = e.id_equipe AND je.est_jouant + 0 > 0
             LEFT JOIN joueurs j ON j.id = je.id_joueur
             WHERE e.id_equipe = ?
             GROUP BY e.code_competition",
            array(array('type' => 'i', 'value' => $id_equipe)));
        $row = $rows[0] ?? array('code_competition' => null, 'total' => 0, 'women' => 0, 'men' => 0);
        return array('code_competition' => $row['code_competition'], 'total' => (int)$row['total'],
                     'women' => (int)$row['women'], 'men' => (int)$row['men']);
    }

    private function has_role(int $id_equipe, string $role): bool
    {
        // $role vient du code (colonne fixe), jamais du client
        $rows = $this->sql_manager->execute(
            "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_equipe = ? AND $role + 0 > 0",
            array(array('type' => 'i', 'value' => $id_equipe)));
        return (int)$rows[0]['cnt'] > 0;
    }

    public function hasTimeSlot($id_equipe): bool
    {
        $rows = $this->sql_manager->execute("SELECT COUNT(*) AS cnt FROM creneau WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => (int)$id_equipe)));
        return (int)$rows[0]['cnt'] > 0;
    }

    /** Le responsable ou son suppléant a-t-il un téléphone / un email ? */
    private function has_leader_contact(int $id_equipe, string $column, string $column2): bool
    {
        // colonnes fixes du code, jamais du client
        $rows = $this->sql_manager->execute(
            "SELECT COUNT(*) AS cnt FROM joueur_equipe je
             JOIN joueurs j ON j.id = je.id_joueur
             WHERE je.id_equipe = ?
               AND (je.is_leader + 0 > 0 OR je.is_vice_leader + 0 > 0)
               AND (NULLIF(TRIM(j.$column), '') IS NOT NULL OR NULLIF(TRIM(j.$column2), '') IS NOT NULL)",
            array(array('type' => 'i', 'value' => $id_equipe)));
        return (int)$rows[0]['cnt'] > 0;
    }

    /**
     * Noms des joueurs jouants de l'équipe qui vérifient la condition.
     * @param string $condition fragment SQL fixe du code, sur players_view `j`
     */
    private function playing_names(int $id_equipe, string $condition): array
    {
        $rows = $this->sql_manager->execute(
            "SELECT CONCAT(j.prenom, ' ', j.nom) AS name
             FROM joueur_equipe je
             JOIN players_view j ON j.id = je.id_joueur
             WHERE je.id_equipe = ? AND je.est_jouant + 0 > 0 AND $condition
             ORDER BY j.nom, j.prenom",
            array(array('type' => 'i', 'value' => $id_equipe)));
        return array_column($rows, 'name');
    }

    private function name_list(array $names): string
    {
        $shown = array_slice($names, 0, 5);
        $more = count($names) - count($shown);
        return implode(', ', $shown) . ($more > 0 ? " et $more autre(s)" : '');
    }

    /** Pénalités automatiques des 60 derniers jours (#345). */
    private function recent_penalties(int $id_equipe): array
    {
        return $this->sql_manager->execute(
            "SELECT m.code_match FROM match_penalties p JOIN matches m ON m.id_match = p.id_match
             WHERE p.id_equipe = ? AND p.created_at >= NOW() - INTERVAL 60 DAY
             ORDER BY p.created_at DESC",
            array(array('type' => 'i', 'value' => $id_equipe)));
    }
}
