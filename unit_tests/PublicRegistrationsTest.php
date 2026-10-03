<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Register.php';

/**
 * Issue #379 — liste publique des inscriptions en page d'accueil.
 *
 * Comme `LateClubsTest`, le test règle lui-même la fenêtre d'inscription du
 * championnat masculin (ouverture, clôture, démarrage) et la restaure dans son
 * `tearDown` : la liste est datée par construction.
 */
class PublicRegistrationsTest extends UfolepTestCase
{
    private array $saved_window = array();
    private int $id_competition;
    private int $id_club;
    private array $team = array();

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->saved_window = $this->sql->execute(
            "SELECT id, start_register_date, limit_register_date, start_date FROM competitions WHERE code_competition = 'm'")[0];
        $this->id_competition = (int)$this->saved_window['id'];
        $this->set_window('CURDATE() - INTERVAL 10 DAY', 'CURDATE() + INTERVAL 10 DAY', 'CURDATE() + INTERVAL 30 DAY');

        $this->id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'issue379 club'");
        foreach (array('A', 'B', 'C') as $k) {
            $this->team[$k] = (int)$this->sql->execute(
                "INSERT INTO equipes SET code_competition = 'm', nom_equipe = ?, id_club = ?",
                array(array('type' => 's', 'value' => "issue379 team $k"), array('type' => 'i', 'value' => $this->id_club)));
            $this->sql->execute("INSERT INTO classements SET code_competition = 'm', division = '76', id_equipe = ?",
                array(array('type' => 'i', 'value' => $this->team[$k])));
        }
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        $this->sql->execute(
            "UPDATE competitions SET start_register_date = ?, limit_register_date = ?, start_date = ? WHERE id = ?",
            array(
                array('type' => 's', 'value' => $this->saved_window['start_register_date']),
                array('type' => 's', 'value' => $this->saved_window['limit_register_date']),
                array('type' => 's', 'value' => $this->saved_window['start_date']),
                array('type' => 'i', 'value' => $this->id_competition),
            ));
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM register WHERE new_team_name LIKE 'issue379%'");
        $this->sql->execute("DELETE FROM classements WHERE division = '76' AND code_competition = 'm'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue379%'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'issue379 club'");
    }

    private function set_window(string $open, string $close, ?string $start): void
    {
        $this->sql->execute("UPDATE competitions SET start_register_date = $open, limit_register_date = $close,
                                    start_date = " . ($start ?? 'NULL') . " WHERE id = $this->id_competition");
    }

    private function register(string $name, string $status, ?int $old_team_id = null, string $created = 'NOW()'): void
    {
        $this->sql->execute(
            "INSERT INTO register SET new_team_name = ?, id_club = ?, id_competition = ?, old_team_id = ?,
                 leader_name = 'SECRETNAME', leader_first_name = 'Secret', leader_email = 'issue379.secret@ufolep.test',
                 leader_phone = '0699999999', remarks = 'remarque privee issue379', status = ?,
                 refusal_reason = IF(? = 'REFUSED', 'motif prive issue379', NULL), creation_date = $created",
            array(
                array('type' => 's', 'value' => $name),
                array('type' => 'i', 'value' => $this->id_club),
                array('type' => 'i', 'value' => $this->id_competition),
                array('type' => 'i', 'value' => $old_team_id),
                array('type' => 's', 'value' => $status),
                array('type' => 's', 'value' => $status),
            ));
    }

    /** @return array<string, array> équipes de test de la liste publique, par nom */
    private function listed(): array
    {
        $result = array();
        foreach ((new Register())->getPublicRegistrations() as $competition) {
            if ($competition['code_competition'] !== 'm') {
                continue;
            }
            foreach ($competition['teams'] as $team) {
                if (str_starts_with((string)$team['equipe'], 'issue379')) {
                    $result[$team['equipe']] = $team;
                }
            }
        }
        return $result;
    }

    private function masculine_is_listed(): bool
    {
        return in_array('m', array_column((new Register())->getPublicRegistrations(), 'code_competition'), true);
    }

    public function test_statuts_types_et_equipes_non_reinscrites(): void
    {
        $this->register('issue379 new', 'PENDING');
        $this->register('issue379 team A', 'VALIDATED', $this->team['A']);
        $this->register('issue379 renamed', 'REFUSED', $this->team['B']);

        $listed = $this->listed();
        ksort($listed);
        $this->assertSame(array(
            'issue379 new' => array('club' => 'issue379 club', 'equipe' => 'issue379 new', 'status' => 'PENDING',
                'type' => 'new', 'ancien_nom' => null),
            'issue379 renamed' => array('club' => 'issue379 club', 'equipe' => 'issue379 renamed', 'status' => 'REFUSED',
                'type' => 'renewal', 'ancien_nom' => 'issue379 team B'),
            'issue379 team A' => array('club' => 'issue379 club', 'equipe' => 'issue379 team A', 'status' => 'VALIDATED',
                'type' => 'renewal', 'ancien_nom' => null),
            'issue379 team C' => array('club' => 'issue379 club', 'equipe' => 'issue379 team C', 'status' => 'NOT_REGISTERED',
                'type' => null, 'ancien_nom' => null),
        ), $listed, 'C, classée sans demande, est « pas réinscrite » ; A et B, réengagées, ne le sont pas');
    }

    public function test_une_nouvelle_equipe_placee_en_division_n_est_pas_listee_deux_fois(): void
    {
        // Préparation des divisions : la nouvelle équipe est créée et mise au
        // classement, sans `old_team_id` dans sa demande.
        $this->register('issue379 placee', 'PENDING');
        $placed = (int)$this->sql->execute(
            "INSERT INTO equipes SET code_competition = 'm', nom_equipe = 'issue379 placee', id_club = ?",
            array(array('type' => 'i', 'value' => $this->id_club)));
        $this->sql->execute("INSERT INTO classements SET code_competition = 'm', division = '76', id_equipe = ?",
            array(array('type' => 'i', 'value' => $placed)));

        $rows = array();
        foreach ((new Register())->getPublicRegistrations() as $competition) {
            foreach ($competition['teams'] as $team) {
                if ($competition['code_competition'] === 'm' && $team['equipe'] === 'issue379 placee') {
                    $rows[] = $team;
                }
            }
        }
        $this->assertCount(1, $rows, 'Listée une seule fois, pas aussi en « pas réinscrite »');
        $this->assertSame('new', $rows[0]['type']);
        $this->assertSame('PENDING', $rows[0]['status']);
    }

    public function test_rien_sur_les_personnes_ne_sort(): void
    {
        $this->register('issue379 new', 'PENDING');
        $this->register('issue379 renamed', 'REFUSED', $this->team['B']);
        $result = (new Register())->getPublicRegistrations();

        foreach ($result as $competition) {
            $competition_keys = array_keys($competition);
            sort($competition_keys);
            $this->assertSame(array('code_competition', 'libelle', 'limit_register_date', 'teams'), $competition_keys);
            foreach ($competition['teams'] as $team) {
                $keys = array_keys($team);
                sort($keys);
                $expected = Register::PUBLIC_REGISTRATION_FIELDS;
                sort($expected);
                $this->assertSame($expected, $keys);
            }
        }
        $json = json_encode($result);
        foreach (array('SECRETNAME', 'issue379.secret', '0699999999', 'remarque privee', 'motif prive') as $secret) {
            $this->assertStringNotContainsString($secret, $json, "« $secret » ne doit pas être public");
        }
    }

    public function test_une_demande_d_avant_l_ouverture_ne_compte_pas(): void
    {
        // Ligne d'une saison précédente, restée en base : l'équipe n'est pas
        // réinscrite pour autant.
        $this->register('issue379 old season', 'VALIDATED', $this->team['A'], 'CURDATE() - INTERVAL 200 DAY');
        $listed = $this->listed();
        $this->assertArrayNotHasKey('issue379 old season', $listed);
        $this->assertSame('NOT_REGISTERED', $listed['issue379 team A']['status']);
    }

    public function test_visible_de_l_ouverture_des_inscriptions_au_demarrage(): void
    {
        $this->assertTrue($this->masculine_is_listed(), 'fenêtre ouverte, démarrage à venir');

        $this->set_window('CURDATE() - INTERVAL 10 DAY', 'CURDATE() + INTERVAL 10 DAY', 'CURDATE() - INTERVAL 200 DAY');
        $this->assertTrue($this->masculine_is_listed(),
            'démarrage antérieur à l\'ouverture : c\'est celui de la saison passée, la liste reste');

        $this->set_window('CURDATE() - INTERVAL 10 DAY', 'CURDATE() + INTERVAL 10 DAY', null);
        $this->assertTrue($this->masculine_is_listed(), 'pas encore de date de démarrage');

        $this->set_window('CURDATE() - INTERVAL 30 DAY', 'CURDATE() - INTERVAL 20 DAY', 'CURDATE() - INTERVAL 1 DAY');
        $this->assertFalse($this->masculine_is_listed(), 'la compétition a démarré');

        $this->set_window('CURDATE() + INTERVAL 5 DAY', 'CURDATE() + INTERVAL 20 DAY', 'CURDATE() + INTERVAL 30 DAY');
        $this->assertFalse($this->masculine_is_listed(), 'les inscriptions ne sont pas encore ouvertes');
    }
}
