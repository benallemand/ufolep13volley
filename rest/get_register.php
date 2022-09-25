<?php
require_once __DIR__ . '/../classes/Register.php';
require_once __DIR__ . '/../includes/fonctions_inc.php';
/**
 * @throws Exception
 */
function get_register($parameters)
{
    $manager = new Register();
    return $manager->get_register();
}