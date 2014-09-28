<?php include("includes/fonctions_inc.php"); ?>
<!DOCTYPE HTML>
<html manifest="" lang="en-US">
    <head>
        <meta charset="windows-1252">
        <title>Ufolep13Mobile</title>
        <link href="http://cdn.sencha.io/touch/sencha-touch-2.4.0/resources/css/sencha-touch.css" rel="stylesheet" />
        <script src="http://cdn.sencha.io/touch/sencha-touch-2.4.0/sencha-touch-all.js"></script>
        <script type="text/javascript" src="js/libs/Commons.js"></script>
        <?php
        if (isset($_SESSION['login']) && isset($_SESSION['password'])) {
            if (isAdmin()) {
                die('<META HTTP-equiv="refresh" content=0;URL=admin.php>');
            }
            if (isTeamLeader()) {
                echo'<script type="text/javascript" src="js/mobilePortal.js"></script>';
            } 
            else {
                echo'<script type="text/javascript" src="js/mobile.js"></script>';
            } 
        } else {
            echo'<script type="text/javascript" src="js/mobile.js"></script>';
        }
        ?>
    </head>
    <body>
    </body>
</html>
