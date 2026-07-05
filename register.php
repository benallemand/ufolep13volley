<?php
// Issue #249 : le formulaire d'inscription public a été remplacé par l'espace
// responsable de club (composant Vue). On conserve l'URL historique en
// redirection : la page cible exige d'être connecté en tant que responsable
// de club.
header('Location: /pages/my_page.html#/club_registrations');
exit(0);
