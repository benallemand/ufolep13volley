<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchPenalty.php';

/**
 * Issue #345 — -1 point aux deux équipes si la feuille de match n'est pas
 * signée 48 h après le match.
 *
 * Division de test « 95 » du championnat masculin, et une coupe de test `us`.
 * La mise en service est passée en paramètre (`$since`) : les matchs de test
 * sont datés d'il y a trois jours.
 */
class MatchPenaltyTest extends UfolepTestCase
{
    private MatchPenalty $penalty;
    private array $team = array();
    private int $id_court;
    private string $since;

    protected function setUp(): void
    {
        parent::setUp();
        $this->penalty = new MatchPenalty();
        $this->delete_test_data();
        $this->since = date('Y-m-d', strtotime('-30 days'));
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'us', libelle = 'issue345 coupe', id_compet_maitre = 'us'");
        $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'issue345 club'");
        $this->id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue345 court'");
        foreach (array('A' => 'm', 'B' => 'm', 'C' => 'us', 'D' => 'us') as $k => $code) {
            $this->team[$k] = $this->sql->execute("INSERT INTO equipes SET code_competition = ?, nom_equipe = ?, id_club = ?",
                array(array('type' => 's', 'value' => $code), array('type' => 's', 'value' => "issue345 $k"),
                      array('type' => 'i', 'value' => $id_club)));
            $this->sql->execute("INSERT INTO classements SET code_competition = ?, division = ?, id_equipe = ?, rank_start = 1, penalite = 0",
                array(array('type' => 's', 'value' => $code), array('type' => 's', 'value' => $code === 'm' ? '95' : '1'),
                      array('type' => 'i', 'value' => $this->team[$k])));
        }
        $this->connect_as_admin();
    }

    private function match(string $code, string $dom, string $ext, string $date, int $sign_dom = 0, int $sign_ext = 0,
                           string $status = 'CONFIRMED', int $certif = 0): int
    {
        static $n = 0;
        $n++;
        return $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = ?, division = ?,
                id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
                date_reception = ?, date_original = ?, match_status = ?, certif = ?,
                is_sign_match_dom = ?, is_sign_match_ext = ?",
            array(array('type' => 's', 'value' => sprintf('ISS345_%02d', $n)), array('type' => 's', 'value' => $code),
                  array('type' => 's', 'value' => $code === 'm' ? '95' : '1'),
                  array('type' => 'i', 'value' => $this->team[$dom]), array('type' => 'i', 'value' => $this->team[$ext]),
                  array('type' => 'i', 'value' => $this->id_court),
                  array('type' => 's', 'value' => $date), array('type' => 's', 'value' => $date),
                  array('type' => 's', 'value' => $status), array('type' => 'i', 'value' => $certif),
                  array('type' => 'i', 'value' => $sign_dom), array('type' => 'i', 'value' => $sign_ext)));
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM match_penalties WHERE id_match IN (SELECT id_match FROM matches WHERE code_match LIKE 'ISS345_%')");
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'ISS345_%'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%ISS345_%'");
        $this->sql->execute("DELETE FROM classements WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'issue345 %')");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue345 %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'issue345 club'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue345 court'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'us'");
        $this->sql->execute("DELETE FROM emails WHERE subject LIKE '%ISS345_%'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function penalite(string $k): int
    {
        return (int)$this->sql->execute("SELECT penalite FROM classements WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->team[$k])))[0]['penalite'];
    }

    private function days_ago(int $days): string
    {
        return date('Y-m-d', strtotime("-$days days"));
    }

    public function test_feuille_non_signee_apres_48h_penalise_les_deux_equipes(): void
    {
        // l'équipe à domicile a signé : les deux sont pénalisées quand même
        $this->match('m', 'A', 'B', $this->days_ago(3), 1, 0);
        self::assertSame(2, $this->penalty->apply_unsigned_sheet_penalties($this->since));
        self::assertSame(1, $this->penalite('A'));
        self::assertSame(1, $this->penalite('B'));
        $rows = $this->sql->execute("SELECT reason FROM match_penalties WHERE id_match IN
                                     (SELECT id_match FROM matches WHERE code_match LIKE 'ISS345_%')");
        self::assertCount(2, $rows);
        self::assertSame(MatchPenalty::REASON_UNSIGNED_SHEET, $rows[0]['reason']);
    }

    public function test_une_seule_fois_par_match(): void
    {
        $this->match('m', 'A', 'B', $this->days_ago(3));
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        self::assertSame(0, $this->penalty->apply_unsigned_sheet_penalties($this->since));
        self::assertSame(1, $this->penalite('A'));
    }

    public function test_pas_de_penalite_avant_48h_ni_si_la_feuille_est_signee(): void
    {
        $this->match('m', 'A', 'B', date('Y-m-d'));                // aujourd'hui : délai non écoulé
        $this->match('m', 'A', 'B', $this->days_ago(3), 1, 1);     // signée des deux côtés
        self::assertSame(0, $this->penalty->apply_unsigned_sheet_penalties($this->since));
        self::assertSame(0, $this->penalite('A'));
    }

    public function test_exclusions(): void
    {
        $this->match('m', 'A', 'B', $this->days_ago(3), 0, 0, 'NOT_CONFIRMED');
        $this->match('m', 'A', 'B', $this->days_ago(3), 0, 0, 'CONFIRMED', 1);   // certifié
        $this->match('us', 'C', 'D', $this->days_ago(3));                        // coupe
        $this->match('m', 'A', 'B', $this->days_ago(60));                        // avant la mise en service
        self::assertSame(0, $this->penalty->apply_unsigned_sheet_penalties($this->since));
    }

    public function test_la_penalite_reste_apres_une_signature_tardive(): void
    {
        $id_match = $this->match('m', 'A', 'B', $this->days_ago(3));
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        $this->sql->execute("UPDATE matches SET is_sign_match_dom = 1, is_sign_match_ext = 1 WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $id_match)));
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        self::assertSame(1, $this->penalite('A'));
    }
}
