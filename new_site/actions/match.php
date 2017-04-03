<?php
session_start();
require_once '../../includes/fonctions_inc.php';
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'ADMINISTRATEUR') {
    ?>
    <a ng-if="x.sheet_received == '1'
    && x.is_file_attached == '1'"
       href="../ajax/downloadMatchFiles.php?id={{x.id_match}}"
       role="button"
       class="btn btn-sm btn-primary">
        <span class="glyphicon glyphicon-download-alt"></span>
        Télécharger les fichiers attachés
    </a>
    <form ng-if="x.certif != '1'
        && x.sheet_received != '1'
        && x.date_reception_raw < today"
          style="display: inline-block"
          ng-submit="declareSheetReceived(x.code_match)">
        <button title="Accuser réception des feuilles de match" type="submit" class="btn btn-sm btn-success">
            Accuser Réception
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </form>
    <form ng-if="x.sheet_received=='1'
        && x.certif != '1'"
          style="display: inline-block"
          ng-submit="validateMatch(x.code_match)">
        <button title="Certifier le match" type="submit" class="btn btn-sm btn-success">
            Valider
            <span class="glyphicon glyphicon-ok"></span>
        </button>
    </form>
    <form ng-if="x.certif == '1'"
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
        && x.report_status != 'REFUSED_BY_EXT'"
          style="display: inline-block"
          ng-submit="refuseReport(x.code_match)">
        <button title="Refuser le report du match" type="submit" class="btn btn-sm btn-danger">
            Refuser le report du match
            <span class="glyphicon glyphicon-time"></span>
        </button>
    </form>
    <div ng-if="x.certif != '1'">
        <a title="Modifier le match"
           class="btn btn-sm btn-warning"
           ng-click="editMatch(x.id_match, matches)">
            Modifier
            <span class="glyphicon glyphicon-edit"></span>
        </a>
    </div>
    <?php
}
if (isset($_SESSION['login']) && $_SESSION['profile_name'] == 'RESPONSABLE_EQUIPE') {
    ?>
    <a ng-if="x.sheet_received == '1'
        && (x.id_equipe_dom == <?php echo $_SESSION['id_equipe'] ?> || x.id_equipe_ext == <?php echo $_SESSION['id_equipe'] ?>)
        && x.is_file_attached == '1'"
       href="../ajax/downloadMatchFiles.php?id={{x.id_match}}"
       role="button"
       class="btn btn-sm btn-primary">
        <span class="glyphicon glyphicon-download-alt"></span>
        Télécharger les fichiers attachés
    </a>
    <form ng-if="x.report_status == 'NOT_ASKED'
        && x.certif != '1'
        && x.sheet_received != '1'
        && (x.id_equipe_dom == <?php echo $_SESSION['id_equipe'] ?> || x.id_equipe_ext == <?php echo $_SESSION['id_equipe'] ?>)"
          style="display: inline-block"
          ng-submit="askForReport(x.code_match)">
        <button title="Demander le report du match" type="submit" class="btn btn-sm btn-success">
            Demander le report du match
            <span class="glyphicon glyphicon-time"></span>
        </button>
    </form>
    <form ng-if="x.report_status == 'ASKED_BY_DOM'
        && x.sheet_received != '1'
        && x.id_equipe_dom != <?php echo $_SESSION['id_equipe'] ?>"
          style="display: inline-block"
          ng-submit="refuseReport(x.code_match)">
        <button title="Refuser le report du match" type="submit" class="btn btn-sm btn-danger">
            Refuser le report du match
            <span class="glyphicon glyphicon-time"></span>
        </button>
    </form>
    <form ng-if="x.report_status == 'ASKED_BY_DOM'
        && x.sheet_received != '1'
        && x.id_equipe_dom != <?php echo $_SESSION['id_equipe'] ?>"
          style="display: inline-block"
          ng-submit="acceptReport(x.code_match)">
        <button title="Accepter le report du match" type="submit" class="btn btn-sm btn-success">
            Accepter le report du match
            <span class="glyphicon glyphicon-time"></span>
        </button>
    </form>
    <form ng-if="x.report_status == 'ASKED_BY_EXT'
        && x.sheet_received != '1'
        && x.id_equipe_ext != <?php echo $_SESSION['id_equipe'] ?>"
          style="display: inline-block"
          ng-submit="refuseReport(x.code_match)">
        <button title="Refuser le report du match" type="submit" class="btn btn-sm btn-danger">
            Refuser le report du match
            <span class="glyphicon glyphicon-time"></span>
        </button>
    </form>
    <form ng-if="x.report_status == 'ASKED_BY_EXT'
        && x.sheet_received != '1'
        && x.id_equipe_ext != <?php echo $_SESSION['id_equipe'] ?>"
          style="display: inline-block"
          ng-submit="acceptReport(x.code_match)">
        <button title="Accepter le report du match" type="submit" class="btn btn-sm btn-success">
            Accepter le report du match
            <span class="glyphicon glyphicon-time"></span>
        </button>
    </form>
    <form ng-if="x.report_status == 'ACCEPTED_BY_DOM'
        && x.sheet_received != '1'
        && x.id_equipe_dom == <?php echo $_SESSION['id_equipe'] ?>"
          style="display: inline-block"
          ng-submit="giveReportDate(x.code_match)">
        <button title="Indiquer la date de report du match" type="submit" class="btn btn-sm btn-info">
            Indiquer la date de report du match
            <span class="glyphicon glyphicon-time"></span>
        </button>
    </form>
    <form ng-if="x.report_status == 'ACCEPTED_BY_EXT'
        && x.sheet_received != '1'
        && x.id_equipe_ext == <?php echo $_SESSION['id_equipe'] ?>"
          style="display: inline-block"
          ng-submit="giveReportDate(x.code_match)">
        <button title="Indiquer la date de report du match" type="submit" class="btn btn-sm btn-info">
            Indiquer la date de report du match
            <span class="glyphicon glyphicon-time"></span>
        </button>
    </form>
    <div ng-if="today >= x.date_reception_raw
        && (x.id_equipe_dom == <?php echo $_SESSION['id_equipe'] ?> || x.id_equipe_ext == <?php echo $_SESSION['id_equipe'] ?>)
        && x.certif != '1'">
        <a title="Envoyer les feuilles de match"
           class="btn btn-sm btn-info"
           ng-href="mailto:{{ x.email_send_sheets }}?subject={{ x.email_send_sheets_subject }}&body={{ x.email_send_sheets_body }}"
           target="_blank">
            Envoyer les feuilles de match
            <span class="glyphicon glyphicon-send"></span>
        </a>
    </div>
    <div ng-if="today >= x.date_reception_raw
        && (x.id_equipe_dom == <?php echo $_SESSION['id_equipe'] ?> || x.id_equipe_ext == <?php echo $_SESSION['id_equipe'] ?>)
        && x.certif != '1'">
        <a title="Modifier le match"
           class="btn btn-sm btn-warning"
           ng-click="editMatch(x.id_match, matches)"
        >
            Modifier
            <span class="glyphicon glyphicon-edit"></span>
        </a>
    </div>
    <?php
}
?>
