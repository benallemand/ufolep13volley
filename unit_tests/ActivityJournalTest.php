<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Team.php';

/**
 * Journal d'activité (`activity`).
 *
 * Deux équipes : « JOURNAL mienne », dont le compte connecté est responsable,
 * et « JOURNAL autre », d'un autre club.
 */
class ActivityJournalTest extends UfolepTestCase
{
    private int $id_user;
    private int $id_admin;
    private int $my_team;
    private int $other_team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->id_user = (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'journal_resp', email = 'journal_resp@ufolep.test'");
        $this->id_admin = (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'journal_admin', email = 'journal_admin@ufolep.test', is_admin = 1");
        foreach (array('mienne', 'autre') as $name) {
            $id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'JOURNAL club $name'");
            $id_team = (int)$this->sql->execute(
                "INSERT INTO equipes SET nom_equipe = 'JOURNAL $name', code_competition = 'm', id_club = ?",
                array(array('type' => 'i', 'value' => $id_club)));
            if ($name === 'mienne') {
                $this->my_team = $id_team;
            } else {
                $this->other_team = $id_team;
            }
        }
        $this->sql->execute("INSERT INTO users_teams SET user_id = ?, team_id = ?", array(
            array('type' => 'i', 'value' => $this->id_user),
            array('type' => 'i', 'value' => $this->my_team),
        ));
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%JOURNAL %'");
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE login LIKE 'journal\\_%')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login LIKE 'journal\\_%'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'JOURNAL %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'JOURNAL club %'");
    }

    /** Le compte responsable de « JOURNAL mienne », admin ou non. */
    private function connect_as_my_team_leader(bool $is_admin): void
    {
        $this->connect_as_team_leader($this->my_team);
        $_SESSION['id_user'] = $this->id_user;
        $_SESSION['login'] = 'journal_resp';
        $_SESSION['is_admin'] = $is_admin;
    }

    private function history_of(int $id_team): array
    {
        $this->connect_as_team_leader($id_team);
        return array_column((new Team())->getActivity(), 'description');
    }

    /**
     * Issue #423 : `activity.id` était un SMALLINT (32 767 au plus). Les
     * lignes anciennes sont purgées mais l'auto-incrément continue : en prod,
     * 28 329 le 12/10/2026 à environ 95 lignes par jour, soit la saturation
     * vers fin novembre, et l'échec de toute écriture qui journalise.
     */
    public function test_l_identifiant_du_journal_ne_sature_pas(): void
    {
        $rows = $this->sql->execute(
            "SELECT data_type FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'activity' AND column_name = 'id'");
        self::assertContains($rows[0]['data_type'] ?? $rows[0]['DATA_TYPE'], array('int', 'bigint'));
    }

    /**
     * Issue #425 : l'historique d'une équipe était celui de ses responsables.
     * Un admin responsable de « JOURNAL mienne » qui modifie « JOURNAL autre »
     * depuis l'administration : la ligne va à « JOURNAL autre », pas à la
     * sienne.
     */
    public function test_l_historique_d_une_equipe_est_celui_des_actions_qui_la_concernent(): void
    {
        $this->connect_as_my_team_leader(true);
        (new Team())->saveTeam('https://autre.example.org', null, $this->other_team);
        (new Team())->saveTeam('https://mienne.example.org', null, null, null, null, null, '1');

        $mine = $this->history_of($this->my_team);
        $other = $this->history_of($this->other_team);
        self::assertCount(1, $mine);
        self::assertStringContainsString('mienne.example.org', $mine[0]);
        self::assertCount(1, $other, "l'action d'administration est rangée dans l'équipe modifiée");
        self::assertStringContainsString('autre.example.org', $other[0]);
    }

    public function test_l_ecran_activite_montre_l_equipe_de_l_action(): void
    {
        $this->connect_as_my_team_leader(true);
        (new Team())->saveTeam('https://autre.example.org', null, $this->other_team);

        $rows = array_values(array_filter((new Team())->getActivity(),
            fn($row) => str_contains((string)$row['description'], 'autre.example.org')));
        self::assertCount(1, $rows);
        self::assertSame('JOURNAL autre', $rows[0]['nom_equipe'], "et non l'équipe de son auteur");
        self::assertSame('journal_resp', $rows[0]['utilisateur']);
        self::assertNull($rows[0]['utilisateur_origine']);
    }

    /**
     * Issue #426 : pendant « Agir en tant que », la ligne est au nom du compte
     * cible, et garde l'admin qui a agi.
     */
    public function test_agir_en_tant_que_garde_l_admin_d_origine(): void
    {
        $this->connect_as_my_team_leader(false);
        $_SESSION['acting_as'] = true;
        $_SESSION['original_admin_id'] = $this->id_admin;
        (new Team())->saveTeam('https://bascule.example.org', null, null, null, null, null, '1');

        $this->connect_as_admin();
        $rows = array_values(array_filter((new Team())->getActivity(),
            fn($row) => str_contains((string)$row['description'], 'bascule.example.org')));
        self::assertCount(1, $rows);
        self::assertSame('journal_resp', $rows[0]['utilisateur']);
        self::assertSame('journal_admin', $rows[0]['utilisateur_origine']);
    }

    public function test_hors_bascule_aucun_admin_d_origine(): void
    {
        // une clé restée en session sans bascule active ne compte pas
        $this->connect_as_my_team_leader(false);
        $_SESSION['original_admin_id'] = $this->id_admin;
        (new Team())->saveTeam('https://normal.example.org', null, null, null, null, null, '1');

        $row = $this->sql->execute("SELECT original_user_id FROM activity WHERE comment LIKE '%normal.example.org%'");
        self::assertNull($row[0]['original_user_id']);
    }
}
