<?php
require_once __DIR__ . '/../classes/MatchManager.php';
/**
 * @throws Exception
 */
function insert_matches_from_script() {
    $manager = new MatchManager();
    $manager->run_insert_matches_from_script();
}
