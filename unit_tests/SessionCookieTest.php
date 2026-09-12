<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

/**
 * Issue #292 — attributs du cookie de session, et rendu des corps d'emails.
 *
 * Le cookie `PHPSESSID` partait sans `HttpOnly`, `Secure` ni `SameSite`. Pris
 * isolément c'eût été de la défense en profondeur ; ici une chaîne d'XSS stocké
 * existait bel et bien, du nom d'équipe non échappé jusqu'au `v-html` du
 * journal des messages, et l'attribut manquant en était le dernier verrou.
 *
 * Le correctif tient en trois pièces, et ce test garde les trois :
 *
 *  1. `bootstrap.php` pose les attributs, et doit être inclus par tout point
 *     d'entrée susceptible d'ouvrir une session ;
 *  2. le corps d'un email ne doit plus être rendu en HTML brut ;
 *  3. les valeurs substituées dans les gabarits doivent être échappées.
 *
 * L'intérêt n'est pas de vérifier le correctif d'aujourd'hui — il est écrit —
 * mais de faire échouer la suite le jour où un huitième point d'entrée
 * apparaîtra sans bootstrap, exactement comme `ajax/indicators.php` était passé
 * au travers du refus par défaut (#284).
 *
 * Pas de `UfolepTestCase` : rien à inspecter d'autre que des fichiers, et son
 * constructeur instancie un `SqlManager`.
 */
class SessionCookieTest extends TestCase
{
    /**
     * Points d'entrée web qui n'ouvrent PAS de session, avec la raison.
     *
     * Ajouter une entrée ici est une décision : elle affirme que le fichier ne
     * touchera jamais à `$_SESSION`, ni directement ni via une classe.
     */
    private const SANS_SESSION = [
        // Redirections pures vers les routes Vue, sans aucune logique.
        'admin.php' => 'redirection vers /admin/',
        'register.php' => 'redirection vers la route Vue',
        'reset_password.php' => 'redirection vers la route Vue',
        'rank_for_cup.php' => 'redirection vers la route Vue',
        // Serveur de développement PHP intégré : jamais exposé.
        'router.php' => 'routeur du serveur PHP intégré, hors production',
        // Page d'accueil statique.
        'index.php' => 'redirection vers /pages/home.html',
        // Proxy vers les photos publiques de la page Facebook.
        'ajax/getVolleyballImages.php' => 'aucun accès à la session',
        // Le bootstrap lui-même, et la table d'autorisations qui est une donnée.
        'bootstrap.php' => 'c\'est le bootstrap',
        'rest/access.php' => 'tableau de configuration, pas un point d\'entrée',
    ];

    /** Fichiers PHP joignables par le web, hors classes et helpers. */
    private function pointsDEntree(): array
    {
        $racine = realpath(__DIR__ . '/..');
        $fichiers = array_merge(
            glob("$racine/*.php"),
            glob("$racine/ajax/*.php"),
            glob("$racine/rest/*.php")
        );
        $relatifs = [];
        foreach ($fichiers as $f) {
            $relatifs[] = str_replace('\\', '/', substr($f, strlen($racine) + 1));
        }
        sort($relatifs);
        return $relatifs;
    }

    /**
     * Le garde-fou principal : tout point d'entrée doit soit inclure le
     * bootstrap, soit être déclaré comme n'ouvrant pas de session.
     */
    public function test_tout_point_d_entree_pose_les_attributs_du_cookie(): void
    {
        $racine = realpath(__DIR__ . '/..');
        $manquants = [];

        foreach ($this->pointsDEntree() as $relatif) {
            if (array_key_exists($relatif, self::SANS_SESSION)) {
                continue;
            }
            $contenu = file_get_contents("$racine/$relatif");
            if (!str_contains($contenu, "bootstrap.php")) {
                $manquants[] = $relatif;
            }
        }

        self::assertSame(
            [],
            $manquants,
            "Ces points d'entrée n'incluent pas bootstrap.php : le cookie de session\n"
            . "y partira sans HttpOnly. Ajouter `require_once .../bootstrap.php` en\n"
            . "tête du fichier, ou le déclarer dans SANS_SESSION s'il ne touche\n"
            . "jamais à la session.\n"
        );
    }

