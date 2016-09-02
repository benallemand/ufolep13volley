<?php
session_start();
require_once '../../includes/fonctions_inc.php';
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'ADMINISTRATEUR') {
    ?>
    <div ng-include src="'pages/match.html'"></div>
    <?php
} else if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'RESPONSABLE_EQUIPE') {
    $test = 'toto';
    ?>
    <div class="alert alert-warning" role="alert">Attention ! Vous ne pouvez éditer que les matches non certifiés que vous avez joués !</div>
    <div ng-include src="'pages/match.html'"></div>
    <?php
}
?>
