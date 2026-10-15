<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Team.php';

/**
 * Issue #431 — qui peut consulter la fiche d'une équipe : un administrateur,
 * toutes ; sinon ses équipes, celles de ses clubs, et celles de leurs
 * adversaires dans un match confirmé ou non (pas archivé).
 *
 * Équipes : A1, A2 (club A), B1, B2 (club B), C1 (club C). Compétition de
 * test « ts ».
 */
class TeamSheetAccessTest extends UfolepTestCase
{
    private array $club = array();
    private array $team = array();
    private int $id_user;
    private int $id_court;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'ts', libelle = 'team sheet tests', id_compet_maitre = 'ts'");
        foreach (array('A', 'B', 'C') as $club) {
            $this->club[$club] = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'FICHE club $club'");
        }
        foreach (array('A1' => 'A', 'A2' => 'A', 'B1' => 'B', 'B2' => 'B', 'C1' => 'C') as $name => $club) {
            $this->team[$name] = (int)$this->sql->execute(
                "INSERT INTO equipes SET code_competition = 'ts', nom_equipe = ?, id_club = ?",
                array(
                    array('type' => 's', 'value' => "FICHE $name"),
                    array('type' => 'i', 'value' => $this->club[$club]),
                ));
        }
        $this->id_court = (int)$this->sql->execute("INSERT INTO gymnase SET nom = 'FICHE gymnase'");
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
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'FICHE%'");
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = 'fiche_resp')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login = 'fiche_resp'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'FICHE %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'FICHE club %'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'FICHE gymnase'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'ts'");
    }

    private function match(string $dom, string $ext, string $status): void
    {
        static $n = 0;
        $n++;
        $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = 'ts', division = '1',
                id_equipe_dom = ?, id_equipe_ext = ?, date_reception = '2031-01-15',
                date_original = '2031-01-15', id_gymnasium = ?, match_status = ?",
            array(
                array('type' => 's', 'value' => "FICHE$n"),
                array('type' => 'i', 'value' => $this->team[$dom]),
                array('type' => 'i', 'value' => $this->team[$ext]),
                array('type' => 'i', 'value' => $this->id_court),
                array('type' => 's', 'value' => $status),
            ));
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

    public function test_un_responsable_voit_ses_adversaires_meme_sans_match_confirme(): void
    {
        // le cas signalé : en début de saison, aucun match n'est confirmé
        $this->match('A1', 'B1', 'NOT_CONFIRMED');
        $this->match('B2', 'A1', 'CONFIRMED');
        $this->match('A1', 'C1', 'ARCHIVED');
        $this->connect_as_leader_of(array('A1'), 'A1');

        $this->assertTrue($this->allowed('A1'), 'sa propre équipe');
        $this->assertTrue($this->allowed('B1'), 'adversaire, match non confirmé');
        $this->assertTrue($this->allowed('B2'), 'adversaire, match confirmé');
        $this->assertFalse($this->allowed('C1'), "adversaire d'un match archivé (saison passée)");
    }

    public function test_sans_match_pas_de_fiche(): void
    {
        $this->match('B1', 'B2', 'NOT_CONFIRMED');
        $this->connect_as_leader_of(array('A1'), 'A1');

        $this->assertFalse($this->allowed('B1'), "équipe que les siennes ne rencontrent pas");
    }

    public function test_toutes_les_equipes_du_compte_comptent_pas_seulement_la_courante(): void
    {
        // équipe courante : A2 ; son autre équipe, A1, rencontre B1
        $this->match('A1', 'B1', 'NOT_CONFIRMED');
        $this->connect_as_leader_of(array('A1', 'A2'), 'A2');

        $this->assertTrue($this->allowed('B1'));
    }

    public function test_un_responsable_de_club_voit_les_adversaires_des_equipes_de_ses_clubs(): void
    {
        $this->match('A1', 'B1', 'NOT_CONFIRMED');
        // phase finale de coupe : adversaire d'une autre division
        $this->match('C1', 'A2', 'CONFIRMED');
        $this->connect_as_club_leader($this->club['A']);
        $_SESSION['id_user'] = $this->id_user;

        $this->assertTrue($this->allowed('A2'), 'une équipe de son club');
        $this->assertTrue($this->allowed('B1'), 'adversaire de A1');
        $this->assertTrue($this->allowed('C1'), 'adversaire de A2');
        $this->assertFalse($this->allowed('B2'), 'aucune équipe du club ne la rencontre');
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
        $this->match('A1', 'B1', 'NOT_CONFIRMED');
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
