<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

/**
 * Issue #288 — cohérence entre les écrans de l'admin Vue et l'API PHP.
 *
 * Trois fois de suite, un écran a été livré avec un formulaire qui n'envoyait
 * pas tous les paramètres **obligatoires** de la méthode appelée. Le routeur
 * REST appelant les méthodes avec des arguments nommés, l'enregistrement
 * échoue alors en 500 (`ArgumentCountError`), et rien ne le signale avant
 * qu'un utilisateur essaie :
 *
 * - `matchmgr/saveMatch` (`parent_code_competition`, `id_journee`) — lot 3
 * - `player/savePlayer` (six paramètres sur quinze) — reste jusqu'à #288,
 *   soit depuis le lot 1
 *
 * Ce test ferme la classe de défaut : il lit les écrans, en extrait les champs
 * postés, et les compare aux signatures PHP par réflexion.
 *
 * Pas de `UfolepTestCase` : rien à lire d'autre que des fichiers.
 */
class AdminScreensTest extends TestCase
{
    /** Préfixe d'URL REST → classe PHP, comme le fait `rest/action.php`. */
    private const CLASSES = [
        'activity' => 'Activity',
        'bilan' => 'Bilan',
        'blacklistcourt' => 'BlackListCourt',
        'blacklistdate' => 'BlackListDate',
        'blacklistteam' => 'BlackListTeam',
        'blacklistteams' => 'BlackListTeams',
        'calendarevents' => 'CalendarEvents',
        'club' => 'Club',
        'commission' => 'Commission',
        'competition' => 'Competition',
        'court' => 'Court',
        'emails' => 'Emails',
        'halloffame' => 'HallOfFame',
        'limitdate' => 'LimitDate',
        'matchmgr' => 'MatchMgr',
        'news' => 'News',
        'player' => 'Players',
        'rank' => 'Rank',
        'register' => 'Register',
        'registry' => 'Registry',
        'team' => 'Team',
        'timeslot' => 'TimeSlot',
        'usermanager' => 'UserManager',
    ];

    /**
     * Le cœur du ticket : aucun écran ne doit oublier un paramètre obligatoire.
     */
    public function test_chaque_formulaire_envoie_les_parametres_obligatoires(): void
    {
        $problemes = [];
        foreach ($this->screens() as $file => $src) {
            if (!preg_match('#save-url="/rest/action\.php/([a-z]+)/([a-zA-Z_]+)"#', $src, $m)) {
                continue;
            }
            [, $classKey, $action] = $m;
            $method = $this->reflect($classKey, $action, $file, $problemes);
            if ($method === null) {
                continue;
            }
            $sent = $this->postedFields($src);
            foreach ($method->getParameters() as $parameter) {
                if ($parameter->isOptional() || $parameter->isVariadic()) {
                    continue;
                }
                if (!in_array($parameter->getName(), $sent, true)) {
                    $problemes[] = sprintf(
                        '%s : %s/%s exige $%s, que le formulaire n\'envoie pas',
                        $file,
                        $classKey,
                        $action,
                        $parameter->getName()
                    );
                }
            }
        }

        self::assertSame(
            [],
            $problemes,
            "Formulaires incomplets — l'enregistrement échouera en 500 :\n"
            . implode("\n", $problemes)
            . "\n\nCorriger en donnant une valeur par défaut aux paramètres PHP "
            . "que l'écran n'a pas à envoyer, ou en déclarant le champ manquant "
            . "(`hidden: true` s'il ne doit pas s'afficher)."
        );
    }

