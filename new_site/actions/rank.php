<?php
session_start();
require_once __DIR__ . '/../../includes/fonctions_inc.php';
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'ADMINISTRATEUR') {
    ?>
    <form style="display: inline-block" ng-submit="removePenalty(x.id_equipe, x.code_competition)">
        <button title="Enlever une pénalité" type="submit" class="btn btn-sm btn-success">
            Enlever une pénalité
            <span class="glyphicon glyphicon-thumbs-up"></span>
        </button>
    </form>
    <form style="display: inline-block" ng-submit="addPenalty(x.id_equipe, x.code_competition)">
        <button title="Donner une pénalité" type="submit" class="btn btn-sm btn-danger">
            Donner une pénalité
            <span class="glyphicon glyphicon-thumbs-down"></span>
        </button>
    </form>
    <form style="display: inline-block" ng-submit="IncrementReportCount(x.id_equipe, x.code_competition)">
        <button title="Comptabiliser un report" type="submit" class="btn btn-sm btn-success">
            Comptabiliser un report
            <span class="glyphicon glyphicon-thumbs-up"></span>
        </button>
    </form>
    <form style="display: inline-block" ng-submit="DecrementReportCount(x.id_equipe, x.code_competition)">
        <button title="Retirer un report" type="submit" class="btn btn-sm btn-danger">
            Retirer un report
            <span class="glyphicon glyphicon-thumbs-down"></span>
        </button>
    </form>
    <?php
}
?>
