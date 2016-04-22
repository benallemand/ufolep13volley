<?php
include("includes/fonctions_inc.php");

// On récupère l'ID de la division 
$div = (isset($_GET["d"])) ? $_GET["d"] : "";
if ($div == "") {
    die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
}
$requires = array();
$controllers = array();
$controllers[] = "'GymnasiumsMap'";
$controllers[] = "'Matches'";
$controllers[] = "'Classement'";
?>

<!DOCTYPE HTML>

<HTML>

<HEAD>
    <TITLE>Play Off - UFOLEP 13 VOLLEY</TITLE>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="favicon.ico"/>
    <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen"/>
    <link href="images/fonts/icomoon/style.css" rel="stylesheet" type="text/css" media="screen"/>
    <link
        href="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css"
        rel="stylesheet"/>

    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/ext-all.js" type="text/javascript"></script>
    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-locale/build/ext-locale-fr.js"></script>
    <script type="text/javascript" src="js/libs/Commons.js"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3"></script>
    <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
    <script type="text/javascript">
        var competition = 'px';
        var division = '<?php echo $div; ?>';
        var connectedUser = '<?php echo getConnectedUser(); ?>';
        var title = "Play Off <?php echo $div; ?> - Championnat Féminin";
        var limitDateLabel = "Date limite des matches : <?php getLimitDate("px"); ?>";
    </script>
    <script type="text/javascript">
        var requires = [<?php echo implode(',', $requires); ?>];
        var controllers = [<?php echo implode(',', $controllers); ?>];
    </script>
    <script type="text/javascript" src="js/championship.js"></script>
</HEAD>
<BODY></BODY>
</HTML>