<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Club.php';
require_once __DIR__ . '/../classes/Rank.php';

/**
 * Issue #377 — `dirtyFields`, héritage d'ExtJS, est supprimé.
 *
 * - L'écran « Divisions / poules » enregistre à nouveau : `Rank::saveRank`
 *   exigeait un `$dirtyFields` que plus aucun formulaire n'envoyait.
 * - Le journal d'activité retrouve le détail des champs modifiés, calculé par
 *   le serveur (ligne d'avant contre valeurs écrites) au lieu d'être déclaré
 *   par le client.
 */
class ActivityChangesTest extends UfolepTestCase
{
    private ?int $id_club = null;
    private ?int $id_team = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->connect_as_admin();
        $this->id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'issue377 club', affiliation_number = '013377'");
        $this->id_team = (int)$this->sql->execute(
            "INSERT INTO equipes SET code_competition = 'm', nom_equipe = 'issue377 team', id_club = $this->id_club");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM classements WHERE division = '77' AND code_competition = 'm'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe = 'issue377 team'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'issue377 club%'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%issue377 club%'");
    }

    private function activities(): array
    {
        return array_column($this->sql->execute(
            "SELECT comment FROM activity WHERE comment LIKE '%issue377 club%' ORDER BY id"), 'comment');
    }

    public function test_une_modification_liste_les_champs_reellement_changes(): void
    {
        (new Club())->saveClub($this->id_club, 'issue377 club renomme', '013377');

        $this->assertSame(array("Club issue377 club renomme : <br/>- nom : issue377 club → issue377 club renomme"),
            $this->activities(), 'seul le nom a changé : le numéro d\'affiliation n\'apparaît pas');
    }

    public function test_un_enregistrement_sans_changement_n_ecrit_rien(): void
    {
        (new Club())->saveClub($this->id_club, 'issue377 club', '013377');
        $this->assertSame(array(), $this->activities());
    }

    public function test_une_creation_est_tracee_comme_telle(): void
    {
        (new Club())->saveClub(null, 'issue377 club neuf', '013378');
        $this->assertSame(array('Création : Club issue377 club neuf'), $this->activities());
    }

    public function test_divisions_et_poules_enregistre_sans_dirtyFields(): void
    {
        // Exactement ce que poste l'écran Ranks.js : ses cinq champs et l'id.
        $id = (new Rank())->saveRank(
            id: null,
            code_competition: 'm',
            division: '77',
            id_equipe: $this->id_team,
            rank_start: 3,
            will_register_again: 1);
        $this->assertNotEmpty($id);
        $rows = $this->sql->execute("SELECT rank_start FROM classements WHERE division = '77' AND id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->id_team)));
        $this->assertSame(3, (int)$rows[0]['rank_start']);
    }
}
