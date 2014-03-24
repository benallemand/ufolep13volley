<?php
include("includes/fonctions_inc.php");

// On récupère l'ID de la division 
$div = (isset($_GET["d"])) ? $_GET["d"] : "";
if ($div == "4o") {
    $div_nom = "Play-offs - Division 4 Masculine";
}
if ($div == "4d") {
    $div_nom = "Play-downs - Division 4 Masculine";
}
if ($div == "5o") {
    $div_nom = "Play-offs - Division 5 Masculine";
}
if ($div == "5d") {
    $div_nom = "Play-downs - Division 5 Masculine";
}

if ($div == "") {
    die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

    <HEAD>
        <TITLE>Championnat Masculin - UFOLEP 13 VOLLEY</TITLE>
        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <link rel="shortcut icon" href="favicon.ico" /><LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="http://cdn.sencha.io/ext/gpl/4.2.0/resources/css/ext-all-neptune.css" rel="stylesheet" />
        <script src="http://cdn.sencha.com/ext/gpl/4.2.0/ext-all.js"></script>
        <script src="http://cdn.sencha.com/ext/gpl/4.2.0/locale/ext-lang-fr.js"></script>
        <script type="text/javascript" src="js/banniere.js"></script>
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3&amp;sensor=false"></script>
        <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
        <script type="text/javascript" src="js/mainMenu.js"></script>
        <script type="text/javascript">
            var competition = 'pf';
            var division = '<?php echo $div; ?>';
        </script>
        <script type="text/javascript" src="js/classement.js"></script>
        <script type="text/javascript" src="js/matches.js"></script>
    </HEAD>

    <BODY>
        <div id="general">
            <div id="banniere"></div>
            <div id="menu"></div>
            <div id="contenu">
                <div id="titre"><H1><?php echo $div_nom; ?></H1></div>
                <?php affich_connecte(); ?>
                <div id="classement"></div>
                <div id="matches"></div>
                <?php affich_admin_site(); ?>
            </div>
        </div>
    </BODY>

</HTML>
