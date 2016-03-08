<?php
include("includes/fonctions_inc.php");
if (!isAdmin()) {
    die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
}
?>

<!DOCTYPE HTML>

<HTML>
    <HEAD>
        <TITLE>Portail Administration - UFOLEP 13 VOLLEY</TITLE>
        <META http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link rel="shortcut icon" href="favicon.ico" />
        <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="https://extjs.cachefly.net/ext/gpl/5.1.0/build/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css" rel="stylesheet" />
        <script src="https://extjs.cachefly.net/ext/gpl/5.1.0/build/ext-all.js"></script>
        <script src="https://extjs.cachefly.net/ext/gpl/5.1.0/build/packages/ext-locale/build/ext-locale-fr.js"></script>
        <script type="text/javascript" src="js/libs/Commons.js"></script>
        <script type="text/javascript" src="js/administration.js"></script>
    </HEAD>
    <BODY>
    </BODY>
</HTML>
