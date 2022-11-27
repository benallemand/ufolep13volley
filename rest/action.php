<?php

function filter_ignored_parameters(array $parameters)
{
    return array_filter($parameters, function ($key) {
        return !in_array($key, array('_dc', 'page', 'start', 'limit'));
    }, ARRAY_FILTER_USE_KEY);
}

try {
    $url = parse_url(filter_input(INPUT_SERVER, 'REQUEST_URI'), PHP_URL_PATH);
    $url = str_replace('/rest/action.php/', '', $url);
    $args = explode('/', $url);
    if (count($args) !== 2) {
        throw new Exception("Bad format for url $url !");
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
        default:
            throw new Exception("Undefined class for url $url !");

    }
    switch (filter_input(INPUT_SERVER, 'REQUEST_METHOD')) {
        case 'POST':
            $parameters = filter_input_array(INPUT_POST);
            if (empty($parameters)) {
                $parameters = array();
            }
            $parameters = filter_ignored_parameters($parameters);
            call_user_func_array(
                array($manager, $action_name),
                $parameters);
            break;
        case 'GET':
            $parameters = filter_input_array(INPUT_GET);
            if (empty($parameters)) {
                $parameters = array();
            }
            $parameters = filter_ignored_parameters($parameters);
            echo json_encode(call_user_func_array(
                array($manager, $action_name),
                $parameters));
            exit(0);
        case 'PUT':
        case 'DELETE':
        default:
            throw new Exception("Unsupported REQUEST_METHOD !");
    }
    echo json_encode(array(
        'success' => true,
        'message' => 'Modification OK'
    ));
} catch (Exception $exception) {
    $resp_code = empty($exception->getCode()) ? 500 : $exception->getCode();
    switch($resp_code) {
        case 401:
            // redirect to login page
            header('Location: /new_site/#/login?redirect=' . filter_input(INPUT_SERVER, 'REQUEST_URI') . '&reason=' . $exception->getMessage());
            exit(0);
        default:
            http_response_code($resp_code);
            echo json_encode(array(
                'success' => false,
                'message' => $exception->getMessage()
            ));
            break;
    }

}