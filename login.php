<?php
// Parametres du cookie de session (HttpOnly, Secure, SameSite) : doit
// passer AVANT le premier session_start(), d'ou cette place en tete de
// point d'entree (issue #292).
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/classes/UserManager.php';

$manager = new UserManager();
$manager->login();
