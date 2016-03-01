<?php
include("includes/fonctions_inc.php");
$requires = array();
$controllers = array();
$controllers[] = "'GymnasiumsMap'";
?>
<!DOCTYPE HTML>
<HTML>
<HEAD>
    <TITLE>Annuaire Equipes - UFOLEP 13 VOLLEY</TITLE>
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
        var title = "Annuaire Equipes";
        <?php
        $idTeam = filter_input(INPUT_GET, 'id');
        $competition = filter_input(INPUT_GET, 'c');
        if (($idTeam !== NULL) || ($competition !== NULL)) {
            echo "var idTeam = $idTeam;";
            echo "var competition = '$competition';";
        }
        ?>
    </script>
    <script type="text/javascript">
        var requires = [<?php echo implode(',', $requires); ?>];
        var controllers = [<?php echo implode(',', $controllers); ?>];
    </script>
    <?php
    if (($idTeam !== NULL) || ($competition !== NULL)) {
        echo '<script type="text/javascript" src="js/teamDetails.js"></script>';
    } else {
        echo '<script type="text/javascript" src="js/annuaire.js"></script>';
    }
    ?>
</HEAD>
<BODY></BODY>
</HTML>
