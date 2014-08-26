This project is intended to manage volleyball championship.

This is written in French, but will be modified in order to be fully english, or with language selection (FR/EN).

Some folders/files have been ignored in GIT : 

/nbproject/sql/ufolep_13volley.sql --> contains sql dump of actual database data
/includes/db_inc.php --> contains function : function conn_db().  It aims to set db connection :
    - $db = mysqli_connect($server, $user, $password);
    - mysqli_select_db($db, $base);
/nbproject/private/
/images/ --> contains misc pictures : 
    - root folder contains major banners, icons
    - equipes folder contains photos of teams
    - photos folder
        - ctsd folder contains photos of site's team
/players_pics/ --> contains photos of players

Used code is PHP/MySQL for server side, Javascript/Sencha ExtJS/Sencha Touch for client side.

Any contribution is welcome :)