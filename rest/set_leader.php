<?php
require_once __DIR__ . '/../classes/TeamManager.php';
require_once __DIR__ . '/../includes/fonctions_inc.php';
/**
 * @throws Exception
 */
function set_leader($parameters)
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
        throw new Exception("Un seul responsable par équipe !");
    }
    $manager = new TeamManager();
    foreach ($ids as $id) {
        if (empty($id)) {
            continue;
        }
        $manager->set_leader($_SESSION['id_equipe'], $id);
    }
}
