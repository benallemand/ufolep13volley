<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchMgr.php';

/**
 * Issue #348 — un renfort ne sert qu'à compléter l'équipe (6 en masculin, 4 en
 * féminin et mixte), un seul par équipe, en respectant la mixité, et une fois
 * par demi-saison.
 *
 * Divisions de test « 96 » des trois championnats. Les renforts viennent d'une
 * équipe de coupe (`ut`), donc d'un autre championnat : éligibles au sens #349.
 */
class ReinforcementRulesTest extends UfolepTestCase
{
    private MatchMgr $match_manager;
    private array $team = array();
    private int $id_club;
    private int $id_photo;
    private int $id_court;
    private int $pool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->match_manager = new MatchMgr();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'ut', libelle = 'issue348 coupe', id_compet_maitre = 'ut'");
        $this->id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'issue348 club'");
        $this->id_photo = $this->sql->execute("INSERT INTO photos SET path_photo = 'players_pics/issue348.jpg'");
        $this->id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue348 court'");
        foreach (array('MO_D' => 'mo', 'MO_E' => 'mo', 'M_D' => 'm', 'M_E' => 'm', 'F_D' => 'f', 'F_E' => 'f',
                       'POOL' => 'ut', 'CUP_D' => 'ut', 'CUP_E' => 'ut') as $k => $code) {
            $this->team[$k] = $this->sql->execute("INSERT INTO equipes SET code_competition = ?, nom_equipe = ?, id_club = ?",
                array(array('type' => 's', 'value' => $code), array('type' => 's', 'value' => "issue348 $k"),
                      array('type' => 'i', 'value' => $this->id_club)));
            $this->sql->execute("INSERT INTO classements SET code_competition = ?, division = ?, id_equipe = ?, rank_start = 1, penalite = 0",
                array(array('type' => 's', 'value' => $code), array('type' => 's', 'value' => $code === 'ut' ? '1' : '96'),
                      array('type' => 'i', 'value' => $this->team[$k])));
        }
        $this->pool = $this->team['POOL'];
    }

    private function player(string $sexe, int $id_team): int
    {
        $id = $this->sql->execute("INSERT INTO joueurs SET nom = 'Issue348', prenom = ?, sexe = ?, id_photo = ?",
            array(array('type' => 's', 'value' => uniqid('p')), array('type' => 's', 'value' => $sexe),
                  array('type' => 'i', 'value' => $this->id_photo)));
        $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?",
            array(array('type' => 'i', 'value' => $id), array('type' => 'i', 'value' => $id_team)));
        return $id;
    }

    /** Des joueurs de l'équipe : 'MMF' => 2 hommes, 1 femme. */
    private function squad(string $sexes, string $team): array
    {
        return array_map(fn($s) => $this->player($s, $this->team[$team]), str_split($sexes));
    }

    private function match(string $code, string $dom, string $ext, string $date = '2026-10-15'): int
    {
        static $n = 0;
        $n++;
        return $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = ?, division = ?,
                id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
                date_reception = ?, date_original = ?, match_status = 'CONFIRMED'",
            array(array('type' => 's', 'value' => sprintf('ISS348_%02d', $n)), array('type' => 's', 'value' => $code),
                  array('type' => 's', 'value' => $code === 'ut' ? '1' : '96'),
                  array('type' => 'i', 'value' => $this->team[$dom]), array('type' => 'i', 'value' => $this->team[$ext]),
                  array('type' => 'i', 'value' => $this->id_court),
                  array('type' => 's', 'value' => $date), array('type' => 's', 'value' => $date)));
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM match_player WHERE id_match IN (SELECT id_match FROM matches WHERE code_match LIKE 'ISS348_%')");
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'ISS348_%'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%ISS348%'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_joueur IN (SELECT id FROM joueurs WHERE nom = 'Issue348')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'Issue348'");
        $this->sql->execute("DELETE FROM photos WHERE path_photo = 'players_pics/issue348.jpg'");
        $this->sql->execute("DELETE FROM classements WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'issue348 %')");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue348 %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'issue348 club'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue348 court'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'ut'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function save(int $id_match, array $players, array $reinforcements, string $as_team): void
    {
        $this->connect_as_team_leader($this->team[$as_team]);
        $this->match_manager->manage_match_players($id_match, $players, null, null, $reinforcements);
    }

    private function assert_refused(callable $call, int $code, string $expected): void
    {
        try {
            $call();
            self::fail("Refus attendu : $expected");
        } catch (Exception $e) {
            self::assertSame($code, $e->getCode(), $e->getMessage());
            self::assertStringContainsString($expected, $e->getMessage());
        }
    }

    public function test_un_renfort_complete_l_equipe_et_est_rattache_a_elle(): void
    {
        $id_match = $this->match('mo', 'MO_D', 'MO_E');
        $own = $this->squad('MMF', 'MO_D');
        $renfort = $this->player('F', $this->pool);
        $this->save($id_match, array_merge($own, array($renfort)), array($renfort => $this->team['MO_D']), 'MO_D');
        $row = $this->sql->execute("SELECT id_team_reinforced FROM match_player WHERE id_match = ? AND id_player = ?",
            array(array('type' => 'i', 'value' => $id_match), array('type' => 'i', 'value' => $renfort)));
        self::assertSame($this->team['MO_D'], (int)$row[0]['id_team_reinforced']);
    }

    public function test_pas_de_renfort_pour_une_equipe_deja_complete(): void
    {
        $id_match = $this->match('mo', 'MO_D', 'MO_E');
        $own = $this->squad('MMFF', 'MO_D');
        $renfort = $this->player('F', $this->pool);
        $this->assert_refused(fn() => $this->save($id_match, array_merge($own, array($renfort)),
            array($renfort => $this->team['MO_D']), 'MO_D'), 409, 'compte déjà 4 joueurs');
    }

    public function test_un_seul_renfort_par_equipe(): void
    {
        $id_match = $this->match('mo', 'MO_D', 'MO_E');
        $own = $this->squad('MF', 'MO_D');
        $r1 = $this->player('F', $this->pool);
        $r2 = $this->player('M', $this->pool);
        $this->assert_refused(fn() => $this->save($id_match, array_merge($own, array($r1, $r2)),
            array($r1 => $this->team['MO_D'], $r2 => $this->team['MO_D']), 'MO_D'), 409, 'un seul renfort');
    }

    public function test_la_mixite_est_respectee(): void
    {
        $id_match = $this->match('mo', 'MO_D', 'MO_E');
        $own = $this->squad('MMM', 'MO_D');
        $homme = $this->player('M', $this->pool);
        $this->assert_refused(fn() => $this->save($id_match, array_merge($own, array($homme)),
            array($homme => $this->team['MO_D']), 'MO_D'), 409, 'une joueuse');
        $femme = $this->player('F', $this->pool);
        // l'extérieur aussi, sans quoi le HAVING de la vue écarte le match
        $adverses = $this->squad('MFF', 'MO_E');
        $this->save($id_match, array_merge($own, $adverses, array($femme)), array($femme => $this->team['MO_D']), 'MO_D');
        // la vue compte la renforte pour la mixité de son équipe
        $status = $this->sql->execute("SELECT count_status, count_renfort_dom FROM match_players_count_view WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $id_match)));
        self::assertSame(1, (int)($status[0]['count_renfort_dom'] ?? 0));
        self::assertStringNotContainsString('à domicile', (string)($status[0]['count_status'] ?? ''));
    }

    public function test_championnat_feminin_renfort_feminin(): void
    {
        $id_match = $this->match('f', 'F_D', 'F_E');
        $own = $this->squad('FFF', 'F_D');
        $homme = $this->player('M', $this->pool);
        $this->assert_refused(fn() => $this->save($id_match, array_merge($own, array($homme)),
            array($homme => $this->team['F_D']), 'F_D'), 409, 'une joueuse');
    }

    public function test_six_joueurs_en_masculin(): void
    {
        $id_match = $this->match('m', 'M_D', 'M_E');
        $cinq = $this->squad('MMMMM', 'M_D');
        $renfort = $this->player('M', $this->pool);
        $this->save($id_match, array_merge($cinq, array($renfort)), array($renfort => $this->team['M_D']), 'M_D');
        $sixieme = $this->player('M', $this->team['M_D']);
        $this->assert_refused(fn() => $this->save($id_match, array_merge($cinq, array($sixieme, $renfort)),
            array($renfort => $this->team['M_D']), 'M_D'), 409, 'compte déjà 6 joueurs');
    }

    public function test_une_fois_par_demi_saison(): void
    {
        $renfort = $this->player('F', $this->pool);
        $octobre = $this->match('mo', 'MO_D', 'MO_E', '2026-10-15');
        $novembre = $this->match('mo', 'MO_D', 'MO_E', '2026-11-20');
        $mars = $this->match('mo', 'MO_D', 'MO_E', '2027-03-10');
        $own = $this->squad('MF', 'MO_D');
        $this->save($octobre, array_merge($own, array($renfort)), array($renfort => $this->team['MO_D']), 'MO_D');
        $this->assert_refused(fn() => $this->save($novembre, array_merge($own, array($renfort)),
            array($renfort => $this->team['MO_D']), 'MO_D'), 409, 'une fois par demi-saison');
        $this->save($mars, array_merge($own, array($renfort)), array($renfort => $this->team['MO_D']), 'MO_D');
        $this->addToAssertionCount(1);
    }

    public function test_un_renfort_doit_designer_son_equipe(): void
    {
        $id_match = $this->match('mo', 'MO_D', 'MO_E');
        $own = $this->squad('MF', 'MO_D');
        $renfort = $this->player('F', $this->pool);
        $this->assert_refused(fn() => $this->save($id_match, array_merge($own, array($renfort)), array(), 'MO_D'),
            400, "Préciser l'équipe renforcée");
        $this->assert_refused(fn() => $this->save($id_match, array_merge($own, array($renfort)),
            array($renfort => $this->team['M_D']), 'MO_D'), 400, "l'une des deux équipes");
    }

    public function test_on_ne_renforce_que_son_equipe(): void
    {
        $id_match = $this->match('mo', 'MO_D', 'MO_E');
        $adverses = $this->squad('MF', 'MO_E');
        $renfort = $this->player('F', $this->pool);
        $this->assert_refused(fn() => $this->save($id_match, array_merge($adverses, array($renfort)),
            array($renfort => $this->team['MO_E']), 'MO_D'), 403, 'votre équipe');
    }

    public function test_pas_de_regle_en_coupe_ni_pour_l_admin(): void
    {
        $cup = $this->match('ut', 'CUP_D', 'CUP_E');
        $own = $this->squad('MMMMMM', 'CUP_D');
        $renfort = $this->player('M', $this->pool);
        $this->save($cup, array_merge($own, array($renfort)), array(), 'CUP_D');

        $id_match = $this->match('mo', 'MO_D', 'MO_E');
        $complete = $this->squad('MMFF', 'MO_D');
        $this->connect_as_admin();
        $this->match_manager->manage_match_players($id_match, array_merge($complete, array($renfort)));
        $this->addToAssertionCount(1);
    }
}
