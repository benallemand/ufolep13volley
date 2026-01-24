<?php
header('Access-Control-Allow-Origin: *');


function exclude_ignored_parameters(array $parameters): array
{
    return array_filter($parameters, function ($key) {
        return !in_array($key, array('_dc', 'page', 'start', 'limit', '_end', '_order', '_sort', '_start'));
    }, ARRAY_FILTER_USE_KEY);
}

function filter_pagination_parameters(array $parameters): array
{
    return array_filter($parameters, function ($key) {
        return in_array($key, array('_end', '_start'));
    }, ARRAY_FILTER_USE_KEY);
}

function filter_sort_order_parameters(array $parameters): array
{
    return array_filter($parameters, function ($key) {
        return in_array($key, array('_order', '_sort'));
    }, ARRAY_FILTER_USE_KEY);
}

function get_pagination_parameters(): ?array
{
    $parameters = filter_input_array(INPUT_GET);
    if(is_null($parameters)) {
        return null;
    }
    $parameters = filter_pagination_parameters($parameters);
    if (count($parameters) != 2) {
        return null;
    }
    return $parameters;
}

function get_sorter_parameters(): ?array
{
    $parameters = filter_input_array(INPUT_GET);
    if(is_null($parameters)) {
        return null;
    }
    $parameters = filter_sort_order_parameters($parameters);
    if (count($parameters) != 2) {
        return null;
    }
    return $parameters;
}

function filter_results_by_pagination(mixed $results, array $pagination)
{
    return array_slice($results, $pagination['_start'], $pagination['_end'] - $pagination['_start'] + 1);
}

function sort_results(mixed $results, array $sorter)
{
    usort($results, function ($a, $b) use ($sorter) {
        if ($sorter['_sort'] == 'date_reception') {
            $a[$sorter['_sort']] = strtotime($a[$sorter['_sort']]);
            $b[$sorter['_sort']] = strtotime($b[$sorter['_sort']]);
            if ($sorter['_order'] === 'ASC') {
                return $a[$sorter['_sort']] - $b[$sorter['_sort']];
            } else {
                return $b[$sorter['_sort']] - $a[$sorter['_sort']];
            }
        }
        if ($sorter['_order'] === 'ASC') {
            return strcmp($a[$sorter['_sort']], $b[$sorter['_sort']]);
        } else {
            return -strcmp($a[$sorter['_sort']], $b[$sorter['_sort']]);
        }
    });
    return $results;
}

