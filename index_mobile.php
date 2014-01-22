<?php include("includes/fonctions_inc.php"); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
    <head> 
        <title>jQuery Mobile </title> 
        <link href="//code.jquery.com/mobile/1.4.0/jquery.mobile-1.4.0.min.css" rel="stylesheet" type="text/css" /> 
        <script src="//code.jquery.com/jquery-1.10.2.min.js"></script> 
        <script src="//code.jquery.com/mobile/1.4.0/jquery.mobile-1.4.0.min.js"></script> 
    </head>
    <body>
        <div class="ui-grid-d">
            <div class="ui-block-a"><div class="ui-bar ui-bar-a" style="height:60px">Competition</div></div>
            <div class="ui-block-b"><div class="ui-bar ui-bar-a" style="height:60px">Domicile</div></div>
            <div class="ui-block-c"><div class="ui-bar ui-bar-a" style="height:60px">Exterieur</div></div>
            <div class="ui-block-d"><div class="ui-bar ui-bar-a" style="height:60px">Score</div></div>
            <div class="ui-block-e"><div class="ui-bar ui-bar-a" style="height:60px">Date</div></div>
            <?php
            $lastResults = json_decode(getLastResults(), true);
            foreach ($lastResults as $result) {
                echo "<div class='ui-block-a'><div class='ui-bar ui-bar-a' style='height:60px'>" . $result['competition'] . "</div></div>";
                if ($result['score_equipe_dom'] > $result['score_equipe_ext']) {
                    echo "<div class='ui-block-b'><div class='ui-bar ui-bar-a' style='height:60px'>" . $result['equipe_domicile'] . "</div></div>";
                    echo "<div class='ui-block-c'><div class='ui-bar ui-bar-b' style='height:60px'>" . $result['equipe_exterieur'] . "</div></div>";
                } else {
                    echo "<div class='ui-block-b'><div class='ui-bar ui-bar-b' style='height:60px'>" . $result['equipe_domicile'] . "</div></div>";
                    echo "<div class='ui-block-c'><div class='ui-bar ui-bar-a' style='height:60px'>" . $result['equipe_exterieur'] . "</div></div>";
                }
                echo "<div class='ui-block-d'><div class='ui-bar ui-bar-a' style='height:60px'>" . $result['set1'] . " " . $result['set2'] . " " . $result['set3'] . " " . $result['set4'] . " " . $result['set5'] . "</div></div>";
                echo "<div class='ui-block-e'><div class='ui-bar ui-bar-a' style='height:60px'>" . $result['date_reception'] . "</div></div>";
            }
            ?>
        </div>
    </body>
</HTML>
