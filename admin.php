<?php
require_once __DIR__ . "/classes/UserManager.php";
if (!UserManager::isAdmin()) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer"/>
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