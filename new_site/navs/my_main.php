<?php
require_once __DIR__ . '/../../classes/Team.php';
@session_start();
?>
<ul class="nav navbar-nav navbar-right">
    <?php
    if (isset($_SESSION['login']) && $_SESSION['profile_name'] != 'ADMINISTRATEUR') {
        $userName = $_SESSION['login'];
        $team = (new Team())->getTeam($_SESSION['id_equipe']);
        ?>
        <li class="dropdown">
            <a class="dropdown-toggle" role="button" data-toggle="dropdown"
               aria-haspopup="true" aria-expanded="false">
                <span class="glyphicon glyphicon-user"></span>
                <?php echo $userName; ?> - <?php echo $team['team_full_name']; ?>
                <span class="caret"></span>
            </a>
            <ul class="dropdown-menu" ng-include src="'navs/my_page.html'"></ul>
        </li>
        <?php
    } else {
        if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'ADMINISTRATEUR') {
            $userName = $_SESSION['login'];
            $team = 'Compte administrateur';
            ?>
            <li class="dropdown">
                <a class="dropdown-toggle" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                    <span class="glyphicon glyphicon-user"></span>
                    <?php echo $userName; ?> - <?php echo $team; ?>
                    <span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="../admin.php"><span class="glyphicon glyphicon-home"></span> Ma page d'accueil</a></li>
                    <li><a href="/rest/action.php/usermanager/logout"><span class="glyphicon glyphicon-log-out"></span>
                            Déconnexion</a>
                    </li>
                </ul>
            </li>
            <?php
        } else {
            ?>
            <li><a href="#login"><span class="glyphicon glyphicon-log-in"></span> Connexion</a></li>
            <?php
        }
    }
    ?>
</ul>
