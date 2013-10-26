<?php

require_once "../includes/fonctions_inc.php";

function getData() {
    $sql = "SELECT * from " . preg_replace('/\.php$/', '', basename(__FILE__));
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    echo json_encode($results);
}

function saveData() {
    $message = '';
    $success = false;
    $dataJson = file_get_contents('php://input');
    echo json_encode(array(
        'success' => $success,
        'message' => $message,
        'data' => $dataJson
    ));
}

function deleteData() {
    $message = '';
    $success = false;
    $dataJson = file_get_contents('php://input');
    echo json_encode(array(
        'success' => $success,
        'message' => $message,
        'data' => $dataJson
    ));
}

function addData() {
    $message = '';
    $success = false;
    $dataJson = file_get_contents('php://input');
    echo json_encode(array(
        'success' => $success,
        'message' => $message,
        'data' => $dataJson
    ));
}

conn_db();
mysql_query("SET NAMES UTF8");
if (!estAdmin()) {
        $message = utf8_encode("Vous n'avez pas les droits suffisants pour exécuter cette action");
        echo json_encode(array(
            'success' => false,
            'message' => $message
        ));
        exit;
}
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        getData();
        break;
    case 'PUT':
        saveData();
        break;
    case 'DELETE':
        deleteData();
        break;
    case 'POST':
        addData();
        break;
    default:
        break;
}
?>
