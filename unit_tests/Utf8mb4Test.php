<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Register.php';
require_once __DIR__ . '/../classes/Team.php';
require_once __DIR__ . '/../classes/UserManager.php';

/**
 * Issue #334 — la base accepte tout l'Unicode, pas seulement cp1252.
 *
 * Un responsable de club n'a pas pu renommer son équipe de Coupe 6x6 Féminin :
 * le formulaire renvoyait « Conversion from collation utf8mb3_general_ci into
 * latin1_swedish_ci impossible for parameter ». Les tables étaient en latin1 —
 * c'est-à-dire cp1252 — et MySQL refuse de convertir un paramètre lié qui
 * contient un caractère absent du jeu de la colonne.
 *
 * Les trois caractères retenus ici ne sont pas décoratifs, ce sont exactement
 * ceux qui échouaient :
 *
 *  - **U+202F, l'espace insécable étroite.** Le cas le plus pénible, et celui
 *    qui a fait le ticket : iOS en français l'insère tout seul avant « ? »,
 *    « ! », « ; » et « : ». Elle est invisible, donc l'utilisateur ne peut ni
 *    la voir ni la retirer.
 *  - **U+2713, le « check ».** Hors cp1252 alors qu'il n'a rien d'exotique.
 *  - **un emoji.** Sur quatre octets : il exige utf8mb4 de bout en bout, la
 *    connexion comprise. C'est lui qui distingue une vraie migration d'un
 *    simple passage en utf8mb3.
 *
 * L'accent, lui, passait déjà : il est dans cp1252. Il est testé quand même,
 * parce que la conversion des tables relit les octets latin1 pour les
 * réencoder — une erreur de manipulation s'y verrait tout de suite.
 */
class Utf8mb4Test extends UfolepTestCase
{
    /** Espace insécable étroite — celle que l'iPhone glisse avant un « ? ». */
    private const ETROITE = "\u{202F}";
    private const CHECK = "\u{2713}";
    private const EMOJI = "\u{1F3D0}";

    private int $id_club;
    private int $id_competition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nettoyer();
        $this->id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'ut334 club'");
        $this->id_competition = (int)$this->sql->execute(
            "INSERT INTO competitions SET
                code_competition = 'u4',
                libelle = 'utf8mb4 tests',
                id_compet_maitre = 'u4',
                start_date = CURRENT_DATE + INTERVAL 30 DAY,
                start_register_date = CURRENT_DATE - INTERVAL 10 DAY,
                limit_register_date = CURRENT_DATE + INTERVAL 10 DAY");
    }

    protected function tearDown(): void
    {
        $this->nettoyer();
        parent::tearDown();
    }

    private function nettoyer(): void
    {
        $this->sql->execute("DELETE FROM classements WHERE code_competition = 'u4'");
        $this->sql->execute("DELETE FROM equipes WHERE code_competition = 'u4'");
        $this->sql->execute("DELETE FROM register WHERE leader_email = 'ut334@ufolep.test'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'u4'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'ut334 club'");
    }

    /**
     * La moitié « code » du ticket. `utf8` est l'alias MySQL d'utf8mb3, qui
     * s'arrête au plan multilingue de base : tant que la connexion l'annonce,
     * aucun emoji ne peut traverser, même vers une colonne utf8mb4.
     *
     * @throws Exception
     */
    public function test_la_connexion_est_annoncee_en_utf8mb4(): void
    {
        self::assertSame('utf8mb4', mysqli_character_set_name(Database::openDbConnection()));
    }

    /**
     * Le cœur du ticket : la conversion se joue sur le PARAMÈTRE LIÉ, pas sur
     * la requête. C'est pour ça qu'un nom d'équipe saisi depuis un iPhone
     * faisait échouer un simple UPDATE.
     *
     * Les cas sont parcourus en boucle plutôt qu'avec un `@dataProvider` :
     * `UfolepTestCase::__construct()` ne relaie pas les arguments que PHPUnit
     * passe au constructeur, si bien qu'un jeu de données n'arriverait jamais
     * jusqu'au test.
     *
     * @throws Exception
     */
    public function test_un_parametre_lie_hors_cp1252_est_stocke_tel_quel(): void
    {
        $cas = [
            'espace etroite (iOS)' => 'Les Fees No Men' . self::ETROITE . '?',
            'check' => 'Volley ' . self::CHECK,
            'emoji' => 'Volley ' . self::EMOJI,
            'accent (deja dans cp1252)' => "Equipe de B\u{00E9}doulen",
        ];

        foreach ($cas as $libelle => $nom) {
            $this->sql->execute("DELETE FROM register WHERE leader_email = 'ut334@ufolep.test'");
            $this->sql->execute(
                "INSERT INTO register SET new_team_name = ?, id_club = ?, id_competition = ?,
                                          leader_name = 'UT334', leader_first_name = 'Test',
                                          leader_phone = '0600000000',
                                          leader_email = 'ut334@ufolep.test'",
                [
                    ['type' => 's', 'value' => $nom],
                    ['type' => 'i', 'value' => $this->id_club],
                    ['type' => 'i', 'value' => $this->id_competition],
                ]);

            $rows = $this->sql->execute(
                "SELECT new_team_name FROM register WHERE leader_email = 'ut334@ufolep.test'");

            self::assertCount(1, $rows, $libelle);
            self::assertSame($nom, $rows[0]['new_team_name'],
                "$libelle : le nom doit revenir octet pour octet, "
                . 'sans caractere de remplacement');
        }
    }

