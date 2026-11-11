<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/Sanction.php';
require_once __DIR__ . '/../classes/Rank.php';

/**
 * Issue #475 — sanctions : un manquement constaté n'a aucun effet ; appliqué
 * en réunion, il touche le classement ou les matchs ; levé ou remis à
 * décider, son effet est défait.
 *
 * Données de test : division « 93 » du championnat masculin, équipes
 * « issue475 A/B/C ». Le classement est lu par `Rank::getRank()`, au barème
 * FFVB (défaite par forfait = -1 pt).
 */
class SanctionTest extends UfolepTestCase
{
    private Sanction $sanction;
    private array $team = array();
    private int $id_court;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanction = new Sanction();
        $this->delete_test_data();
        $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'issue475 club'");
        $this->id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue475 court'");
        foreach (array('A', 'B', 'C') as $k) {
            $this->team[$k] = $this->sql->execute("INSERT INTO equipes SET code_competition = 'm', nom_equipe = ?, id_club = ?",
                array(array('type' => 's', 'value' => "issue475 $k"), array('type' => 'i', 'value' => $id_club)));
            $this->sql->execute("INSERT INTO classements SET code_competition = 'm', division = '93', id_equipe = ?, rank_start = 1, penalite = 0",
                array(array('type' => 'i', 'value' => $this->team[$k])));
        }
        $this->connect_as_admin();
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM sanctions WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'issue475 %')");
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'ISS475_%'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%issue475 %'");
        $this->sql->execute("DELETE FROM classements WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'issue475 %')");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue475 %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'issue475 club'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue475 court'");
    }

    /** Match joué : `$dom` gagne 3-1. */
    private function match(string $code, string $dom, string $ext): int
    {
        return $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = 'm', division = '93',
                id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
                date_reception = CURRENT_DATE - INTERVAL 10 DAY, date_original = CURRENT_DATE - INTERVAL 10 DAY,
                match_status = 'CONFIRMED',
                set_1_dom = 25, set_1_ext = 20, set_2_dom = 23, set_2_ext = 25,
                set_3_dom = 25, set_3_ext = 18, set_4_dom = 25, set_4_ext = 22,
                is_sign_match_dom = 1, is_sign_match_ext = 0",
            array(array('type' => 's', 'value' => "ISS475_$code"),
                  array('type' => 'i', 'value' => $this->team[$dom]), array('type' => 'i', 'value' => $this->team[$ext]),
                  array('type' => 'i', 'value' => $this->id_court)));
    }

    private function create(string $k, string $type, int $points = 0, ?string $code_match = null,
                            string $motif = 'autre'): int
    {
        return $this->sanction->saveSanction(null, 'm|' . $this->team[$k], $code_match === null ? null : "ISS475_$code_match",
            $motif, $type, $points, 'issue475 test');
    }

    /** @return array<string, array> classement de la division de test, par équipe A/B/C */
    private function rank(): array
    {
        $out = array();
        foreach ((new Rank())->getRank('m', '93') as $row) {
            $k = array_search((int)$row['id_equipe'], $this->team, true);
            if ($k !== false) {
                $out[$k] = $row;
            }
        }
        return $out;
    }

    private function match_row(int $id_match): array
    {
        return $this->sql->execute("SELECT set_1_dom, set_1_ext, set_4_dom, match_status,
                                           is_sign_match_dom + 0 AS sd, is_sign_match_ext + 0 AS se
                                    FROM matches WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $id_match)))[0];
    }

    private function status(int $id): string
    {
        return $this->sql->execute("SELECT status FROM sanctions WHERE id = ?",
            array(array('type' => 'i', 'value' => $id)))[0]['status'];
    }

    public function test_une_sanction_constatee_n_a_aucun_effet(): void
    {
        $this->create('A', 'points', 2);
        self::assertSame(0, (int)$this->rank()['A']['penalites']);
    }

    public function test_points_appliques_leves_et_remis_a_decider(): void
    {
        $id = $this->create('A', 'points', 2);
        $this->sanction->applySanctions((string)$id, 'réunion du 05/01');
        self::assertSame('APPLIQUEE', $this->status($id));
        self::assertSame(2, (int)$this->rank()['A']['penalites']);
        self::assertSame(-2, (int)$this->rank()['A']['points']);

        $this->sanction->liftSanctions((string)$id, 'clémence');
        self::assertSame('LEVEE', $this->status($id));
        self::assertSame(0, (int)$this->rank()['A']['penalites']);

        $this->sanction->applySanctions((string)$id);
        $this->sanction->reopenSanctions((string)$id);
        self::assertSame('CONSTATEE', $this->status($id));
        self::assertSame(0, (int)$this->rank()['A']['penalites']);
    }

    public function test_les_points_de_plusieurs_sanctions_s_additionnent(): void
    {
        $ids = $this->create('A', 'points', 1) . ',' . $this->create('A', 'points', 3);
        $this->create('A', 'points', 5);   // constatée : ne compte pas
        $this->sanction->applySanctions($ids);
        self::assertSame(4, (int)$this->rank()['A']['penalites']);
    }

    public function test_defaite_par_penalite_inverse_le_resultat_puis_le_restaure(): void
    {
        $id_match = $this->match('01', 'A', 'B');
        self::assertSame(3, (int)$this->rank()['A']['points']);
        $id = $this->create('A', 'defaite_penalite', 0, '01');

        $this->sanction->applySanctions((string)$id);
        $row = $this->match_row($id_match);
        self::assertSame(array(0, 25, 0, 1, 1), array((int)$row['set_1_dom'], (int)$row['set_1_ext'],
            (int)$row['set_4_dom'], (int)$row['sd'], (int)$row['se']));
        $rank = $this->rank();
        self::assertSame(-1, (int)$rank['A']['points'], 'défaite par pénalité : -1 pt (article 12)');
        self::assertSame(3, (int)$rank['B']['points']);

        $this->sanction->reopenSanctions((string)$id);
        $row = $this->match_row($id_match);
        self::assertSame(array(25, 20, 25, 1, 0), array((int)$row['set_1_dom'], (int)$row['set_1_ext'],
            (int)$row['set_4_dom'], (int)$row['sd'], (int)$row['se']));
        self::assertSame(3, (int)$this->rank()['A']['points']);
    }

    public function test_forfait_de_l_equipe_qui_recoit_ou_qui_se_deplace(): void
    {
        $id_match = $this->match('02', 'A', 'B');
        $id = $this->create('B', 'forfait', 0, '02');
        $this->sanction->applySanctions((string)$id);
        $row = $this->match_row($id_match);
        self::assertSame(array(25, 0), array((int)$row['set_1_dom'], (int)$row['set_1_ext']));
        self::assertSame(-1, (int)$this->rank()['B']['points']);
    }

    public function test_annulation_du_match(): void
    {
        $id_match = $this->match('03', 'A', 'B');
        $id = $this->create('A', 'annulation_match', 0, '03');
        $this->sanction->applySanctions((string)$id);
        self::assertSame('ARCHIVED', $this->match_row($id_match)['match_status']);
        self::assertSame(0, (int)$this->rank()['A']['joues']);

        $this->sanction->liftSanctions((string)$id);
        self::assertSame('CONFIRMED', $this->match_row($id_match)['match_status']);
        self::assertSame(1, (int)$this->rank()['A']['joues']);
    }

    public function test_forfait_general_retire_l_equipe_et_annule_ses_matchs(): void
    {
        $m1 = $this->match('04', 'A', 'B');
        $m2 = $this->match('05', 'C', 'A');
        $m3 = $this->match('06', 'B', 'C');
        $id = $this->create('A', 'forfait_general', 0, null, 'forfaits_repetes');

        $this->sanction->applySanctions((string)$id);
        self::assertSame('ARCHIVED', $this->match_row($m1)['match_status']);
        self::assertSame('ARCHIVED', $this->match_row($m2)['match_status']);
        self::assertSame('CONFIRMED', $this->match_row($m3)['match_status'], 'les matchs des autres équipes restent');
        $rank = $this->rank();
        self::assertArrayNotHasKey('A', $rank, "l'équipe sort du classement");
        self::assertSame(1, (int)$rank['B']['joues'], 'B ne garde que son match contre C');

        $this->sanction->reopenSanctions((string)$id);
        self::assertSame('CONFIRMED', $this->match_row($m1)['match_status']);
        self::assertSame('CONFIRMED', $this->match_row($m2)['match_status']);
        self::assertArrayHasKey('A', $this->rank());
    }

    public function test_deux_sanctions_appliquees_sur_le_meme_match_sont_refusees(): void
    {
        $this->match('07', 'A', 'B');
        $first = $this->create('A', 'forfait', 0, '07');
        $second = $this->create('B', 'defaite_penalite', 0, '07');
        $this->sanction->applySanctions((string)$first);
        $this->expectExceptionMessage('Une autre sanction déjà appliquée modifie ce match');
        $this->sanction->applySanctions((string)$second);
    }

    public function test_validations_de_saisie(): void
    {
        $this->match('08', 'A', 'B');
        foreach (array(
                     fn() => $this->create('A', 'forfait'),                      // sans match
                     fn() => $this->create('A', 'points', 0),                    // 0 point
                     fn() => $this->create('C', 'forfait', 0, '08'),             // C n'a pas joué ce match
                     fn() => $this->sanction->saveSanction(null, 'm|0', null, 'autre', 'avertissement', 0, null),
                     fn() => $this->sanction->saveSanction(null, 'm|' . $this->team['A'], null, 'inconnu', 'avertissement', 0, null),
                 ) as $i => $call) {
            try {
                $call();
                self::fail("saisie n°$i : une exception était attendue");
            } catch (Exception $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    public function test_une_sanction_decidee_ne_se_modifie_ni_ne_se_supprime(): void
    {
        $id = $this->create('A', 'points', 1);
        $this->sanction->applySanctions((string)$id);
        try {
            $this->sanction->saveSanction($id, 'm|' . $this->team['A'], null, 'autre', 'points', 2, null);
            self::fail('modifier une sanction décidée doit être refusé');
        } catch (Exception $e) {
            self::assertStringContainsString('remettez-la', $e->getMessage());
        }
        $this->expectExceptionMessage("Une sanction appliquée ne se supprime pas");
        $this->sanction->deleteSanctions((string)$id);
    }

    public function test_suppression_d_une_sanction_constatee(): void
    {
        $id = $this->create('A', 'avertissement');
        $this->sanction->deleteSanctions((string)$id);
        self::assertEmpty($this->sql->execute("SELECT id FROM sanctions WHERE id = ?",
            array(array('type' => 'i', 'value' => $id))));
    }

    public function test_liste_admin_et_sanctions_de_l_equipe(): void
    {
        $this->match('09', 'A', 'B');
        $pending = $this->create('A', 'avertissement', 0, '09', 'feuille_mal_remplie');
        $applied = $this->create('A', 'points', 1);
        $lifted = $this->create('A', 'points', 2);
        $this->sanction->applySanctions((string)$applied);
        $this->sanction->liftSanctions((string)$lifted);

        $listed = array_values(array_filter($this->sanction->getSanctions(),
            fn($r) => (int)$r['id_equipe'] === $this->team['A']));
        self::assertCount(3, $listed);
        self::assertSame('à décider', $listed[0]['statut'], 'les sanctions à décider d\'abord');
        self::assertSame('Feuille de match mal remplie', $listed[0]['motif_label']);
        self::assertSame('ISS475_09', $listed[0]['code_match']);

        $team = $this->sanction->get_team_sanctions($this->team['A']);
        self::assertSame(array('CONSTATEE', 'APPLIQUEE'), array_column($team, 'status'),
            'une sanction levée n\'est pas montrée au responsable');
        self::assertSame('1 point en moins', $team[1]['sanction_label']);
        unset($pending);
    }

    public function test_catalogue_du_formulaire(): void
    {
        $catalog = $this->sanction->getSanctionCatalog();
        self::assertContains('m|' . $this->team['A'], array_column($catalog['teams'], 'value'));
        self::assertContains('forfait_general', array_column($catalog['types'], 'value'));
        self::assertContains('feuille_non_signee', array_column($catalog['motifs'], 'value'));
    }
}
