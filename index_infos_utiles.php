<?php
include("includes/fonctions_inc.php");
$requires = array();
$controllers = array();
$controllers[] = "'GymnasiumsMap'";
?>

<!DOCTYPE HTML>

<HTML>

<HEAD>
    <TITLE>Accueil - UFOLEP 13 VOLLEY</TITLE>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="favicon.ico"/>
    <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen"/>
    <link
        href="https://extjs.cachefly.net/ext/gpl/5.1.0/build/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css"
        rel="stylesheet"/>
    <script src="https://extjs.cachefly.net/ext/gpl/5.1.0/build/ext-all.js"></script>
    <script src="https://extjs.cachefly.net/ext/gpl/5.1.0/build/packages/ext-locale/build/ext-locale-fr.js"></script>
    <script type="text/javascript" src="js/libs/Commons.js"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3"></script>
    <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
    <script type="text/javascript">
        var connectedUser = '<?php echo getConnectedUser(); ?>';
        var title = "Infos Utiles";
    </script>
    <script type="text/javascript">
        var requires = [<?php echo implode(',', $requires); ?>];
        var controllers = [<?php echo implode(',', $controllers); ?>];
    </script>
    <script type="text/javascript" src="js/usefulInformations.js"></script>
</HEAD>
<BODY></BODY>
</HTML>
