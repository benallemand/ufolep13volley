<?php
require_once __DIR__ . '/../classes/MatchManager.php';
/**
 * @throws Exception
 */
function update_match($parameters)
{
    $manager = new MatchManager();
    $manager->save_match($parameters);
}