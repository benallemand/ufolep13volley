<?php

require_once __DIR__ . "/../classes/CompetitionManager.php";
try {
    $competition_manager = new CompetitionManager();
    $ids = filter_input(INPUT_POST, 'ids');
    $competition_manager->delete_blacklist_by_city($ids);
} catch (Exception $ex) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Erreur durant la modification: ' . $ex->getMessage()
    ));
    return;
}
echo json_encode(array(
    'success' => true,
    'message' => 'Modification OK'
));
return;