<?php
include("includes/fonctions_inc.php");
if (!isAdmin()) {
    die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE>Administration UFOLEP 13 VOLLEY</TITLE>
    <META
            http-equiv="Content-Type"
            content="text/html; charset=utf-8"/>
    <link
            rel="shortcut icon"
            href="favicon.ico"/>
    <link
            href="includes/main.css"
            rel="stylesheet"
            type="text/css"
            media="screen"/>
    <link
            rel="stylesheet"
            href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css"/>
    <link
            href="images/fonts/icomoon/style.css"
            rel="stylesheet"
            type="text/css"
            media="screen"/>
    <link
            href="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/theme-neptune/resources/theme-neptune-all.css"
            rel="stylesheet"/>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/ext-all.js"
            type="text/javascript"></script>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/locale/locale-fr.js"
            type="text/javascript"></script>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/theme-neptune/theme-neptune.js"
            type="text/javascript"></script>
    <script
            type="text/javascript" src="js/libs/Commons.js"></script>
    <script
            type="text/javascript" src="js/administration.js"></script>
</HEAD>
<BODY>
</BODY>
</HTML>