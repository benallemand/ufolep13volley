<?php
@session_start();
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'ADMINISTRATEUR') {
    ?>
    <?php
} else if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'RESPONSABLE_EQUIPE') {
    ?>
    <div class="alert alert-warning" role="alert">Attention ! Vous ne pouvez éditer que les matches non certifiés que vous avez joués !</div>
    <?php
}
?>
