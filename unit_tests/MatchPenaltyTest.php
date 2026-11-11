<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchPenalty.php';

/**
 * Issue #345 — feuille de match non signée 7 jours après le match (48 h avant
 * l'issue #456) : un manquement est relevé pour les deux équipes. Depuis #475,
 * c'est une sanction « constatée » (1 point envisagé) : le classement n'est
 * pas touché tant que la commission ne l'a pas appliquée.
 *
 * Division de test « 95 » du championnat masculin, et une coupe de test `us`.
 * La mise en service est passée en paramètre (`$since`) : les matchs de test
 * sont datés d'il y a huit jours.
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
        foreach (array('A' => 'm', 'B' => 'm', 'C' => 'us', 'D' => 'us', 'E' => 'm', 'F' => 'm') as $k => $code) {
            $this->team[$k] = $this->sql->execute("INSERT INTO equipes SET code_competition = ?, nom_equipe = ?, id_club = ?",
                array(array('type' => 's', 'value' => $code), array('type' => 's', 'value' => "issue345 $k"),
                      array('type' => 'i', 'value' => $id_club)));
            $this->sql->execute("INSERT INTO classements SET code_competition = ?, division = ?, id_equipe = ?, rank_start = 1, penalite = 0",
                array(array('type' => 's', 'value' => $code), array('type' => 's', 'value' => $code === 'm' ? '95' : '1'),
                      array('type' => 'i', 'value' => $this->team[$k])));
        }
        // Match parasite, hors des matchs de test : comme ceux de la base de CI,
        // il est pénalisé dans la même passe et ne doit fausser aucun test.
        $this->sql->execute(
            "INSERT INTO matches SET code_match = 'N345', code_competition = 'm', division = '95',
                id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
                date_reception = ?, date_original = ?, match_status = 'CONFIRMED'",
            array(array('type' => 'i', 'value' => $this->team['E']), array('type' => 'i', 'value' => $this->team['F']),
                  array('type' => 'i', 'value' => $this->id_court),
                  array('type' => 's', 'value' => $this->days_ago(8)), array('type' => 's', 'value' => $this->days_ago(8))));
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
        $this->sql->execute("DELETE FROM sanctions WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'issue345 %')");
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'ISS345_%' OR code_match = 'N345'");
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

    /**
     * Sanctions relevées sur les matchs de test. On ne compte jamais le total
     * renvoyé par apply_unsigned_sheet_penalties() : la base de CI contient
     * d'autres matchs récents et non signés, relevés dans la même passe.
     */
    private function fixture_sanctions(): array
    {
        return $this->sql->execute("SELECT * FROM sanctions WHERE id_match IN
                                    (SELECT id_match FROM matches WHERE code_match LIKE 'ISS345_%')");
    }

    private function days_ago(int $days): string
    {
        return date('Y-m-d', strtotime("-$days days"));
    }

    public function test_feuille_non_signee_apres_7_jours_constate_un_manquement_pour_les_deux_equipes(): void
    {
        // l'équipe à domicile a signé : les deux sont concernées quand même
        $this->match('m', 'A', 'B', $this->days_ago(8), 1, 0);
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        $rows = $this->fixture_sanctions();
        self::assertCount(2, $rows);
        foreach ($rows as $row) {
            self::assertSame(MatchPenalty::MOTIF_UNSIGNED_SHEET, $row['motif']);
            self::assertSame('CONSTATEE', $row['status']);
            self::assertSame('AUTO', $row['source']);
            self::assertSame('points', $row['sanction_type']);
            self::assertSame(1, (int)$row['points']);
        }
        // issue #475 : rien n'est retiré avant la décision de la commission
        self::assertSame(0, $this->penalite('A'));
        self::assertSame(0, $this->penalite('B'));
    }

    public function test_une_seule_fois_par_match(): void
    {
        $this->match('m', 'A', 'B', $this->days_ago(8));
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        self::assertCount(2, $this->fixture_sanctions());
    }

    public function test_rien_avant_7_jours_ni_si_la_feuille_est_signee(): void
    {
        $this->match('m', 'A', 'B', date('Y-m-d'));                // aujourd'hui : délai non écoulé
        $this->match('m', 'A', 'B', $this->days_ago(3));           // 3 jours : relevé à 48 h avant #456
        $this->match('m', 'A', 'B', $this->days_ago(6));           // 6 jours : délai non écoulé
        $this->match('m', 'A', 'B', $this->days_ago(8), 1, 1);     // signée des deux côtés
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        self::assertCount(0, $this->fixture_sanctions());
    }

    public function test_exclusions(): void
    {
        $this->match('m', 'A', 'B', $this->days_ago(8), 0, 0, 'NOT_CONFIRMED');
        $this->match('m', 'A', 'B', $this->days_ago(8), 0, 0, 'CONFIRMED', 1);   // certifié
        $this->match('us', 'C', 'D', $this->days_ago(8));                        // coupe
        $this->match('m', 'A', 'B', $this->days_ago(60));                        // avant la mise en service
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        self::assertCount(0, $this->fixture_sanctions());
    }

    public function test_le_manquement_reste_apres_une_signature_tardive(): void
    {
        $id_match = $this->match('m', 'A', 'B', $this->days_ago(8));
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        $this->sql->execute("UPDATE matches SET is_sign_match_dom = 1, is_sign_match_ext = 1 WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $id_match)));
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        self::assertCount(2, $this->fixture_sanctions());
    }

    /** Une sanction levée (clémence) n'est pas relevée une seconde fois. */
    public function test_une_sanction_levee_n_est_pas_relevee_a_nouveau(): void
    {
        $this->match('m', 'A', 'B', $this->days_ago(8));
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        $ids = implode(',', array_column($this->fixture_sanctions(), 'id'));
        (new Sanction())->liftSanctions($ids, 'clémence');
        $this->penalty->apply_unsigned_sheet_penalties($this->since);
        $rows = $this->fixture_sanctions();
        self::assertCount(2, $rows);
        self::assertSame(array('LEVEE', 'LEVEE'), array_column($rows, 'status'));
    }

    /** L'email prévient que la commission décidera, sans annoncer de point retiré. */
    public function test_email_annonce_une_decision_de_la_commission(): void
    {
        $template = file_get_contents(__DIR__ . '/../templates/emails/penalty_unsigned_match_sheet.fr.html');
        self::assertStringContainsString('réunion de fin de demi-saison', $template);
        self::assertStringNotContainsString('est retiré', $template);
    }
}
