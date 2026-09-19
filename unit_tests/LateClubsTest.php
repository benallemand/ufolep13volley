<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';

/**
 * Issue #338 — l'indicateur « Clubs sans aucune inscription ».
 *
 * Le test ouvre lui-même la fenêtre d'inscription des trois championnats, et la
 * restaure dans son `tearDown` : la requête est datée par construction, et sans
 * cela elle ne renverrait rien dix mois sur douze — un test qui passe parce
 * qu'il ne teste rien. C'est le même parti pris que `registrations_setup.php`
 * côté E2E.
 */
class LateClubsTest extends UfolepTestCase
{
    private string $suffixe;
    private int $id_club;
    private int $id_equipe;
    private int $id_compte;
    private int $id_competition;
    private string $email;

    /** Fenêtres d'inscription des championnats, à remettre en place. */
    private array $fenetres;

    protected function setUp(): void
    {
        parent::setUp();
        $this->suffixe = 'ISSUE338_' . uniqid();
        $this->email = strtolower($this->suffixe) . '@ufolep.test';

        $this->fenetres = $this->sql->execute(
            "SELECT id, start_register_date, limit_register_date
             FROM competitions WHERE code_competition IN ('m', 'f', 'mo')");
        $this->ouvrirLaFenetre();

        $this->id_competition = (int)$this->sql->execute(
            "SELECT id FROM competitions WHERE code_competition = 'm'")[0]['id'];

        $this->id_club = (int)$this->sql->execute(
            "INSERT INTO clubs SET nom = ?",
            [['type' => 's', 'value' => "Club $this->suffixe"]]
        );
        // Une équipe classée la saison passée : c'est ce qui rend le club
        // attendu cette saison.
        $this->id_equipe = (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = ?, code_competition = 'm', id_club = ?",
            [
                ['type' => 's', 'value' => "Equipe $this->suffixe"],
                ['type' => 'i', 'value' => $this->id_club],
            ]
        );
        $this->sql->execute(
            "INSERT INTO classements SET code_competition = 'm', division = '1', id_equipe = ?",
            [['type' => 'i', 'value' => $this->id_equipe]]
        );
        $this->id_compte = (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = ?, email = ?, password_hash = ?",
            [
                ['type' => 's', 'value' => $this->email],
                ['type' => 's', 'value' => $this->email],
                ['type' => 's', 'value' => md5($this->email)],
            ]
        );
        $this->sql->execute(
            "INSERT INTO users_clubs SET user_id = ?, club_id = ?",
            [
                ['type' => 'i', 'value' => $this->id_compte],
                ['type' => 'i', 'value' => $this->id_club],
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->sql->execute("DELETE FROM register WHERE id_club = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        $this->sql->execute("DELETE FROM users_clubs WHERE club_id = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        $this->sql->execute("DELETE FROM comptes_acces WHERE id = ?",
            [['type' => 'i', 'value' => $this->id_compte]]);
        $this->sql->execute("DELETE FROM classements WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $this->id_equipe]]);
        $this->sql->execute("DELETE FROM equipes WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $this->id_equipe]]);
        $this->sql->execute("DELETE FROM clubs WHERE id = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        $this->restaurerLesFenetres();
        parent::tearDown();
    }

    private function ouvrirLaFenetre(): void
    {
        $this->sql->execute(
            "UPDATE competitions
             SET start_register_date = CURRENT_DATE - INTERVAL 5 DAY,
                 limit_register_date = CURRENT_DATE + INTERVAL 5 DAY
             WHERE code_competition IN ('m', 'f', 'mo')");
    }

    private function fermerLaFenetre(): void
    {
        $this->sql->execute(
            "UPDATE competitions
             SET limit_register_date = CURRENT_DATE - INTERVAL 1 DAY
             WHERE code_competition IN ('m', 'f', 'mo')");
    }

    private function restaurerLesFenetres(): void
    {
        foreach ($this->fenetres as $fenetre) {
            $this->sql->execute(
                "UPDATE competitions
                 SET start_register_date = ?, limit_register_date = ?
                 WHERE id = ?",
                [
                    ['type' => 's', 'value' => $fenetre['start_register_date']],
                    ['type' => 's', 'value' => $fenetre['limit_register_date']],
                    ['type' => 'i', 'value' => (int)$fenetre['id']],
                ]
            );
        }
    }

    private function inscrireUneEquipe(): void
    {
        $this->sql->execute(
            "INSERT INTO register SET new_team_name = ?, id_club = ?, id_competition = ?,
                                      leader_name = 'Nom', leader_first_name = 'Prenom',
                                      leader_email = ?, leader_phone = '0700000000'",
            [
                ['type' => 's', 'value' => "Equipe $this->suffixe"],
                ['type' => 'i', 'value' => $this->id_club],
                ['type' => 'i', 'value' => $this->id_competition],
                ['type' => 's', 'value' => $this->email],
            ]
        );
    }

    private function retardataires(): array
    {
        return $this->sql->execute(
            file_get_contents(__DIR__ . '/../sql/clubs_without_registration.sql'));
    }

    private function ligneDuClub(): ?array
    {
        foreach ($this->retardataires() as $ligne) {
            if ((int)$ligne['indicator_id'] === $this->id_club) {
                return $ligne;
            }
        }
        return null;
    }

    public function test_un_club_attendu_qui_n_a_rien_inscrit_est_signale(): void
    {
        $ligne = $this->ligneDuClub();
        self::assertNotNull($ligne, "le club a joué la saison passée et n'a rien inscrit");
        self::assertSame("Club $this->suffixe", $ligne['club']);
        self::assertSame('1', (string)$ligne['equipes_saison_passee']);
        self::assertSame('Championnat Masculin', $ligne['competitions_saison_passee']);
        self::assertSame('5', (string)$ligne['jours_restants']);
        // Le compte du club est le référent officiel depuis #326 : c'est lui
        // qu'on relance, pas les responsables d'équipe.
        self::assertSame($this->email, $ligne['contact']);
    }

    public function test_une_seule_inscription_suffit_a_sortir_de_la_liste(): void
    {
        $this->inscrireUneEquipe();
        self::assertNull($this->ligneDuClub(),
            "le club a commencé sa saisie : ce n'est plus un retardataire, "
            . "les équipes qui lui manquent relèvent de « Equipes non réengagées »");
    }

    public function test_un_club_qui_ne_se_reengage_pas_n_est_pas_en_retard(): void
    {
        $this->sql->execute(
            "UPDATE classements SET will_register_again = 0 WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $this->id_equipe]]);
        self::assertNull($this->ligneDuClub(),
            "le club a dit qu'il ne revenait pas : il est parti, pas en retard");
    }

    /**
     * Le garde-fou qui évite que la tuile reste allumée toute l'année : une
     * fois la date limite passée, `register` garde ses lignes mais l'indicateur
     * se tait.
     */
    public function test_la_date_limite_passee_l_indicateur_se_tait(): void
    {
        $this->fermerLaFenetre();
        self::assertSame([], $this->retardataires(),
            "hors fenêtre d'inscription, plus aucun club n'est en retard");
    }
}
