<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchMgr.php';

/**
 * Workflow de report : qui peut demander, accepter, refuser un report et en
 * donner la date, et dans quel état du match.
 *
 * Les quatre actions répondaient 403 (absentes de `rest/access.php`) ; avant de
 * les rouvrir, `MatchMgr::assert_report_action_allowed()` impose les règles de
 * `utils/reportUtils.js` : l'équipe de la session joue le match, on accepte ou
 * refuse la demande de l'adversaire (pas la sienne), seule l'équipe qui a
 * accepté donne la date, jamais une fois le score saisi. La commission peut
 * refuser un report sans jouer le match.
 *
 * Deux matchs de test : A (équipe 1 à domicile contre équipe 2) et B (équipe 3
 * contre équipe 4), qui sert d'équipe étrangère au match A.
 *
 * Pas de @dataProvider (incompatible avec le constructeur de UfolepTestCase) :
 * des boucles.
 */
class ReportWorkflowAccessTest extends UfolepTestCase
{
    private const CODE_A = 'RPTACC_A';
    private const CODE_B = 'RPTACC_B';
    private const ACTIONS = array('askForReport', 'acceptReport', 'refuseReport', 'giveReportDate');

    private MatchMgr $match_manager;
    /** @var int[] équipes 1 à 4 */
    private array $teams = array();

