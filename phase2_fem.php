<?php include("includes/fonctions_inc.php"); ?>
<?php $compet = "ff"; ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

    <HEAD>
        <TITLE>Championnat Féminin Phase 2 - UFOLEP 13 VOLLEY</TITLE>
        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="http://cdn.sencha.com/ext/gpl/4.2.0/resources/css/ext-all.css" rel="stylesheet" />
        <script type="text/javascript" src="http://cdn.sencha.io/ext-4.2.0-gpl/ext-all.js"></script>
        <script type="text/javascript" src="js/mainMenu.js"></script>
    </HEAD>

    <BODY>
        <div id="general">
            <div id="banniere"></div>
            <div id="menu"></div>
            <div id="contenu">
                <div id="titre"><H1>Championnat Féminin - Phase 2</H1></div>
                <?php affich_connecte(); ?>
                <?php //affich_admin_page($compet);?>
                <BR><p class="H1">Poule 1</p>
                <div id="classement"><?php affich_classement($compet, "1"); ?></div>
                <div id="matches"><?php affich_journee($compet, "1"); ?></div>
                <BR><p class="H1">Poule 2</p>
                <div id="classement"><?php affich_classement($compet, "2"); ?></div>
                <div id="matches"><?php affich_journee($compet, "2"); ?></div>
                <BR><p class="H1">Poule 3</p>
                <div id="classement"><?php affich_classement($compet, "3"); ?></div>
                <div id="matches"><?php affich_journee($compet, "3"); ?></div>
                <BR><p class="H1">Poule 4</p>
                <div id="classement"><?php affich_classement($compet, "4"); ?></div>
                <div id="matches"><?php affich_journee($compet, "4"); ?></div>
                <BR><hr>

                <?php affich_admin_site(); ?>
            </div>
        </div>
    </BODY>

</HTML>
