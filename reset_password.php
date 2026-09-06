<?php
// Issue #266 : le formulaire ExtJS a été remplacé par un composant Vue de la SPA
// publique. On conserve l'URL historique en redirection (elle a circulé par
// email et en favori).
header('Location: /pages/home.html#/reset_password');
exit(0);
