<?phpinclude("includes/fonctions_inc.php");?><!DOCTYPE HTML><HTML>    <HEAD>        <TITLE>Coupe Isoardi - UFOLEP 13 VOLLEY</TITLE>        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />        <link rel="shortcut icon" href="favicon.ico" /><LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />        <link href="http://dev.sencha.com/ext/5.0.0/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css" rel="stylesheet" />        <script src="http://dev.sencha.com/ext/5.0.0/ext-all.js"></script>        <script src="http://dev.sencha.com/ext/5.0.0/packages/ext-locale/build/ext-locale-fr.js" charset="UTF-8"></script>        <script type="text/javascript" src="js/libs/Commons.js"></script>        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3&amp;sensor=false"></script>        <script type="text/javascript" src="js/libs/GMapPanel.js"></script>        <script type="text/javascript">            var competition = 'pf';            var division = '1';            var connectedUser = '<?php echo getConnectedUser(); ?>';            var title = "Phase Finale - Coupe Isoardi";            var limitDateLabel = "Date limite des matches : <?php getLimitDate("pf"); ?>";        </script>        <script type="text/javascript" src="js/matches.js"></script>    </HEAD>    <BODY></BODY></HTML>