<?php

/**
 * Garde du routeur REST contre les fragments SQL venus du client (issue #354).
 *
 * `rest/action.php` passe chaque paramètre de la requête comme argument nommé
 * de la méthode appelée. Or plusieurs méthodes routées prennent un morceau de
 * SQL tout fait : `Generic::get($query)` (héritée par toutes les classes),
 * `getCompetitions($query)`, `getTeams($query)`, `get_emails($where)`… Conçus
 * pour les appels internes, ils devenaient une clause WHERE libre dès que le
 * client ajoutait `?query=` — sans connexion sur `club/get`, `commission/get`,
 * `competition/getCompetitions`.
 *
 * Le garde refuse donc, pour toute action routée :
 *   - un paramètre dont le nom désigne un argument SQL brut de la méthode ;
 *   - une clé numérique : PHP la transmet en argument POSITIONNEL, ce qui
 *     remplirait `$query` sans jamais le nommer (`club/get?0=...`).
 * Seules exceptions : les méthodes où `query` est un terme de recherche, lié ou
 * échappé par la méthode elle-même.
 */

/** Noms d'argument qui portent du SQL brut dans les méthodes routées. */
const RAW_SQL_PARAMETER_NAMES = array('query', 'where', 'bindings', 'order', 'order_by', 'sql');

/** Classe::méthode => arguments de la liste ci-dessus qui sont des termes de recherche. */
const SEARCH_TERM_PARAMETERS = array(
    'MatchMgr::getReinforcementPlayers' => array('query'), // paramètre lié (#351)
    'Players::getPlayers' => array('query'),               // échappé (#270)
);

/**
 * @throws Exception 400 si la requête tente de fournir un argument SQL brut
 */
function assert_no_raw_sql_parameter(object|string $manager, string $action_name, array $parameters): void
{
    $class_name = is_object($manager) ? get_class($manager) : $manager;
    $method = new ReflectionMethod($class_name, $action_name);
    $method_parameters = array_map(fn(ReflectionParameter $p) => $p->getName(), $method->getParameters());
    $allowed = SEARCH_TERM_PARAMETERS[$method->getDeclaringClass()->getName() . '::' . $action_name]
        ?? SEARCH_TERM_PARAMETERS[$class_name . '::' . $action_name]
        ?? array();
    foreach (array_keys($parameters) as $key) {
        if (is_int($key)) {
            throw new Exception("Paramètre positionnel refusé !", 400);
        }
        if (in_array($key, RAW_SQL_PARAMETER_NAMES, true)
            && in_array($key, $method_parameters, true)
            && !in_array($key, $allowed, true)) {
            throw new Exception("Paramètre « $key » refusé !", 400);
        }
    }
}
