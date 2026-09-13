<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/Club.php';

/**
 * Issue #326 — le référent d'un club, c'est son compte.
 *
 * `users_clubs` est la seule source où l'email est à la fois obligatoire et
 * unique, et c'est cette ligne qui porte le rôle (issue #245). Les colonnes
 * `clubs.*_responsable` ne sont plus qu'un contact de dernier recours, que
 * #327 retirera.
 *
 * Le lien personne → compte (`joueurs.id_compte`) est posé **explicitement**
 * au lieu d'être redéduit d'une comparaison d'emails : `joueurs` porte `email`
 * ET `email2`, les deux tables ont des collations différentes, et 6 comptes sur
 * 146 correspondaient à plusieurs personnes en base. Les tests couvrent les
 * deux cas — celui qui se tranche tout seul, et celui qui ne doit surtout pas
 * se trancher tout seul.
 */
class ClubAccountTest extends UfolepTestCase
{
    private Club $club;

    private string $suffixe;
    private int $id_club;
    private int $id_equipe;
    private int $id_personne;
    private string $email;

    protected function setUp(): void
    {
        parent::setUp();
        $this->club = new Club();
        $this->suffixe = 'ISSUE326_' . uniqid();
        $this->email = strtolower($this->suffixe) . '@ufolep.test';

        $this->id_club = (int)$this->sql->execute(
            "INSERT INTO clubs SET nom = ?",
            [['type' => 's', 'value' => "Club $this->suffixe"]]
        );
        // Une équipe engagée : l'indicateur ne regarde que les clubs actifs.
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
        $this->id_personne = $this->creerPersonne($this->email);
    }

    protected function tearDown(): void
    {
        $this->sql->execute("DELETE FROM emails WHERE to_email LIKE ?",
            [['type' => 's', 'value' => '%' . strtolower($this->suffixe) . '%']]);
        $this->sql->execute("DELETE FROM users_clubs WHERE club_id = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        // `id_compte` est en ON DELETE SET NULL : les personnes se delient
        // toutes seules quand le compte part.
        $this->sql->execute("DELETE FROM comptes_acces WHERE email LIKE ?",
            [['type' => 's', 'value' => '%' . strtolower($this->suffixe) . '%']]);
        $this->sql->execute("DELETE FROM joueurs WHERE nom = ?",
            [['type' => 's', 'value' => $this->suffixe]]);
        $this->sql->execute("DELETE FROM classements WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $this->id_equipe]]);
        $this->sql->execute("DELETE FROM equipes WHERE id_equipe = ?",
            [['type' => 'i', 'value' => $this->id_equipe]]);
        $this->sql->execute("DELETE FROM clubs WHERE id = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE ?",
            [['type' => 's', 'value' => '%' . $this->suffixe . '%']]);
        parent::tearDown();
    }

    private function creerPersonne(string $email, string $prenom = 'Referent'): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO joueurs SET nom = ?, prenom = ?, sexe = 'M', id_club = ?,
                                     email = ?, telephone = '0700000000'",
            [
                ['type' => 's', 'value' => $this->suffixe],
                ['type' => 's', 'value' => $prenom],
                ['type' => 'i', 'value' => $this->id_club],
                ['type' => 's', 'value' => $email],
            ]
        );
    }

    private function idCompteDe(int $id_personne): ?int
    {
        $ligne = $this->sql->execute("SELECT id_compte FROM joueurs WHERE id = ?",
            [['type' => 'i', 'value' => $id_personne]]);
        return $ligne[0]['id_compte'] === null ? null : (int)$ligne[0]['id_compte'];
    }

    private function idComptesDuClub(): array
    {
        return array_map('intval', array_column(
            $this->sql->execute("SELECT user_id FROM users_clubs WHERE club_id = ?",
                [['type' => 'i', 'value' => $this->id_club]]),
            'user_id'
        ));
    }

    /** Identifiants de clubs remontés par `clubs_without_account.sql`. */
    private function clubsSansCompte(): array
    {
        return array_map('intval', array_column(
            $this->sql->execute(file_get_contents(__DIR__ . '/../sql/clubs_without_account.sql')),
            'indicator_id'
        ));
    }

