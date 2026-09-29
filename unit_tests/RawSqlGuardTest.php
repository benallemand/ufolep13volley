<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../rest/raw_sql_guard.php';

/**
 * Issue #354 — injections SQL sans connexion par les arguments « SQL brut »
 * des méthodes routées (`Generic::get($query)` et consorts), et correctifs
 * ciblés de `team/getTeam`, `rank/getDivisionsFromCompetition`, `club/get`.
 *
 * Tous les tests sont en lecture seule.
 */
class RawSqlGuardTest extends UfolepTestCase
{
    /**
     * Classe routée (segment d'URL) => classe PHP, lue dans le `switch` de
     * `rest/action.php` pour ne pas maintenir une seconde liste.
     */
    private static function routed_classes(): array
    {
        $source = file_get_contents(__DIR__ . '/../rest/action.php');
        preg_match_all("/case '(\\w+)':\\s*require_once __DIR__ \\. \"\\/\\.\\.\\/classes\\/(\\w+)\\.php\";\\s*\\\$manager = new (\\w+)\\(/",
            $source, $matches, PREG_SET_ORDER);
        $routes = array();
        foreach ($matches as $m) {
            require_once __DIR__ . '/../classes/' . $m[2] . '.php';
            $routes[$m[1]] = $m[3];
        }
        return $routes;
    }

    private function assert_refused(string $class, string $action, array $parameters): void
    {
        try {
            assert_no_raw_sql_parameter($class, $action, $parameters);
            self::fail("$class::$action devait refuser " . json_encode(array_keys($parameters)));
        } catch (Exception $e) {
            self::assertSame(400, $e->getCode(), $e->getMessage());
        }
    }

    public function test_le_garde_est_branche_sur_get_et_post(): void
    {
        $source = file_get_contents(__DIR__ . '/../rest/action.php');
        self::assertSame(2, substr_count($source, 'assert_no_raw_sql_parameter($manager, $action_name, $parameters);'));
    }

    public function test_les_cinq_endpoints_du_ticket_refusent_une_clause(): void
    {
        $routes = self::routed_classes();
        $this->assert_refused($routes['club'], 'get', array('query' => '1=0'));
        $this->assert_refused($routes['commission'], 'get', array('query' => '1=0'));
        $this->assert_refused($routes['competition'], 'getCompetitions', array('query' => '1=0'));
        $this->assert_refused($routes['club'], 'get', array('bindings' => array()));
    }

    public function test_une_cle_numerique_est_refusee(): void
    {
        // `?0=...` : PHP passerait la valeur en premier argument positionnel, `$query`.
        $routes = self::routed_classes();
        $this->assert_refused($routes['club'], 'get', array(0 => '1=0'));
        $this->assert_refused($routes['team'], 'getTeam', array(0 => '1'));
    }

    public function test_les_termes_de_recherche_restent_acceptes(): void
    {
        $routes = self::routed_classes();
        assert_no_raw_sql_parameter($routes['matchmgr'], 'getReinforcementPlayers', array('id_match' => '1', 'query' => 'dupont'));
        assert_no_raw_sql_parameter($routes['player'], 'getPlayers', array('query' => 'dupont'));
        assert_no_raw_sql_parameter($routes['team'], 'getTeam', array('id' => '12'));
        $this->addToAssertionCount(3);
    }

    /**
     * Toute action déclarée dans `rest/access.php` dont la méthode porte un
     * argument SQL brut doit être protégée — une future méthode `get*($query)`
     * comprise, sans rien déclarer.
     */
    public function test_toute_action_routable_avec_un_argument_sql_brut_est_protegee(): void
    {
        $routes = self::routed_classes();
        $access = require __DIR__ . '/../rest/access.php';
        $checked = 0;
        foreach ($access as $segment => $actions) {
            if (!isset($routes[$segment])) {
                continue;
            }
            foreach (array_keys($actions) as $action) {
                if (!method_exists($routes[$segment], $action)) {
                    continue;
                }
                $method = new ReflectionMethod($routes[$segment], $action);
                $key = $method->getDeclaringClass()->getName() . '::' . $action;
                foreach ($method->getParameters() as $parameter) {
                    $name = $parameter->getName();
                    if (!in_array($name, RAW_SQL_PARAMETER_NAMES, true)
                        || in_array($name, SEARCH_TERM_PARAMETERS[$key] ?? SEARCH_TERM_PARAMETERS[$routes[$segment] . '::' . $action] ?? array(), true)) {
                        continue;
                    }
                    $this->assert_refused($routes[$segment], $action, array($name => '1=1'));
                    $checked++;
                }
            }
        }
        // club/get, commission/get, getCompetitions, getTeams, getRanks, getTimeSlots, player/get…
        self::assertGreaterThanOrEqual(8, $checked);
    }

    // --- correctifs ciblés

    public function test_get_team_refuse_un_identifiant_non_numerique(): void
    {
        require_once __DIR__ . '/../classes/Team.php';
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Identifiant d'équipe invalide");
        (new Team())->getTeam('1 OR 1=1');
    }

    public function test_get_divisions_from_competition_lie_son_parametre(): void
    {
        require_once __DIR__ . '/../classes/Rank.php';
        $rank = new Rank();
        // Concaténée, la charge ramenait les divisions de toutes les compétitions.
        self::assertSame(array(), $rank->getDivisionsFromCompetition("zz' OR '1'='1"));
        $legit = $this->sql->execute("SELECT code_competition FROM classements LIMIT 1");
        if ($legit) {
            self::assertNotEmpty($rank->getDivisionsFromCompetition($legit[0]['code_competition']));
        }
    }

    public function test_la_liste_publique_des_clubs_ne_contient_que_l_identifiant_et_le_nom(): void
    {
        require_once __DIR__ . '/../classes/Club.php';
        $clubs = (new Club())->getClubList();
        foreach ($clubs as $club) {
            self::assertSame(array('id', 'nom'), array_keys($club));
        }
        $access = require __DIR__ . '/../rest/access.php';
        self::assertSame('admin', $access['club']['get']);
        self::assertSame('public', $access['club']['getClubList']);
    }
}
