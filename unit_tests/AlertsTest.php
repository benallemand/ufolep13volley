<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Alerts.php';

/**
 * Issue #346 — encart d'alertes du tableau de bord.
 *
 * Un club avec deux équipes engagées en championnat mixte : A (le responsable
 * connecté) et B. A compte trois joueurs jouants, dont un sans photo et un sans
 * licence, plus un membre non jouant qui ne doit pas compter dans l'effectif.
 */
class AlertsTest extends UfolepTestCase
{
    private Alerts $alerts;
    private int $id_club;
    private array $team = array();

    protected function setUp(): void
    {
        parent::setUp();
        $this->alerts = new Alerts();
        $this->delete_test_data();
        $this->id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'issue346 club'");
        foreach (array('A', 'B') as $k) {
            $this->team[$k] = $this->sql->execute("INSERT INTO equipes SET code_competition = 'mo', nom_equipe = ?, id_club = ?",
                array(array('type' => 's', 'value' => "issue346 $k"), array('type' => 'i', 'value' => $this->id_club)));
            $this->sql->execute("INSERT INTO classements SET code_competition = 'mo', division = '94', id_equipe = ?, rank_start = 1, penalite = 0",
                array(array('type' => 'i', 'value' => $this->team[$k])));
        }
        $id_photo = $this->sql->execute("INSERT INTO photos SET path_photo = 'players_pics/issue346.jpg'");
        $players = array(
            array('Avec', 'F', $id_photo, 'L346001', 1),
            array('Sansphoto', 'M', null, 'L346002', 1),
            array('Sanslicence', 'F', $id_photo, null, 1),
            array('Nonjouant', 'M', $id_photo, 'L346003', 0),
        );
        foreach ($players as [$prenom, $sexe, $photo, $licence, $playing]) {
            $id = $this->sql->execute("INSERT INTO joueurs SET nom = 'Issue346', prenom = ?, sexe = ?, id_photo = ?, num_licence = ?, id_club = ?",
                array(array('type' => 's', 'value' => $prenom), array('type' => 's', 'value' => $sexe),
                      array('type' => 'i', 'value' => $photo), array('type' => 's', 'value' => $licence),
                      array('type' => 'i', 'value' => $this->id_club)));
            $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?, est_jouant = ?",
                array(array('type' => 'i', 'value' => $id), array('type' => 'i', 'value' => $this->team['A']),
                      array('type' => 'i', 'value' => $playing)));
        }
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM match_penalties WHERE id_match IN (SELECT id_match FROM matches WHERE code_match = 'ISS346')");
        $this->sql->execute("DELETE FROM matches WHERE code_match = 'ISS346'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_joueur IN (SELECT id FROM joueurs WHERE nom = 'Issue346')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'Issue346'");
        $this->sql->execute("DELETE FROM photos WHERE path_photo = 'players_pics/issue346.jpg'");
        $this->sql->execute("DELETE FROM classements WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'issue346 %')");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue346 %'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue346 court'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'issue346 club'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function issues(array $alerts, ?string $team = null): array
    {
        $out = array();
        foreach ($alerts as $alert) {
            if ($team === null || $alert['team'] === $team) {
                $out[] = $alert['issue'];
            }
        }
        return $out;
    }

    private function find(array $alerts, string $prefix): ?array
    {
        foreach ($alerts as $alert) {
            if (str_starts_with($alert['issue'], $prefix)) {
                return $alert;
            }
        }
        return null;
    }

    public function test_effectif_et_roles_de_l_equipe_du_responsable(): void
    {
        $this->connect_as_team_leader($this->team['A']);
        $alerts = $this->alerts->getAlerts();
        // 3 jouants : le membre non jouant ne compte pas (#325)
        self::assertNotNull($this->find($alerts, "Pas assez de joueurs dans l'équipe (3)"));
        self::assertNotNull($this->find($alerts, "Responsable d'équipe non défini"));
        self::assertNotNull($this->find($alerts, "Capitaine d'équipe non défini"));
        self::assertSame(array('issue346 A'), array_values(array_unique(array_column($alerts, 'team'))));
        foreach ($alerts as $alert) {
            self::assertArrayHasKey('link', $alert);
            self::assertContains($alert['criticity'], array('error', 'warning', 'info'));
        }
    }

    public function test_joueurs_sans_photo_et_sans_licence(): void
    {
        $this->connect_as_team_leader($this->team['A']);
        $alerts = $this->alerts->getAlerts();
        $photo = $this->find($alerts, 'Joueurs sans photo');
        self::assertNotNull($photo);
        self::assertStringContainsString('Sansphoto', $photo['issue']);
        self::assertStringNotContainsString('Nonjouant', $photo['issue']);
        self::assertSame('error', $photo['criticity']);
        $licence = $this->find($alerts, 'Joueurs sans licence');
        self::assertStringContainsString('Sanslicence', $licence['issue']);
    }

    public function test_une_penalite_recue_est_signalee(): void
    {
        $id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue346 court'");
        $id_match = $this->sql->execute(
            "INSERT INTO matches SET code_match = 'ISS346', code_competition = 'mo', division = '94',
                id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
                date_reception = CURRENT_DATE, date_original = CURRENT_DATE, match_status = 'CONFIRMED'",
            array(array('type' => 'i', 'value' => $this->team['A']), array('type' => 'i', 'value' => $this->team['B']),
                  array('type' => 'i', 'value' => $id_court)));
        $this->sql->execute("INSERT INTO match_penalties SET id_match = ?, id_equipe = ?, code_competition = 'mo', reason = 'feuille_non_signee_48h'",
            array(array('type' => 'i', 'value' => $id_match), array('type' => 'i', 'value' => $this->team['A'])));
        $this->connect_as_team_leader($this->team['A']);
        $penalty = $this->find($this->alerts->getAlerts(), 'Pénalité automatique');
        self::assertNotNull($penalty);
        self::assertStringContainsString('ISS346', $penalty['issue']);
    }

    public function test_le_responsable_de_club_voit_toutes_ses_equipes(): void
    {
        $this->connect_as_club_leader($this->id_club);
        $alerts = $this->alerts->getAlerts();
        $teams = array_values(array_unique(array_column($alerts, 'team')));
        sort($teams);
        self::assertSame(array('issue346 A', 'issue346 B'), $teams);
        self::assertNotEmpty($this->issues($alerts, 'issue346 B'));
    }

    public function test_rien_pour_l_admin(): void
    {
        $this->connect_as_admin();
        self::assertSame(array(), $this->alerts->getAlerts());
    }
}
