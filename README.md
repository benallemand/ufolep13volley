This project is intended to manage volleyball championship.

Deploy
======
* git clone the repo

        php composer.phar update
* create .env file from .env.template

        source .env

Notes
=====

Files has been ignored in GIT : 
/includes/conf.php --> contains :

    <?php

    $flickr_api_key = "<api key>";

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