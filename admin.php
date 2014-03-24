<?php
include("includes/fonctions_inc.php");
if (!estAdmin()) {
    die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>
    <HEAD>
        <TITLE>Administration - UFOLEP 13 VOLLEY</TITLE>
        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <link rel="shortcut icon" href="favicon.ico" />
        <link href="http://cdn.sencha.io/ext/gpl/4.2.0/resources/css/ext-all-neptune.css" rel="stylesheet" />
        <script src="http://cdn.sencha.com/ext/gpl/4.2.0/ext-all.js"></script>
        <script src="http://cdn.sencha.com/ext/gpl/4.2.0/locale/ext-lang-fr.js" charset="UTF-8"></script>
        <script type="text/javascript" src="js/administration.js"></script>
    </HEAD>
    <BODY>
    </BODY>
</HTML>