    /**
     * Le câblage lui-même : `AdminGrid` doit transmettre son `id-field` au
     * formulaire (issue #299).
     *
     * Ce test existe parce que le contrôle « champ posté inconnu » ci-dessous
     * **ne suffit pas**, ce que j'ai constaté en le vérifiant : `postedFields()`
     * déduit l'identifiant du `id-field` **déclaré par l'écran**, donc il
     * modélise l'intention, pas la réalité. Retirer la liaison dans `AdminGrid`
     * ne change aucune source d'écran, et le contrôle reste vert alors que trois
     * écrans sont en 500.
     *
     * D'où cette assertion structurelle, la seule qui morde sur ce défaut :
     * c'est la grille qui rend le formulaire, c'est donc elle qui doit lui
     * passer l'identifiant.
     */
    public function test_la_grille_transmet_son_id_field_au_formulaire(): void
    {
        $grille = file_get_contents(__DIR__ . '/../admin/components/grid/AdminGrid.js');

        $debut = strpos($grille, '<admin-edit-modal');
        self::assertNotFalse($debut, 'la fenêtre d\'édition a été renommée ?');
        $balise = substr($grille, $debut, (int)strpos($grille, '/>', $debut) - $debut);

        self::assertStringContainsString(
            ':id-field="idField"',
            $balise,
            "AdminGrid doit passer son id-field a AdminEditModal.\n"
            . "Sans cette liaison le formulaire retombe sur son defaut 'id' et\n"
            . "poste un parametre que la methode PHP ne declare pas : 500 pour\n"
            . "LimitDates, Matches et Teams, duplication silencieuse pour\n"
            . "Commission (methode variadique, donc pas d'erreur).\n"
        );
    }

    /**
     * Le contrôle **inverse**, et c'est celui qui manquait (issue #299).
     *
     * Le routeur appelle les méthodes en arguments nommés. Il y a donc deux
     * façons d'échouer, pas une :
     *
     *  - un paramètre obligatoire non envoyé -> `ArgumentCountError` ;
     *  - un champ envoyé que la méthode ne déclare pas -> `Error : Unknown
     *    named parameter`.
     *
     * Le test ne couvrait que la première. La seconde a laissé passer un défaut
     * qui mettait **trois écrans en 500** — dates limites, matchs, équipes — et
     * en faisait **dupliquer un quatrième** en silence : `AdminGrid` ne
     * transmettait pas son `id-field` au formulaire, qui postait donc `id` là
     * où la méthode attend `id_date`, `id_match` ou `id_equipe`.
     *
     * Les méthodes **variadiques** sont exclues : `Generic::save_with_args()`
     * collecte tout argument nommé inconnu, elle ne peut pas échouer ainsi.
     * C'est d'ailleurs pourquoi l'écran Commission dupliquait au lieu de
     * planter — un silence plus coûteux qu'une erreur.
     */
    public function test_aucun_champ_poste_n_est_inconnu_de_la_methode(): void
    {
        $problemes = [];
        foreach ($this->screens() as $file => $src) {
            if (!preg_match('#save-url="/rest/action\.php/([a-z]+)/([a-zA-Z_]+)"#', $src, $m)) {
                continue;
            }
            [, $classKey, $action] = $m;
            $method = $this->reflect($classKey, $action, $file, $problemes);
            if ($method === null) {
                continue;
            }
            $variadique = false;
            $acceptes = [];
            foreach ($method->getParameters() as $parameter) {
                $acceptes[] = $parameter->getName();
                $variadique = $variadique || $parameter->isVariadic();
            }
            if ($variadique) {
                continue;
            }
            foreach ($this->postedFields($src) as $champ) {
                if (!in_array($champ, $acceptes, true)) {
                    $problemes[] = sprintf(
                        '%s : %s/%s ne déclare pas $%s, que le formulaire envoie',
                        $file,
                        $classKey,
                        $action,
                        $champ
                    );
                }
            }
        }

        self::assertSame(
            [],
            $problemes,
            "Champs postés inconnus — l'enregistrement échouera en 500 :\n"
            . implode("\n", $problemes)
            . "\n\nCorriger en renommant le champ de l'écran, ou en ajoutant le "
            . "paramètre à la méthode PHP avec une valeur par défaut."
        );
    }