    /**
     * Le premier des deux passages où la panne guettait : la demande
     * d'inscription elle-même, celle que l'utilisateur a vu échouer.
     *
     * @throws Exception
     */
    public function test_la_demande_d_inscription_accepte_le_nom_de_l_iphone(): void
    {
        $nom = 'Coupe 6x6 Feminin' . self::ETROITE . '?';
        $this->connect_as_club_leader($this->id_club);

        try {
            (new Register())->register(
                $nom, $this->id_club, $this->id_competition, null,
                'UT334', 'Test', 'ut334@ufolep.test', '0600000000',
                null, null, null, null, null, null, 'test');
            self::fail("une creation reussie leve l'exception 201 (message de confirmation)");
        } catch (Exception $e) {
            self::assertSame(201, $e->getCode(), $e->getMessage());
        }

        $rows = $this->sql->execute(
            "SELECT new_team_name FROM register WHERE leader_email = 'ut334@ufolep.test'");
        self::assertCount(1, $rows);
        self::assertSame($nom, $rows[0]['new_team_name']);
    }

    /**
     * Le second passage : à la validation, `new_team_name` est recopié dans
     * `equipes.nom_equipe`. Un nom accepté à la saisie pouvait donc encore
     * échouer plus tard, entre les mains de l'administrateur.
     *
     * @throws Exception
     */
    public function test_le_renommage_d_une_equipe_accepte_le_nom_de_l_iphone(): void
    {
        $id_equipe = (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = 'ut334 avant', code_competition = 'u4', id_club = ?",
            [['type' => 'i', 'value' => $this->id_club]]);

        $nom = 'Coupe 6x6 Feminin' . self::ETROITE . '? ' . self::EMOJI;
        $this->connect_as_admin();
        (new Team())->saveTeam(null, null, $id_equipe, null, null, null, $nom);

        $rows = $this->sql->execute("SELECT nom_equipe FROM equipes WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $id_equipe]]);
        self::assertSame($nom, $rows[0]['nom_equipe']);
    }

    /**
     * Le piège de la migration. MySQL ne stocke pas le texte d'une vue tel
     * qu'on le lui donne : il le normalise, et injecte un
     * `convert(... using utf8mb3)` partout où un CONCAT mélangeait une colonne
     * latin1 et une colonne utf8mb3. Ces conversions survivent à la conversion
     * des tables et **retronquent** ce qu'on vient tout juste d'élargir : le
     * nom serait intact en base et ressortirait en « ? » à l'écran.
     *
     * `teams_view.team_full_name` est précisément un de ces CONCAT — il en
     * portait six. Rejouer le fichier de vue les fait disparaître ; ce test
     * dit si on a oublié de le faire.
     *
     * @throws Exception
     */
    public function test_la_vue_des_equipes_ne_retronque_pas_le_nom(): void
    {
        $nom = 'ut334 ' . self::EMOJI;
        $id_equipe = (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = ?, code_competition = 'u4', id_club = ?",
            [
                ['type' => 's', 'value' => $nom],
                ['type' => 'i', 'value' => $this->id_club],
            ]);

        $rows = $this->sql->execute(
            "SELECT nom_equipe, team_full_name FROM teams_view WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $id_equipe]]);

        self::assertCount(1, $rows);
        self::assertSame($nom, $rows[0]['nom_equipe']);
        self::assertStringContainsString(self::EMOJI, $rows[0]['team_full_name'],
            'la vue doit rendre le nom entier : un convert(... using utf8mb3) '
            . 'residuel le remplacerait par un « ? »');
    }
}
