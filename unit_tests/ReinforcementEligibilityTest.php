<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchMgr.php';

/**
 * Issue #349 — un renfort vient d'un autre championnat, ou d'une division
 * strictement inférieure du même championnat.
 *
 * Match de test en division « 98 » du championnat masculin, entre DOM et EXT.
 * Joueurs candidats :
 *   UP     joue en division 97 (supérieure)          -> refusé
 *   SAME   joue en division 98 (même division)       -> refusé
 *   DOWN   joue en division 99 (inférieure)          -> accepté
 *   OTHER  joue en championnat féminin               -> accepté
 *   NONPL  non jouant en 97, jouant en 99            -> accepté (#325)
 */
class ReinforcementEligibilityTest extends UfolepTestCase
{
    private MatchMgr $match_manager;
    private array $team = array();
    private array $player = array();
    private int $id_match;
    private int $id_cup_match;

    protected function setUp(): void
    {
        parent::setUp();
        $this->match_manager = new MatchMgr();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'uv', libelle = 'issue349 coupe', id_compet_maitre = 'uv'");
        $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'issue349 club'");
        $id_photo = $this->sql->execute("INSERT INTO photos SET path_photo = 'players_pics/issue349.jpg'");
        foreach (array('DOM' => array('m', '98'), 'EXT' => array('m', '98'), 'T97' => array('m', '97'),
                       'T98' => array('m', '98'), 'T99' => array('m', '99'), 'TF' => array('f', '1'),
                       'CUP1' => array('uv', '1'), 'CUP2' => array('uv', '1')) as $k => [$code, $division]) {
            $this->team[$k] = $this->sql->execute("INSERT INTO equipes SET code_competition = ?, nom_equipe = ?, id_club = ?",
                array(array('type' => 's', 'value' => $code), array('type' => 's', 'value' => "issue349 $k"),
                      array('type' => 'i', 'value' => $id_club)));
            $this->sql->execute("INSERT INTO classements SET code_competition = ?, division = ?, id_equipe = ?, rank_start = 1, penalite = 0",
                array(array('type' => 's', 'value' => $code), array('type' => 's', 'value' => $division),
                      array('type' => 'i', 'value' => $this->team[$k])));
        }
        $memberships = array('UP' => array(array('T97', 1)), 'SAME' => array(array('T98', 1)), 'DOWN' => array(array('T99', 1)),
                             'OTHER' => array(array('TF', 1)), 'NONPL' => array(array('T97', 0), array('T99', 1)));
        foreach ($memberships as $k => $teams) {
            $this->player[$k] = $this->sql->execute(
                "INSERT INTO joueurs SET nom = 'Issue349', prenom = ?, sexe = 'M', id_photo = ?",
                array(array('type' => 's', 'value' => $k), array('type' => 'i', 'value' => $id_photo)));
            foreach ($teams as [$team, $playing]) {
                $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?, est_jouant = ?",
                    array(array('type' => 'i', 'value' => $this->player[$k]),
                          array('type' => 'i', 'value' => $this->team[$team]),
                          array('type' => 'i', 'value' => $playing)));
            }
        }
        $id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue349 court'");
        $this->id_match = $this->create_match('ISS349_M', 'm', '98', 'DOM', 'EXT', $id_court);
        $this->id_cup_match = $this->create_match('ISS349_C', 'uv', '1', 'CUP1', 'CUP2', $id_court);
        $this->connect_as_team_leader($this->team['DOM']);
    }

    private function create_match(string $code, string $competition, string $division, string $dom, string $ext, int $id_court): int
    {
        return $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = ?, division = ?,
                id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
                date_reception = CURRENT_DATE, date_original = CURRENT_DATE, match_status = 'CONFIRMED'",
            array(array('type' => 's', 'value' => $code), array('type' => 's', 'value' => $competition),
                  array('type' => 's', 'value' => $division), array('type' => 'i', 'value' => $this->team[$dom]),
                  array('type' => 'i', 'value' => $this->team[$ext]), array('type' => 'i', 'value' => $id_court)));
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM match_player WHERE id_match IN (SELECT id_match FROM matches WHERE code_match LIKE 'ISS349_%')");
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'ISS349_%'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%ISS349%'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_joueur IN (SELECT id FROM joueurs WHERE nom = 'Issue349')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'Issue349'");
        $this->sql->execute("DELETE FROM photos WHERE path_photo = 'players_pics/issue349.jpg'");
        $this->sql->execute("DELETE FROM classements WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'issue349 %')");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue349 %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'issue349 club'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue349 court'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'uv'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function blocked_by_prenom(int $id_match): array
    {
        $out = array();
        foreach ($this->match_manager->getReinforcementPlayers($id_match, 'issue349') as $row) {
            $out[$row['prenom']] = $row['reinforcement_blocked'];
        }
        return $out;
    }

    public function test_la_recherche_signale_les_renforts_ineligibles(): void
    {
        $blocked = $this->blocked_by_prenom($this->id_match);
        self::assertStringContainsString('division 97', (string)$blocked['UP']);
        self::assertStringContainsString('division 98', (string)$blocked['SAME']);
        self::assertNull($blocked['DOWN']);
        self::assertNull($blocked['OTHER'], 'un autre championnat est autorisé');
        self::assertNull($blocked['NONPL'], "l'appartenance non jouante ne compte pas");
    }

    public function test_un_renfort_de_division_superieure_ou_egale_est_refuse(): void
    {
        foreach (array('UP', 'SAME') as $k) {
            try {
                $this->match_manager->manage_match_players($this->id_match, array(), $this->player[$k]);
                self::fail("$k devait être refusé");
            } catch (Exception $e) {
                self::assertSame(409, $e->getCode(), $k);
                self::assertStringContainsString('Renfort refusé', $e->getMessage());
            }
        }
    }

    public function test_un_renfort_eligible_est_accepte(): void
    {
        $this->match_manager->manage_match_players($this->id_match, array($this->player['DOWN'], $this->player['OTHER']));
        self::assertCount(2, $this->sql->execute("SELECT id_player FROM match_player WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $this->id_match))));
    }

    public function test_pas_de_regle_en_coupe(): void
    {
        $this->connect_as_team_leader($this->team['CUP1']);
        self::assertNull($this->blocked_by_prenom($this->id_cup_match)['UP']);
    }

    public function test_l_admin_corrige_sans_condition(): void
    {
        $this->connect_as_admin();
        $this->match_manager->manage_match_players($this->id_match, array($this->player['UP']));
        self::assertCount(1, $this->sql->execute("SELECT id_player FROM match_player WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $this->id_match))));
    }
}