    public function test_le_compte_cree_est_rattache_au_club_et_a_la_personne(): void
    {
        $this->connect_as_admin();
        self::assertContains($this->id_club, $this->clubsSansCompte(),
            "le club a une équipe engagée et aucun compte : il doit être signalé");

        $this->club->createClubAccount($this->id_club, $this->email);

        $comptes = $this->idComptesDuClub();
        self::assertCount(1, $comptes);
        self::assertSame($comptes[0], $this->idCompteDe($this->id_personne),
            "la personne qui porte cet email est rattachée au compte créé");
        self::assertNotContains($this->id_club, $this->clubsSansCompte(),
            "l'indicateur ne le signale plus");
    }

    /**
     * Le cas qui condamne la jointure sur l'email : deux personnes partagent
     * l'adresse — famille, adresse générique de club. Le compte se crée, mais
     * le lien reste à poser à la main.
     */
    public function test_un_email_porte_par_deux_personnes_ne_lie_personne(): void
    {
        $jumelle = $this->creerPersonne($this->email, 'Conjointe');
        $this->connect_as_admin();

        $this->club->createClubAccount($this->id_club, $this->email);

        self::assertCount(1, $this->idComptesDuClub(), "le club a bien son compte");
        self::assertNull($this->idCompteDe($this->id_personne));
        self::assertNull($this->idCompteDe($jumelle));
    }

    /**
     * Un email = un compte (issue #247). Un référent déjà responsable d'une
     * équipe ne doit pas se voir créer un second compte.
     */
    public function test_un_compte_existant_est_rattache_sans_etre_duplique(): void
    {
        $id_compte = (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = ?, email = ?",
            [
                ['type' => 's', 'value' => $this->email],
                ['type' => 's', 'value' => $this->email],
            ]
        );
        $this->connect_as_admin();

        $this->club->createClubAccount($this->id_club, $this->email);

        self::assertSame([$id_compte], $this->idComptesDuClub());
        $comptes = $this->sql->execute("SELECT COUNT(*) AS cnt FROM comptes_acces WHERE email = ?",
            [['type' => 's', 'value' => $this->email]]);
        self::assertSame(1, (int)$comptes[0]['cnt']);
    }

    /** Rejouer l'action ne crée ni second compte ni second rattachement. */
    public function test_l_action_est_idempotente(): void
    {
        $this->connect_as_admin();
        $this->club->createClubAccount($this->id_club, $this->email);
        $this->club->createClubAccount($this->id_club, $this->email);

        self::assertCount(1, $this->idComptesDuClub());
    }

    public function test_une_adresse_invalide_est_refusee(): void
    {
        $this->connect_as_admin();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("adresse email valide");
        $this->club->createClubAccount($this->id_club, 'pas-une-adresse');
    }

    public function test_seul_un_administrateur_peut_creer_un_compte_de_club(): void
    {
        $this->connect_as_club_leader($this->id_club);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(403);
        $this->club->createClubAccount($this->id_club, $this->email);
    }

    /**
     * Les adresses proposées évitent une ressaisie — donc une faute de frappe
     * dans l'adresse à laquelle partent les identifiants. Elles viennent des
     * personnes du club : depuis #327, le club n'a plus de coordonnées libres.
     */
    public function test_les_adresses_proposees_viennent_des_personnes_du_club(): void
    {
        $autre = strtolower($this->suffixe) . '.joueur@ufolep.test';
        $this->creerPersonne($autre, 'Joueur');
        $this->connect_as_admin();

        $emails = array_column($this->club->getAccountCandidates($this->id_club), 'email');

        self::assertContains($this->email, $emails);
        self::assertContains($autre, $emails);
        self::assertSame(count($emails), count(array_unique($emails)),
            "pas deux fois la même adresse à départager");
    }

    /**
     * La grille des clubs remplace les cinq colonnes retirées par ce qu'elles
     * prétendaient porter : le compte, et la personne derrière (issue #327).
     */
    public function test_la_grille_des_clubs_montre_le_compte_et_le_referent(): void
    {
        $this->connect_as_admin();
        $this->club->createClubAccount($this->id_club, $this->email);

        $lignes = $this->sql->execute($this->club->getSql("c.id = $this->id_club"));

        self::assertCount(1, $lignes);
        self::assertSame($this->email, $lignes[0]['comptes']);
        self::assertStringContainsString('Referent', $lignes[0]['referents'],
            "la personne rattachée au compte est nommée");
        self::assertArrayNotHasKey('email_responsable', $lignes[0],
            "les colonnes de coordonnées libres ont disparu");
    }
}
