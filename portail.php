<?php
include("includes/fonctions_inc.php");
$requires = array();
$controllers = array();
$controllers[] = "'GymnasiumsMap'";
if (isset($_SESSION['login']) && isset($_SESSION['password'])) {
    if (isAdmin()) {
        die('<META HTTP-equiv="refresh" content=0;URL=admin.php>');
    }
    if (!isTeamLeader()) {
        die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
    }
    $controllers[] = "'TeamManagement'";
} else {
    $controllers[] = "'Login'";
}
?>

<!DOCTYPE HTML>

<HTML>

<HEAD>
    <TITLE>Authentification Portail - UFOLEP 13 VOLLEY</TITLE>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="favicon.ico"/>
    <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen"/>
    <link
        href="https://extjs.cachefly.net/ext/gpl/5.1.0/build/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css"
        rel="stylesheet"/>
    <script src="https://extjs.cachefly.net/ext/gpl/5.1.0/build/ext-all.js"></script>
    <script src="https://extjs.cachefly.net/ext/gpl/5.1.0/build/packages/ext-locale/build/ext-locale-fr.js"></script>
    <script type="text/javascript" src="js/libs/Commons.js"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3"></script>
    <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
    <script type="text/javascript">
        var connectedUser = '<?php echo getConnectedUser(); ?>';
        var title = "Portail";
    </script>
    <script type="text/javascript">
        var requires = [<?php echo implode(',', $requires); ?>];
        var controllers = [<?php echo implode(',', $controllers); ?>];
    </script>
    <?php
    if (isset($_SESSION['login']) && isset($_SESSION['password'])) {
        echo '<script type="text/javascript" src="js/portal.js"></script>';
    } else {
        echo '<script type="text/javascript" src="js/login.js"></script>';
    }
    ?>
</HEAD>
<BODY></BODY>
</HTML>
