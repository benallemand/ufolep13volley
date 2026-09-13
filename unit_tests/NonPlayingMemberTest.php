<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/Players.php';

/**
 * Issue #325 — un membre d'équipe peut ne pas y jouer.
 *
 * Le cas type : un joueur du championnat masculin qui est aussi responsable
 * d'une équipe féminine. Il doit être rattaché à l'équipe pour la piloter, mais
 * il n'en est pas un membre jouant — il ne compte pas dans l'effectif, n'a pas
 * besoin de licence à ce titre, et n'est pas présentable en match.
 *
 * Les deux propriétés à garder sont symétriques, et les tests les vérifient
 * **dans les deux sens** : non jouant, le membre disparaît des contrôles
 * d'effectif et de licence ; jouant, il y revient. Un test qui ne vérifierait
 * que l'absence passerait aussi avec une requête cassée qui ne renvoie rien.
 *
 * Le test crée ses propres données — club, équipe féminine, quatre joueuses,
 * un responsable masculin — et les supprime ensuite. La base de développement
 * porte des données de production : rien ici ne s'appuie sur l'existant.
 */
class NonPlayingMemberTest extends UfolepTestCase
{
    private Players $players;

    private string $suffixe;
    private int $id_club;
    private int $id_team;
    private int $id_homme;
    /** @var int[] */
    private array $id_joueuses = array();
    private int $id_compte;

    /** Valeur d'origine, restaurée par le tearDown. */
    private ?string $limite_inscription_f = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->players = new Players();
        $this->suffixe = 'ISSUE325_' . uniqid();

        $this->id_club = (int)$this->sql->execute(
            "INSERT INTO clubs SET nom = ?",
            [['type' => 's', 'value' => "Club $this->suffixe"]]
        );
        $this->id_team = (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = ?, code_competition = 'f', id_club = ?",
            [
                ['type' => 's', 'value' => "Equipe $this->suffixe"],
                ['type' => 'i', 'value' => $this->id_club],
            ]
        );
        // Les contrôles d'effectif et de licence ne regardent que les équipes
        // engagées (`id_equipe IN (SELECT id_equipe FROM classements)`).
        $this->sql->execute(
            "INSERT INTO classements SET code_competition = 'f', division = '1', id_equipe = ?",
            [['type' => 'i', 'value' => $this->id_team]]
        );

        // Quatre joueuses licenciées : l'effectif minimal d'une équipe
        // féminine, pour que seule la présence d'un homme puisse la rendre
        // incomplète.
        foreach (range(1, 4) as $numero) {
            $this->id_joueuses[] = $this->creerPersonne('F', "LIC325$numero", true);
        }
        // Le responsable : un homme, sans licence ni homologation.
        $this->id_homme = $this->creerPersonne('M', null, false);

        $this->sql->execute(
            "INSERT INTO joueur_equipe (id_joueur, id_equipe, est_jouant, is_leader)
             VALUES (?, ?, b'0', b'1')",
            [
                ['type' => 'i', 'value' => $this->id_homme],
                ['type' => 'i', 'value' => $this->id_team],
            ]
        );
        foreach ($this->id_joueuses as $id_joueuse) {
            $this->sql->execute(
                "INSERT INTO joueur_equipe (id_joueur, id_equipe) VALUES (?, ?)",
                [
                    ['type' => 'i', 'value' => $id_joueuse],
                    ['type' => 'i', 'value' => $this->id_team],
                ]
            );
        }

        // `teams_incomplete.sql` part du compte rattaché à l'équipe.
        $this->id_compte = (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = ?, email = ?",
            [
                ['type' => 's', 'value' => $this->suffixe],
                ['type' => 's', 'value' => strtolower($this->suffixe) . '@ufolep.test'],
            ]
        );
        $this->sql->execute(
            "INSERT INTO users_teams SET user_id = ?, team_id = ?",
            [
                ['type' => 'i', 'value' => $this->id_compte],
                ['type' => 'i', 'value' => $this->id_team],
            ]
        );