    protected function setUp(): void
    {
        parent::setUp();
        $this->match_manager = new MatchMgr();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'ur', libelle = 'report access tests', id_compet_maitre = 'ur'");
        for ($i = 1; $i <= 4; $i++) {
            $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'rptacc club $i'");
            $this->teams[$i] = $this->sql->execute(
                "INSERT INTO equipes SET code_competition = 'ur', nom_equipe = 'rptacc team $i', id_club = ?",
                array(array('type' => 'i', 'value' => $id_club)));
            // un responsable joignable (emails du workflow) et une ligne de
            // classement (compteur de reports)
            $id_player = $this->sql->execute(
                "INSERT INTO joueurs SET nom = 'Rptacc', prenom = 'Resp $i', sexe = 'M', email = ?, id_club = ?",
                array(
                    array('type' => 's', 'value' => "rptacc_$i@test.fr"),
                    array('type' => 'i', 'value' => $id_club),
                ));
            $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?, is_leader = 1", array(
                array('type' => 'i', 'value' => $id_player),
                array('type' => 'i', 'value' => $this->teams[$i]),
            ));
            $this->sql->execute("INSERT INTO classements SET code_competition = 'ur', division = '1', id_equipe = ?, report_count = 0",
                array(array('type' => 'i', 'value' => $this->teams[$i])));
        }
        $id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'rptacc court'");
        $this->create_match(self::CODE_A, $this->teams[1], $this->teams[2], $id_court);
        $this->create_match(self::CODE_B, $this->teams[3], $this->teams[4], $id_court);
    }

    private function create_match(string $code, int $dom, int $ext, int $id_court): void
    {
        $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = 'ur', division = '1',
                id_equipe_dom = ?, id_equipe_ext = ?, date_reception = '2031-01-15',
                id_gymnasium = ?, date_original = '2031-01-15', match_status = 'CONFIRMED'",
            array(
                array('type' => 's', 'value' => $code),
                array('type' => 'i', 'value' => $dom),
                array('type' => 'i', 'value' => $ext),
                array('type' => 'i', 'value' => $id_court),
            ));
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM emails WHERE subject LIKE '%RPTACC\\_%'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%RPTACC\\_%' OR comment LIKE '%rptacc team %'");
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'RPTACC\\_%'");
        $this->sql->execute("DELETE FROM classements WHERE code_competition = 'ur'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_joueur IN (SELECT id FROM joueurs WHERE nom = 'Rptacc')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'Rptacc'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'rptacc team %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'rptacc club %'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'rptacc court'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'ur'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function set_match_state(string $report_status, string $match_status = 'CONFIRMED', bool $score_filled = false): void
    {
        $sets = $score_filled ? 25 : 0;
        $this->sql->execute(
            "UPDATE matches SET report_status = ?, match_status = ?,
                set_1_dom = ?, set_2_dom = ?, set_3_dom = ?
             WHERE code_match = ?",
            array(
                array('type' => 's', 'value' => $report_status),
                array('type' => 's', 'value' => $match_status),
                array('type' => 'i', 'value' => $sets),
                array('type' => 'i', 'value' => $sets),
                array('type' => 'i', 'value' => $sets),
                array('type' => 's', 'value' => self::CODE_A),
            ));
    }

    /**
     * Tout ce qu'une action de report peut écrire : statut, date, compteurs.
     */
    private function match_state(): array
    {
        $match = $this->sql->execute(
            "SELECT report_status, date_reception FROM matches WHERE code_match = ?",
            array(array('type' => 's', 'value' => self::CODE_A)));
        $counts = $this->sql->execute(
            "SELECT id_equipe, report_count FROM classements WHERE code_competition = 'ur' ORDER BY id_equipe");
        return array('match' => $match[0], 'report_counts' => $counts);
    }

    private function call_action(string $action): void
    {
        switch ($action) {
            case 'askForReport':
                $this->match_manager->askForReport(self::CODE_A, 'ut');
                break;
            case 'acceptReport':
                $this->match_manager->acceptReport(self::CODE_A);
                break;
            case 'refuseReport':
                $this->match_manager->refuseReport(self::CODE_A, 'ut');
                break;
            case 'giveReportDate':
                $this->match_manager->giveReportDate(self::CODE_A, '15/06/2031');
                break;
        }
    }

    /**
     * L'action doit être refusée avec ce message, et ne rien écrire.
     */
    private function assert_refused(string $action, string $expected_message, string $label): void
    {
        $before = $this->match_state();
        try {
            $this->call_action($action);
            self::fail("$label : l'action aurait dû être refusée");
        } catch (Exception $e) {
            self::assertStringContainsString($expected_message, $e->getMessage(), "$label : " . $e->getMessage());
        }
        self::assertSame($before, $this->match_state(), "$label a écrit malgré le refus");
    }

    /**
     * Le garde seul, sans les écritures ni les emails qui suivent.
     */
    private function guard(string $action): ?int
    {
        $match = $this->match_manager->get_match_by_code_match(self::CODE_A);
        $method = new ReflectionMethod(MatchMgr::class, 'assert_report_action_allowed');
        $method->setAccessible(true);
        return $method->invoke($this->match_manager, $action, $match);
    }

    /**
     * L'état dans lequel chaque action est légitime pour l'une des équipes :
     * le refus observé ne doit donc venir que de l'appartenance au match.
     */
    private static function state_valid_for(string $action): string
    {
        return array(
            'askForReport' => 'NOT_ASKED',
            'acceptReport' => 'ASKED_BY_DOM',
            'refuseReport' => 'ASKED_BY_DOM',
            'giveReportDate' => 'ACCEPTED_BY_DOM',
        )[$action];
    }

    // --- Appartenance au match

    public function test_une_equipe_hors_du_match_ne_peut_rien_faire(): void
    {
        foreach (self::ACTIONS as $action) {
            $this->set_match_state(self::state_valid_for($action));
            $this->connect_as_team_leader($this->teams[3]);
            $this->assert_refused($action, "Seul le responsable d'une des deux équipes", "$action (équipe 3)");
        }
    }

    public function test_une_session_sans_role_responsable_est_refusee(): void
    {
        foreach (self::ACTIONS as $action) {
            $this->set_match_state(self::state_valid_for($action));
            // l'équipe du match en session, mais pas le rôle
            $this->connect_as_team_leader($this->teams[2]);
            $_SESSION['is_team_leader'] = false;
            $this->assert_refused($action, "Seul le responsable d'une des deux équipes", "$action (sans rôle)");
        }
    }

    public function test_la_commission_ne_peut_que_refuser(): void
    {
        foreach (array('askForReport', 'acceptReport', 'giveReportDate') as $action) {
            $this->set_match_state(self::state_valid_for($action));
            $this->connect_as_admin();
            $this->assert_refused($action, "Seul le responsable d'une des deux équipes", "$action (admin)");
        }
        // refuser : oui, sans jouer le match et quel que soit l'état
        foreach (array('ASKED_BY_DOM', 'ACCEPTED_BY_EXT', 'NOT_ASKED') as $report_status) {
            $this->set_match_state($report_status);
            $this->connect_as_admin();
            self::assertNull($this->guard('refuseReport'), "refus de la commission en $report_status");
        }
    }

    public function test_un_admin_responsable_du_match_refuse_au_nom_de_son_equipe(): void
    {
        $this->set_match_state('ASKED_BY_EXT');
        $this->connect_as_team_leader($this->teams[1]);
        $_SESSION['is_admin'] = true;
        self::assertSame($this->teams[1], $this->guard('refuseReport'));
    }

    // --- États

    public function test_accepter_ou_refuser_exige_une_demande_de_l_adversaire(): void
    {
        // l'équipe 1 reçoit : seule une demande de l'équipe 2 (ASKED_BY_EXT) se traite
        $interdits = array('NOT_ASKED', 'ASKED_BY_DOM', 'ACCEPTED_BY_DOM', 'ACCEPTED_BY_EXT',
            'REFUSED_BY_DOM', 'REFUSED_BY_EXT', 'REFUSED_BY_ADMIN');
        foreach (array('acceptReport', 'refuseReport') as $action) {
            foreach ($interdits as $report_status) {
                $this->set_match_state($report_status);
                $this->connect_as_team_leader($this->teams[1]);
                $this->assert_refused($action, "Aucune demande de report de l'équipe adverse",
                    "$action en $report_status (domicile)");
            }
            // et symétriquement pour l'équipe 2
            $this->set_match_state('ASKED_BY_EXT');
            $this->connect_as_team_leader($this->teams[2]);
            $this->assert_refused($action, "Aucune demande de report de l'équipe adverse",
                "$action de sa propre demande (extérieur)");
        }
    }

    public function test_un_report_ne_se_demande_qu_une_fois(): void
    {
        foreach (array('ASKED_BY_DOM', 'ASKED_BY_EXT', 'ACCEPTED_BY_DOM', 'REFUSED_BY_EXT', 'REFUSED_BY_ADMIN') as $report_status) {
            $this->set_match_state($report_status);
            $this->connect_as_team_leader($this->teams[2]);
            $this->assert_refused('askForReport', "Un report a déjà été demandé", "askForReport en $report_status");
        }
    }

    public function test_seule_l_equipe_qui_a_accepte_donne_la_date(): void
    {
        $cas = array(
            // l'équipe 1 a accepté : ce n'est pas à l'équipe 2 de donner la date
            array('ACCEPTED_BY_DOM', 2),
            array('ACCEPTED_BY_EXT', 1),
            // pas encore accepté
            array('ASKED_BY_EXT', 1),
            array('NOT_ASKED', 1),
            array('REFUSED_BY_DOM', 1),
        );
        foreach ($cas as [$report_status, $team]) {
            $this->set_match_state($report_status);
            $this->connect_as_team_leader($this->teams[$team]);
            $this->assert_refused('giveReportDate', "Seule l'équipe qui a accepté le report",
                "giveReportDate en $report_status (équipe $team)");
        }
    }

    public function test_un_match_au_score_saisi_ne_se_reporte_plus(): void
    {
        $cas = array(
            array('askForReport', 'NOT_ASKED', 1),
            array('acceptReport', 'ASKED_BY_EXT', 1),
            array('refuseReport', 'ASKED_BY_EXT', 1),
            array('giveReportDate', 'ACCEPTED_BY_DOM', 1),
        );
        foreach ($cas as [$action, $report_status, $team]) {
            $this->set_match_state($report_status, 'CONFIRMED', true);
            $this->connect_as_team_leader($this->teams[$team]);
            $this->assert_refused($action, "score de ce match est déjà saisi", "$action (score saisi)");
        }
    }

    public function test_un_match_non_confirme_ne_se_reporte_pas(): void
    {
        foreach (self::ACTIONS as $action) {
            $this->set_match_state(self::state_valid_for($action), 'ARCHIVED');
            $this->connect_as_team_leader($this->teams[1]);
            $this->assert_refused($action, "Seuls les matchs confirmés", "$action (archivé)");
        }
        // la commission non plus
        $this->set_match_state('ASKED_BY_DOM', 'ARCHIVED');
        $this->connect_as_admin();
        $this->assert_refused('refuseReport', "Seuls les matchs confirmés", "refuseReport (admin, archivé)");
    }

    public function test_les_etats_attendus_sont_acceptes(): void
    {
        $cas = array(
            array('askForReport', 'NOT_ASKED', 1),
            array('askForReport', 'NOT_ASKED', 2),
            array('acceptReport', 'ASKED_BY_EXT', 1),
            array('acceptReport', 'ASKED_BY_DOM', 2),
            array('refuseReport', 'ASKED_BY_EXT', 1),
            array('refuseReport', 'ASKED_BY_DOM', 2),
            array('giveReportDate', 'ACCEPTED_BY_DOM', 1),
            array('giveReportDate', 'ACCEPTED_BY_EXT', 2),
        );
        foreach ($cas as [$action, $report_status, $team]) {
            $this->set_match_state($report_status);
            $this->connect_as_team_leader($this->teams[$team]);
            self::assertSame($this->teams[$team], $this->guard($action), "$action en $report_status (équipe $team)");
        }
    }

    // --- De bout en bout

    /**
     * Le cas signalé : accepter la demande de l'adversaire passe, et le report
     * est compté à l'équipe qui l'a demandé.
     */
    public function test_accepter_la_demande_de_l_adversaire_compte_le_report_au_demandeur(): void
    {
        $this->set_match_state('ASKED_BY_EXT');
        $this->connect_as_team_leader($this->teams[1]);
        // les gabarits d'email sont lus en `../templates/…`, relatif à rest/
        $cwd = getcwd();
        chdir(__DIR__ . '/../rest');
        try {
            self::assertTrue($this->match_manager->acceptReport(self::CODE_A));
        } finally {
            chdir($cwd);
        }
        $state = $this->match_state();
        self::assertSame('ACCEPTED_BY_DOM', $state['match']['report_status']);
        $counts = array_column($state['report_counts'], 'report_count', 'id_equipe');
        self::assertSame(0, (int)$counts[$this->teams[1]]);
        self::assertSame(1, (int)$counts[$this->teams[2]]);
    }
}
