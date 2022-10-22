<?php
require_once __DIR__ . '/../classes/Team.php';
/**
 * @throws Exception
 */
function set_vice_leader($parameters)
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
    if (count($ids) > 1) {
        throw new Exception("Un seul suppléant par équipe !");
    }
    $manager = new Team();
    foreach ($ids as $id) {
        if (empty($id)) {
            continue;
        }
        $manager->set_vice_leader($_SESSION['id_equipe'], $id);
    }
}
