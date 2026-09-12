<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Players.php';

use PHPUnit\Framework\TestCase;

/**
 * Issue #295 — substitution de l'image de repli, et coût de la vérification.
 *
 * `adjust_photo_path_from_results()` faisait un `file_exists()` **par joueur**,
 * soit 3 651 accès disque pour un appel à `getPlayers`. C'était le poste le plus
 * coûteux de l'endpoint — 3,98 s sur 4,46, la requête SQL n'en prenant que 0,87.
 * Elle lit désormais une fois chaque répertoire concerné.
 *
 * Ce test garde les deux propriétés : le **comportement** (quelle image est
 * substituée, et quand) et la **méthode** (pas de retour au `file_exists` par
 * ligne, qui ne se verrait pas autrement qu'en chronométrant).
 *
 * Pas de `UfolepTestCase` : aucune base n'est nécessaire, et son constructeur
 * instancie un `SqlManager`.
 */
class PlayerPhotoPathTest extends TestCase
{
    private const DOSSIER = __DIR__ . '/../players_pics';

    /** Fichier réel créé pour le test, retiré ensuite. */
    private string $fichier;
    private string $nom_fichier;

    protected function setUp(): void
    {
        if (!is_dir(self::DOSSIER)) {
            mkdir(self::DOSSIER, 0777, true);
        }
        $this->nom_fichier = 'test_issue295_' . uniqid() . '.jpg';
        $this->fichier = self::DOSSIER . '/' . $this->nom_fichier;
        file_put_contents($this->fichier, 'contenu factice');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->fichier)) {
            unlink($this->fichier);
        }
    }

    public function test_une_photo_presente_est_conservee(): void
    {
        $chemin = 'players_pics/' . $this->nom_fichier;
        $resultat = Players::adjust_photo_path_from_results([
            ['path_photo' => $chemin, 'path_photo_low' => $chemin, 'sexe' => 'M'],
        ]);

        self::assertSame($chemin, $resultat[0]['path_photo']);
        self::assertSame($chemin, $resultat[0]['path_photo_low']);
    }

    /**
     * Le cas qui a échappé à la première version de ce test.
     *
     * `path_photo_low` est **déduit** de `path_photo` par un `REPLACE` dans
     * `players_view` : rien ne garantit que la vignette existe, et elle manque
     * pour une bonne part des joueurs — seules les photos téléversées depuis
     * l'application passent par `generateLowPhoto()`. Vérifié en production :
     * `players_pics/akkouchearnaud1.jpg` répond 200, sa vignette 404.
     *
     * Le défaut est ancien, mais il est resté invisible tant qu'aucun écran
     * n'affichait la vignette. La grille des joueurs le fait depuis #295, d'où
     * une volée de 404 en console.
     *
     * Mon test initial passait le **même** fichier dans les deux chemins : il ne
     * pouvait donc pas voir la différence. C'est ce cas-ci qui garde le
     * correctif.
     */
    public function test_une_vignette_manquante_se_rabat_sur_la_photo_pleine(): void
    {
        $plein = 'players_pics/' . $this->nom_fichier;
        $vignette = 'players_pics_low/' . $this->nom_fichier; // jamais créée

        $resultat = Players::adjust_photo_path_from_results([
            ['path_photo' => $plein, 'path_photo_low' => $vignette, 'sexe' => 'M'],
        ]);

        self::assertSame($plein, $resultat[0]['path_photo']);
        self::assertSame(
            $plein,
            $resultat[0]['path_photo_low'],
            "sans repli, le navigateur demande une vignette inexistante et récolte un 404"
        );
    }

    /**
     * Quand la vignette existe, c'est bien elle qui est servie — le repli ne
     * doit pas écraser le cas nominal.
     */
    public function test_une_vignette_presente_est_conservee(): void
    {
        $dossier_bas = __DIR__ . '/../players_pics_low';
        if (!is_dir($dossier_bas)) {
            mkdir($dossier_bas, 0777, true);
        }
        $vignette_fichier = $dossier_bas . '/' . $this->nom_fichier;
        file_put_contents($vignette_fichier, 'vignette factice');

        try {
            $plein = 'players_pics/' . $this->nom_fichier;
            $vignette = 'players_pics_low/' . $this->nom_fichier;
            $resultat = Players::adjust_photo_path_from_results([
                ['path_photo' => $plein, 'path_photo_low' => $vignette, 'sexe' => 'M'],
            ]);

            self::assertSame($vignette, $resultat[0]['path_photo_low']);
        } finally {
            unlink($vignette_fichier);
        }
    }

    public function test_une_photo_absente_est_remplacee_selon_le_sexe(): void
    {
        $absent = 'players_pics/ce_fichier_n_existe_pas_295.jpg';
        $resultat = Players::adjust_photo_path_from_results([
            ['path_photo' => $absent, 'path_photo_low' => $absent, 'sexe' => 'M'],
            ['path_photo' => $absent, 'path_photo_low' => $absent, 'sexe' => 'F'],
        ]);

        self::assertSame('images/MaleMissingPhoto.png', $resultat[0]['path_photo']);
        self::assertSame('images/MaleMissingPhoto.png', $resultat[0]['path_photo_low']);
        self::assertSame('images/FemaleMissingPhoto.png', $resultat[1]['path_photo']);
        self::assertSame('images/FemaleMissingPhoto.png', $resultat[1]['path_photo_low']);
    }

    public function test_un_chemin_vide_est_remplace(): void
    {
        $resultat = Players::adjust_photo_path_from_results([
            ['path_photo' => '', 'path_photo_low' => '', 'sexe' => 'F'],
        ]);

        self::assertSame('images/FemaleMissingPhoto.png', $resultat[0]['path_photo']);
    }

    /**
     * L'index est constitué **par répertoire** : `photos.path_photo` pointe soit
     * dans `players_pics` (4 523 lignes), soit dans `teams_pics` (50). Une seule
     * lecture de `players_pics` ne doit pas faire passer les chemins de
     * `teams_pics` pour manquants, ni l'inverse.
     */
    public function test_les_deux_repertoires_sont_indexes_separement(): void
    {
        $present = 'players_pics/' . $this->nom_fichier;
        $absent = 'teams_pics/ce_fichier_n_existe_pas_295.png';

        $resultat = Players::adjust_photo_path_from_results([
            ['path_photo' => $absent, 'path_photo_low' => $absent, 'sexe' => 'M'],
            ['path_photo' => $present, 'path_photo_low' => $present, 'sexe' => 'M'],
            ['path_photo' => $absent, 'path_photo_low' => $absent, 'sexe' => 'F'],
        ]);

        self::assertSame('images/MaleMissingPhoto.png', $resultat[0]['path_photo']);
        self::assertSame($present, $resultat[1]['path_photo'], 'le chemin présent doit survivre');
        self::assertSame('images/FemaleMissingPhoto.png', $resultat[2]['path_photo']);
    }

    /**
     * Garde-fou de méthode.
     *
     * Un retour au `file_exists()` par ligne ne casserait aucun test de
     * comportement — il rendrait simplement l'endpoint quatre fois plus lent,
     * ce qui ne se voit qu'en chronométrant. D'où cette vérification sur la
     * source : la fonction doit passer par l'index de répertoire.
     */
    public function test_aucun_acces_disque_par_ligne(): void
    {
        $source = file_get_contents(__DIR__ . '/../classes/Players.php');
        $debut = strpos($source, 'public static function adjust_photo_path_from_results');
        self::assertNotFalse($debut, 'la méthode a été renommée ?');
        $corps = substr($source, $debut, 1200);

        self::assertStringNotContainsString(
            'file_exists',
            $corps,
            "adjust_photo_path_from_results ne doit pas appeler file_exists : c'est\n"
            . "un accès disque par joueur, soit 3 651 pour un appel à getPlayers.\n"
            . "Passer par photoFileExists(), qui indexe chaque répertoire une fois.\n"
        );
        self::assertStringContainsString('photoFileExists', $corps);
    }
}
