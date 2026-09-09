<?php
// Issue #265, lot 6 : l'administration ExtJS a été remplacée par la SPA Vue
// `admin/index.html`, servie sur /admin/. On conserve l'URL historique en
// redirection : elle est en favori chez les membres de la commission depuis des
// années.
//
// Pas de garde `UserManager::isAdmin()` ici, contrairement à l'ancienne page :
// /admin/ porte la sienne côté client (`requireRoles(['admin'])`) et chaque
// endpoint REST refuse un non-admin (`rest/access.php`). Rediriger un visiteur
// non connecté vers /admin/ lui donne le message d'accès correct, au lieu du
// renvoi silencieux vers l'accueil.
// Redirection temporaire (302) et non permanente, comme les autres URLs
// historiques conservées : un 301 se grave dans le cache du navigateur et
// deviendrait pénible à corriger.
header('Location: /admin/');
exit(0);
