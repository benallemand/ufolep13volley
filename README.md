This project is intended to manage volleyball championship.

Deploy
======
* git clone the repo

        php composer.phar update
        php composer.phar install
* create .env file from .env.template, update information to fit your needs, then

        source .env

Notes
=====

Files has been ignored in GIT : 
- /players_pics/ --> contains photos of players
- /teams_pics/ --> contains photos of teams
- /match_files/ --> contains match sheets

Used code is 

- PHP/MySQL for server side
- Sencha for admin client side.
- AngularJS for client side.
- Vue.js / tailwind / daisyui migration in progress for client side.

Any contribution is welcome :)