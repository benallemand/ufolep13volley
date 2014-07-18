<?php
include("includes/fonctions_inc.php");

// On r�cup�re l'ID de la division 
$div = (isset($_GET["d"])) ? $_GET["d"] : "";
if ($div == "") {
    die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

    <HEAD>
        <TITLE>Championnat F�minin - UFOLEP 13 VOLLEY</TITLE>
        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <link rel="shortcut icon" href="favicon.ico" /><LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="http://dev.sencha.com/ext/5.0.0/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css" rel="stylesheet" />
        <script src="http://dev.sencha.com/ext/5.0.0/ext-all.js"></script>
        <script src="http://dev.sencha.com/ext/5.0.0/packages/ext-locale/build/ext-locale-fr.js" charset="UTF-8"></script>
        <script type="text/javascript" src="js/banniere.js"></script>
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3&amp;sensor=false"></script>
        <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
        <script type="text/javascript" src="js/mainMenu.js"></script>
        <script type="text/javascript">
            var competition = 'f';
            var division = '<?php echo $div; ?>';
            var connectedUser = '<?php echo getConnectedUser(); ?>';
        </script>
        <script type="text/javascript" src="js/championship.js"></script>
    </HEAD>

    <BODY>
        <div id="general">
            <div id="banniere"></div>
            <div id="menu"></div>
            <div id="titre"><H1>Division <?php echo $div; ?> - Championnat F�minin</H1></div>
            <div id="infos">
                <?php
                echo "Date limite des matches : ";
                affich_infos("f");
                ?>
            </div> 
            <div id="contenu">
            </div>
        </div>
    </BODY>

</HTML>
