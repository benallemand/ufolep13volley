<?php
include("includes/fonctions_inc.php");
$division = filter_input(INPUT_GET, 'd');
$code_competition = filter_input(INPUT_GET, 'c');
if (empty($division) || empty($code_competition)) {
    die('<META HTTP-equiv="refresh" content=0;URL=index.php>');
    // dummy update
}
$requires = array();
$controllers = array();
$controllers[] = "'GymnasiumsMap'";
if (isAdmin()) {
    $controllers[] = "'Matches'";
    $controllers[] = "'Classement'";
}
$competition = getCompetition($code_competition);
$libelle = $competition['libelle'];
$title = "$libelle - UFOLEP 13 VOLLEY";
$group_title = "Division";
switch ($code_competition) {
    case 'kh':
    case 'c':
        $group_title = "Poule";
        break;
}

?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?php echo $title; ?> </TITLE>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="favicon.ico"/>
    <link href="includes/main.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css"/>
    <link href="images/fonts/icomoon/style.css" rel="stylesheet" type="text/css" media="screen"/>
    <link
        href="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-theme-neptune/build/resources/ext-theme-neptune-all-debug.css"
        rel="stylesheet"/>
    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/ext-all.js" type="text/javascript"></script>
    <script src="https://cdn.sencha.com/ext/gpl/5.1.0/build/packages/ext-locale/build/ext-locale-fr.js"></script>
    <script type="text/javascript" src="js/libs/Commons.js"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3"></script>
    <script type="text/javascript" src="js/libs/GMapPanel.js"></script>
    <script type="text/javascript">
        var competition = '<?php echo $code_competition; ?>';
        var division = '<?php echo $division; ?>';
        var connectedUser = '<?php echo getConnectedUser(); ?>';
        var title = "<?php echo $group_title . " " . $division; ?> - <?php echo $libelle; ?>";
        var limitDateLabel = "Date limite des matches : <?php getLimitDate($code_competition); ?>";
    </script>
    <script type="text/javascript">
        var requires = [<?php echo implode(',', $requires); ?>];
        var controllers = [<?php echo implode(',', $controllers); ?>];
    </script>
    <script type="text/javascript" src="js/championship.js"></script>
</HEAD>
<BODY></BODY>
</HTML>