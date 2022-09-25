<?php
require_once __DIR__ . '/../classes/Register.php';
/**
 * @throws Exception
 */
function register($parameters)
{
    $manager = new Register();
    $manager->register($parameters);
}