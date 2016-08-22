<?php
session_start();
require_once '../../includes/fonctions_inc.php';
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'ADMINISTRATEUR') {
    ?>
    <form style="display: inline-block" ng-submit="removePenalty(x.id_equipe, x.code_competition)">
        <button title="Enlever une pénalité" type="submit" class="btn btn-sm btn-success">
            <span class="glyphicon glyphicon-thumbs-up"></span>
        </button>
    </form>
    <form style="display: inline-block" ng-submit="addPenalty(x.id_equipe, x.code_competition)">
        <button title="Donner une pénalité" type="submit" class="btn btn-sm btn-danger">
            <span class="glyphicon glyphicon-thumbs-down"></span>
        </button>
    </form>
    <?php
}
?>
