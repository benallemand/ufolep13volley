<?php
require_once __DIR__ . '/../classes/Players.php';
/**
 * @throws Exception
 */
function update_player($parameters)
{
    $manager = new Players();
    $manager->update_player($parameters);
}