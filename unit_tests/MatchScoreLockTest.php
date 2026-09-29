<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchMgr.php';

/**
 * Issue #344 — le score ne se saisit qu'après la signature des deux fiches
 * équipes, sauf forfait déclaré ; l'admin corrige sans condition.
 */
class MatchScoreLockTest extends UfolepTestCase
{
    private MatchMgr $match_manager;
    private int $user_id;
    private int $team_dom;
    private int $id_match;

    protected function setUp(): void
    {
        parent::setUp();
        $this->match_manager = new MatchMgr();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'ux', libelle = 'issue344 tests', id_compet_maitre = 'ux'");
        $this->user_id = $this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'issue344_leader', email = 'issue344@test.fr', password_hash = MD5('test')");
        $teams = array();
        foreach (array('dom', 'ext') as $side) {
            $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'issue344 club $side'");
            $teams[$side] = $this->sql->execute(
                "INSERT INTO equipes SET code_competition = 'ux', nom_equipe = 'issue344 $side', id_club = $id_club");
        }
        $this->team_dom = $teams['dom'];
        $this->sql->execute("INSERT INTO users_teams SET user_id = ?, team_id = ?", array(
            array('type' => 'i', 'value' => $this->user_id),
            array('type' => 'i', 'value' => $this->team_dom),
        ));
        $id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue344 court'");
        $this->id_match = $this->sql->execute(
            "INSERT INTO matches SET code_match = 'ISS344', code_competition = 'ux', division = '1',
                id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
                date_reception = CURRENT_DATE, date_original = CURRENT_DATE, match_status = 'CONFIRMED'",
            array(
                array('type' => 'i', 'value' => $teams['dom']),
                array('type' => 'i', 'value' => $teams['ext']),
                array('type' => 'i', 'value' => $id_court),
            ));
        $this->connect_as_team_leader($this->team_dom);
        $_SESSION['login'] = 'issue344_leader';
        $_SESSION['id_user'] = $this->user_id;
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM matches WHERE code_match = 'ISS344'");
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = 'issue344_leader')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login = 'issue344_leader'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%ISS344%'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue344 %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'issue344 club %'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue344 court'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'ux'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function sign_team_sheets(bool $dom, bool $ext): void
    {
        $this->sql->execute("UPDATE matches SET is_sign_team_dom = ?, is_sign_team_ext = ? WHERE id_match = ?", array(
            array('type' => 'i', 'value' => $dom ? 1 : 0),
            array('type' => 'i', 'value' => $ext ? 1 : 0),
            array('type' => 'i', 'value' => $this->id_match),
        ));
    }

    /** 3-0 pour le domicile, ou un forfait si $forfeit est donné. */
    private function save_score(?string $forfeit = null, bool $with_score = true): void
    {
        $s = $with_score ? array(25, 25, 25, 0, 0, 10, 10, 10, 0, 0) : array_fill(0, 10, 0);
        $this->match_manager->save_match($this->id_match, 'ISS344',
            $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9],
            'HOME', 'note ISS344', null, $forfeit);
    }

    private function stored_sets(): array
    {
        return $this->sql->execute("SELECT set_1_dom, set_1_ext, set_2_dom, set_2_ext, set_3_dom, set_3_ext, set_4_dom
                                    FROM matches WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $this->id_match)))[0];
    }

    public function test_score_refuse_sans_les_fiches_equipes(): void
    {
        try {
            $this->save_score();
            self::fail('Le score devait être refusé');
        } catch (Exception $e) {
            self::assertSame(409, $e->getCode());
            self::assertStringContainsString('issue344 dom, issue344 ext', $e->getMessage());
        }
        self::assertSame(0, (int)$this->stored_sets()['set_1_dom']);
    }

    public function test_le_message_nomme_la_fiche_manquante(): void
    {
        $this->sign_team_sheets(true, false);
        try {
            $this->save_score();
            self::fail('Le score devait être refusé');
        } catch (Exception $e) {
            self::assertStringContainsString('Fiche non signée : issue344 ext.', $e->getMessage());
        }
    }

    public function test_score_accepte_une_fois_les_deux_fiches_signees(): void
    {
        $this->sign_team_sheets(true, true);
        $this->save_score();
        self::assertSame(25, (int)$this->stored_sets()['set_1_dom']);
    }

    public function test_arbitrage_et_commentaire_restent_enregistrables_sans_score(): void
    {
        $this->save_score(null, false);
        $row = $this->sql->execute("SELECT referee, note FROM matches WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $this->id_match)))[0];
        self::assertSame('HOME', $row['referee']);
    }

    public function test_forfait_declare_sans_fiche_equipe(): void
    {
        $this->save_score('ext');
        $sets = $this->stored_sets();
        self::assertSame(array(25, 0, 25, 0, 25, 0), array_map('intval', array(
            $sets['set_1_dom'], $sets['set_1_ext'], $sets['set_2_dom'], $sets['set_2_ext'], $sets['set_3_dom'], $sets['set_3_ext'])));
        self::assertSame(0, (int)$sets['set_4_dom']);
    }

    public function test_forfait_invalide(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->save_score('personne');
    }

    public function test_la_saisie_en_direct_respecte_le_meme_verrou(): void
    {
        require_once __DIR__ . '/../classes/LiveScore.php';
        $live = new LiveScore();
        $live->startLiveScore('ISS344');
        try {
            $live->saveToMatch('ISS344');
            self::fail("Le score en direct ne doit pas passer avant la signature des fiches");
        } catch (Exception $e) {
            self::assertSame(409, $e->getCode());
        } finally {
            $this->sql->execute("DELETE FROM live_scores WHERE id_match = 'ISS344'");
        }
    }

    public function test_l_admin_corrige_sans_condition(): void
    {
        $this->connect_as_admin();
        $this->save_score();
        self::assertSame(10, (int)$this->stored_sets()['set_1_ext']);
    }
}
