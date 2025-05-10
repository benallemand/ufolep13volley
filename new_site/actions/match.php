<?php
@session_start();
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'ADMINISTRATEUR') {
    ?>
    <form ng-if="x.certif != '1'
        && x.sheet_received != '1'
        && x.date_reception_raw < today
        && x.match_status == 'CONFIRMED'"
          style="display: inline-block"
          ng-submit="declareSheetReceived(x.code_match)">
        <button title="Accuser réception des feuilles de match" type="submit" class="btn btn-sm btn-success">
            Accuser Réception
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </form>
    <form ng-if="(x.sheet_received=='1' || x.is_forfait=='1')
                 && x.certif != '1'
                 && x.match_status == 'CONFIRMED'"
          style="display: inline-block"
          ng-submit="validateMatch(x.code_match)">
        <button title="Certifier le match" type="submit" class="btn btn-sm btn-success">
            Valider
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </form>
    <form ng-if="x.certif == 1
                 && x.match_status == 'CONFIRMED'"
          style="display: inline-block"
          ng-submit="invalidateMatch(x.code_match)">
        <button title="Dévalider le match" type="submit" class="btn btn-sm btn-danger">
            Invalider
            <span class="glyphicon glyphicon-remove"></span>
        </button>
    </form>
    <form ng-if="x.report_status != 'NOT_ASKED'
        && x.report_status != 'REFUSED_BY_ADMIN'
        && x.report_status != 'REFUSED_BY_DOM'
        && x.report_status != 'REFUSED_BY_EXT'
        && x.match_status == 'CONFIRMED'"
          style="display: inline-block"
          ng-submit="refuseReport(x.code_match)">
        <button title="Refuser le report du match" type="submit" class="btn btn-sm btn-danger">
            Refuser le report du match
            <span class="glyphicon glyphicon-time"></span>
        </button>
    </form>
    <div ng-if="x.certif != '1'
                && x.match_status == 'CONFIRMED'">
        <a title="Modifier le match"
           class="btn btn-sm btn-warning"
           ng-href="/match.php?id_match={{x.id_match}}"
           target="_self">
            Modifier match
            <span class="glyphicon glyphicon-edit"></span>
        </a>
        <a title="Modifier les fiches équipes"
           class="btn btn-sm btn-info"
           ng-href="/team_sheets.php?id_match={{x.id_match}}"
           target="_self">
            Modifier equipes
            <span class="glyphicon glyphicon-edit"></span>
        </a>
    </div>
    <?php
}
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'RESPONSABLE_EQUIPE') {
    ?>
    <a title="Contacter le responsable"
       class="btn btn-sm btn-info"
       ng-href="mailto:{{x.contact_com}};{{x.email_dom}};{{x.email_ext}}"
       target="_self">
        Contact
        <span class="glyphicon glyphicon-envelope"></span>
    </a>
    <div ng-if="(x.id_equipe_dom == <?php echo $_SESSION['id_equipe'] ?> || x.id_equipe_ext == <?php echo $_SESSION['id_equipe'] ?>)
        && x.certif != '1'
        && x.match_status == 'CONFIRMED'">
        <a title="Modifier le match"
           class="btn btn-sm btn-warning"
           ng-href="/match.php?id_match={{x.id_match}}"
           target="_self">
            Modifier match
            <span class="glyphicon glyphicon-edit"></span>
        </a>
        <a title="Modifier les fiches équipes"
           class="btn btn-sm btn-info"
           ng-href="/team_sheets.php?id_match={{x.id_match}}"
           target="_self">
            Modifier equipes
            <span class="glyphicon glyphicon-edit"></span>
        </a>
    </div>
    <?php
}
?>
