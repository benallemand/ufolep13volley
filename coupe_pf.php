<?php include("includes/fonctions_inc.php"); ?><?php// On r�cup�re l'ID de la phase finale if (!isset($_GET["c"])) {    die('<META HTTP-equiv="refresh" content=0;URL=index.php>');}?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"><HTML>    <HEAD>        <TITLE>Phase Finale de Coupe - UFOLEP 13 VOLLEY</TITLE>        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />        <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />        <link href="http://cdn.sencha.com/ext/gpl/4.2.0/resources/css/ext-all.css" rel="stylesheet" />        <script type="text/javascript" src="http://cdn.sencha.io/ext-4.2.0-gpl/ext-all.js"></script>        <script type="text/javascript" src="js/mainMenu.js"></script>    </HEAD>    <BODY>        <div id="general">            <div id="banniere"></div>            <div id="menu"></div>            <div id="contenu">                <div id="titre"><H1><?php echo recup_nom_compet($_GET["c"]); ?></H1></div>                <?php affich_connecte(); ?>                <div id="infos"><?php echo "Date limite des matches : ";                affich_infos("cf", $div); ?></div>                 <div id="matches"><?php affich_pf_coupe($_GET["c"]); ?></div><?php affich_admin_site(); ?>            </div>        </div>    </BODY></HTML>