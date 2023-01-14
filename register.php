<?php
require_once __DIR__ . '/classes/Competition.php';
$title = "Inscriptions aux compétitions UFOLEP 13 Volley-ball";
$limit_html_label = "<h2 style='text-align: center'>DATES LIMITES D'INSCRIPTION :%dates_limites%</h2>";
$dates_limites = "";
$manager = new Competition();
$competitions = $manager->getCompetitions();
foreach ($competitions as $competition) {
    $libelle = $competition['libelle'];
    $start_date = $competition['start_register_date'];
    $end_date = $competition['limit_register_date'];
    if (!empty($start_date) && !empty($end_date)) {
        $dates_limites .= "<br/><span>$libelle : du $start_date au $end_date</span>";
    }
}
$limit_html_label = str_replace('%dates_limites%', $dates_limites, $limit_html_label);

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
        var user_details = <?php echo json_encode($user_details); ?>;
    </script>
</HEAD>
<BODY>
</BODY>
</HTML>