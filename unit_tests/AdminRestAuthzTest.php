<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . "/../classes/Generic.php";

/**
 * Issue #268 — garde d'autorisation des endpoints REST d'administration
 * et assainissement des listes d'ids.
 *
 * Les tests HTTP de bout en bout (403 pour un anonyme) sont dans
 * e2e/tests/issue_268.spec.js : ici on couvre ce qui est testable sans serveur,
 * c'est-à-dire le découpage des ids et la cohérence de la liste d'endpoints.
 */
class AdminRestAuthzTest extends UfolepTestCase
{
    public function test_parse_id_list_accepte_une_liste_simple(): void
    {
        self::assertSame([1, 2, 3], Generic::parse_id_list('1,2,3'));
    }

    public function test_parse_id_list_tolere_les_espaces_et_les_doublons(): void
    {
        self::assertSame([4, 5], Generic::parse_id_list(' 4 , 5 , 4 '));
    }

    public function test_parse_id_list_accepte_un_tableau_et_un_entier(): void
    {
        self::assertSame([7, 8], Generic::parse_id_list([7, '8']));
        self::assertSame([9], Generic::parse_id_list(9));
    }

    public function test_parse_id_list_vide_pour_une_entree_absente(): void
    {
        self::assertSame([], Generic::parse_id_list(null));
        self::assertSame([], Generic::parse_id_list(''));
    }

    /**
     * Le cœur de la faille : une charge d'injection ne doit produire aucun id.
     * Avant le correctif, `$ids` partait brut dans `... WHERE id IN($ids)`,
     * donc `-1) OR (1=1` vidait la table.
     */
    public function test_parse_id_list_neutralise_les_charges_d_injection(): void
    {
        $payloads = [
            '-1) OR (1=1',
            '1); DROP TABLE gymnase; --',
            "1' OR '1'='1",
            'abc',
            '1,2) OR (1=1',
        ];
        foreach ($payloads as $payload) {
            $parsed = Generic::parse_id_list($payload);
            foreach ($parsed as $id) {
                self::assertIsInt($id, "La charge « $payload » a produit un id non entier");
            }
        }
        // Aucune de ces charges ne doit passer telle quelle
        self::assertSame([], Generic::parse_id_list('-1) OR (1=1'));
        self::assertSame([], Generic::parse_id_list('abc'));
        // Le préfixe numérique d'une charge mixte est conservé, le reste tombe
        self::assertSame([1], Generic::parse_id_list('1,2) OR (1=1'));
    }

    /**
     * `getUsers` renvoyait `password_hash` à qui le demandait. Le champ n'est ni
     * affiché ni édité par l'admin : il n'a aucune raison de sortir de la base.
     */
    public function test_get_users_n_expose_pas_l_empreinte_du_mot_de_passe(): void
    {
        $sql = file_get_contents(__DIR__ . '/../sql/get_users.sql');
        self::assertNotFalse($sql, 'sql/get_users.sql introuvable');
        self::assertStringNotContainsString('password_hash', $sql);
    }

    public function test_la_liste_d_acces_est_bien_formee(): void
    {
        $access = require __DIR__ . '/../rest/access.php';
        self::assertIsArray($access);
        self::assertNotEmpty($access);
        foreach ($access as $class_name => $actions) {
            self::assertIsString($class_name);
            self::assertIsArray($actions, "Les actions de $class_name doivent être un tableau");
            self::assertNotEmpty($actions, "$class_name ne doit pas avoir une liste vide");
            foreach ($actions as $action => $level) {
                self::assertContains(
                    $level,
                    ['public', 'user', 'admin'],
                    "Niveau inconnu « $level » pour $class_name/$action"
                );
            }
        }
    }

