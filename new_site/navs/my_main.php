<?php
require_once __DIR__ . '/../../classes/Team.php';
@session_start();
?>
<ul class="nav navbar-nav navbar-right">
    <?php
    if (!UserManager::is_connected()) {
    ?>
    <li><a href="#login">
            <button type="button" class="btn btn-primary">
                <span class="glyphicon glyphicon-log-in"></span> Connexion
            </button>
        </a>
    </li>
</ul>
<?php
exit(0);
}
switch ($_SESSION['profile_name']) {
    case 'RESPONSABLE_EQUIPE':
        $userName = $_SESSION['login'];
        $team = (new Team())->getTeam($_SESSION['id_equipe']);
        $nav_title = $userName . " - " . $team['team_full_name'];
        break;
    case 'ADMINISTRATEUR':
        $userName = $_SESSION['login'];
        $team = 'Compte administrateur';
        $menu_source = 'navs/admin_page.html';
        $nav_title = $userName . " - " . $team;
        break;
    case 'SUPPORT':
        $userName = $_SESSION['login'];
        $team = 'Compte support';
        $menu_source = 'navs/support_page.html';
        $nav_title = $userName . " - " . $team;
        break;
    default:
        ?>
        </ul>
        <?php
        exit(0);
}
?>
<?php
if (UserManager::isTeamLeader()) {
    ?>
    <li>
        <a href="/pages/my_page.html">
            <button type="button" class="btn btn-primary">
                <span class="glyphicon glyphicon-user"></span> <?php echo $nav_title; ?>
            </button>
        </a>
    </li>
    <li>
        <a href="/rest/action.php/usermanager/logout">
            <button type="button" class="btn btn-danger">
                <span class="glyphicon glyphicon-log-out"></span> déconnexion
            </button>
        </a>
    </li>

    </ul>
    <?php
    exit(0);
}
?>
<li class="dropdown">
    <a class="dropdown-toggle" role="button" data-toggle="dropdown"
       aria-haspopup="true" aria-expanded="false">
        <span class="glyphicon glyphicon-user"></span>
        <?php echo $nav_title; ?>
        <span class="caret"></span>
    </a>
    <ul class="dropdown-menu" ng-include src="'<?php echo $menu_source; ?>'"></ul>
</li>
</ul>
