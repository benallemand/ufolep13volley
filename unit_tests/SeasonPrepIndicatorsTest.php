<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';

/**
 * Indicateurs de préparation de saison (issue #395) : décalage des créneaux,
 * équipes non réengagées, équipes incomplètes, inscriptions incomplètes,
 * factures, et la vue `club_contacts_view` qu'ils partagent.
 *
 * Les données vivent dans une compétition de test « ip », sauf pour « Equipes
 * non réengagées », qui ne regarde que les championnats : celle-là passe par
 * le championnat féminin, dont la fenêtre d'inscription est ouverte le temps
 * du test puis restaurée.
 */
class SeasonPrepIndicatorsTest extends UfolepTestCase
{
    private int $id_competition;
    private int $id_competition_f;
    private ?string $start_register_f;
    private int $id_club;
    private int $id_club_sans_compte;
    private int $id_gymnase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->id_competition = (int)$this->sql->execute(
            "INSERT INTO competitions SET code_competition = 'ip', libelle = 'Championnat IP test',
                                          id_compet_maitre = 'ip',
                                          start_register_date = CURRENT_DATE - INTERVAL 30 DAY,
                                          limit_register_date = CURRENT_DATE - INTERVAL 1 DAY");
        $competition_f = $this->sql->execute("SELECT id, start_register_date FROM competitions WHERE code_competition = 'f'");
        $this->id_competition_f = (int)$competition_f[0]['id'];
        $this->start_register_f = $competition_f[0]['start_register_date'];
        $this->sql->execute("UPDATE competitions SET start_register_date = CURRENT_DATE - INTERVAL 30 DAY WHERE code_competition = 'f'");

        $this->id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'IP club'");
        $id_compte = (int)$this->sql->execute("INSERT INTO comptes_acces SET login = 'ip_club', email = 'ip_club@ufolep.test'");
        $this->sql->execute("INSERT INTO users_clubs SET user_id = ?, club_id = ?",
            array(array('type' => 'i', 'value' => $id_compte), array('type' => 'i', 'value' => $this->id_club)));
        $this->id_club_sans_compte = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'IP club sans compte'");
        $this->id_gymnase = (int)$this->sql->execute("INSERT INTO gymnase SET nom = 'IP gymnase', ville = 'Ipville', nb_terrain = 1");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        if ($this->start_register_f === null) {
            $this->sql->execute("UPDATE competitions SET start_register_date = NULL WHERE code_competition = 'f'");
        } else {
            $this->sql->execute("UPDATE competitions SET start_register_date = ? WHERE code_competition = 'f'",
                array(array('type' => 's', 'value' => $this->start_register_f)));
        }
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM register WHERE new_team_name LIKE 'IP %'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'IP %')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'IPTEST'");
        $this->sql->execute("DELETE FROM classements WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'IP %')");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'IP %'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'IP gymnase'");
        $this->sql->execute("DELETE FROM users_clubs WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = 'ip_club')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login = 'ip_club'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'IP club%'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'ip'");
    }

    private function team(string $name, string $competition = 'ip', ?int $id_club = null): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = ?, code_competition = ?, id_club = ?",
            array(
                array('type' => 's', 'value' => $name),
                array('type' => 's', 'value' => $competition),
                array('type' => 'i', 'value' => $id_club ?? $this->id_club),
            ));
    }

    /** Créneau en place : jour et heure, dans le gymnase de test. */
    private function slot(int $id_team, string $day, int $priority): void
    {
        $this->sql->execute(
            "INSERT INTO creneau SET id_gymnase = ?, jour = ?, heure = '20:00', id_equipe = ?, usage_priority = ?",
            array(
                array('type' => 'i', 'value' => $this->id_gymnase),
                array('type' => 's', 'value' => $day),
                array('type' => 'i', 'value' => $id_team),
                array('type' => 'i', 'value' => $priority),
            ));
    }

    /**
     * Demande d'inscription. `$days` : jours des créneaux demandés, à 20:00
     * dans le gymnase de test ; un tableau vide = aucun créneau.
     */
    private function registration(string $name, array $days, ?int $old_team_id = null,
                                  string $status = 'VALIDATED', ?int $id_competition = null): void
    {
        $this->sql->execute(
            "INSERT INTO register SET new_team_name = ?, id_club = ?, id_competition = ?, old_team_id = ?,
                 leader_name = 'IPLEADER', leader_first_name = 'Test', leader_email = 'ip_leader@ufolep.test',
                 leader_phone = '0600000000', status = ?,
                 id_court_1 = ?, day_court_1 = ?, hour_court_1 = ?,
                 id_court_2 = ?, day_court_2 = ?, hour_court_2 = ?",
            array(
                array('type' => 's', 'value' => $name),
                array('type' => 'i', 'value' => $this->id_club),
                array('type' => 'i', 'value' => $id_competition ?? $this->id_competition),
                array('type' => 'i', 'value' => $old_team_id),
                array('type' => 's', 'value' => $status),
                array('type' => 'i', 'value' => isset($days[0]) ? $this->id_gymnase : null),
                array('type' => 's', 'value' => $days[0] ?? null),
                array('type' => 's', 'value' => isset($days[0]) ? '20:00' : null),
                array('type' => 'i', 'value' => isset($days[1]) ? $this->id_gymnase : null),
                array('type' => 's', 'value' => $days[1] ?? null),
                array('type' => 's', 'value' => isset($days[1]) ? '20:00' : null),
            ));
    }

    /** @return array<string, array> lignes du fichier `sql/$file` dont `$column` commence par « IP », indexées par elle */
    private function rows(string $file, string $column): array
    {
        $result = array();
        foreach ($this->sql->execute(file_get_contents(__DIR__ . '/../sql/' . $file)) as $row) {
            if (str_starts_with((string)$row[$column], 'IP ')) {
                $result[$row[$column]] = $row;
            }
        }
        ksort($result);
        return $result;
    }

    public function test_decalage_des_creneaux(): void
    {
        $modifie = $this->team('IP Modifie');
        $this->slot($modifie, 'Lundi', 1);
        $this->registration('IP Modifie', array('Mardi'), $modifie);

        $inverse = $this->team('IP Inverse');
        $this->slot($inverse, 'Lundi', 1);
        $this->slot($inverse, 'Jeudi', 2);
        $this->registration('IP Inverse', array('Jeudi', 'Lundi'), $inverse);

        $identique = $this->team('IP Identique');
        $this->slot($identique, 'Lundi', 1);
        $this->registration('IP Identique', array('Lundi'), $identique);

        // Le même créneau saisi deux fois n'en fait qu'un.
        $doublon = $this->team('IP Doublon');
        $this->slot($doublon, 'Mercredi', 1);
        $this->registration('IP Doublon', array('Mercredi', 'Mercredi'), $doublon);

        // Nouvelle équipe, pas encore créée.
        $this->registration('IP Nouvelle', array('Vendredi'));

        // Une demande refusée n'est pas à régler.
        $refusee = $this->team('IP Refusee');
        $this->slot($refusee, 'Lundi', 1);
        $this->registration('IP Refusee', array('Mardi'), $refusee, 'REFUSED');

        $rows = $this->rows('mismatch_register_timeslots.sql', 'equipe');

        $this->assertSame(array('IP Inverse', 'IP Modifie', 'IP Nouvelle'), array_keys($rows));
        $this->assertSame('créneau modifié', $rows['IP Modifie']['ecart']);
        $this->assertSame('IP gymnase (Ipville) Mardi 20:00', $rows['IP Modifie']['demande']);
        $this->assertSame('IP gymnase (Ipville) Lundi 20:00', $rows['IP Modifie']['en_place']);
        $this->assertSame('ip_club@ufolep.test', $rows['IP Modifie']['contact_club']);
        $this->assertSame('ip_leader@ufolep.test', $rows['IP Modifie']['responsable_equipe']);
        $this->assertSame('ordre de préférence inversé', $rows['IP Inverse']['ecart']);
        $this->assertSame('aucun créneau en place', $rows['IP Nouvelle']['ecart']);
        $this->assertSame('aucun', $rows['IP Nouvelle']['en_place']);
    }

    public function test_une_reinscription_ne_designe_que_son_ancienne_equipe(): void
    {
        // Homonyme dans la compétition (#390) : seule l'équipe désignée par
        // `old_team_id` compte, quel que soit le créneau de l'autre.
        $ancienne = $this->team('IP Homonyme');
        $this->slot($ancienne, 'Lundi', 1);
        $autre = $this->team('IP Homonyme');
        $this->slot($autre, 'Jeudi', 1);
        $this->registration('IP Homonyme', array('Lundi'), $ancienne);

        $this->assertSame(array(), $this->rows('mismatch_register_timeslots.sql', 'equipe'));
    }

    public function test_contact_du_club_sans_compte_repli_sur_les_responsables(): void
    {
        $team = $this->team('IP Equipe sans compte', 'ip', $this->id_club_sans_compte);
        $id_player = (int)$this->sql->execute(
            "INSERT INTO joueurs SET nom = 'IPTEST', prenom = 'Resp', sexe = 'F', id_club = ?, email = 'ip_resp@ufolep.test'",
            array(array('type' => 'i', 'value' => $this->id_club_sans_compte)));
        $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?, is_leader = 1",
            array(array('type' => 'i', 'value' => $id_player), array('type' => 'i', 'value' => $team)));

        $contacts = array();
        foreach ($this->sql->execute("SELECT id_club, contact FROM club_contacts_view WHERE id_club IN (?, ?)", array(
            array('type' => 'i', 'value' => $this->id_club),
            array('type' => 'i', 'value' => $this->id_club_sans_compte),
        )) as $row) {
            $contacts[(int)$row['id_club']] = $row['contact'];
        }
        $this->assertSame('ip_club@ufolep.test', $contacts[$this->id_club]);
        $this->assertSame('ip_resp@ufolep.test', $contacts[$this->id_club_sans_compte]);
    }

    public function test_equipes_non_reengagees(): void
    {
        // Nouvelle équipe créée depuis son inscription, déjà placée : elle est
        // inscrite, même sans `old_team_id` (Aix5 en 2026).
        $nouvelle = $this->team('IP Nouvelle F', 'f');
        $this->sql->execute("INSERT INTO classements SET code_competition = 'f', division = '5', id_equipe = ?, rank_start = 1",
            array(array('type' => 'i', 'value' => $nouvelle)));
        $this->registration('IP Nouvelle F', array('Lundi'), null, 'VALIDATED', $this->id_competition_f);

        $partie = $this->team('IP Partie F', 'f');
        $this->sql->execute("INSERT INTO classements SET code_competition = 'f', division = '5', id_equipe = ?, rank_start = 2",
            array(array('type' => 'i', 'value' => $partie)));

        $refusee = $this->team('IP Refusee F', 'f');
        $this->sql->execute("INSERT INTO classements SET code_competition = 'f', division = '5', id_equipe = ?, rank_start = 3",
            array(array('type' => 'i', 'value' => $refusee)));
        $this->registration('IP Refusee F', array('Lundi'), $refusee, 'REFUSED', $this->id_competition_f);

        $rows = $this->rows('not_registered_teams.sql', 'ancien_nom');

        $this->assertSame(array('IP Partie F', 'IP Refusee F'), array_keys($rows));
        $this->assertSame('ip_club@ufolep.test', $rows['IP Partie F']['contact_club']);
        $this->assertEquals('5', $rows['IP Partie F']['division']);
    }

    public function test_une_equipe_sans_compte_ni_joueur_est_incomplete(): void
    {
        $team = $this->team('IP Vide');
        $this->sql->execute("INSERT INTO classements SET code_competition = 'ip', division = '1', id_equipe = ?, rank_start = 1",
            array(array('type' => 'i', 'value' => $team)));

        $rows = $this->rows('teams_incomplete.sql', 'equipe');

        $this->assertSame(array('IP Vide'), array_keys($rows));
        $this->assertSame('ip_club@ufolep.test', $rows['IP Vide']['contact_email']);
        $this->assertEquals(0, $rows['IP Vide']['reponsable_ok']);
    }

    public function test_inscriptions_incompletes(): void
    {
        $this->registration('IP Sans creneau', array());
        $this->registration('IP Volante refusee', array(), null, 'REFUSED');
        $this->registration('IP Complete', array('Lundi'));
        $this->sql->execute("UPDATE register SET hour_court_1 = NULL WHERE new_team_name = 'IP Complete'");
        $this->registration('IP Correcte', array('Lundi', 'Jeudi'), null, 'PENDING');

        $rows = $this->rows('indicator-register-incomplete-teams.sql', 'equipe');

        $this->assertSame(array('IP Complete', 'IP Sans creneau'), array_keys($rows));
        $this->assertSame('aucun créneau', $rows['IP Sans creneau']['probleme']);
        $this->assertSame('créneau 1 sans jour ou sans heure', $rows['IP Complete']['probleme']);
        $this->assertSame('validée', $rows['IP Sans creneau']['statut']);
        $this->assertSame('ip_club@ufolep.test', $rows['IP Sans creneau']['contact_club']);
    }

    public function test_une_demande_refusee_n_est_pas_facturee(): void
    {
        $this->registration('IP Facturee', array('Lundi'));
        $this->registration('IP Refusee', array(), null, 'REFUSED');
        // La facture ne retient que les demandes créées de juillet à novembre.
        $this->sql->execute("UPDATE register SET creation_date = CONCAT(YEAR(CURRENT_DATE), '-09-15') WHERE new_team_name LIKE 'IP %'");

        foreach (array('register_invoices.sql', 'register_not_paid.sql') as $file) {
            $rows = $this->rows($file, 'club');
            $this->assertArrayHasKey('IP club', $rows, $file);
            $this->assertStringContainsString('IP Facturee', $rows['IP club']['competitions'], $file);
            $this->assertStringNotContainsString('IP Refusee', $rows['IP club']['competitions'], $file);
            $this->assertEquals(5, $rows['IP club']['cout'], $file);
        }
    }
}
