<?php include("includes/fonctions_inc.php"); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
    <HEAD>
        <TITLE>Annuaire Equipes - UFOLEP 13 VOLLEY</TITLE>
        <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <link rel="shortcut icon" href="favicon.ico" /><LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="http://dev.sencha.com/ext/5.0.0/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css" rel="stylesheet" />
        <script src="http://dev.sencha.com/ext/5.0.0/ext-all.js"></script>
        <script src="http://dev.sencha.com/ext/5.0.0/packages/ext-locale/build/ext-locale-fr.js" charset="UTF-8"></script>
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3&amp;sensor=false"></script>
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
        <?php
        if (($idTeam !== NULL) || ($competition !== NULL)) {
            echo '<script type="text/javascript" src="js/teamDetails.js"></script>';
        } else {
            echo '<script type="text/javascript" src="js/annuaire.js"></script>';
        }
        ?>
    </HEAD>
    <BODY/>
</HTML>