    /**
     * Le bootstrap doit poser les trois attributs. `Secure` est conditionnel —
     * le forcer casserait les sessions en dev sur http://localhost — mais il
     * doit être calculé, pas absent.
     */
    public function test_le_bootstrap_pose_les_trois_attributs(): void
    {
        $bootstrap = file_get_contents(__DIR__ . '/../bootstrap.php');

        self::assertStringContainsString('session_set_cookie_params', $bootstrap);
        self::assertRegExp(
            "/'httponly'\s*=>\s*true/",
            $bootstrap,
            "HttpOnly doit être posé en dur : c'est le coeur du correctif"
        );
        self::assertRegExp(
            "/'samesite'\s*=>\s*'Lax'/",
            $bootstrap,
            'SameSite doit être explicite'
        );
        self::assertRegExp(
            "/'secure'\s*=>\s*ufolep_is_https\(\)/",
            $bootstrap,
            "Secure doit dépendre du protocole réel, pas être forcé"
        );
    }

    /**
     * Derrière Caddy (`reverse_proxy php:80`), PHP reçoit du HTTP en clair et
     * `\$_SERVER['HTTPS']` vaut NULL même quand le visiteur est en HTTPS. La
     * détection doit donc tenir compte de l'en-tête du proxy, sinon le cookie
     * part sans `Secure` sur biggyben et en production.
     */
    public function test_la_detection_https_tient_compte_du_proxy(): void
    {
        // Le bootstrap poserait les parametres de cookie a l'inclusion, ce qui
        // echoue en CLI (« headers already sent »). Sa constante de garde
        // neutralise ce bloc : seule la fonction de detection est declaree.
        if (!defined('UFOLEP_BOOTSTRAPPED')) {
            define('UFOLEP_BOOTSTRAPPED', true);
        }
        require_once __DIR__ . '/../bootstrap.php';

        $cas = [
            'HTTPS direct' => [['HTTPS' => 'on'], true],
            'HTTPS a off' => [['HTTPS' => 'off'], false],
            'derriere Caddy' => [['HTTP_X_FORWARDED_PROTO' => 'https'], true],
            'proxy en clair' => [['HTTP_X_FORWARDED_PROTO' => 'http'], false],
            'port 443' => [['SERVER_PORT' => '443'], true],
            'dev en clair' => [['SERVER_PORT' => '80'], false],
        ];

        $sauvegarde = $_SERVER;
        try {
            foreach ($cas as $libelle => [$serveur, $attendu]) {
                $_SERVER = $serveur;
                self::assertSame($attendu, ufolep_is_https(), "cas : $libelle");
            }
        } finally {
            $_SERVER = $sauvegarde;
        }
    }

    /**
     * Le corps d'un email ne doit jamais être rendu en HTML brut : il contient
     * des données saisies par des responsables. Les deux écrans qui l'affichent
     * passent par une iframe cloisonnée.
     */
    public function test_aucun_corps_d_email_n_est_rendu_en_html_brut(): void
    {
        $ecrans = [
            'pages/components/panel/TeamLeaderMessages.js',
            'admin/components/screens/Emails.js',
        ];

        foreach ($ecrans as $relatif) {
            $contenu = file_get_contents(__DIR__ . '/../' . $relatif);

            self::assertNotRegExp(
                '/v-html\s*=\s*"[^"]*body/',
                $contenu,
                "$relatif rend un corps d'email en HTML brut : utiliser une iframe sandbox"
            );
            self::assertRegExp(
                '/sandbox=""/',
                $contenu,
                "$relatif doit afficher le corps dans une iframe cloisonnée"
            );
        }
    }

    /**
     * Toute valeur substituée dans un gabarit d'email doit être échappée. Sans
     * cela, un nom d'équipe hostile se retrouve tel quel dans `emails.body`,
     * puis dans la boîte mail du destinataire — que notre iframe protège, mais
     * pas son client de messagerie.
     */
    public function test_les_gabarits_d_emails_echappent_leurs_valeurs(): void
    {
        $contenu = file_get_contents(__DIR__ . '/../classes/Emails.php');

        preg_match_all(
            "/str_replace\('(%[a-z_]+%)', (.+?), \\\$message\\);/",
            $contenu,
            $trouves,
            PREG_SET_ORDER
        );

        self::assertNotEmpty($trouves, 'Aucune interpolation trouvée : le motif a-t-il changé ?');

        $non_echappees = [];
        foreach ($trouves as [$tout, $cle, $valeur]) {
            if (!str_starts_with($valeur, 'self::escapeHtml')) {
                $non_echappees[] = $cle;
            }
        }

        self::assertSame(
            [],
            $non_echappees,
            "Ces valeurs sont insérées dans un gabarit HTML sans échappement :\n"
            . "utiliser self::escapeHtml(), ou self::escapeHtmlList() si le HTML\n"
            . "de la valeur est voulu (cas de teams_list, séparé par des <br/>).\n"
        );
    }
}
