<?php
require_once __DIR__ . '/../classes/Register.php';
/**
 * @throws Exception
 */
function get_register($parameters)
{
    $manager = new Register();
    return $manager->get_register();
}