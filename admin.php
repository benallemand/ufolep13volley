<?php
include("includes/fonctions_inc.php");
if (!isAdmin()) {
    die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>
<HEAD>
    <TITLE>Administration UFOLEP 13 VOLLEY</TITLE>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="favicon.ico"/>
    <link href="includes/main.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css"/>
    <link href="images/fonts/icomoon/style.css" rel="stylesheet" type="text/css" media="screen"/>
    <link
        href="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css"
        rel="stylesheet"/>
    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/ext-all.js" type="text/javascript"></script>
    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-locale/build/ext-locale-fr.js"
            type="text/javascript"></script>
    <script type="text/javascript" src="js/libs/Commons.js"></script>
    <script type="text/javascript" src="js/administration.js"></script>
</HEAD>
<BODY>
</BODY>
</HTML>
