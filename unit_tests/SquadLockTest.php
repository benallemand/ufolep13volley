<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/Players.php';
require_once __DIR__ . '/../classes/Team.php';

/**
 * Issue #32 — l'effectif d'une équipe de Coupe Khoury Hanna se fige dès qu'elle
 * a signé la fiche d'un de ses matchs.
 *
 * Ouverte depuis janvier 2017. L'objet de la règle est d'empêcher qu'une équipe
 * se renforce en cours de compétition : elle joue avec l'effectif qu'elle avait
 * au coup d'envoi.
 *
 * Le test **crée ses propres données** — un club, une équipe KH, un joueur, un
 * match — et les supprime ensuite. Il ne s'appuie sur aucune équipe réelle : la
 * base de développement porte des données de production, et 36 des 62 équipes
 * KH y sont déjà verrouillées, ce qui rendrait tout test « sur l'existant »
 * dépendant de l'état du moment.
 */
class SquadLockTest extends UfolepTestCase
{
    private Players $players;
    private Team $team;

    /** Créés par le setUp, détruits par le tearDown. */
    private int $id_club;
    private int $id_team_kh;
    private int $id_team_championnat;
    private int $id_player;
    private string $code_match;

    protected function setUp(): void
    {
        $this->players = new Players();
        $this->team = new Team();
        $suffixe = 'ISSUE32_' . uniqid();

        $this->sql->execute(
            "INSERT INTO clubs (nom) VALUES (?)",
            [['type' => 's', 'value' => $suffixe]]
        );
        $this->id_club = (int)$this->sql->execute("SELECT LAST_INSERT_ID() AS id")[0]['id'];

        $this->id_team_kh = $this->creerEquipe('kh', "KH $suffixe");
        // Témoin : la règle ne doit pas déborder sur les autres compétitions.
        $this->id_team_championnat = $this->creerEquipe('m', "M $suffixe");

        $this->sql->execute(
            "INSERT INTO joueurs (nom, prenom, id_club) VALUES ('TEST', ?, ?)",
            [
                ['type' => 's', 'value' => $suffixe],
                ['type' => 'i', 'value' => $this->id_club],
            ]
        );
        $this->id_player = (int)$this->sql->execute("SELECT LAST_INSERT_ID() AS id")[0]['id'];

        $this->code_match = 'KH_TEST_' . substr($suffixe, -12);
    }

    protected function tearDown(): void
    {
        foreach ([$this->id_team_kh, $this->id_team_championnat] as $id_team) {
            $this->sql->execute("DELETE FROM joueur_equipe WHERE id_equipe = ?",
                [['type' => 'i', 'value' => $id_team]]);
        }
        $this->sql->execute("DELETE FROM matches WHERE code_match = ?",
            [['type' => 's', 'value' => $this->code_match]]);
        $this->sql->execute("DELETE FROM equipes WHERE id_equipe IN (?, ?)", [
            ['type' => 'i', 'value' => $this->id_team_kh],
            ['type' => 'i', 'value' => $this->id_team_championnat],
        ]);
        // addPlayerToTeam journalise chaque ajout : ces traces sont ecrites dans
        // la vraie table `activity`, il faut les retirer aussi.
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE ?",
            [['type' => 's', 'value' => '%TEST ISSUE32%']]);
        $this->sql->execute("DELETE FROM joueurs WHERE id = ?",
            [['type' => 'i', 'value' => $this->id_player]]);
        $this->sql->execute("DELETE FROM clubs WHERE id = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
    }

    private function creerEquipe(string $code_competition, string $nom): int
    {
        $this->sql->execute(
            "INSERT INTO equipes (nom_equipe, code_competition, id_club) VALUES (?, ?, ?)",
            [
                ['type' => 's', 'value' => $nom],
                ['type' => 's', 'value' => $code_competition],
                ['type' => 'i', 'value' => $this->id_club],
            ]
        );
        return (int)$this->sql->execute("SELECT LAST_INSERT_ID() AS id")[0]['id'];
    }

