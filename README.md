This project is intended to manage volleyball championship.

Files has been ignored in GIT : 
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

/classes/Configuration.php contains:

    <?php
    
    class Configuration
    {
        const MAIL_HOST = 'smtp.gmail.com';
        const MAIL_SMTPAUTH = true;
        const MAIL_USERNAME = '<email address>';
        const MAIL_PASSWORD = '<password>';
        const MAIL_SMTPSECURE = 'tls';
        const MAIL_PORT = 587;
    }


- /players_pics/ --> contains photos of players
- /teams_pics/ --> contains photos of teams
- /match_files/ --> contains match sheets

Used code is 

- PHP/MySQL for server side
- Sencha for admin client side.
- AngularJS for client side.

Any contribution is welcome :)