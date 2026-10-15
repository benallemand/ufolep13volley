<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Team.php';

/**
 * Issue #431 — qui peut consulter la fiche d'une équipe.
 *
 * Compétition de test « ts » :
 * - division 1 : A1 (club A), B1 (club B) ;
 * - division 2 : B2 (club B) ;
 * - division 3 : A2 (club A), C1 (club C).
 * Aucun match : la fiche d'un adversaire ne dépend plus d'un match confirmé
 * (il n'y en a aucun en début de saison).
 */
class TeamSheetAccessTest extends UfolepTestCase
{
    private array $club = array();
    private array $team = array();
    private int $id_user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'ts', libelle = 'team sheet tests', id_compet_maitre = 'ts'");
        foreach (array('A', 'B', 'C') as $club) {
            $this->club[$club] = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'FICHE club $club'");
        }
        foreach (array('A1' => array('A', '1'), 'B1' => array('B', '1'), 'B2' => array('B', '2'),
                     'A2' => array('A', '3'), 'C1' => array('C', '3')) as $name => [$club, $division]) {
            $this->team[$name] = (int)$this->sql->execute(
                "INSERT INTO equipes SET code_competition = 'ts', nom_equipe = ?, id_club = ?",
                array(
                    array('type' => 's', 'value' => "FICHE $name"),
                    array('type' => 'i', 'value' => $this->club[$club]),
                ));
            $this->sql->execute("INSERT INTO classements SET code_competition = 'ts', division = ?, id_equipe = ?",
                array(
                    array('type' => 's', 'value' => $division),
                    array('type' => 'i', 'value' => $this->team[$name]),
                ));
        }
        $this->id_user = (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'fiche_resp', email = 'fiche_resp@ufolep.test'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = 'fiche_resp')");
        $this->sql->execute("DELETE FROM users_clubs WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = 'fiche_resp')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login = 'fiche_resp'");
        $this->sql->execute("DELETE FROM classements WHERE code_competition = 'ts'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'FICHE %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'FICHE club %'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'ts'");
    }

    /** Responsable des équipes données (users_teams), sur l'équipe courante donnée. */
    private function connect_as_leader_of(array $names, string $current): void
    {
        foreach ($names as $name) {
            $this->sql->execute("INSERT INTO users_teams SET user_id = ?, team_id = ?", array(
                array('type' => 'i', 'value' => $this->id_user),
                array('type' => 'i', 'value' => $this->team[$name]),
            ));
        }
        $this->connect_as_team_leader($this->team[$current]);
        $_SESSION['id_user'] = $this->id_user;
    }

    private function allowed(string $name): bool
    {
        return (new Team())->isTeamSheetAllowedForUser($this->team[$name]);
    }

    public function test_un_responsable_voit_les_equipes_de_sa_division(): void
    {
        $this->connect_as_leader_of(array('A1'), 'A1');

        $this->assertTrue($this->allowed('A1'), 'sa propre équipe');
        $this->assertTrue($this->allowed('B1'), 'une autre équipe de sa division, sans match confirmé');
        $this->assertFalse($this->allowed('B2'), "une équipe d'une autre division");
        $this->assertFalse($this->allowed('C1'), "une équipe d'une autre division");
    }

    public function test_toutes_les_equipes_du_compte_comptent_pas_seulement_la_courante(): void
    {
        // équipe courante : A2 (division 3) ; son autre équipe, A1, est en division 1
        $this->connect_as_leader_of(array('A1', 'A2'), 'A2');

        $this->assertTrue($this->allowed('C1'), "division de l'équipe courante");
        $this->assertTrue($this->allowed('B1'), "division d'une autre équipe du compte");
        $this->assertFalse($this->allowed('B2'));
    }

    public function test_un_responsable_de_club_voit_les_divisions_des_equipes_de_ses_clubs(): void
    {
        // compte de club, sans équipe : le club A a A1 (division 1) et A2 (division 3)
        $this->connect_as_club_leader($this->club['A']);
        $_SESSION['id_user'] = $this->id_user;

        $this->assertTrue($this->allowed('A2'), 'une équipe de son club');
        $this->assertTrue($this->allowed('B1'), 'la division de A1');
        $this->assertTrue($this->allowed('C1'), 'la division de A2');
        $this->assertFalse($this->allowed('B2'), "aucune équipe du club en division 2");
    }

    public function test_un_administrateur_voit_toutes_les_fiches(): void
    {
        $this->connect_as_admin();

        foreach (array_keys($this->team) as $name) {
            $this->assertTrue($this->allowed($name), $name);
        }
    }

    public function test_un_compte_sans_equipe_ni_club_ne_voit_rien(): void
    {
        $this->connect_as_team_leader(null);
        $_SESSION['is_team_leader'] = false;
        $_SESSION['id_user'] = $this->id_user;

        $this->assertFalse($this->allowed('B1'));
    }

    public function test_la_fiche_refuse_avec_un_message(): void
    {
        $this->connect_as_leader_of(array('A1'), 'A1');

        $this->expectExceptionMessage("Vous n'avez pas la permission de télécharger cette fiche équipe !");
        (new Team())->getTeamSheet($this->team['B2']);
    }
}
