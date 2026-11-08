<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/Team.php';
require_once __DIR__ . '/../classes/Register.php';

/**
 * Issue #467 — une équipe du championnat masculin est inscrite à la coupe
 * Isoardi ; son responsable (d'équipe, ou de club via le sélecteur d'équipe)
 * peut l'en désinscrire, ou l'y réinscrire, pendant la période d'inscription
 * à la coupe Khoury Hanna, et seulement pendant celle-ci.
 *
 * Le test crée son club, ses équipes et une inscription, et remet les dates
 * d'inscription de la coupe KH telles qu'il les a trouvées.
 */
class CupOptOutTest extends UfolepTestCase
{
    private Team $team;
    private int $id_club;
    private int $id_team_m;
    private int $id_team_f;
    private array $kh_dates;
    private string $suffixe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->team = new Team();
        $this->suffixe = 'ISSUE467_' . uniqid();
        $this->id_club = (int)$this->sql->execute(
            "INSERT INTO clubs (nom) VALUES (?)",
            [['type' => 's', 'value' => $this->suffixe]]);
        $this->id_team_m = $this->creerEquipe('m', "M $this->suffixe", 1);
        $this->id_team_f = $this->creerEquipe('f', "F $this->suffixe", 0);
        $this->kh_dates = $this->sql->execute(
            "SELECT start_register_date, limit_register_date FROM competitions WHERE code_competition = 'kh'")[0];
    }

    protected function tearDown(): void
    {
        $this->sql->execute(
            "UPDATE competitions SET start_register_date = ?, limit_register_date = ? WHERE code_competition = 'kh'",
            [
                ['type' => 's', 'value' => $this->kh_dates['start_register_date']],
                ['type' => 's', 'value' => $this->kh_dates['limit_register_date']],
            ]);
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE ?",
            [['type' => 's', 'value' => "%$this->suffixe%"]]);
        $this->sql->execute("DELETE FROM register WHERE id_club = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        $this->sql->execute("DELETE FROM equipes WHERE id_club = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        $this->sql->execute("DELETE FROM clubs WHERE id = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        parent::tearDown();
    }

    private function creerEquipe(string $code_competition, string $nom, int $is_cup_registered): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO equipes (nom_equipe, code_competition, id_club, is_cup_registered) VALUES (?, ?, ?, ?)",
            [
                ['type' => 's', 'value' => $nom],
                ['type' => 's', 'value' => $code_competition],
                ['type' => 'i', 'value' => $this->id_club],
                ['type' => 'i', 'value' => $is_cup_registered],
            ]);
    }

    /** Période KH : ouverte (hier → dans 10 jours) ou close (terminée hier). */
    private function periode(bool $ouverte): void
    {
        $this->sql->execute($ouverte
            ? "UPDATE competitions SET start_register_date = CURDATE() - INTERVAL 1 DAY,
                                       limit_register_date = CURDATE() + INTERVAL 10 DAY
               WHERE code_competition = 'kh'"
            : "UPDATE competitions SET start_register_date = CURDATE() - INTERVAL 30 DAY,
                                       limit_register_date = CURDATE() - INTERVAL 1 DAY
               WHERE code_competition = 'kh'");
    }

    private function coupe(int $id_team): int
    {
        return (int)$this->sql->execute(
            "SELECT is_cup_registered + 0 AS v FROM equipes WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $id_team]])[0]['v'];
    }

    public function test_le_responsable_desinscrit_puis_reinscrit_son_equipe(): void
    {
        $this->periode(true);
        $this->connect_as_team_leader($this->id_team_m);

        $etat = $this->team->getMyCupRegistration();
        self::assertTrue($etat['concerned']);
        self::assertTrue($etat['is_cup_registered']);
        self::assertTrue($etat['is_open']);

        $this->team->setMyCupRegistration('0');
        self::assertSame(0, $this->coupe($this->id_team_m));
        self::assertFalse($this->team->getMyCupRegistration()['is_cup_registered']);

        $this->team->setMyCupRegistration('1');
        self::assertSame(1, $this->coupe($this->id_team_m));

        $journal = array_column($this->sql->execute(
            "SELECT comment FROM activity WHERE comment LIKE ? ORDER BY id",
            [['type' => 's', 'value' => "%$this->suffixe%"]]), 'comment');
        self::assertCount(2, $journal);
        self::assertStringContainsString('désinscrite de la coupe Isoardi', $journal[0]);
        self::assertStringContainsString('réinscrite à la coupe Isoardi', $journal[1]);
    }

    public function test_le_responsable_de_club_agit_sur_l_equipe_selectionnee(): void
    {
        $this->periode(true);
        $this->connect_as_club_leader($this->id_club, $this->id_team_m);
        // le sélecteur d'équipe du menu pose ce drapeau (UserManager)
        $_SESSION['is_team_leader'] = true;

        $this->team->setMyCupRegistration('0');
        self::assertSame(0, $this->coupe($this->id_team_m));
    }

    public function test_refus_hors_periode(): void
    {
        $this->periode(false);
        $this->connect_as_team_leader($this->id_team_m);
        self::assertFalse($this->team->getMyCupRegistration()['is_open']);

        try {
            $this->team->setMyCupRegistration('0');
            self::fail('la désinscription doit être refusée hors période');
        } catch (Exception $e) {
            self::assertSame(403, $e->getCode());
            self::assertStringContainsString('ne peut plus être changée', $e->getMessage());
        }
        self::assertSame(1, $this->coupe($this->id_team_m));
    }

    public function test_seul_le_championnat_masculin_est_concerne(): void
    {
        $this->periode(true);
        $this->connect_as_team_leader($this->id_team_f);
        self::assertFalse($this->team->getMyCupRegistration()['concerned']);

        $this->expectExceptionMessage('Seules les équipes du championnat masculin');
        $this->team->setMyCupRegistration('1');
    }

    public function test_refus_sans_equipe_selectionnee(): void
    {
        $this->periode(true);
        $this->connect_as_club_leader($this->id_club);
        $this->expectExceptionCode(403);
        $this->team->setMyCupRegistration('0');
    }

    public function test_save_team_ne_contourne_pas_la_periode(): void
    {
        $this->periode(false);
        $this->connect_as_team_leader($this->id_team_m);
        try {
            $this->team->saveTeam(is_cup_registered: '0', my_team: '1');
            self::fail('saveTeam ne doit pas changer la coupe hors admin');
        } catch (Exception $e) {
            self::assertSame(403, $e->getCode());
        }
        self::assertSame(1, $this->coupe($this->id_team_m));

        // l'admin garde la main, à toute date (écran Équipes)
        $this->connect_as_admin();
        $this->team->saveTeam(id_equipe: (string)$this->id_team_m, is_cup_registered: '0');
        self::assertSame(0, $this->coupe($this->id_team_m));
    }

    public function test_l_initialisation_de_saison_reprend_le_choix_de_l_inscription(): void
    {
        $register = new Register();
        foreach ([[0, 1], [1, 0]] as [$choix, $avant]) {
            $this->sql->execute("UPDATE equipes SET is_cup_registered = ? WHERE id_equipe = ?", [
                ['type' => 'i', 'value' => $avant],
                ['type' => 'i', 'value' => $this->id_team_m],
            ]);
            $id_register = (int)$this->sql->execute(
                "INSERT INTO register SET new_team_name = ?, id_club = ?, id_competition = 1,
                    leader_name = 'TEST', leader_first_name = 'Test', leader_email = 'issue467@ufolep.test',
                    leader_phone = '0600000000', status = 'VALIDATED', is_cup_registered = ?",
                [
                    // le nom d'inscription est unique
                    ['type' => 's', 'value' => "M $this->suffixe choix $choix"],
                    ['type' => 'i', 'value' => $this->id_club],
                    ['type' => 'i', 'value' => $choix],
                ]);
            $register->apply_cup_choice($id_register, $this->id_team_m);
            self::assertSame($choix, $this->coupe($this->id_team_m));
        }
    }
}
