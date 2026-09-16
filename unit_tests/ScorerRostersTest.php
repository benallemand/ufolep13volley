<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/LiveScore.php';

/**
 * Issue #332 — effectifs servis à l'écran arbitre du live scoring.
 *
 * Ce qui est vérifié ici tient en trois propriétés, qui ont chacune une raison
 * d'être ailleurs dans le code :
 *
 *  - **la vignette** est jointe, avec l'image de repli garantie par
 *    `adjust_photo_path_from_results()` (issue #295) — sans elle, une case de
 *    terrain afficherait une icône cassée ;
 *  - **`nom_court`** est le prénom suivi de l'initiale du nom : un nom de
 *    famille entier ne tient pas dans une case de 55 px ;
 *  - **les membres non jouants** (issue #325) sont exclus — ils sont rattachés à
 *    l'équipe pour la piloter, pas pour y jouer, et ne sont donc pas plaçables
 *    sur le terrain.
 *
 * L'autorisation, elle, vit dans `ajax/live_score.php` : c'est le
 * `canModifyLiveScore()` qui garde déjà les POST, et le spec E2E la couvre.
 */
class ScorerRostersTest extends UfolepTestCase
{
    private LiveScore $live;

    private string $suffixe;
    private int $id_club;
    private int $id_equipe_dom;
    private int $id_equipe_ext;
    private string $code_match;

    protected function setUp(): void
    {
        parent::setUp();
        $this->live = new LiveScore();
        $this->suffixe = 'ISSUE332_' . uniqid();

        $this->id_club = (int)$this->sql->execute(
            "INSERT INTO clubs SET nom = ?",
            [['type' => 's', 'value' => "Club $this->suffixe"]]
        );
        $this->id_equipe_dom = $this->creerEquipe('Dom');
        $this->id_equipe_ext = $this->creerEquipe('Ext');

        $this->code_match = 'M332_' . substr($this->suffixe, -12);
        $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = 'm', division = '1',
                                     id_equipe_dom = ?, id_equipe_ext = ?, date_reception = CURRENT_DATE",
            [
                ['type' => 's', 'value' => $this->code_match],
                ['type' => 'i', 'value' => $this->id_equipe_dom],
                ['type' => 'i', 'value' => $this->id_equipe_ext],
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_equipe IN (?, ?)", [
            ['type' => 'i', 'value' => $this->id_equipe_dom],
            ['type' => 'i', 'value' => $this->id_equipe_ext],
        ]);
        $this->sql->execute("DELETE FROM matches WHERE code_match = ?",
            [['type' => 's', 'value' => $this->code_match]]);
        $this->sql->execute("DELETE FROM equipes WHERE id_equipe IN (?, ?)", [
            ['type' => 'i', 'value' => $this->id_equipe_dom],
            ['type' => 'i', 'value' => $this->id_equipe_ext],
        ]);
        $this->sql->execute("DELETE FROM joueurs WHERE nom = ?",
            [['type' => 's', 'value' => $this->suffixe]]);
        $this->sql->execute("DELETE FROM clubs WHERE id = ?",
            [['type' => 'i', 'value' => $this->id_club]]);
        parent::tearDown();
    }

    private function creerEquipe(string $libelle): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = ?, code_competition = 'm', id_club = ?",
            [
                ['type' => 's', 'value' => "$libelle $this->suffixe"],
                ['type' => 'i', 'value' => $this->id_club],
            ]
        );
    }

    /** @return int identifiant du joueur créé */
    private function ajouterJoueur(int $id_equipe, string $prenom, bool $jouant = true): int
    {
        $id = (int)$this->sql->execute(
            "INSERT INTO joueurs SET nom = ?, prenom = ?, sexe = 'M', id_club = ?",
            [
                ['type' => 's', 'value' => $this->suffixe],
                ['type' => 's', 'value' => $prenom],
                ['type' => 'i', 'value' => $this->id_club],
            ]
        );
        $this->sql->execute(
            "INSERT INTO joueur_equipe (id_joueur, id_equipe, est_jouant) VALUES (?, ?, ?)",
            [
                ['type' => 'i', 'value' => $id],
                ['type' => 'i', 'value' => $id_equipe],
                ['type' => 'i', 'value' => $jouant ? 1 : 0],
            ]
        );
        return $id;
    }

    public function test_les_deux_effectifs_sont_servis_avec_leur_vignette(): void
    {
        $this->ajouterJoueur($this->id_equipe_dom, 'Alex');
        $this->ajouterJoueur($this->id_equipe_ext, 'Camille');

        $rosters = $this->live->getScorerRosters($this->code_match);

        self::assertArrayHasKey('dom', $rosters);
        self::assertArrayHasKey('ext', $rosters);
        self::assertCount(1, $rosters['dom']);
        self::assertCount(1, $rosters['ext']);

        $joueur = $rosters['dom'][0];
        self::assertNotEmpty($joueur['path_photo_low'],
            "une vignette est toujours servie, l'image de repli à défaut");
        self::assertArrayHasKey('id', $joueur);
    }

    /** Le nom entier ne tient pas dans une case de terrain de 55 px. */
    public function test_le_nom_court_est_le_prenom_et_l_initiale(): void
    {
        $this->ajouterJoueur($this->id_equipe_dom, 'Alex');

        $rosters = $this->live->getScorerRosters($this->code_match);

        self::assertSame(
            'Alex ' . strtoupper(substr($this->suffixe, 0, 1)) . '.',
            $rosters['dom'][0]['nom_court']
        );
    }

    /**
     * Un membre non jouant est rattaché à l'équipe pour la piloter (issue
     * #325) : il ne doit pas être proposable sur le terrain.
     */
    public function test_un_membre_non_jouant_n_est_pas_proposable(): void
    {
        $jouant = $this->ajouterJoueur($this->id_equipe_dom, 'Alex');
        $nonJouant = $this->ajouterJoueur($this->id_equipe_dom, 'Dominique', false);

        $ids = array_map('intval', array_column(
            $this->live->getScorerRosters($this->code_match)['dom'], 'id'));

        self::assertContains($jouant, $ids);
        self::assertNotContains($nonJouant, $ids);
    }

    public function test_un_match_inconnu_est_refuse(): void
    {
        $this->expectException(Exception::class);
        $this->live->getScorerRosters('M332_INEXISTANT');
    }
}