        // La requête des équipes incomplètes ne se déclenche qu'une fois la
        // date limite d'inscription passée. Elle est portée par la compétition,
        // donc globale : on l'avance le temps du test et on la remet ensuite.
        $ligne = $this->sql->execute("SELECT limit_register_date FROM competitions WHERE code_competition = 'f'");
        $this->limite_inscription_f = $ligne[0]['limit_register_date'] ?? null;
        $this->sql->execute(
            "UPDATE competitions SET limit_register_date = CURRENT_DATE - INTERVAL 1 DAY WHERE code_competition = 'f'"
        );
    }

    protected function tearDown(): void
    {
        if ($this->limite_inscription_f === null) {
            $this->sql->execute("UPDATE competitions SET limit_register_date = NULL WHERE code_competition = 'f'");
        } else {
            $this->sql->execute(
                "UPDATE competitions SET limit_register_date = ? WHERE code_competition = 'f'",
                [['type' => 's', 'value' => $this->limite_inscription_f]]
            );
        }
        $this->sql->execute("DELETE FROM users_teams WHERE team_id = ?",
            [['type' => 'i', 'value' => $this->id_team]]);
        $this->sql->execute("DELETE FROM comptes_acces WHERE id = ?",
            [['type' => 'i', 'value' => $this->id_compte]]);
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $this->id_team]]);
        $this->sql->execute("DELETE FROM classements WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $this->id_team]]);
        $this->sql->execute("DELETE FROM equipes WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $this->id_team]]);
        $this->sql->execute("DELETE FROM joueurs WHERE nom = ?",
            [['type' => 's', 'value' => $this->suffixe]]);
        $this->sql->execute("DELETE FROM clubs WHERE id = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        // `set_playing` et `addPlayerToTeam` journalisent : les traces partent
        // dans la vraie table `activity`.
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE ?",
            [['type' => 's', 'value' => '%' . $this->suffixe . '%']]);
        parent::tearDown();
    }

    private function creerPersonne(string $sexe, ?string $licence, bool $homologue): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO joueurs SET nom = ?, prenom = ?, sexe = ?, id_club = ?,
                                     email = ?, telephone = '0700000000',
                                     num_licence = ?,
                                     date_homologation = " . ($homologue ? "CURRENT_DATE - INTERVAL 30 DAY" : "NULL"),
            [
                ['type' => 's', 'value' => $this->suffixe],
                ['type' => 's', 'value' => $sexe . ($licence ?? 'X')],
                ['type' => 's', 'value' => $sexe],
                ['type' => 'i', 'value' => $this->id_club],
                ['type' => 's', 'value' => strtolower($sexe . ($licence ?? 'x') . $this->suffixe) . '@ufolep.test'],
                ['type' => 's', 'value' => $licence],
            ]
        );
    }

    /** Identifiants remontés par un fichier de `sql/`, colonne `indicator_id`. */
    private function idsSignales(string $fichier): array
    {
        $lignes = $this->sql->execute(file_get_contents(__DIR__ . '/../sql/' . $fichier));
        return array_map('intval', array_column($lignes, 'indicator_id'));
    }

    /** Noms d'équipe remontés par `teams_incomplete.sql`. */
    private function equipesIncompletes(): array
    {
        $lignes = $this->sql->execute(file_get_contents(__DIR__ . '/../sql/teams_incomplete.sql'));
        return array_column($lignes, 'equipe');
    }

    /**
     * Le défaut qui a motivé l'issue : la règle `garcons > 0` d'une équipe
     * féminine la déclarait incomplète à vie dès qu'un homme la pilotait.
     */
    public function test_un_membre_non_jouant_ne_compte_pas_dans_l_effectif(): void
    {
        self::assertNotContains(
            "Equipe $this->suffixe",
            $this->equipesIncompletes(),
            "quatre joueuses et un responsable non jouant : l'équipe est complète"
        );

        $this->connect_as_admin();
        $this->players->set_playing([$this->id_homme], $this->id_team, 1);

        self::assertContains(
            "Equipe $this->suffixe",
            $this->equipesIncompletes(),
            "le même homme déclaré jouant rend bien l'équipe féminine irrégulière"
        );
    }

    /**
     * Un responsable non jouant n'a pas besoin de licence à ce titre : le
     * signaler serait une alerte qu'on ne peut pas corriger.
     */
    public function test_un_membre_non_jouant_n_est_pas_signale_sans_licence(): void
    {
        self::assertNotContains($this->id_homme, $this->idsSignales('no_licence.sql'));
        self::assertNotContains($this->id_homme, $this->idsSignales('not_valid_players.sql'));

        $this->connect_as_admin();
        $this->players->set_playing([$this->id_homme], $this->id_team, 1);

        self::assertContains($this->id_homme, $this->idsSignales('no_licence.sql'),
            "déclaré jouant, le même homme sans licence est une vraie anomalie");
        self::assertContains($this->id_homme, $this->idsSignales('not_valid_players.sql'));
    }

    /**
     * La fiche d'équipe est la liste des licenciés présentables en match.
     */
    public function test_la_fiche_d_equipe_ignore_le_membre_non_jouant(): void
    {
        $this->connect_as_team_leader($this->id_team);
        $ids = array_map('intval', array_column($this->players->getPlayersPdf($this->id_team), 'id'));

        self::assertNotContains($this->id_homme, $ids);
        self::assertContains($this->id_joueuses[0], $ids, "les joueuses, elles, y figurent");
    }

    public function test_set_playing_bascule_dans_les_deux_sens(): void
    {
        $this->connect_as_admin();
        self::assertFalse($this->players->isPlayingInTeam($this->id_homme, $this->id_team));

        $this->players->set_playing([$this->id_homme], $this->id_team, 1);
        self::assertTrue($this->players->isPlayingInTeam($this->id_homme, $this->id_team));

        $this->players->set_playing([$this->id_homme], $this->id_team, 0);
        self::assertFalse($this->players->isPlayingInTeam($this->id_homme, $this->id_team));
    }

    /**
     * L'écran effectif du responsable n'envoie pas d'`id_team` : l'équipe est
     * celle de la session. Comme les rôles se cumulent (issue #245), un
     * administrateur qui est aussi responsable passe par là — et recevait
     * « Aucune équipe n'est désignée ! », le repli sur la session ayant été
     * réservé aux non-admins. Relevé en recette par Benjamin.
     */
    public function test_un_admin_responsable_agit_sur_l_equipe_de_sa_session(): void
    {
        $this->connect_as_team_leader($this->id_team);
        $_SESSION['is_admin'] = true;

        $this->players->set_playing([$this->id_homme], null, 1);

        self::assertTrue($this->players->isPlayingInTeam($this->id_homme, $this->id_team));
    }

    /**
     * Le resserrage, lui, reste : un responsable d'équipe n'agit que sur la
     * sienne, même s'il poste un autre `id_team`.
     */
    public function test_un_responsable_ne_peut_pas_agir_sur_une_autre_equipe(): void
    {
        $autre_equipe = (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = ?, code_competition = 'f', id_club = ?",
            [
                ['type' => 's', 'value' => "Autre $this->suffixe"],
                ['type' => 'i', 'value' => $this->id_club],
            ]
        );
        $this->connect_as_team_leader($autre_equipe);

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("n'est pas dans l'équipe");
            // L'`id_team` posté est ignoré au profit de celui de la session :
            // le joueur n'est pas dans l'équipe de la session, donc refus.
            $this->players->set_playing([$this->id_homme], $this->id_team, 1);
        } finally {
            $this->sql->execute("DELETE FROM equipes WHERE id_equipe = ?",
                [['type' => 'i', 'value' => $autre_equipe]]);
        }
    }

    /** Un capitaine joue : le capitanat n'a pas de sens sans cela. */
    public function test_un_membre_non_jouant_ne_peut_pas_etre_capitaine(): void
    {
        $this->connect_as_admin();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("ne joue pas dans cette équipe");
        $this->players->set_captain([$this->id_homme], $this->id_team);
    }

    /** Et réciproquement : on ne rend pas non jouant le capitaine en poste. */
    public function test_le_capitaine_ne_peut_pas_devenir_non_jouant(): void
    {
        $this->connect_as_admin();
        $this->players->set_captain([$this->id_joueuses[0]], $this->id_team);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("nommez un autre capitaine");
        $this->players->set_playing([$this->id_joueuses[0]], $this->id_team, 0);
    }

    /**
     * Nommer responsable quelqu'un qui n'est pas encore dans l'équipe est le
     * chemin par lequel un administrateur crée une appartenance non jouante.
     */
    public function test_nommer_un_responsable_non_jouant_l_ajoute_sans_le_faire_jouer(): void
    {
        $etranger = $this->creerPersonne('M', 'LIC325Z', true);
        $this->connect_as_admin();

        $this->players->set_leader([$etranger], $this->id_team, 0);

        self::assertTrue($this->players->isPlayerInTeam($etranger, $this->id_team));
        self::assertFalse($this->players->isPlayingInTeam($etranger, $this->id_team));
    }
}
