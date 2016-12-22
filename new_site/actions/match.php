<?php
session_start();
require_once '../../includes/fonctions_inc.php';
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'ADMINISTRATEUR') {
    ?>
    <form ng-hide="x.certif=='1' || x.sheet_received=='1' || (x.set_1_dom == '0' && x.set_1_ext == '0')"
          style="display: inline-block" ng-submit="declareSheetReceived(x.code_match)">
        <button title="Accuser réception des feuilles de match" type="submit" class="btn btn-sm btn-success">
            Accuser Réception
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </form>
    <form ng-hide="x.certif=='1' || (x.set_1_dom == '0' && x.set_1_ext == '0')" style="display: inline-block"
          ng-submit="validateMatch(x.code_match)">
        <button title="Certifier le match" type="submit" class="btn btn-sm btn-success">
            Valider
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </form>
    <form ng-hide="x.certif=='0'" style="display: inline-block" ng-submit="invalidateMatch(x.code_match)">
        <button title="Dévalider le match" type="submit" class="btn btn-sm btn-danger">
            Invalider
            <span class="glyphicon glyphicon-remove"></span>
        </button>
    </form>
    <?php
}
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'RESPONSABLE_EQUIPE') {
    ?>
<!--    <form ng-if="x.date_reception_raw >= limit_date_for_report"-->
<!--          style="display: inline-block" ng-submit="askForReport(x.code_match)">-->
<!--        <button title="Demander le report du match" type="submit" class="btn btn-sm btn-success">-->
<!--            Demander le report du match-->
<!--            <span class="glyphicon glyphicon-time"></span>-->
<!--        </button>-->
<!--    </form>-->
    <?php
}
?>