    /**
     * Le cœur du correctif #270 : le routeur dispatche n'importe quelle méthode
     * publique des classes routées. Ces actions-là ne doivent JAMAIS être
     * joignables — `sqlmanager/execute` exécutait du SQL arbitraire sans
     * authentification.
     */
    public function test_les_actions_dangereuses_ne_sont_pas_listees(): void
    {
        $access = require __DIR__ . '/../rest/access.php';
        $interdits = [
            ['sqlmanager', 'execute'],
            ['generic', 'save'],
            ['generic', 'delete'],
            ['generic', 'save_with_args'],
            ['usermanager', 'remove'],
            ['usermanager', 'createUser'],
            ['matchmgr', 'generate_matches'],
            // Génération retirée du PHP (#279) : elle vit dans les scripts
            // Python de `ufolep13volley_python`. Ces actions supprimaient les
            // matchs existants avant de régénérer — les rouvrir permettrait
            // d'écraser le calendrier produit par Python.
            ['matchmgr', 'generateAll'],
            ['matchmgr', 'generateMatches'],
            ['competition', 'generate_matches_final_phase_cup'],
            ['day', 'getDays'],
            ['rank', 'resetRankPoints'],
            ['emails', 'send_pending_emails'],
        ];
        foreach ($interdits as [$class_name, $action]) {
            self::assertArrayNotHasKey(
                $action,
                $access[$class_name] ?? [],
                "$class_name/$action ne doit pas être joignable via l'API"
            );
        }
    }

    /**
     * Garde-fou de non-régression le plus utile du lot : tout endpoint référencé
     * par un frontend DOIT être déclaré dans `rest/access.php`, sinon il répond
     * 403 en production alors que l'écran l'appelle.
     *
     * C'est exactement le bug qui a échappé à la première version : `rank/getRank`
     * est construit dynamiquement (`/rest/action.php/rank/${endpoint}`) et n'a donc
     * pas été vu par un grep sur les littéraux — la page de classement était cassée.
     */
    public function test_tous_les_endpoints_appeles_par_les_frontends_sont_declares(): void
    {
        $access = require __DIR__ . '/../rest/access.php';
        $root = realpath(__DIR__ . '/..');

        $referenced = [];
        foreach ($this->frontendFiles($root) as $file) {
            $content = file_get_contents($file);
            if (preg_match_all('#action\.php/([a-z]+)/([a-zA-Z_]+)#', $content, $m, PREG_SET_ORDER)) {
                foreach ($m as $hit) {
                    $referenced[$hit[1] . '/' . $hit[2]] = str_replace($root, '', $file);
                }
            }
        }

        // Actions construites dynamiquement : invisibles a un grep sur les
        // litteraux. Les tenir a jour ici fait echouer le test si l'on en ajoute
        // une sans la declarer dans access.php.
        $dynamiques = [
            // pages/components/table/Rank.js  ->  /rest/action.php/rank/${endpoint}
            'rank/getRank', 'rank/getRankFFVB', 'rank/addPenalty', 'rank/removePenalty',
            'rank/incrementReportCount', 'rank/decrementReportCount',
            // pages/components/panel/Timeslots.js  ->  /rest/action.php/timeslot/${action}
            'timeslot/saveTimeSlot', 'timeslot/removeTimeSlot',
            // pages/components/panel/Players.js  ->  /rest/action.php/player/${action}
            'player/set_leader', 'player/set_vice_leader', 'player/set_captain',
            'player/remove_from_team',
            // js/controller/manage_register.js  ->  'rest/action.php/register/' + action
            // admin/components/screens/Registrations.js  ->  `/rest/action.php/register/${action}`
            'register/validateRegistration', 'register/unvalidateRegistration',
            'register/fill_ranks', 'register/create_teams_and_accounts',
        ];
        foreach ($dynamiques as $key) {
            $referenced[$key] = $referenced[$key] ?? '(URL construite dynamiquement)';
        }

        $manquants = [];
        foreach ($referenced as $key => $origin) {
            [$class_name, $action] = explode('/', $key, 2);
            if (!isset($access[$class_name][$action])) {
                $manquants[] = "$key  (appelé depuis $origin)";
            }
        }

        self::assertSame(
            [],
            $manquants,
            'Endpoints appelés par un frontend mais absents de rest/access.php : '
            . implode(' | ', $manquants)
        );
    }

