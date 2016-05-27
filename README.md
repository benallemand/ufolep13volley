This project is intended to manage volleyball championship.

1 file has been ignored in GIT : 
/includes/db_inc.php --> contains :

    <?php

    $flickr_api_key = "<api key>";

    function conn_db()
    {
        global $db;
        $db = mysqli_connect('<mysql host>', '<user>', '<password>', '<db name>', '<port>');
        mysqli_query($db, "SET NAMES UTF8");
        mysqli_query($db, "SET lc_time_names = 'fr_FR'");
    }

    function disconn_db()
    {
        global $db;
        mysqli_close($db);
    }

/players_pics/ --> contains photos of players
/teams_pics/ --> contains photos of teams

Used code is PHP/MySQL for server side, Javascript/Sencha ExtJS/Sencha Touch for client side.

Any contribution is welcome :)