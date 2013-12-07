<?php
include("includes/fonctions_inc.php");

// On récupère l'ID de la division 
$div = (isset($_GET["d"])) ? $_GET["d"] : "";
if ($div == "") {
    die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

    <HEAD>
        <TITLE>Championnat Féminin - UFOLEP 13 VOLLEY</TITLE>
        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <link rel="shortcut icon" href="favicon.ico" /><LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="http://cdn.sencha.io/ext/gpl/4.2.0/resources/css/ext-all-neptune.css" rel="stylesheet" />
        <script src="http://cdn.sencha.com/ext/gpl/4.2.0/ext-all.js"></script>
        <script type="text/javascript" src="js/banniere.js"></script>
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3&amp;sensor=false"></script>
        <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
        <script type="text/javascript" src="js/mainMenu.js"></script>
    </HEAD>

    <BODY>
        <div id="general">
            <div id="banniere"></div>
            <div id="menu"></div>
            <div id="contenu">
                <div id="titre"><H1>Championnat Féminin</H1></div>
                <?php affich_connecte(); ?>
                <div id="classement"><?php affich_classement("f", $div); ?></div>
                <div id="infos"><?php echo "Date limite des matches : ";
                affich_infos("f", $div);
                ?></div> 
                <div id="matches"><?php affich_journee("f", $div); ?></div>
<?php affich_admin_site(); ?>
            </div>
        </div>
    </BODY>

</HTML>
