<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/UserManager.php';

/**
 * Menu de sélection d'équipe (`usermanager/getMyManageableTeams`) : un club
 * garde en base ses équipes des saisons passées. Le menu ne garde que les
 * équipes engagées (classement) ou réinscrites (engagement non refusé), plus
 * l'équipe courante de la session.
 *
 * Le test crée son club, ses équipes, un classement et des engagements.
 */
class ManageableTeamsTest extends UfolepTestCase
{
    private int $id_club;
    private string $suffixe;
    /** @var array<string, int> */
    private array $teams = array();

    protected function setUp(): void
    {
        parent::setUp();
        $this->suffixe = 'MENU_' . uniqid();
        $this->id_club = (int)$this->sql->execute("INSERT INTO clubs (nom) VALUES (?)",
            [['type' => 's', 'value' => $this->suffixe]]);
        foreach (['engagee', 'reinscrite', 'refusee', 'ancienne', 'courante', 'nouvelle', 'nouvelle_refusee'] as $cle) {
            $this->teams[$cle] = (int)$this->sql->execute(
                "INSERT INTO equipes (nom_equipe, code_competition, id_club) VALUES (?, 'kh', ?)",
                [
                    ['type' => 's', 'value' => "$cle $this->suffixe"],
                    ['type' => 'i', 'value' => $this->id_club],
                ]);
        }
        $this->sql->execute(
            "INSERT INTO classements (code_competition, division, id_equipe, rank_start) VALUES ('kh', '1', ?, 1)",
            [['type' => 'i', 'value' => $this->teams['engagee']]]);
        $this->engager($this->teams['reinscrite'], 'PENDING');
        $this->engager($this->teams['refusee'], 'REFUSED');
        // nouvelles équipes : l'inscription n'a pas d'équipe d'origine, elle
        // désigne l'équipe créée par son nom (exemple réel : « L'attaque des
        // Moineaux », coupe KH)
        $this->inscrire_nouvelle("nouvelle $this->suffixe", 'VALIDATED');
        $this->inscrire_nouvelle("nouvelle_refusee $this->suffixe", 'REFUSED');
    }

    private function inscrire_nouvelle(string $name, string $status): void
    {
        $this->sql->execute(
            "INSERT INTO register SET new_team_name = ?, id_club = ?, id_competition = 6, old_team_id = NULL,
                leader_name = 'TEST', leader_first_name = 'Test', leader_email = 'menu@ufolep.test',
                leader_phone = '0600000000', status = ?",
            [
                ['type' => 's', 'value' => $name],
                ['type' => 'i', 'value' => $this->id_club],
                ['type' => 's', 'value' => $status],
            ]);
    }

    protected function tearDown(): void
    {
        $this->sql->execute("DELETE FROM users_teams WHERE team_id IN (SELECT id_equipe FROM equipes WHERE id_club = ?)",
            [['type' => 'i', 'value' => $this->id_club]]);
        $this->sql->execute("DELETE FROM register WHERE id_club = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        $this->sql->execute("DELETE FROM classements WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE id_club = ?)",
            [['type' => 'i', 'value' => $this->id_club]]);
        $this->sql->execute("DELETE FROM equipes WHERE id_club = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        $this->sql->execute("DELETE FROM clubs WHERE id = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        parent::tearDown();
    }

    private function engager(int $id_team, string $status): void
    {
        $this->sql->execute(
            "INSERT INTO register SET new_team_name = ?, id_club = ?, id_competition = 6, old_team_id = ?,
                leader_name = 'TEST', leader_first_name = 'Test', leader_email = 'menu@ufolep.test',
                leader_phone = '0600000000', status = ?",
            [
                ['type' => 's', 'value' => "register $status $this->suffixe"],
                ['type' => 'i', 'value' => $this->id_club],
                ['type' => 'i', 'value' => $id_team],
                ['type' => 's', 'value' => $status],
            ]);
    }

    /** @return int[] */
    private function menu(): array
    {
        return array_map('intval', array_column((new UserManager())->getMyManageableTeams(), 'id_equipe'));
    }

    public function test_seules_les_equipes_engagees_ou_reinscrites_restent(): void
    {
        $this->connect_as_club_leader($this->id_club);
        $_SESSION['id_user'] = 1;
        $menu = $this->menu();
        self::assertContains($this->teams['engagee'], $menu);
        self::assertContains($this->teams['reinscrite'], $menu, "une réinscription en attente garde l'équipe");
        self::assertNotContains($this->teams['refusee'], $menu, "une réinscription refusée ne compte pas");
        self::assertNotContains($this->teams['ancienne'], $menu);
        self::assertNotContains($this->teams['courante'], $menu);
    }

    public function test_l_equipe_courante_reste_dans_le_menu(): void
    {
        $this->connect_as_club_leader($this->id_club, $this->teams['courante']);
        $_SESSION['id_user'] = 1;
        $menu = $this->menu();
        self::assertContains($this->teams['courante'], $menu);
        self::assertNotContains($this->teams['ancienne'], $menu);
    }

    public function test_une_nouvelle_equipe_inscrite_reste_dans_le_menu(): void
    {
        $this->connect_as_club_leader($this->id_club);
        $_SESSION['id_user'] = 1;
        $menu = $this->menu();
        self::assertContains($this->teams['nouvelle'], $menu, "une nouvelle équipe validée, pas encore en division");
        self::assertNotContains($this->teams['nouvelle_refusee'], $menu, "une inscription refusée ne compte pas");
    }

    /** Le responsable d'équipe (sans rôle club) voit aussi sa nouvelle équipe. */
    public function test_le_responsable_d_equipe_voit_sa_nouvelle_equipe(): void
    {
        $this->sql->execute("INSERT INTO users_teams SET user_id = 1, team_id = ?",
            [['type' => 'i', 'value' => $this->teams['nouvelle']]]);
        $this->connect_as_team_leader($this->teams['engagee']);
        $_SESSION['id_user'] = 1;
        self::assertContains($this->teams['nouvelle'], $this->menu());
    }
}
