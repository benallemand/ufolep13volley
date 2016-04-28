<?php
include("includes/fonctions_inc.php");
$requires = array();
$controllers = array();
$controllers[] = "'GymnasiumsMap'";
$controllers[] = "'Matches'";
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE>Phase Finale - Coupe Isoardi - UFOLEP 13 VOLLEY</TITLE>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="favicon.ico"/>
    <link href="includes/main.css" rel="stylesheet" type="text/css" media="screen"/>
    <link href="images/fonts/icomoon/style.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css"/>
    <link
        href="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css"
        rel="stylesheet"/>
    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/ext-all.js" type="text/javascript"></script>
    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-locale/build/ext-locale-fr.js"></script>
    <script type="text/javascript" src="js/libs/Commons.js"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3"></script>
    <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
    <script type="text/javascript">
        var competition = 'cf';
        var division = '1';
        var connectedUser = '<?php echo getConnectedUser(); ?>';
        var title = "Phase Finale - Coupe Isoardi";
        var limitDateLabel = "Date limite des matches : <?php getLimitDate("cf"); ?>";
    </script>
    <script type="text/javascript">
        var requires = [<?php echo implode(',', $requires); ?>];
        var controllers = [<?php echo implode(',', $controllers); ?>];
    </script>
    <script type="text/javascript" src="js/matches.js"></script>
</HEAD>
<BODY></BODY>
</HTML>