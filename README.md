This project is intended to manage volleyball championship.

Some folders/files have been ignored in GIT : 

/nbproject/sql/*clustermysql05_hosteur_com.sql --> contains sql dump of actual database data
/includes/db_inc.php --> contains function : function conn_db().  It aims to set db connection :
    - $db = mysqli_connect($server, $user, $password);
    - mysqli_select_db($db, $base);
/nbproject/private/
/players_pics/ --> contains photos of players
/teams_pics/ --> contains photos of teams

Used code is PHP/MySQL for server side, Javascript/Sencha ExtJS/Sencha Touch for client side.

Any contribution is welcome :)