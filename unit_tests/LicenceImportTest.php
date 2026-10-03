<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/Players.php';
require_once __DIR__ . '/../classes/Files.php';

/**
 * Import des licences liguasso (issue #404) : journal d'activité, numéro
 * saisi à la main, club de la licence, noms composés, recherche du joueur.
 *
 * Les licences sont simulées : la lecture du PDF (`Files`) n'est pas en jeu.
 * Joueurs « LITEST… », clubs « LI club … ».
 */
class LicenceImportTest extends UfolepTestCase
{
    private const AFFILIATION_A = '13990001';
    private const AFFILIATION_B = '13990002';
    private int $club_a;
    private int $club_b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->club_a = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'LI club A', affiliation_number = ?",
            array(array('type' => 's', 'value' => self::AFFILIATION_A)));
        $this->club_b = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'LI club B', affiliation_number = ?",
            array(array('type' => 's', 'value' => self::AFFILIATION_B)));
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%LITEST%'");
        $this->sql->execute("DELETE FROM joueurs WHERE nom LIKE 'LITEST%'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'LI club %'");
    }

    private function player(string $prenom, int $id_club, ?string $licence = null, string $nom = 'LITEST'): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO joueurs SET nom = ?, prenom = ?, sexe = 'M', id_club = ?, departement_affiliation = 13, num_licence = ?",
            array(
                array('type' => 's', 'value' => $nom),
                array('type' => 's', 'value' => $prenom),
                array('type' => 'i', 'value' => $id_club),
                array('type' => 's', 'value' => $licence),
            ));
    }

    private function licence(string $last_first_name, string $number, string $affiliation): array
    {
        return array(
            'departement' => '13',
            'licence_number' => $number,
            'last_first_name' => $last_first_name,
            'sexe' => 'M',
            'club' => 'Nom imprimé sur la licence',
            'licence_club' => $affiliation,
            'homologation_date' => '20/09/2026',
            'photo' => null,
        );
    }

    private function row(int $id): array
    {
        return $this->sql->execute("SELECT * FROM joueurs WHERE id = ?", array(array('type' => 'i', 'value' => $id)))[0];
    }

    public function test_le_journal_nomme_le_joueur_importe(): void
    {
        $this->player('Paul', $this->club_a, 'LT0000001');
        $this->connect_as_club_leader($this->club_a);

        (new Players())->search_player_and_save_from_licence($this->licence('LITEST Paul', 'LT0000001', self::AFFILIATION_A));

        $comments = array_column($this->sql->execute(
            "SELECT comment FROM activity WHERE comment LIKE '%date_homologation%' AND comment LIKE '%LITEST%'"), 'comment');
        $this->assertCount(1, $comments);
        $this->assertStringStartsWith('Paul LITEST', $comments[0]);
    }

    public function test_le_numero_saisi_perd_prefixe_et_espaces(): void
    {
        $this->connect_as_admin();
        (new Players())->save(array(
            'prenom' => 'Saisie', 'nom' => 'LITEST', 'sexe' => 'F', 'id_club' => $this->club_a,
            'num_licence' => " 013_LT0000099\t",
        ));
        $stored = $this->sql->execute("SELECT num_licence FROM joueurs WHERE nom = 'LITEST' AND prenom = 'Saisie'");
        $this->assertSame('LT0000099', $stored[0]['num_licence']);
        $this->assertSame('96769993', Players::normalize_licence_number('013_96769993'));
        $this->assertSame('96766617', Players::normalize_licence_number("96766617\t"));
        $this->assertSame('DY10000187', Players::normalize_licence_number('DY10000187'));
    }

    public function test_la_licence_d_un_autre_club_est_refusee_au_responsable(): void
    {
        $this->connect_as_club_leader($this->club_a);
        try {
            (new Players())->search_player_and_save_from_licence(
                $this->licence('LITEST Autre', 'LT0000003', self::AFFILIATION_B));
            $this->fail("La licence d'un autre club doit être refusée");
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
            $this->assertStringContainsString('n° ' . self::AFFILIATION_B, $e->getMessage());
        }
        $this->assertSame(array(), $this->sql->execute("SELECT id FROM joueurs WHERE prenom = 'Autre' AND nom = 'LITEST'"));

        // L'administrateur, lui, importe tout.
        $this->connect_as_admin();
        (new Players())->search_player_and_save_from_licence(
            $this->licence('LITEST Autre', 'LT0000003', self::AFFILIATION_B));
        $created = $this->sql->execute("SELECT id_club FROM joueurs WHERE prenom = 'Autre' AND nom = 'LITEST'");
        $this->assertEquals($this->club_b, $created[0]['id_club']);
    }

    public function test_un_club_sans_numero_d_affiliation_importe_quand_meme(): void
    {
        $sans_numero = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'LI club sans numero'");
        $this->connect_as_club_leader($sans_numero);

        (new Players())->search_player_and_save_from_licence($this->licence('LITEST Inconnu', 'LT0000009', '13999999'));

        $created = $this->sql->execute("SELECT id_club FROM joueurs WHERE prenom = 'Inconnu' AND nom = 'LITEST'");
        $this->assertEquals($sans_numero, $created[0]['id_club'], "rattaché au club du responsable, aucun club créé");
        $this->assertSame(array(), $this->sql->execute("SELECT id FROM clubs WHERE affiliation_number = '13999999'"));
    }

    public function test_un_changement_de_club_suit_la_licence(): void
    {
        $id = $this->player('Transfert', $this->club_b, 'LT0000004');
        $this->connect_as_club_leader($this->club_a);

        (new Players())->search_player_and_save_from_licence(
            $this->licence('LITEST Transfert', 'LT0000004', self::AFFILIATION_A));

        $this->assertEquals($this->club_a, $this->row($id)['id_club']);
    }

    public function test_les_noms_composes(): void
    {
        $this->assertSame(array('VIEIRA DOS SANTOS', 'Lidia'), Players::split_licence_name('VIEIRA DOS SANTOS Lidia'));
        $this->assertSame(array('DUBOIS', 'Yasmina'), Players::split_licence_name('DUBOIS Yasmina'));
        $this->assertSame(array('MARTIN', 'Jean-Marc'), Players::split_licence_name('MARTIN Jean-Marc'));
        $this->assertSame(array("D'ARTAGNAN", 'Charles Henri'), Players::split_licence_name("D'ARTAGNAN Charles Henri"));
        $this->assertSame(array('DUPONT', 'JEAN'), Players::split_licence_name('DUPONT JEAN'));
        $this->assertSame(array('ÉLÉONORE', 'Zoé'), Players::split_licence_name('ÉLÉONORE  Zoé'));
    }

    public function test_licence_et_nom_designant_deux_joueurs_la_licence_l_emporte(): void
    {
        $by_licence = $this->player('Un', $this->club_a, 'LT0000005');
        $by_name = $this->player('Deux', $this->club_a);
        $this->connect_as_club_leader($this->club_a);

        (new Players())->search_player_and_save_from_licence(
            $this->licence('LITEST Deux', 'LT0000005', self::AFFILIATION_A));

        $this->assertSame('2026-09-20', $this->row($by_licence)['date_homologation']);
        $this->assertNull($this->row($by_name)['num_licence']);
    }

    public function test_un_homonyme_deja_licencie_ailleurs_n_est_pas_ecrase(): void
    {
        $homonyme = $this->player('Homo', $this->club_b, 'OLD999');
        $this->connect_as_club_leader($this->club_a);
        try {
            (new Players())->search_player_and_save_from_licence(
                $this->licence('LITEST Homo', 'LT0000006', self::AFFILIATION_A));
            $this->fail("Un homonyme licencié dans un autre club ne doit pas être écrasé");
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
            $this->assertStringContainsString('homonyme', $e->getMessage());
        }
        $this->assertSame('OLD999', $this->row($homonyme)['num_licence']);
    }

    public function test_une_licence_ecartee_n_arrete_pas_le_fichier(): void
    {
        $this->connect_as_club_leader($this->club_a);
        $players = new Players();
        $licences = array(
            $this->licence('LITEST Ecartee', 'LT0000007', self::AFFILIATION_B),
            $this->licence('LITEST Importee', 'LT0000008', self::AFFILIATION_A),
        );
        // Le PDF est remplacé par une lecture simulée.
        $files = new class($licences) extends Files {
            public function __construct(private array $licences)
            {
                parent::__construct();
            }

            public function get_licences_data(string $input_pdf_path): array
            {
                return $this->licences;
            }
        };
        $property = new ReflectionProperty(Players::class, 'files');
        $property->setAccessible(true);
        $property->setValue($players, $files);
        $_FILES['licences'] = array('name' => 'licences.pdf', 'tmp_name' => '/tmp/licences.pdf');

        try {
            $result = $players->update_from_licence_file();
        } finally {
            unset($_FILES['licences']);
        }

        // Compte rendu licence par licence (#394).
        $this->assertSame('1 licence(s) importée(s), 1 écartée(s)', $result['message']);
        $this->assertCount(2, $result['report']);
        $this->assertSame('LITEST Ecartee', $result['report'][0]['joueur']);
        $this->assertSame('rejected', $result['report'][0]['status']);
        $this->assertStringContainsString("n'est pas le vôtre", $result['report'][0]['message']);
        $this->assertSame(array('joueur' => 'LITEST Importee', 'status' => 'created', 'photo' => false),
            $result['report'][1]);
        $this->assertCount(1, $this->sql->execute("SELECT id FROM joueurs WHERE prenom = 'Importee' AND nom = 'LITEST'"));
    }

    public function test_un_admin_aussi_responsable_d_equipe_met_a_jour_sans_erreur(): void
    {
        // Le 03/10/2026, l'import d'une licence par un compte admin ET
        // responsable d'équipe finissait en « Erreur durant l'ajout du joueur à
        // l'équipe », après avoir pourtant mis la fiche à jour.
        $id = $this->player('Valero', $this->club_a, 'LT0000010');
        $this->connect_as_admin();
        $_SESSION['is_team_leader'] = true;
        $_SESSION['id_equipe'] = 1;

        $result = (new Players())->search_player_and_save_from_licence(
            $this->licence('LITEST Valero', 'LT0000010', self::AFFILIATION_A));

        $this->assertSame('updated', $result['status']);
        $this->assertSame('2026-09-20', $this->row($id)['date_homologation']);
        $this->assertSame(array(), $this->sql->execute(
            "SELECT 1 FROM joueur_equipe WHERE id_joueur = ?", array(array('type' => 'i', 'value' => $id))),
            "l'administrateur n'ajoute pas le joueur à son équipe");
    }

    public function test_un_fichier_sans_licence_reconnue_est_refuse(): void
    {
        $this->connect_as_club_leader($this->club_a);
        $players = new Players();
        $files = new class extends Files {
            public function get_licences_data(string $input_pdf_path): array
            {
                throw new Exception('Unable to parse PDF');
            }
        };
        $property = new ReflectionProperty(Players::class, 'files');
        $property->setAccessible(true);
        $property->setValue($players, $files);
        $_FILES['licences'] = array('name' => 'photo.jpg', 'tmp_name' => '/tmp/photo.jpg');

        try {
            $players->update_from_licence_file();
            $this->fail("Un fichier illisible doit être refusé");
        } catch (Exception $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertStringContainsString('Aucune licence reconnue', $e->getMessage());
            $this->assertStringNotContainsString('Unable to parse', $e->getMessage(), "pas de message technique au club");
        } finally {
            unset($_FILES['licences']);
        }
    }
}