    /**
     * La liste `$dynamiques` du test précédent est tenue à la main, donc elle
     * dérive : `register/validateRegistration` y manquait, et les boutons
     * « Valider / Dévalider » des inscriptions répondaient 403 depuis le refus
     * par défaut (#272) sans que personne le voie.
     *
     * Ce test rend l'oubli mécanique : il repère les URLs construites
     * dynamiquement et exige que la classe appelée soit couverte par au moins
     * une entrée de `$dynamiques`. Ajouter un nouveau site d'appel dynamique
     * fait donc échouer la suite tant que ses actions ne sont pas énumérées.
     */
    public function test_toute_classe_appelee_dynamiquement_est_enumeree(): void
    {
        $root = realpath(__DIR__ . '/..');
        $classes_dynamiques = [];
        foreach ($this->frontendFiles($root) as $file) {
            $content = file_get_contents($file);
            // action.php/<classe>/ suivi d'une interpolation ou d'une
            // concaténation : `${...}`, `' + x` ou `" + x`
            if (preg_match_all(
                '#action\.php/([a-z]+)/(?:\$\{|\'\s*\+|"\s*\+)#',
                $content,
                $m,
                PREG_SET_ORDER
            )) {
                foreach ($m as $hit) {
                    $classes_dynamiques[$hit[1]] = str_replace($root, '', $file);
                }
            }
            // /rest/action.php/${classe}/... : la classe elle-même est calculée
            if (preg_match('#action\.php/\$\{#', $content)) {
                $classes_dynamiques['(classe calculée)'] = str_replace($root, '', $file);
            }
        }

        $enumerees = [];
        foreach ($this->actionsDynamiquesEnumerees() as $key) {
            [$class_name] = explode('/', $key, 2);
            $enumerees[$class_name] = true;
        }

        $non_couvertes = [];
        foreach ($classes_dynamiques as $class_name => $origin) {
            if (!isset($enumerees[$class_name])) {
                $non_couvertes[] = "$class_name  (appelé dynamiquement depuis $origin)";
            }
        }

        self::assertSame(
            [],
            $non_couvertes,
            "Classes appelées via une URL construite dynamiquement mais absentes de "
            . "la liste \$dynamiques : " . implode(' | ', $non_couvertes)
        );
    }

    /**
     * Actions dont l'URL est construite dynamiquement côté frontend, donc
     * invisibles à un grep sur les littéraux. Doit rester synchronisée avec la
     * liste locale de test_tous_les_endpoints_appeles_par_les_frontends_sont_declares.
     *
     * @return string[]
     */
    private function actionsDynamiquesEnumerees(): array
    {
        return [
            'rank/getRank', 'rank/getRankFFVB', 'rank/addPenalty', 'rank/removePenalty',
            'rank/incrementReportCount', 'rank/decrementReportCount',
            'timeslot/saveTimeSlot', 'timeslot/removeTimeSlot',
            'player/set_leader', 'player/set_vice_leader', 'player/set_captain',
            'player/remove_from_team',
            'register/validateRegistration', 'register/unvalidateRegistration',
            'register/fill_ranks', 'register/create_teams_and_accounts',
        ];
    }

    /**
     * @return string[] fichiers JS des frontends (SPA publique, admin Vue, ExtJS)
     */
    private function frontendFiles(string $root): array
    {
        $files = [];
        foreach (['pages', 'admin', 'js'] as $dir) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator("$root/$dir", FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $f) {
                if ($f->isFile() && $f->getExtension() === 'js') {
                    $files[] = $f->getPathname();
                }
            }
        }
        foreach (glob("$root/*.js") as $f) {
            $files[] = $f;
        }
        return $files;
    }

    /**
     * Actions d'administration dépourvues de contrôle de rôle interne : elles
     * dépendent entièrement du niveau déclaré ici.
     */
    public function test_les_ecritures_sans_garde_interne_sont_en_admin(): void
    {
        $access = require __DIR__ . '/../rest/access.php';
        $attendus = [
            ['matchmgr', 'certify_match'],
            ['rank', 'saveFinalsHostDraw'],
            ['rank', 'saveFullFinalsDraw'],
            ['rank', 'saveCupPoolAssignments'],
        ];
        foreach ($attendus as [$class_name, $action]) {
            self::assertSame(
                'admin',
                $access[$class_name][$action] ?? null,
                "$class_name/$action doit être réservé aux administrateurs"
            );
        }
    }

    /**
     * Garde-fou de non-régression : ces actions ressemblent à des actions
     * d'administration mais sont appelées par l'espace responsable d'équipe
     * (pages/components/panel/Players.js, URL construite dynamiquement).
     * Les ajouter à la liste casserait la gestion des joueurs par les responsables.
     */
    public function test_les_actions_du_responsable_d_equipe_ne_sont_pas_reservees_aux_admins(): void
    {
        $access = require __DIR__ . '/../rest/access.php';
        foreach (['set_leader', 'set_captain', 'set_vice_leader', 'remove_from_team'] as $action) {
            self::assertSame(
                'user',
                $access['player'][$action] ?? null,
                "player/$action est utilisé par l'espace responsable d'équipe : niveau 'user' attendu"
            );
        }
    }
}
