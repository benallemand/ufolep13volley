<?php
include("includes/fonctions_inc.php");
$requires = array();
$controllers = array();
$controllers[] = "'Menu'";
$controllers[] = "'GymnasiumsMap'";
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

<HEAD>
    <TITLE>Accueil - UFOLEP 13 VOLLEY</TITLE>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="favicon.ico"/>
    <link href="includes/main.css" rel="stylesheet" type="text/css" media="screen"/>
    <link href="images/fonts/icomoon/style.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css"/>
    <LINK href="includes/coverflow.css" rel="stylesheet" type="text/css" media="screen"/>
    <link
        href="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css"
        rel="stylesheet"/>

    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/ext-all.js" type="text/javascript"></script>
    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-locale/build/ext-locale-fr.js"
            type="text/javascript"></script>
    <script type="text/javascript" src="js/libs/Commons.js"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3"></script>
    <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
    <script type="text/javascript">
        var connectedUser = '<?php echo getConnectedUser(); ?>';
        var title = "Accueil - UFOLEP 13 Volley";
    </script>
    <script type="text/javascript">
        var requires = [<?php echo implode(',', $requires); ?>];
        var controllers = [<?php echo implode(',', $controllers); ?>];
    </script>
    <script type="text/javascript" src="js/accueil.js"></script>
</HEAD>
<BODY></BODY>
</HTML>
