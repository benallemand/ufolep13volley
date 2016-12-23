<?php
session_start();
require_once '../../includes/fonctions_inc.php';
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'ADMINISTRATEUR') {
    ?>
    <div ng-include src="'pages/edit_existing_player.html'"></div>
    <?php
} else if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'RESPONSABLE_EQUIPE') {
    ?>
    <div class="alert alert-warning" role="alert">Attention ! Vous ne pouvez éditer que les joueurs qui sont dans votre équipe !</div>
    <div ng-include src="'pages/edit_existing_player.html'"></div>
    <?php
}
?>
