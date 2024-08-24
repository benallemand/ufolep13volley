<?php
require_once __DIR__ . '/classes/Competition.php';
$title = "Inscriptions aux compétitions UFOLEP 13 Volley-ball";
$limit_html_label = "<table class='limit_dates'>";
$limit_html_label .= "<thead>";
$limit_html_label .= "<tr><th colspan='3'>INSCRIPTIONS</th></tr>";
$limit_html_label .= "<tr><th>Compétition</th><th>Ouverture</th><th>Fermeture</th></tr>";
$limit_html_label .= "</thead>";
$limit_html_label .= "<tbody>";
$manager = new Competition();
$competitions = $manager->getCompetitions();
$configuration = new Configuration();

foreach ($competitions as $competition) {
    $libelle = $competition['libelle'];
    $start_date = $competition['start_register_date'];
    $end_date = $competition['limit_register_date'];
    if (!empty($start_date) && !empty($end_date)) {
        $limit_html_label .= "<tr><th style='text-align: left'>$libelle</th><td>$start_date</td><td>$end_date</td></tr>";
    }
}
$limit_html_label .= "</tbody>";
$limit_html_label .= "</table>";

@session_start();
$user_details = $_SESSION;
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE> <?php echo $title; ?></TITLE>
    <META
            http-equiv="Content-Type"
            content="text/html; charset=utf-8"/>
    <link
            rel="shortcut icon"
            href="favicon.ico"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer"/>
    <link
            href="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/theme-crisp-touch/resources/theme-crisp-touch-all.css"
            rel="stylesheet"/>
    <link
            href="/cells.css"
            rel="stylesheet"/>
    <style>
        table.limit_dates, table.limit_dates td {
            border: 1px solid #333;
            min-width: 200px;
            text-align: center;
        }

        thead,
        tfoot {
            background-color: #333;
            color: #fff;
        }

    </style>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/ext-all.js"
            type="text/javascript"></script>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/locale/locale-fr.js"
            type="text/javascript"></script>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/theme-crisp-touch/theme-crisp-touch.js"
            type="text/javascript"></script>
    <script
            type="text/javascript" src="js/register.js"></script>
    <script type="text/javascript">
        var title = "<?php echo $title; ?>";
        var limit_html_label = "<?php echo $limit_html_label; ?>";
        var week_seeding_tournament = "<?php echo $configuration->seeding_tournament_week ?>";
        var user_details = <?php echo json_encode($user_details); ?>;
    </script>
</HEAD>
<BODY>
</BODY>
</HTML>