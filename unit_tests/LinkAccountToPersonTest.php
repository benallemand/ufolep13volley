<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/UserManager.php';

/**
 * Issue #331 — rattacher à la main une personne à un compte, là où
 * l'automatisme (#326) refuse de trancher.
 */
class LinkAccountToPersonTest extends UfolepTestCase
{
    private int $id_club;
    private int $account;
    private int $other_account;
    private array $person = array();

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'issue331 club'");
        $this->account = (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'issue331_club', email = 'issue331.club@ufolep.test', password_hash = 'x'");
        $this->other_account = (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'issue331_other', email = 'issue331.other@ufolep.test', password_hash = 'x'");
        $this->sql->execute("INSERT INTO users_clubs SET user_id = $this->account, club_id = $this->id_club");
        // Deux personnes du club portent l'email générique du compte : le cas
        // ambigu que l'automatisme laisse de côté. La troisième est ailleurs.
        foreach (array('A' => $this->id_club, 'B' => $this->id_club, 'C' => null) as $k => $club) {
            $this->person[$k] = (int)$this->sql->execute(
                "INSERT INTO joueurs SET nom = 'ISSUE331', prenom = ?, sexe = 'F', email = ?, id_club = ?",
                array(
                    array('type' => 's', 'value' => "Personne$k"),
                    array('type' => 's', 'value' => $k === 'C' ? 'issue331.autre@ufolep.test' : 'issue331.club@ufolep.test'),
                    array('type' => 'i', 'value' => $club),
                ));
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
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'ISSUE331'");
        $this->sql->execute("DELETE FROM users_clubs WHERE user_id IN (SELECT id FROM comptes_acces WHERE login LIKE 'issue331_%')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login LIKE 'issue331_%'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'issue331 club'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE 'Compte issue331_%'");
    }

    /** @return array<string, ?int> personne => compte lié */
    private function links(): array
    {
        $links = array();
        foreach ($this->person as $k => $id) {
            $row = $this->sql->execute("SELECT id_compte FROM joueurs WHERE id = $id")[0];
            $links[$k] = $row['id_compte'] === null ? null : (int)$row['id_compte'];
        }
        return $links;
    }

    public function test_rattacher_une_personne_au_choix_parmi_les_ambigues(): void
    {
        (new UserManager())->linkAccountToPerson($this->account, $this->person['B']);
        $this->assertSame(array('A' => null, 'B' => $this->account, 'C' => null), $this->links());

        $users = (new UserManager())->getUsers();
        $mine = array_values(array_filter($users, fn($u) => (int)$u['id'] === $this->account))[0];
        $this->assertSame('ISSUE331 PersonneB', $mine['person_name'], 'la colonne « Personne » des comptes se remplit');
    }

    public function test_changer_de_personne_detache_la_precedente(): void
    {
        $users = new UserManager();
        $users->linkAccountToPerson($this->account, $this->person['A']);
        $users->linkAccountToPerson($this->account, $this->person['C']);
        $this->assertSame(array('A' => null, 'B' => null, 'C' => $this->account), $this->links(),
            'une seule personne par compte, et une personne sans email correspondant est acceptée');
    }

    public function test_une_personne_liee_a_un_autre_compte_perd_cet_ancien_lien(): void
    {
        $users = new UserManager();
        $users->linkAccountToPerson($this->other_account, $this->person['A']);
        $users->linkAccountToPerson($this->account, $this->person['A']);
        $this->assertSame(array('A' => $this->account, 'B' => null, 'C' => null), $this->links(),
            'jamais deux liens : le compte précédent de la personne la perd');
    }

    public function test_detacher_remet_id_compte_a_null_sans_toucher_au_reste(): void
    {
        $users = new UserManager();
        $users->linkAccountToPerson($this->account, $this->person['A']);
        $users->linkAccountToPerson($this->account, '');
        $this->assertSame(array('A' => null, 'B' => null, 'C' => null), $this->links());
        $this->assertCount(1, $this->sql->execute("SELECT id FROM joueurs WHERE id = ?",
            array(array('type' => 'i', 'value' => $this->person['A']))), 'la personne existe toujours');
        $this->assertCount(1, $this->sql->execute("SELECT id FROM comptes_acces WHERE id = $this->account"),
            'le compte existe toujours');
        $this->assertCount(2, $this->sql->execute("SELECT id FROM activity WHERE comment LIKE 'Compte issue331_club :%'"),
            'rattachement et détachement journalisés');
    }

    public function test_reserve_a_l_admin_et_identifiants_verifies(): void
    {
        $this->connect_as_club_leader($this->id_club);
        foreach (array(fn() => (new UserManager())->linkAccountToPerson($this->account, $this->person['A']),
                     fn() => (new UserManager())->getPersonCandidates($this->account)) as $call) {
            try {
                $call();
                $this->fail('réservé à l\'admin');
            } catch (Exception $e) {
                $this->assertSame(403, $e->getCode());
            }
        }
        $this->connect_as_admin();
        foreach (array(array(999999, $this->person['A']), array($this->account, 999999999)) as [$user, $player]) {
            try {
                (new UserManager())->linkAccountToPerson($user, $player);
                $this->fail('identifiant inconnu');
            } catch (Exception $e) {
                $this->assertSame(404, $e->getCode(), $e->getMessage());
            }
        }
        $this->assertSame(array('A' => null, 'B' => null, 'C' => null), $this->links());
    }

    public function test_les_personnes_du_club_et_de_l_email_sont_proposees_en_tete(): void
    {
        (new UserManager())->linkAccountToPerson($this->other_account, $this->person['C']);
        $candidates = (new UserManager())->getPersonCandidates($this->account);
        $mine = array_values(array_filter($candidates, fn($c) => str_starts_with($c['name'], 'ISSUE331')));
        $by_name = array_column($mine, null, 'name');

        $this->assertSame(1, (int)$by_name['ISSUE331 PersonneA']['suggested']);
        $this->assertSame(1, (int)$by_name['ISSUE331 PersonneB']['suggested']);
        $this->assertSame(0, (int)$by_name['ISSUE331 PersonneC']['suggested']);
        $this->assertSame('issue331_other', $by_name['ISSUE331 PersonneC']['linked_login'],
            'le sélecteur dit à quel compte la personne est déjà liée');
        $first_other = array_search(0, array_map('intval', array_column($candidates, 'suggested')), true);
        $last_suggested = max(array_keys(array_filter(array_column($candidates, 'suggested'), fn($s) => (int)$s === 1)));
        $this->assertLessThan($first_other, $last_suggested, 'les personnes suggérées viennent d\'abord');
    }
}
