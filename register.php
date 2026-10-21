<?php
// Issue #249 : le formulaire d'inscription public a été remplacé par l'espace
// responsable de club (composant Vue). On conserve l'URL historique en
// redirection : la page cible exige d'être connecté en tant que responsable
// de club.
require_once __DIR__ . '/bootstrap.php';
header('Location: ' . ufolep_url('/pages/my_page.html#/club_registrations'));
exit(0);
