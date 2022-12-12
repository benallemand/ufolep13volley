<?php
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE>Fiche équipe en ligne</TITLE>
    <META
            http-equiv="Content-Type"
            content="text/html; charset=utf-8"/>
    <link
            rel="shortcut icon"
            href="favicon.ico"/>
    <link
            href="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/theme-crisp-touch/resources/theme-crisp-touch-all.css"
            rel="stylesheet"/>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/ext-all.js"
            type="text/javascript"></script>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/locale/locale-fr.js"
            type="text/javascript"></script>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/theme-crisp-touch/theme-crisp-touch.js"
            type="text/javascript"></script>
    <script
            type="text/javascript" src="js/libs/Commons.js"></script>
    <script
            type="text/javascript" src="js/manage_match_players.js"></script>
    <script>
        <?php
        @session_start();
        $session = json_encode($_SESSION);
        ?>
        var user_details = <?php echo($session) ?>;
    </script>
</HEAD>
<BODY>
</BODY>
</HTML>