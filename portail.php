<?php include("includes/fonctions_inc.php"); ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

    <HEAD>
        <TITLE>Authentification Portail - UFOLEP 13 VOLLEY</TITLE>
        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="http://cdn.sencha.io/ext/gpl/4.2.0/resources/css/ext-all-neptune.css" rel="stylesheet" />
        <script src="http://cdn.sencha.com/ext/gpl/4.2.0/ext-all.js"></script>
        <script type="text/javascript" src="js/banniere.js"></script>
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3&amp;sensor=false"></script>
        <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
        <script type="text/javascript" src="js/mainMenu.js"></script>

        <SCRIPT language="javascript">
            function popup(page) {
                window.open(page, '', 'resizable=no, location=no, width=700, height=400, menubar=no, status=no, scrollbars=no, menubar=no');
            }
        </SCRIPT>

    </HEAD>

    <BODY>
        <div id="general">
            <div id="banniere"></div>
            <div id="menu"></div>
            <div id="contenu">
                <div id="titre"><H1>Portail Equipes</H1></div>
                <div id="portail">

                    <?php
// on traite si la variable log=err est détectée
                    $err = "";
                    if (isset($_GET['log'])) {
                        if ($_GET == "wpass") {
                            $err = "Echec d'authentification - Veuillez remplir tous les champs";
                        } elseif ($_GET == "noauth") {
                            $err = "Echec d'authentification - Login ou mot de passe incorrect !";
                        } else {
                            $err = "Echec d'authentification - Veuillez réessayer";
                        }
                    }
// on traite si les sessions $_SESSION sont créées
                    if (isset($_SESSION['login']) && isset($_SESSION['password'])) {
                        affich_portail_equipe($_SESSION['login']);
                    } else { // sinon on affiche le formulaire
                        affich_formulaire($err);
                    }
                    ?>
                </div>
            </div>
        </div>
    </BODY>

</HTML>
