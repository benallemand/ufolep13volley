<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Players.php';
require_once __DIR__ . '/../classes/Alerts.php';
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/UserManager.php';

/**
 * Issue #419 — un administrateur qui est aussi responsable d'équipe fait, depuis
 * son espace, tout ce que fait un responsable (les rôles se cumulent, #245).
 *
 * L'ajout d'un joueur à l'équipe courante est demandé par l'écran
 * (`add_to_my_team`), jamais déduit du rôle : l'espace responsable le demande,
 * l'administration jamais — sinon chaque fiche qu'un admin-responsable modifie
 * depuis l'administration rejoindrait son équipe (#407).
 */
class AdminLeaderCumulTest extends UfolepTestCase
{
    private int $id_club;
    private int $id_team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'CUMUL club'");
        $this->id_team = (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = 'CUMUL equipe', code_competition = 'm', id_club = ?",
            array(array('type' => 'i', 'value' => $this->id_club)));
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM emails WHERE body LIKE '%CUMULT1%' OR subject LIKE '%CUMULT1%'");
        $this->sql->execute("DELETE FROM matches WHERE code_match = 'CUMULT1'");
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = 'cumul_test')");
        $this->sql->execute("DELETE FROM users_clubs WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = 'cumul_test')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login = 'cumul_test'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_joueur IN (SELECT id FROM joueurs WHERE nom = 'CUMULTEST')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'CUMULTEST'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'CUMUL %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'CUMUL club%'");
    }

    private function connect_as_admin_and_team_leader(): void
    {
        $this->connect_as_team_leader($this->id_team);
        $_SESSION['is_admin'] = true;
    }

    private function player(string $first_name): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO joueurs SET nom = 'CUMULTEST', prenom = ?, sexe = 'M', id_club = ?",
            array(
                array('type' => 's', 'value' => $first_name),
                array('type' => 'i', 'value' => $this->id_club),
            ));
    }

    private function team_player_names(): array
    {
        $rows = $this->sql->execute(
            "SELECT j.prenom FROM joueur_equipe je JOIN joueurs j ON j.id = je.id_joueur
             WHERE je.id_equipe = ? AND j.nom = 'CUMULTEST' ORDER BY j.prenom",
            array(array('type' => 'i', 'value' => $this->id_team)));
        return array_column($rows, 'prenom');
    }

    /** Formulaire de l'espace responsable : création d'un joueur. */
    private function create_from_my_space(string $first_name, $add_to_my_team): void
    {
        (new Players())->update_player(null, $first_name, 'CUMULTEST', 'M', 13, $this->id_club,
            add_to_my_team: $add_to_my_team);
    }

    public function test_admin_responsable_ajoute_un_joueur_existant_a_son_equipe(): void
    {
        $id_player = $this->player('Existant');
        $this->connect_as_admin_and_team_leader();

        $this->assertTrue((new Players())->addPlayerToMyTeam($id_player));
        $this->assertSame(array('Existant'), $this->team_player_names());
    }

    public function test_admin_responsable_cree_un_joueur_depuis_son_espace(): void
    {
        $this->connect_as_admin_and_team_leader();

        $this->create_from_my_space('Nouveau', '1');

        $this->assertSame(array('Nouveau'), $this->team_player_names());
    }

    public function test_admin_responsable_hors_de_son_espace_ne_remplit_pas_son_equipe(): void
    {
        // Sans demande de l'écran (administration, « Équipes / comptes ») :
        // pas d'ajout, c'est ce que #407 protège.
        $this->connect_as_admin_and_team_leader();

        $this->create_from_my_space('Admin', null);
        (new Players())->savePlayer('Ecran admin', 'CUMULTEST', null, null, 'M', 13, $this->id_club);

        $this->assertSame(array(), $this->team_player_names());
    }

    public function test_responsable_non_admin_ajoute_toujours_a_son_equipe(): void
    {
        $this->connect_as_team_leader($this->id_team);

        $this->create_from_my_space('Sans drapeau', null);
        $this->create_from_my_space('Avec drapeau', '1');

        $this->assertSame(array('Avec drapeau', 'Sans drapeau'), $this->team_player_names());
    }

    public function test_joueur_sans_club_rejoint_le_club_de_l_equipe_d_un_admin_responsable(): void
    {
        // `getMyTeamIdClub` répondait false pour un admin : le joueur entrait
        // dans l'équipe sans être rattaché au club, et l'ajout échouait.
        $id_player = $this->player('Sans club');
        $this->sql->execute("UPDATE joueurs SET id_club = NULL WHERE id = ?",
            array(array('type' => 'i', 'value' => $id_player)));
        $this->connect_as_admin_and_team_leader();

        $this->assertTrue((new Players())->addPlayerToMyTeam($id_player));
        $club = $this->sql->execute("SELECT id_club FROM joueurs WHERE id = ?",
            array(array('type' => 'i', 'value' => $id_player)));
        $this->assertEquals($this->id_club, $club[0]['id_club']);
    }

    public function test_admin_responsable_a_les_alertes_de_son_equipe(): void
    {
        // Équipe vide : au moins l'alerte d'effectif.
        $this->connect_as_admin_and_team_leader();

        $alerts = (new Alerts())->getAlerts();

        $this->assertNotEmpty(array_filter($alerts, fn($a) => (int)$a['id_equipe'] === $this->id_team));
    }

    public function test_admin_sans_equipe_n_a_pas_d_alertes(): void
    {
        $this->connect_as_admin();

        $this->assertSame(array(), (new Alerts())->getAlerts());
    }

    public function test_historique_de_l_espace_responsable_limite_a_son_equipe(): void
    {
        $this->connect_as_team_leader($this->id_team);
        $as_leader = (new Players())->getActivity();

        $this->connect_as_admin_and_team_leader();
        $this->assertEquals($as_leader, (new Players())->getActivity(null, '1'));
    }

    public function test_admin_responsable_ne_signe_que_pour_son_equipe(): void
    {
        $opponent = (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = 'CUMUL adversaire', code_competition = 'm', id_club = ?",
            array(array('type' => 'i', 'value' => $this->id_club)));
        $id_match = (int)$this->sql->execute(
            "INSERT INTO matches SET code_match = 'CUMULT1', code_competition = 'm', division = '1',
                                     id_equipe_dom = ?, id_equipe_ext = ?, date_reception = CURRENT_DATE,
                                     match_status = 'CONFIRMED'",
            array(array('type' => 'i', 'value' => $this->id_team), array('type' => 'i', 'value' => $opponent)));
        $this->connect_as_admin_and_team_leader();

        try {
            (new MatchMgr())->sign_team_sheet($id_match);
        } catch (Exception $exception) {
            $this->assertSame(200, $exception->getCode(), $exception->getMessage());
        }

        $match = $this->sql->execute("SELECT is_sign_team_dom + 0 AS dom, is_sign_team_ext + 0 AS ext FROM matches WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $id_match)));
        $this->assertEquals(1, $match[0]['dom']);
        $this->assertEquals(0, $match[0]['ext'], "l'admin-responsable ne signe pas pour l'adversaire");
    }

    public function test_agir_en_tant_que_ne_donne_pas_plus_de_droits_au_responsable_de_club(): void
    {
        // Le compte de l'équipe est admin, et référent d'un autre club.
        $other_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'CUMUL club autre'");
        $id_user = (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'cumul_test', email = 'cumul_test@ufolep.test', is_admin = 1");
        $this->sql->execute("INSERT INTO users_teams SET user_id = ?, team_id = ?",
            array(array('type' => 'i', 'value' => $id_user), array('type' => 'i', 'value' => $this->id_team)));
        $this->sql->execute("INSERT INTO users_clubs SET user_id = ?, club_id = ?",
            array(array('type' => 'i', 'value' => $id_user), array('type' => 'i', 'value' => $other_club)));
        $this->connect_as_club_leader($this->id_club);

        (new UserManager())->switch_to_club_team_leader($id_user);

        $this->assertTrue(UserManager::isTeamLeader());
        $this->assertFalse(UserManager::isAdmin(), "le rôle admin du compte cible n'est pas repris");
        $this->assertNotContains($other_club, $_SESSION['club_ids'], "ni ses autres clubs");
    }

    public function test_import_de_licence_depuis_l_espace_d_un_admin_responsable(): void
    {
        $this->connect_as_admin_and_team_leader();
        $players = new Players();
        $licence = array(
            'last_first_name' => 'CUMULTEST Licence',
            'licence_number' => 'CU0000419',
            'sexe' => 'M',
            'departement' => 13,
            'homologation_date' => '01/10/2026',
            // Club inconnu : l'admin le crée (#404), nettoyé avec les autres.
            'licence_club' => 'CUMUL0419',
            'club' => 'CUMUL club licence',
            'photo' => null,
        );

        $players->search_player_and_save_from_licence($licence, true);
        $this->assertSame(array('Licence'), $this->team_player_names());

        // Le même import depuis l'administration n'ajoute pas.
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->id_team)));
        $players->search_player_and_save_from_licence($licence, false);
        $this->assertSame(array(), $this->team_player_names());
    }
}
