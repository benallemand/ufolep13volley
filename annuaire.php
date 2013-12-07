<?php include("includes/fonctions_inc.php"); ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

    <HEAD>
        <TITLE>Annuaire Equipes - UFOLEP 13 VOLLEY</TITLE>
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
                <div id="titre"><H1>Annuaire Equipes</H1></div>

                <?php
                if (isset($_GET['id']) && isset($_GET['c'])) {
                    ?>
                    <div id="annuaire"><div id="details_equipe">
                            <?php
                            affich_details_equipe($_GET['id'], $_GET['c']);
                            ?>
                        </div></div>
                    <?php
                } else {
                    ?>
                    <div id="annuaire_complet"></div>
                    <script type="text/javascript" src="js/annuaire.js"></script>
                    <?php
                }
                ?>

            </div>
        </div>
    </BODY>

</HTML>
