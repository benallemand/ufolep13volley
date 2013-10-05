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
        <TITLE>Championnat Masculin - UFOLEP 13 VOLLEY</TITLE>
        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="http://cdn.sencha.com/ext/gpl/4.2.0/resources/css/ext-all.css" rel="stylesheet" />
        <script type="text/javascript" src="http://cdn.sencha.io/ext-4.2.0-gpl/ext-all.js"></script>
        <script type="text/javascript" src="js/banniere.js"></script>
        <script type="text/javascript" src="js/mainMenu.js"></script>
    </HEAD>

    <BODY>
        <div id="general">
            <div id="banniere"></div>
            <div id="menu"></div>
            <div id="contenu">
                <div id="titre"><H1>Division <?php echo $div; ?> - Championnat Masculin</H1></div>
                <?php affich_connecte(); ?>
                <div id="classement"><?php affich_classement("m", $div); ?></div>
                <div id="infos">
                    <?php echo "Date limite des matches : ";
                    affich_infos("m", $div);
                    ?>
                </div> 
                <div id="matches"><?php affich_journee("m", $div); ?></div>
<?php affich_admin_site(); ?> 
            </div>
        </div>
    </BODY>

</HTML>