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

        return array_values(array_unique($fields));
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