    /**
     * Garde-fou plus large : toute action citée par un écran doit exister comme
     * méthode publique. Un `delete-url` mal orthographié répondrait 403 (refus
     * par défaut) et passerait pour un problème de droits.
     */
    public function test_chaque_action_citee_par_un_ecran_existe(): void
    {
        $problemes = [];
        foreach ($this->screens() as $file => $src) {
            if (!preg_match_all('#action\.php/([a-z]+)/([a-zA-Z_]+)#', $src, $matches, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($matches as [, $classKey, $action]) {
                $this->reflect($classKey, $action, $file, $problemes);
            }
        }
        self::assertSame([], $problemes, implode("\n", $problemes));
    }

    /** @return array<string, string> nom de fichier => source */
    private function screens(): array
    {
        $screens = [];
        foreach (glob(__DIR__ . '/../admin/components/screens/*.js') as $path) {
            $screens[basename($path)] = file_get_contents($path);
        }
        self::assertNotEmpty($screens, 'Aucun écran trouvé dans admin/components/screens/');
        return $screens;
    }

    /**
     * Champs postés par le formulaire d'un écran.
     *
     * Deux formes cohabitent : la déclaration explicite `name: 'x'`, et la
     * liste de champs cachés de `Registrations.js`, un tableau de chaînes
     * étalé dans `fields` (`...caches.map((name) => ({ name, hidden: true }))`).
     * `AdminEditModal` envoie toujours en plus l'identifiant, et les méthodes
     * PHP acceptent `dirtyFields` par héritage d'ExtJS.
     *
     * @return string[]
     */
    private function postedFields(string $src): array
    {
        preg_match_all("#name: '([a-zA-Z_0-9]+)'#", $src, $explicites);
        $fields = $explicites[1];

        if (str_contains($src, 'hidden: true')) {
            // Tableaux de chaînes simples : la liste des champs transportés.
            if (preg_match_all('#\[\s*((?:\'[a-z_0-9]+\',?\s*)+)\]#', $src, $tableaux)) {
                foreach ($tableaux[1] as $contenu) {
                    preg_match_all("#'([a-z_0-9]+)'#", $contenu, $noms);
                    $fields = array_merge($fields, $noms[1]);
                }
            }
        }

        $idField = preg_match('#id-field="([a-z_]+)"#', $src, $m) ? $m[1] : 'id';
        $fields[] = $idField;
        $fields[] = 'dirtyFields';

        // Les champs FICHIER ne sont pas des arguments nommés : `FormData`
        // transporte l'objet `File`, PHP le range dans `$_FILES` et non dans
        // `$_POST`, et le routeur ne construit ses arguments que depuis
        // `$_POST`. `Players::save()` récupère la photo par `$_FILES` en fin de
        // course. Les compter fausserait les deux sens du contrôle.
        $fichiers = array();
        if (preg_match_all("#name: '([a-zA-Z_0-9]+)'[^}]*type: 'file'#", $src, $m)) {
            $fichiers = $m[1];
        }

        return array_values(array_diff(array_unique($fields), $fichiers));
    }

    /**
     * Réflexion sur une action REST. Empile un problème et renvoie null si la
     * classe ou la méthode manque.
     */
    private function reflect(
        string $classKey,
        string $action,
        string $file,
        array &$problemes
    ): ?ReflectionMethod {
        $className = self::CLASSES[$classKey] ?? null;
        if ($className === null) {
            $problemes[] = "$file : préfixe REST « $classKey » inconnu de AdminScreensTest::CLASSES";
            return null;
        }
        $path = __DIR__ . "/../classes/$className.php";
        if (!file_exists($path)) {
            $problemes[] = "$file : classes/$className.php introuvable";
            return null;
        }
        require_once $path;
        if (!method_exists($className, $action)) {
            $problemes[] = "$file : $className::$action() n'existe pas";
            return null;
        }
        return new ReflectionMethod($className, $action);
    }
}
