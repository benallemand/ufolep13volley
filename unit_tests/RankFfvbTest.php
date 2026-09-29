<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Rank.php';
require_once __DIR__ . '/../classes/HallOfFame.php';

/**
 * Issue #347 — barème FFVB pour les championnats, UFOLEP pour les coupes.
 *
 * Divisions de test « 98 » et « 99 » du championnat masculin (`m`, pour que
 * le barème FFVB s'applique), et une coupe de test `uz`.
 */
class RankFfvbTest extends UfolepTestCase
{
    private Rank $rank;
    private array $team = array();
    private int $court;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rank = new Rank();
        $this->delete_test_data();
        $this->connect_as_admin();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'uz', libelle = 'issue347 coupe', id_compet_maitre = 'uz'");
        $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'issue347 club'");
        $this->court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue347 court'");
        $layout = array('A' => array('m', '99'), 'B' => array('m', '99'), 'C' => array('m', '99'), 'D' => array('m', '99'),
                        'E' => array('m', '98'), 'F' => array('m', '98'), 'G' => array('m', '98'),
                        'X' => array('uz', '1'), 'Y' => array('uz', '1'));
        $rank_start = 1;
        foreach ($layout as $k => [$code, $division]) {
            $this->team[$k] = $this->sql->execute("INSERT INTO equipes SET code_competition = ?, nom_equipe = ?, id_club = ?",
                array(
                    array('type' => 's', 'value' => $code),
                    array('type' => 's', 'value' => "issue347 team $k"),
                    array('type' => 'i', 'value' => $id_club),
                ));
            $this->sql->execute("INSERT INTO classements SET code_competition = ?, division = ?, id_equipe = ?, rank_start = ?, penalite = 0",
                array(
                    array('type' => 's', 'value' => $code),
                    array('type' => 's', 'value' => $division),
                    array('type' => 'i', 'value' => $this->team[$k]),
                    array('type' => 'i', 'value' => $rank_start++),
                ));
        }
    }

    /** Un match joué : $sets = [[dom, ext], ...]. */
    private function match(string $code, string $division, string $dom, string $ext, array $sets,
                           bool $signed = true, string $date = '2026-10-15'): void
    {
        static $n = 0;
        $n++;
        $fields = array();
        $bindings = array(
            array('type' => 's', 'value' => sprintf('ISS347_%03d', $n)),
            array('type' => 's', 'value' => $code),
            array('type' => 's', 'value' => $division),
            array('type' => 'i', 'value' => $this->team[$dom]),
            array('type' => 'i', 'value' => $this->team[$ext]),
            array('type' => 'i', 'value' => $this->court),
            array('type' => 's', 'value' => $date),
            array('type' => 's', 'value' => $date),
            array('type' => 'i', 'value' => $signed ? 1 : 0),
            array('type' => 'i', 'value' => $signed ? 1 : 0),
        );
        foreach ($sets as $i => [$d, $e]) {
            $s = $i + 1;
            $fields[] = "set_{$s}_dom = ?, set_{$s}_ext = ?";
            $bindings[] = array('type' => 'i', 'value' => $d);
            $bindings[] = array('type' => 'i', 'value' => $e);
        }
        $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = ?, division = ?,
                id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
                date_reception = ?, date_original = ?, match_status = 'CONFIRMED', certif = 1,
                is_sign_match_dom = ?, is_sign_match_ext = ?, " . implode(', ', $fields),
            $bindings);
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'ISS347_%'");
        $this->sql->execute("DELETE FROM classements WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'issue347 team %')");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue347 team %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'issue347 club'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue347 court'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'uz'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function by_team(array $rows): array
    {
        $out = array();
        foreach ($rows as $row) {
            $key = array_search((int)$row['id_equipe'], $this->team, true);
            $out[$key] = $row;
        }
        return $out;
    }

    public function test_bareme_ffvb_en_championnat(): void
    {
        $this->match('m', '99', 'A', 'B', array(array(25, 10), array(25, 10), array(25, 10)));             // 3-0
        $this->match('m', '99', 'A', 'C', array(array(25, 10), array(10, 25), array(25, 10), array(10, 25), array(15, 10))); // 3-2
        $this->match('m', '99', 'B', 'C', array(array(25, 10), array(10, 25), array(25, 10), array(25, 10))); // 3-1
        $this->match('m', '99', 'A', 'D', array(array(25, 0), array(25, 0), array(25, 0)));                // forfait de D

        $rows = $this->rank->getRank('m', '99');
        $r = $this->by_team($rows);
        self::assertSame(8, (int)$r['A']['points'], '3-0, 3-2 et victoire par forfait : 3 + 2 + 3');
        self::assertSame(3, (int)$r['B']['points'], '0-3 puis 3-1 : 0 + 3');
        self::assertSame(1, (int)$r['C']['points'], '2-3 puis 1-3 : 1 + 0');
        self::assertSame(-1, (int)$r['D']['points'], 'forfait : -1');
        self::assertSame(array('A', 'B', 'C', 'D'),
            array_map(fn($row) => array_search((int)$row['id_equipe'], $this->team, true), $rows));
        self::assertArrayHasKey('quotient_sets', $rows[0]);
    }

    public function test_les_penalites_sont_deduites(): void
    {
        $this->match('m', '99', 'A', 'B', array(array(25, 10), array(25, 10), array(25, 10)));
        $this->sql->execute("UPDATE classements SET penalite = 2 WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->team['A'])));
        self::assertSame(1, (int)$this->by_team($this->rank->getRank('m', '99'))['A']['points']);
    }

    public function test_departage_par_quotient_de_sets_et_equipe_invaincue_en_tete(): void
    {
        // E et F : 3 points et 1 victoire chacune. E n'a concédé aucun set
        // (quotient infini), F en a concédé un (quotient 3) : E passe devant.
        $this->match('m', '98', 'F', 'G', array(array(25, 10), array(10, 25), array(25, 10), array(25, 10))); // 3-1
        $this->match('m', '98', 'E', 'G', array(array(25, 10), array(25, 10), array(25, 10)));                // 3-0
        $rows = $this->rank->getRank('m', '98');
        self::assertSame(array('E', 'F', 'G'),
            array_map(fn($row) => array_search((int)$row['id_equipe'], $this->team, true), $rows));
    }

    public function test_une_coupe_garde_le_bareme_ufolep(): void
    {
        $this->match('uz', '1', 'X', 'Y', array(array(25, 10), array(10, 25), array(25, 10), array(10, 25), array(15, 10))); // 3-2
        $r = $this->by_team($this->rank->getRank('uz', '1'));
        self::assertSame(3, (int)$r['X']['points'], 'victoire : 3 points, même en 3-2');
        self::assertSame(1, (int)$r['Y']['points'], 'défaite : 1 point');
        self::assertArrayNotHasKey('quotient_sets', $r['X']);
    }

    public function test_rang_d_une_equipe_de_championnat(): void
    {
        $this->match('m', '99', 'B', 'A', array(array(25, 10), array(25, 10), array(25, 10)));
        self::assertSame(1, (int)$this->rank->getTeamRank('m', '99', $this->team['B']));
    }

    public function test_bareme_selon_la_competition_et_la_periode(): void
    {
        self::assertTrue(Rank::uses_ffvb_scale('m'));
        self::assertTrue(Rank::uses_ffvb_scale('mo', '2027-06-30'));
        self::assertFalse(Rank::uses_ffvb_scale('f', '2026-06-30'), 'saison passée : barème UFOLEP');
        self::assertFalse(Rank::uses_ffvb_scale('kh'));
        self::assertFalse(Rank::uses_ffvb_scale('c'));
    }

    public function test_palmares_au_bareme_de_la_periode(): void
    {
        $hof = new HallOfFame();
        // Saison 2026-2027 : A gagne 3-2 (2 pts FFVB), C gagne 3-0 (3 pts).
        $this->match('m', '99', 'A', 'B', array(array(25, 10), array(10, 25), array(25, 10), array(10, 25), array(15, 10)));
        $this->match('m', '99', 'C', 'D', array(array(25, 10), array(25, 10), array(25, 10)));
        $ffvb = $this->by_team($hof->getTop2ByDivision('m', '2026-09-01', '2027-06-30'));
        self::assertSame(array('C', 'A'), array_keys($ffvb));
        self::assertSame(2, (int)$ffvb['A']['points']);

        // Saison 2025-2026, régénérée : barème UFOLEP, la victoire 3-2 vaut 3 pts.
        $this->match('m', '99', 'A', 'B', array(array(25, 10), array(10, 25), array(25, 10), array(10, 25), array(15, 10)),
            true, '2026-03-10');
        $ufolep = $this->by_team($hof->getTop2ByDivision('m', '2025-09-01', '2026-06-30'));
        self::assertSame(3, (int)$ufolep['A']['points']);
    }
}