try {
    $url = parse_url(filter_input(INPUT_SERVER, 'REQUEST_URI'), PHP_URL_PATH);
    $url = str_replace('/rest/action.php/', '', $url);
    $args = explode('/', $url);
    if (count($args) !== 2) {
        throw new Exception("Mauvais format d'url $url !");
    }
    $class_name = $args[0];
    $action_name = $args[1];
    switch ($class_name) {
        case 'activity':
            require_once __DIR__ . "/../classes/Activity.php";
            $manager = new Activity();
            break;
        case 'alerts':
            require_once __DIR__ . "/../classes/Alerts.php";
            $manager = new Alerts();
            break;
        case 'blacklistcourt':
            require_once __DIR__ . "/../classes/BlackListCourt.php";
            $manager = new BlackListCourt();
            break;
        case 'blacklistdate':
            require_once __DIR__ . "/../classes/BlackListDate.php";
            $manager = new BlackListDate();
            break;
        case 'blacklistteam':
            require_once __DIR__ . "/../classes/BlackListTeam.php";
            $manager = new BlackListTeam();
            break;
        case 'blacklistteams':
            require_once __DIR__ . "/../classes/BlackListTeams.php";
            $manager = new BlackListTeams();
            break;
        case 'club':
            require_once __DIR__ . "/../classes/Club.php";
            $manager = new Club();
            break;
        case 'competition':
            require_once __DIR__ . "/../classes/Competition.php";
            $manager = new Competition();
            break;
        case 'configuration':
            require_once __DIR__ . "/../classes/Configuration.php";
            $manager = new Configuration();
            break;
        case 'court':
            require_once __DIR__ . "/../classes/Court.php";
            $manager = new Court();
            break;
        case 'database':
            require_once __DIR__ . "/../classes/Database.php";
            $manager = new Database();
            break;
        case 'day':
            require_once __DIR__ . "/../classes/Day.php";
            $manager = new Day();
            break;
        case 'emails':
            require_once __DIR__ . "/../classes/Emails.php";
            $manager = new Emails();
            break;
        case 'files':
            require_once __DIR__ . "/../classes/Files.php";
            $manager = new Files();
            break;
        case 'generic':
            require_once __DIR__ . "/../classes/Generic.php";
            $manager = new Generic();
            break;
        case 'halloffame':
            require_once __DIR__ . "/../classes/HallOfFame.php";
            $manager = new HallOfFame();
            break;
        case 'limitdate':
            require_once __DIR__ . "/../classes/LimitDate.php";
            $manager = new LimitDate();
            break;
        case 'matchmgr':
            require_once __DIR__ . "/../classes/MatchMgr.php";
            $manager = new MatchMgr();
            break;
        case 'news':
            require_once __DIR__ . "/../classes/News.php";
            $manager = new News();
            break;
        case 'photo':
            require_once __DIR__ . "/../classes/Photo.php";
            $manager = new Photo();
            break;
        case 'player':
            require_once __DIR__ . "/../classes/Players.php";
            $manager = new Players();
            break;
        case 'rank':
            require_once __DIR__ . "/../classes/Rank.php";
            $manager = new Rank();
            break;
        case 'register':
            require_once __DIR__ . "/../classes/Register.php";
            $manager = new Register();
            break;
        case 'registry':
            require_once __DIR__ . "/../classes/Registry.php";
            $manager = new Registry();
            break;
        case 'sqlmanager':
            require_once __DIR__ . "/../classes/SqlManager.php";
            $manager = new SqlManager();
            break;
        case 'team':
            require_once __DIR__ . "/../classes/Team.php";
            $manager = new Team();
            break;
        case 'timeslot':
            require_once __DIR__ . "/../classes/TimeSlot.php";
            $manager = new TimeSlot();
            break;
        case 'usermanager':
            require_once __DIR__ . "/../classes/UserManager.php";
            $manager = new UserManager();
            break;
        case 'commission':
            require_once __DIR__ . "/../classes/Commission.php";
            $manager = new Commission();
            break;
        default:
            throw new Exception("Classe non définie pour l'url $url !");

    }
    switch (filter_input(INPUT_SERVER, 'REQUEST_METHOD')) {
        case 'POST':
            $parameters = filter_input_array(INPUT_POST);
            if (empty($parameters)) {
                $parameters = array();
            }
            $parameters = exclude_ignored_parameters($parameters);
            call_user_func_array(
                array($manager, $action_name),
                $parameters);
            break;
        case 'GET':
            $parameters = filter_input_array(INPUT_GET);
            if (empty($parameters)) {
                $parameters = array();
            }
            $parameters = exclude_ignored_parameters($parameters);
            $results = call_user_func_array(
                array($manager, $action_name),
                $parameters);
            header('Access-Control-Expose-Headers: X-Total-Count');
            header('X-Total-Count: ' . count($results));
            $sorter = get_sorter_parameters();
            if (!empty($sorter)) {
                $results = sort_results($results, $sorter);
            }
            $pagination = get_pagination_parameters();
            if (!empty($pagination)) {
                $results = filter_results_by_pagination($results, $pagination);
            }
            echo json_encode($results);
            exit(0);
        case 'PUT':
        case 'DELETE':
        default:
            throw new Exception("Méthode HTTP non définie !");
    }
    echo json_encode(array(
        'success' => true,
        'message' => 'Modification OK'
    ));
} catch (Exception $exception) {
    $resp_code = empty($exception->getCode()) ? 500 : $exception->getCode();
    switch ($resp_code) {
        case 401:
            // redirect to login page
            header('Location: /pages/home.html#/login?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '&reason=' . $exception->getMessage());
            exit(0);
        case 201:
        case 200:
            http_response_code($resp_code);
            echo json_encode(array(
                'success' => true,
                'message' => $exception->getMessage()
            ));
            break;
        default:
            http_response_code($resp_code);
            echo json_encode(array(
                'success' => false,
                'message' => $exception->getMessage()
            ));
            error_log($exception->getMessage());
            break;
    }

}
catch (ArgumentCountError $argumentCountError) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => $argumentCountError->getMessage()
    ));

}