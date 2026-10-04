<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/Players.php';

/**
 * Fusion de deux fiches d'un même joueur (issue #409, lot 2).
 *
 * Données dans une compétition de test « pm » ; joueurs « PMTEST ».
 */
class PlayerMergeTest extends UfolepTestCase
{
    private int $club;
    private int $team_a;
    private int $team_b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'pm', libelle = 'PM test', id_compet_maitre = 'pm'");
        $this->club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'PM club'");
        $this->team_a = (int)$this->sql->execute("INSERT INTO equipes SET nom_equipe = 'PM Equipe A', code_competition = 'pm', id_club = ?",
            array(array('type' => 'i', 'value' => $this->club)));
        $this->team_b = (int)$this->sql->execute("INSERT INTO equipes SET nom_equipe = 'PM Equipe B', code_competition = 'pm', id_club = ?",
            array(array('type' => 'i', 'value' => $this->club)));
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'PMT%'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE code_competition = 'pm')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'PMTEST'");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login = 'pm_compte'");
        $this->sql->execute("DELETE FROM equipes WHERE code_competition = 'pm'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'PM club'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'pm'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%PMTEST%'");
    }

    private function player(string $prenom, array $fields = array()): int
    {
        $sets = array("nom = 'PMTEST'", 'prenom = ?', 'id_club = ?');
        $bindings = array(array('type' => 's', 'value' => $prenom), array('type' => 'i', 'value' => $this->club));
        foreach ($fields as $key => $value) {
            $sets[] = "$key = ?";
            $bindings[] = array('type' => is_int($value) ? 'i' : 's', 'value' => $value);
        }
        return (int)$this->sql->execute("INSERT INTO joueurs SET " . implode(', ', $sets), $bindings);
    }

    private function member(int $player, int $team, int $leader = 0): void
    {
        $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?, is_leader = ?", array(
            array('type' => 'i', 'value' => $player),
            array('type' => 'i', 'value' => $team),
            array('type' => 'i', 'value' => $leader),
        ));
    }

    private function match(string $code): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = 'pm', division = '1',
                                     id_equipe_dom = ?, id_equipe_ext = ?, date_reception = CURRENT_DATE",
            array(
                array('type' => 's', 'value' => $code),
                array('type' => 'i', 'value' => $this->team_a),
                array('type' => 'i', 'value' => $this->team_b),
            ));
    }

    private function onSheet(int $match, int $player): void
    {
        $this->sql->execute("INSERT INTO match_player SET id_match = ?, id_player = ?",
            array(array('type' => 'i', 'value' => $match), array('type' => 'i', 'value' => $player)));
    }

    public function test_la_fusion_reporte_tout_sur_la_fiche_gardee(): void
    {
        $compte = (int)$this->sql->execute("INSERT INTO comptes_acces SET login = 'pm_compte', email = 'pm_compte@ufolep.test'");
        $keep = $this->player('Garde', array('email' => 'garde@ufolep.test'));
        $remove = $this->player('Double', array(
            'num_licence' => 'PM0000001', 'departement_affiliation' => 13, 'date_homologation' => '2026-09-20',
            'email' => 'double@ufolep.test', 'telephone' => '0600000000', 'id_compte' => $compte,
        ));
        $this->member($keep, $this->team_a);
        $this->member($remove, $this->team_a, 1);
        $this->member($remove, $this->team_b);
        $both = $this->match('PMT1');
        $only = $this->match('PMT2');
        $this->onSheet($both, $keep);
        $this->onSheet($both, $remove);
        $this->onSheet($only, $remove);

        $this->connect_as_admin();
        (new Players())->mergePlayers((string)$keep, (string)$remove);

        $this->assertSame(array(), $this->sql->execute("SELECT id FROM joueurs WHERE id = $remove"));
        $kept = $this->sql->execute("SELECT * FROM joueurs WHERE id = $keep")[0];
        $this->assertSame('PM0000001', $kept['num_licence'], 'licence reportée');
        $this->assertSame('2026-09-20', $kept['date_homologation'], 'homologation reportée');
        $this->assertSame('garde@ufolep.test', $kept['email'], 'un champ rempli est conservé');
        $this->assertSame('0600000000', $kept['telephone'], 'un champ vide est complété');
        $this->assertEquals($compte, $kept['id_compte'], 'compte reporté');

        $teams = $this->sql->execute("SELECT id_equipe, is_leader + 0 AS leader FROM joueur_equipe WHERE id_joueur = $keep ORDER BY id_equipe");
        $this->assertSame(array($this->team_a, $this->team_b), array_map('intval', array_column($teams, 'id_equipe')));
        $this->assertEquals(1, $teams[0]['leader'], 'le rôle de responsable est repris');

        $sheets = $this->sql->execute("SELECT id_match FROM match_player WHERE id_player = $keep ORDER BY id_match");
        $this->assertSame(array($both, $only), array_map('intval', array_column($sheets, 'id_match')),
            'chaque feuille une seule fois');

        $this->assertCount(1, $this->sql->execute(
            "SELECT id FROM activity WHERE comment LIKE 'Fusion de la fiche PMTEST Double%dans PMTEST Garde%'"));
    }

    public function test_reserve_a_l_administrateur(): void
    {
        $keep = $this->player('Garde');
        $remove = $this->player('Double');
        $this->connect_as_club_leader($this->club);
        try {
            (new Players())->mergePlayers((string)$keep, (string)$remove);
            $this->fail('La fusion doit être refusée à un responsable');
        } catch (Exception $e) {
            $this->assertSame(403, $e->getCode());
        }
        $this->assertCount(2, $this->sql->execute("SELECT id FROM joueurs WHERE nom = 'PMTEST'"));
    }

    public function test_deux_fiches_differentes(): void
    {
        $keep = $this->player('Garde');
        $this->connect_as_admin();
        $this->expectExceptionCode(400);
        (new Players())->mergePlayers((string)$keep, (string)$keep);
    }
}
