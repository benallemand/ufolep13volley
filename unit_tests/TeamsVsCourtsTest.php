<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';

/**
 * Indicateur d'alerte « Inscriptions - Terrains vs Equipes » : seuls les clubs
 * qui inscrivent plus d'équipes que leurs terrains n'en reçoivent
 * (créneaux distincts × terrains × 2), demandes refusées exclues.
 *
 * Données dans une compétition de test « tv ».
 */
class TeamsVsCourtsTest extends UfolepTestCase
{
    private int $id_competition;
    private int $id_court;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->id_competition = (int)$this->sql->execute(
            "INSERT INTO competitions SET code_competition = 'tv', libelle = 'teams vs courts', id_compet_maitre = 'tv'");
        $this->id_court = (int)$this->sql->execute("INSERT INTO gymnase SET nom = 'tv gymnase', nb_terrain = 1");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM register WHERE new_team_name LIKE 'TV Team%'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'tv gymnase'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'tv'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'tv club %'");
    }

    /** Un club et ses demandes, toutes sur le même créneau du gymnase de test. */
    private function club(string $name, array $statuses): void
    {
        $id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = ?", array(array('type' => 's', 'value' => $name)));
        foreach ($statuses as $i => $status) {
            $this->sql->execute(
                "INSERT INTO register SET new_team_name = ?, id_club = ?, id_competition = ?,
                     leader_name = 'TVLEADER', leader_first_name = 'Test', leader_email = 'tv_leader@ufolep.test',
                     leader_phone = '0600000000', status = ?,
                     id_court_1 = ?, day_court_1 = 'Lundi', hour_court_1 = '20:00'",
                array(
                    array('type' => 's', 'value' => "TV Team $name $i"),
                    array('type' => 'i', 'value' => $id_club),
                    array('type' => 'i', 'value' => $this->id_competition),
                    array('type' => 's', 'value' => $status),
                    array('type' => 'i', 'value' => $this->id_court),
                ));
        }
    }

    /** @return array<string, array> lignes de l'indicateur pour les clubs de test, par nom */
    private function alerts(): array
    {
        $rows = $this->sql->execute(file_get_contents(__DIR__ . '/../sql/indicator-teams-vs-courts.sql'));
        $result = array();
        foreach ($rows as $row) {
            if (str_starts_with($row['club_nom'], 'tv club ')) {
                $result[$row['club_nom']] = $row;
            }
        }
        return $result;
    }

    public function test_seuls_les_clubs_en_depassement_sont_signales(): void
    {
        // Un créneau × un terrain × 2 = 2 équipes au plus.
        $this->club('tv club trop', array('PENDING', 'VALIDATED', 'PENDING'));
        $this->club('tv club juste', array('PENDING', 'VALIDATED'));

        $alerts = $this->alerts();
        $this->assertSame(array('tv club trop'), array_keys($alerts));
        $this->assertEquals(3, $alerts['tv club trop']['nombre_equipes_inscrites']);
        $this->assertEquals(2, $alerts['tv club trop']['nombre_max_equipes_autorisees']);
        $this->assertEquals(1, $alerts['tv club trop']['equipes_en_trop']);
    }

    public function test_une_demande_refusee_ne_compte_pas(): void
    {
        // ES Roquevaire en 2026 : ses deux équipes volantes refusées le
        // faisaient passer de 4 à 6 équipes pour 4 places.
        $this->club('tv club refus', array('PENDING', 'PENDING', 'REFUSED'));

        $this->assertSame(array(), $this->alerts());
    }
}