    /**
     * @param int $id_team équipe recevante du match créé
     * @param int $signe   1 pour signer la fiche du camp recevant
     */
    private function creerMatch(int $id_team, int $signe, string $code_competition = 'kh'): void
    {
        $this->sql->execute(
            // `division` est NOT NULL sans defaut, comme code_competition et les
            // deux equipes : les omettre fait echouer l'INSERT.
            "INSERT INTO matches (code_match, code_competition, division, id_equipe_dom,
                                  id_equipe_ext, date_reception, is_sign_team_dom)
             VALUES (?, ?, '1', ?, ?, '2026-01-20', ?)",
            [
                ['type' => 's', 'value' => $this->code_match],
                ['type' => 's', 'value' => $code_competition],
                ['type' => 'i', 'value' => $id_team],
                ['type' => 'i', 'value' => $this->id_team_championnat],
                ['type' => 'i', 'value' => $signe],
            ]
        );
    }

    public function test_effectif_ouvert_tant_qu_aucune_fiche_n_est_signee(): void
    {
        $this->creerMatch($this->id_team_kh, 0);
        $this->connect_as_team_leader($this->id_team_kh);

        self::assertNull($this->team->getSquadLock($this->id_team_kh));
        self::assertTrue($this->players->addPlayerToTeam($this->id_player, $this->id_team_kh));
    }

    public function test_effectif_fige_des_la_signature_de_la_fiche(): void
    {
        $this->creerMatch($this->id_team_kh, 1);
        $this->connect_as_team_leader($this->id_team_kh);

        $lock = $this->team->getSquadLock($this->id_team_kh);
        self::assertNotNull($lock, "l'équipe a signé : son effectif doit être figé");
        self::assertSame($this->code_match, $lock['code_match']);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(403);
        $this->players->addPlayerToTeam($this->id_player, $this->id_team_kh);
    }

    /**
     * La phase finale ('kf') réutilise les `id_equipe` inscrits en 'kh' : une
     * signature en finale doit verrouiller au même titre.
     */
    public function test_une_signature_en_phase_finale_fige_aussi(): void
    {
        $this->creerMatch($this->id_team_kh, 1, 'kf');
        $this->connect_as_team_leader($this->id_team_kh);

        self::assertNotNull($this->team->getSquadLock($this->id_team_kh));
    }

    /**
     * Le verrou ne concerne QUE la Khoury Hanna. Un championnat se joue avec un
     * effectif qui peut évoluer, et les autres coupes réutilisent les équipes de
     * championnat : y étendre la règle gèlerait tout le monde.
     */
    public function test_les_autres_competitions_ne_sont_pas_concernees(): void
    {
        $this->creerMatch($this->id_team_championnat, 1, 'm');
        $this->connect_as_team_leader($this->id_team_championnat);

        self::assertNull($this->team->getSquadLock($this->id_team_championnat));
        self::assertTrue(
            $this->players->addPlayerToTeam($this->id_player, $this->id_team_championnat)
        );
    }

    /**
     * L'administrateur garde la main : la commission doit pouvoir corriger une
     * saisie ou accorder une dérogation sans passer par une requête SQL. Son
     * ajout reste tracé dans le journal d'activité.
     */
    public function test_l_administrateur_peut_toujours_ajouter(): void
    {
        $this->creerMatch($this->id_team_kh, 1);
        $this->connect_as_admin();

        self::assertNotNull($this->team->getSquadLock($this->id_team_kh));
        self::assertTrue($this->players->addPlayerToTeam($this->id_player, $this->id_team_kh));
    }

    /**
     * Une dérogation doit se voir dans le journal d'activité.
     *
     * Sans marqueur, l'ajout d'un administrateur dans un effectif figé serait
     * indiscernable d'un ajout ordinaire, et il faudrait le reconstituer par
     * recoupement — c'est exactement le travail que fait, laborieusement,
     * l'indicateur de l'issue #233.
     */
    public function test_une_derogation_est_journalisee_comme_telle(): void
    {
        $this->creerMatch($this->id_team_kh, 1);
        $this->connect_as_admin();
        $this->players->addPlayerToTeam($this->id_player, $this->id_team_kh);

        // Le commentaire porte le nom du joueur, pas son id : on relit par le nom.
        $nom = $this->players->getPlayerFullName($this->id_player);
        $trace = $this->sql->execute(
            "SELECT comment FROM activity WHERE comment LIKE ? ORDER BY id DESC LIMIT 1",
            [['type' => 's', 'value' => 'Ajout DEROGATOIRE de ' . $nom . '%']]
        );

        self::assertCount(1, $trace, 'la dérogation doit laisser une trace explicite');
        self::assertStringContainsString($this->code_match, $trace[0]['comment']);
    }

    /**
     * Garde-fou d'architecture : `joueur_equipe` ne doit être alimentée QUE par
     * `addPlayerToTeam`, seul endroit qui porte le contrôle. `add_to_team`
     * portait une copie de l'INSERT, ce qui offrait un chemin de contournement.
     */
    public function test_un_seul_point_d_insertion_dans_joueur_equipe(): void
    {
        $source = file_get_contents(__DIR__ . '/../classes/Players.php');
        $inserts = preg_match_all('/INSERT\s+(INTO\s+)?joueur_equipe/i', $source);

        self::assertSame(
            1,
            $inserts,
            "joueur_equipe doit n'être alimentée que par addPlayerToTeam : toute\n"
            . "autre insertion contourne le verrouillage de l'effectif (issue #32).\n"
        );
    }
}
