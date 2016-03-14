<?php
include("includes/fonctions_inc.php");
$requires = array();
$controllers = array();
$controllers[] = "'GymnasiumsMap'";
$controllers[] = "'Matches'";
?>

<!DOCTYPE HTML>
<HTML>

<HEAD>
    <TITLE>Coupe Koury Hanna - UFOLEP 13 VOLLEY</TITLE>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="favicon.ico"/>
    <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen"/>
    <link
        href="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css"
        rel="stylesheet"/>
    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/ext-all.js"></script>
    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-locale/build/ext-locale-fr.js"></script>
    <script type="text/javascript" src="js/libs/Commons.js"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3"></script>
    <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
    <script type="text/javascript">
        var competition = 'kf';
        var division = '1';
        var connectedUser = '<?php echo getConnectedUser(); ?>';
        var title = "Phase Finale - Coupe Koury Hanna";
        var limitDateLabel = "Date limite des matches : <?php getLimitDate("kf"); ?>";
    </script>
    <script type="text/javascript">
        var requires = [<?php echo implode(',', $requires); ?>];
        var controllers = [<?php echo implode(',', $controllers); ?>];
    </script>
    <script type="text/javascript" src="js/matches.js"></script>
</HEAD>
<BODY></BODY>
</HTML>

