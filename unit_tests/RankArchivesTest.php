<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Rank.php';

/**
 * Issue #435 — reconstitution d'une saison archivée et lecture des archives.
 *
 * Saison de test 2001-2002, sans aucune donnée réelle :
 * - championnat « m », division Z9 : A, B, C, D ; matchs en novembre et en
 *   janvier (1re demi-saison, rattrapage compris) et en mars (2e) ;
 * - coupe « c », poule Z9, et sa phase finale « cf » en juin.
 */
class RankArchivesTest extends UfolepTestCase
{
    private const SEASON = '2001-2002';
    private array $team = array();
    private int $id_court;
    private int $id_club;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'ARCHI club'");
        $this->id_court = (int)$this->sql->execute("INSERT INTO gymnase SET nom = 'ARCHI gymnase'");
        foreach (array('A', 'B', 'C', 'D') as $name) {
            $this->team[$name] = (int)$this->sql->execute(
                "INSERT INTO equipes SET code_competition = 'm', nom_equipe = ?, id_club = ?",
                array(array('type' => 's', 'value' => "ARCHI $name"), array('type' => 'i', 'value' => $this->id_club)));
        }
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM classements_archives WHERE saison = ? OR code_competition = 'ty'",
            array(array('type' => 's', 'value' => self::SEASON)));
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'ARCHI%'");
        $this->sql->execute("DELETE FROM classements WHERE code_competition = 'ty'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'ty'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'ARCHI %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'ARCHI club'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'ARCHI gymnase'");
    }

    /**
     * Un match : `$sets` = liste de [dom, ext] par set ; 'ff_ext' = forfait de
     * l'équipe à l'extérieur (0-25 ×3 pour elle, feuille signée).
     */
    private function match(string $code, string $dom, string $ext, string $date, array $sets,
                           string $status = 'ARCHIVED', string $division = 'Z9'): void
    {
        static $n = 0;
        $n++;
        $fields = array();
        $bindings = array(
            array('type' => 's', 'value' => "ARCHI$n"),
            array('type' => 's', 'value' => $code),
            array('type' => 's', 'value' => $division),
            array('type' => 'i', 'value' => $this->team[$dom]),
            array('type' => 'i', 'value' => $this->team[$ext]),
            array('type' => 's', 'value' => $date),
            array('type' => 's', 'value' => $date),
            array('type' => 'i', 'value' => $this->id_court),
            array('type' => 's', 'value' => $status),
        );
        foreach ($sets as $i => [$d, $e]) {
            $s = $i + 1;
            $fields[] = "set_{$s}_dom = $d, set_{$s}_ext = $e";
        }
        $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = ?, division = ?,
                id_equipe_dom = ?, id_equipe_ext = ?, date_reception = ?, date_original = ?,
                id_gymnasium = ?, match_status = ?, is_sign_match_dom = 1, is_sign_match_ext = 1, "
            . implode(', ', $fields), $bindings);
    }

    private const WIN_3_0 = array(array(25, 10), array(25, 10), array(25, 10));
    private const WIN_3_2 = array(array(25, 20), array(20, 25), array(25, 20), array(20, 25), array(15, 10));
    private const FORFEIT_DOM = array(array(0, 25), array(0, 25), array(0, 25));

    private function archive(string $code, int $half): array
    {
        return $this->sql->execute(
            "SELECT * FROM classements_archives WHERE saison = ? AND demi_saison = ? AND code_competition = ?
             ORDER BY division, rang",
            array(
                array('type' => 's', 'value' => self::SEASON),
                array('type' => 'i', 'value' => $half),
                array('type' => 's', 'value' => $code),
            ));
    }

    public function test_la_reconstitution_donne_le_meme_classement_que_le_site(): void
    {
        // compétition de test « ty », non archivée : getRank fait foi
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'ty', libelle = 'ARCHI test', id_compet_maitre = 'ty'");
        foreach ($this->team as $id) {
            $this->sql->execute("INSERT INTO classements SET code_competition = 'ty', division = 'Z9', id_equipe = ?",
                array(array('type' => 'i', 'value' => $id)));
        }
        // A et B à égalité de points et de sets, A gagne la confrontation
        // directe ; C perd un match par forfait
        $this->match('ty', 'A', 'B', '2001-11-05', self::WIN_3_2, 'CONFIRMED');
        $this->match('ty', 'B', 'C', '2001-11-12', self::WIN_3_0, 'CONFIRMED');
        $this->match('ty', 'C', 'A', '2001-11-19', self::WIN_3_2, 'CONFIRMED');
        $this->match('ty', 'C', 'D', '2001-11-26', self::FORFEIT_DOM, 'CONFIRMED');
        $this->match('ty', 'D', 'A', '2001-12-03', array(array(10, 25), array(10, 25), array(10, 25)), 'CONFIRMED');
        $this->match('ty', 'B', 'D', '2001-12-10', self::WIN_3_0, 'CONFIRMED');
        $keys = array('id_equipe', 'rang', 'points', 'joues', 'gagnes', 'perdus', 'sets_pour', 'sets_contre',
            'matches_lost_by_forfeit_count');
        $pick = static fn(array $rows) => array_map(
            static fn($row) => array_map('intval', array_intersect_key($row, array_flip($keys))), $rows);
        $site = $pick((new Rank())->getRank('ty', 'Z9'));

        $this->sql->execute("UPDATE matches SET match_status = 'ARCHIVED' WHERE code_competition = 'ty'");
        (new Rank())->rebuild_period('ty', array('saison' => self::SEASON, 'demi_saison' => 0), '2001-07-01', '2002-06-30');
        $rebuilt = $pick(array_map(static fn($row) => $row + array('matches_lost_by_forfeit_count' => $row['forfaits']),
            $this->archive('ty', 0)));

        $this->assertSame($site, $rebuilt);
        $this->assertSame('REBUILT', $this->archive('ty', 0)[0]['source']);
    }

    public function test_un_championnat_se_reconstitue_en_deux_demi_saisons(): void
    {
        $this->match('m', 'A', 'B', '2001-11-06', self::WIN_3_0);
        $this->match('m', 'C', 'A', '2002-01-15', self::WIN_3_0); // rattrapage de la 1re demi-saison
        $this->match('m', 'D', 'C', '2002-03-12', self::WIN_3_0);

        $report = (new Rank())->rebuild_season(self::SEASON);

        $this->assertSame(array('2001-2002 · 1re demi-saison', '2001-2002 · 2e demi-saison'),
            array_column(array_filter($report, static fn($l) => $l['competition'] === 'm'), 'periode'));
        $this->assertSame(array('ARCHI A', 'ARCHI C', 'ARCHI B'), array_column($this->archive('m', 1), 'nom_equipe'));
        $this->assertSame(array('ARCHI D', 'ARCHI C'), array_column($this->archive('m', 2), 'nom_equipe'));
    }

    public function test_une_penalite_connue_est_deduite_et_reclasse(): void
    {
        $this->match('m', 'A', 'B', '2001-11-06', self::WIN_3_0);
        $this->match('m', 'C', 'D', '2001-11-13', self::WIN_3_2);

        (new Rank())->rebuild_season(self::SEASON, array(), array('m' => array(1 => array($this->team['A'] => 3))));

        $rows = $this->archive('m', 1);
        // C 3 pts ; D 1 pt (sets -1) devant B 1 pt (sets -3) ; A 3 - 3 = 0, dernière
        $this->assertSame(array('ARCHI C', 'ARCHI D', 'ARCHI B', 'ARCHI A'), array_column($rows, 'nom_equipe'));
        $this->assertSame(3, (int)$rows[3]['penalites']);
        $this->assertSame(0, (int)$rows[3]['points']);
    }

    public function test_les_noms_de_l_epoque_l_emportent_sur_le_nom_actuel(): void
    {
        $this->match('m', 'A', 'B', '2001-11-06', self::WIN_3_0);

        (new Rank())->rebuild_season(self::SEASON, array($this->team['A'] => 'ARCHI A 2001'));

        $this->assertSame('ARCHI A 2001', $this->archive('m', 1)[0]['nom_equipe']);
    }

    public function test_une_periode_figee_ne_se_reconstitue_pas(): void
    {
        $this->match('m', 'A', 'B', '2001-11-06', self::WIN_3_0);
        $this->sql->execute(
            "INSERT INTO classements_archives SET saison = ?, demi_saison = 1, code_competition = 'm',
                libelle_competition = 'x', division = 'Z9', rang = 1, id_equipe = ?, nom_equipe = 'figée',
                points = 0, joues = 0, gagnes = 0, perdus = 0, sets_pour = 0, sets_contre = 0,
                source = 'FROZEN', frozen_at = NOW()",
            array(array('type' => 's', 'value' => self::SEASON), array('type' => 'i', 'value' => $this->team['A'])));

        $report = (new Rank())->rebuild_season(self::SEASON);

        $this->assertSame('skipped', $report[0]['status']);
        $this->assertSame(array('figée'), array_column($this->archive('m', 1), 'nom_equipe'));
    }

    public function test_le_bareme_ffvb_ne_se_reconstitue_pas(): void
    {
        $this->expectExceptionCode(409);
        (new Rank())->rebuild_period('m', array('saison' => '2026-2027', 'demi_saison' => 1), '2026-07-01', '2027-01-31');
    }

    public function test_lecture_des_archives_periodes_classement_resultats_et_phases_finales(): void
    {
        $this->match('m', 'A', 'B', '2001-11-06', self::WIN_3_2);
        $this->match('c', 'C', 'D', '2002-01-21', self::WIN_3_0);
        $this->match('cf', 'C', 'A', '2002-06-10', self::WIN_3_0, 'ARCHIVED', '1');
        $rank = new Rank();
        $rank->rebuild_season(self::SEASON);
        // un renommage ultérieur ne change pas les archives
        $this->sql->execute("UPDATE equipes SET nom_equipe = 'ARCHI A renommée' WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->team['A'])));

        $periods = array_values(array_filter($rank->getArchivePeriods(), static fn($p) => $p['saison'] === self::SEASON));
        $this->assertSame(array('2001-2002 · 1re demi-saison', '2001-2002'), array_column($periods, 'label'));
        $cup = $periods[1]['competitions'][0];
        $this->assertSame(array('c', array('Z9'), true, true),
            array($cup['code_competition'], $cup['divisions'], $cup['rebuilt'], $cup['has_finals']));

        $ranks = $rank->getArchivedRank(self::SEASON, '1', 'm', 'Z9');
        $this->assertSame(array('ARCHI A', 'ARCHI B'), array_column($ranks, 'equipe'));

        $results = $rank->getArchivedResults(self::SEASON, '1', 'm', 'Z9');
        $this->assertCount(1, $results);
        $this->assertSame(array('06/11/2001', 'ARCHI A', 'ARCHI B', 3, 2, '25-20 20-25 25-20 20-25 15-10'),
            array($results[0]['date'], $results[0]['equipe_dom'], $results[0]['equipe_ext'],
                $results[0]['score_dom'], $results[0]['score_ext'], $results[0]['sets']));

        $finals = $rank->getArchivedResults(self::SEASON, '0', 'c', 'finales');
        $this->assertCount(1, $finals, 'la phase finale cf se lit avec la coupe c');
        $this->assertSame('ARCHI C', $finals[0]['equipe_dom']);
    }
}
