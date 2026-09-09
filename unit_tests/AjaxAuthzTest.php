<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

/**
 * Issue #284 — contrôle d'accès des endpoints de `ajax/`.
 *
 * Le refus par défaut de #270 vit dans `rest/action.php` : il ne couvre que les
 * actions de `rest/access.php`. Les fichiers de `ajax/` sont des points d'entrée
 * HTTP à part entière, sans routeur et sans garde commune — `indicators.php` a
 * ainsi servi 47 requêtes d'administration à des anonymes pendant des mois,
 * dont toutes les adresses des responsables et 180 comptes.
 *
 * Ce test n'a rien d'astucieux : il liste les fichiers du répertoire et exige
 * que chacun soit soit **explicitement assumé public**, soit porteur d'un
 * contrôle d'accès. Le point est qu'un nouveau fichier déposé dans `ajax/`
 * fasse échouer la suite tant que la question n'a pas été tranchée.
 *
 * Pas de `UfolepTestCase` ici : rien à inspecter d'autre que des fichiers, et
 * `UfolepTestCase` instancie un `SqlManager` dans son constructeur.
 */
class AjaxAuthzTest extends TestCase
{
    /**
     * Endpoints publics assumés, avec la raison. Ajouter une entrée ici est une
     * décision : elle veut dire « ce fichier peut répondre à un anonyme ».
     */
    private const PUBLICS = [
        // Page live d'un match, ouverte à tous. Les écritures sont gardées dans
        // le fichier (admin ou responsable de l'équipe, 403 sinon).
        'live_score.php',
        // Carrousel de la page d'accueil : ne renvoie que des photos déjà
        // publiques de la page Facebook de l'association.
        'getVolleyballImages.php',
    ];

    /** Marqueurs acceptés comme contrôle d'accès. */
    private const MARQUEURS = [
        'UserManager::isAdmin',
        'UserManager::isConnected',
        'UserManager::is_connected',
        'isClubLeader',
        'isTeamLeader',
    ];

    public function test_chaque_endpoint_ajax_est_garde_ou_assume_public(): void
    {
        $dir = realpath(__DIR__ . '/../ajax');
        self::assertNotFalse($dir, 'Le répertoire ajax/ est introuvable');

        $sans_garde = [];
        foreach (glob("$dir/*.php") as $file) {
            $nom = basename($file);
            if (in_array($nom, self::PUBLICS, true)) {
                continue;
            }
            $contenu = file_get_contents($file);
            $garde = false;
            foreach (self::MARQUEURS as $marqueur) {
                if (str_contains($contenu, $marqueur)) {
                    $garde = true;
                    break;
                }
            }
            if (!$garde) {
                $sans_garde[] = $nom;
            }
        }

        self::assertSame(
            [],
            $sans_garde,
            "Endpoints de ajax/ sans contrôle d'accès et non listés comme publics : "
            . implode(', ', $sans_garde)
            . ". Soit y ajouter une garde, soit les inscrire dans AjaxAuthzTest::PUBLICS "
            . "en expliquant pourquoi ils peuvent répondre à un anonyme."
        );
    }

    /**
     * Garde-fou ciblé : celui qui a fuité. Une garde retirée par mégarde d'un
     * `git revert` ou d'un refactor doit faire rougir la suite.
     */
    public function test_les_indicateurs_sont_reserves_aux_administrateurs(): void
    {
        $contenu = file_get_contents(__DIR__ . '/../ajax/indicators.php');
        self::assertNotFalse($contenu, 'ajax/indicators.php est introuvable');
        self::assertStringContainsString('UserManager::isAdmin', $contenu);
        // La garde doit précéder l'exécution des requêtes, pas la suivre.
        $position_garde = strpos($contenu, 'UserManager::isAdmin');
        $position_requetes = strpos($contenu, 'new Indicator(');
        self::assertNotFalse($position_requetes, 'Les indicateurs ne sont plus construits ici ?');
        self::assertLessThan(
            $position_requetes,
            $position_garde,
            "Le contrôle d'accès doit être fait avant de construire les indicateurs"
        );
    }

    /**
     * `getImageFromText.php` rendait du texte base64 en image pour masquer les
     * adresses des robots, usage abandonné. Il était ouvert et allouait une
     * image dimensionnée sur l'entrée (`15 * strlen($string)`).
     */
    public function test_l_endpoint_image_depuis_texte_a_bien_disparu(): void
    {
        self::assertFileNotExists(
            __DIR__ . '/../ajax/getImageFromText.php',
            "Supprimé par #284 : sans appelant, ouvert, et allouant une image "
            . "dimensionnée sur l'entrée."
        );
    }
}
