<?php
require_once __DIR__ . '/../classes/Team.php';
/**
 * @throws Exception
 */
function add_to_team($parameters)
{
    if (!isset($parameters['ids'])) {
        throw new Exception("Cannot find ids !");
    }
    if (empty($parameters['ids'])) {
        throw new Exception("ids is empty !");
    }
    $ids = explode(',', $parameters['ids']);
    if (empty($ids)) {
        throw new Exception("ids is empty !");
    }
    $manager = new Team();
    foreach ($ids as $id) {
        if (empty($id)) {
            continue;
        }
        $manager->add_to_team($_SESSION['id_equipe'], $id);
    }
}
