<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Rank.php';
require_once __DIR__ . '/../classes/Competition.php';

/**
 * Issue #435 — classement figé de chaque demi-saison.
 *
 * Compétition de test « tz » (barème UFOLEP), sur une saison future :
 * - division 1 : T1, T2, T3 ; T1 bat T2 et T3, T2 bat T3 ; T2 a une pénalité
 *   et deux reports ;
 * - division X (à placer) : T4, qui n'a pas joué.
 */
class RankFreezeTest extends UfolepTestCase
{
    private const CODE = 'tz';
    private int $id_competition;
    private int $id_club;
    private array $team = array();
    private int $id_court;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->id_competition = (int)$this->sql->execute(
            "INSERT INTO competitions SET code_competition = ?, libelle = 'GELER coupe test', id_compet_maitre = ?,
                start_date = '2099-01-01'",
            array(array('type' => 's', 'value' => self::CODE), array('type' => 's', 'value' => self::CODE)));
        $this->id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'GELER club'");
        $this->id_court = (int)$this->sql->execute("INSERT INTO gymnase SET nom = 'GELER gymnase'");
        foreach (array('T1' => '1', 'T2' => '1', 'T3' => '1', 'T4' => 'X') as $name => $division) {
            $this->team[$name] = (int)$this->sql->execute(
                "INSERT INTO equipes SET code_competition = ?, nom_equipe = ?, id_club = ?",
                array(
                    array('type' => 's', 'value' => self::CODE),
                    array('type' => 's', 'value' => "GELER $name"),
                    array('type' => 'i', 'value' => $this->id_club),
                ));
            $this->sql->execute(
                "INSERT INTO classements SET code_competition = ?, division = ?, id_equipe = ?, rank_start = ?",
                array(
                    array('type' => 's', 'value' => self::CODE),
                    array('type' => 's', 'value' => $division),
                    array('type' => 'i', 'value' => $this->team[$name]),
                    array('type' => 'i', 'value' => (int)substr($name, 1)),
                ));
        }
        $this->sql->execute("UPDATE classements SET penalite = 1, report_count = 2 WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->team['T2'])));
        $this->match('T1', 'T2', '2098-11-10');
        $this->match('T1', 'T3', '2098-11-17');
        $this->match('T2', 'T3', '2098-12-01');
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM classements_archives WHERE code_competition = ?",
            array(array('type' => 's', 'value' => self::CODE)));
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%GELER coupe test%'");
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'GELER%'");
        $this->sql->execute("DELETE FROM classements WHERE code_competition = ?",
            array(array('type' => 's', 'value' => self::CODE)));
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'GELER %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'GELER club'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'GELER gymnase'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = ?",
            array(array('type' => 's', 'value' => self::CODE)));
    }

    /** Victoire 3-0 de `$dom`, match confirmé. */
    private function match(string $dom, string $ext, string $date): void
    {
        static $n = 0;
        $n++;
        $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = ?, division = '1',
                id_equipe_dom = ?, id_equipe_ext = ?, date_reception = ?, date_original = ?,
                id_gymnasium = ?, match_status = 'CONFIRMED',
                set_1_dom = 25, set_1_ext = 10, set_2_dom = 25, set_2_ext = 10, set_3_dom = 25, set_3_ext = 10,
                is_sign_match_dom = 1, is_sign_match_ext = 1",
            array(
                array('type' => 's', 'value' => "GELER$n"),
                array('type' => 's', 'value' => self::CODE),
                array('type' => 'i', 'value' => $this->team[$dom]),
                array('type' => 'i', 'value' => $this->team[$ext]),
                array('type' => 's', 'value' => $date),
                array('type' => 's', 'value' => $date),
                array('type' => 'i', 'value' => $this->id_court),
            ));
    }

    private function archive(): array
    {
        return $this->sql->execute(
            "SELECT * FROM classements_archives WHERE code_competition = ? ORDER BY division, rang",
            array(array('type' => 's', 'value' => self::CODE)));
    }

    public function test_la_periode_se_deduit_du_premier_match(): void
    {
        $this->assertSame(array('saison' => '2026-2027', 'demi_saison' => 1), Rank::period_of('m', '2026-11-04'));
        $this->assertSame(array('saison' => '2026-2027', 'demi_saison' => 2), Rank::period_of('f', '2027-03-01'));
        $this->assertSame(array('saison' => '2026-2027', 'demi_saison' => 0), Rank::period_of('c', '2027-01-18'),
            'une coupe dure toute la saison');
        $this->assertSame('2026-2027 · 2e demi-saison', Rank::period_label(array('saison' => '2026-2027', 'demi_saison' => 2)));
    }

    public function test_figer_recopie_le_classement_avec_penalites_et_reports(): void
    {
        $this->connect_as_admin();
        $result = (new Rank())->freezeRanks((string)$this->id_competition);

        $this->assertSame('1 classement(s) figé(s)', $result['message']);
        $this->assertSame('2098-2099', $result['report'][0]['periode']);
        $rows = $this->archive();
        $this->assertSame(array('GELER T1', 'GELER T2', 'GELER T3'), array_column($rows, 'nom_equipe'),
            "division 1 dans l'ordre ; la division X, où personne n'a joué, n'est pas figée");
        $t2 = $rows[1];
        $this->assertSame(
            array('rang' => 2, 'points' => 3, 'joues' => 2, 'gagnes' => 1, 'perdus' => 1,
                'penalites' => 1, 'reports' => 2, 'club' => 'GELER club', 'source' => 'FROZEN'),
            array(
                'rang' => (int)$t2['rang'], 'points' => (int)$t2['points'], 'joues' => (int)$t2['joues'],
                'gagnes' => (int)$t2['gagnes'], 'perdus' => (int)$t2['perdus'],
                'penalites' => (int)$t2['penalites'], 'reports' => (int)$t2['reports'],
                'club' => $t2['club'], 'source' => $t2['source'],
            ),
            'barème UFOLEP : victoire 3, défaite 1, pénalité déduite (3 + 1 - 1)');
        $this->assertSame('2098-11-10', $t2['premier_match']);
        $this->assertSame('2098-12-01', $t2['dernier_match']);
    }

    public function test_la_copie_ne_bouge_plus_apres_un_renommage(): void
    {
        $this->connect_as_admin();
        (new Rank())->freezeRanks((string)$this->id_competition);
        $this->sql->execute("UPDATE equipes SET nom_equipe = 'GELER T1 renommée' WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->team['T1'])));

        $this->assertSame('GELER T1', $this->archive()[0]['nom_equipe']);
    }

    public function test_refiger_remplace_la_copie(): void
    {
        $this->connect_as_admin();
        $rank = new Rank();
        $rank->freezeRanks((string)$this->id_competition);
        // pénalité tardive
        $this->sql->execute("UPDATE classements SET penalite = 3 WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->team['T2'])));
        $rank->freezeRanks((string)$this->id_competition);

        $rows = $this->archive();
        $this->assertCount(3, $rows, 'pas de doublon');
        $this->assertSame(3, (int)$rows[array_search('GELER T2', array_column($rows, 'nom_equipe'))]['penalites']);
    }

    public function test_la_copie_automatique_ne_remplace_pas_la_copie_manuelle(): void
    {
        $this->connect_as_admin();
        $rank = new Rank();
        $rank->freezeRanks((string)$this->id_competition);
        $this->sql->execute("UPDATE classements SET penalite = 3 WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->team['T2'])));

        $line = $rank->freeze_competition(self::CODE, false);

        $this->assertSame('skipped', $line['status']);
        $this->assertSame(1, (int)$this->archive()[1]['penalites']);
    }

    public function test_rien_a_figer_sans_match_joue(): void
    {
        $this->sql->execute("UPDATE matches SET match_status = 'ARCHIVED' WHERE code_match LIKE 'GELER%'");
        $this->connect_as_admin();

        $result = (new Rank())->freezeRanks((string)$this->id_competition);

        $this->assertSame('skipped', $result['report'][0]['status']);
        $this->assertSame(array(), $this->archive());
    }

    public function test_figer_est_reserve_aux_administrateurs(): void
    {
        $this->connect_as_team_leader($this->team['T1']);

        $this->expectExceptionCode(403);
        (new Rank())->freezeRanks((string)$this->id_competition);
    }

    public function test_la_remise_a_zero_des_points_exige_une_copie(): void
    {
        $this->connect_as_admin();
        $competition = new Competition();
        try {
            $competition->resetCompetition((string)$this->id_competition);
            $this->fail('la remise à zéro aurait dû être refusée');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
            $this->assertStringContainsString("n'est pas figé", $e->getMessage());
        }
        $penalty = fn() => (int)$this->sql->execute("SELECT penalite FROM classements WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->team['T2'])))[0]['penalite'];
        $this->assertSame(1, $penalty(), 'pénalité intacte');

        (new Rank())->freezeRanks((string)$this->id_competition);
        $competition->resetCompetition((string)$this->id_competition);
        $this->assertSame(0, $penalty(), 'remise à zéro une fois le classement figé');
    }
}
