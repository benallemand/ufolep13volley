<?php
require_once __DIR__ . '/../classes/MatchMgr.php';
/**
 * @throws Exception
 */
function update_match($parameters)
{
    $manager = new MatchMgr();
    $manager->save_match($parameters);
}