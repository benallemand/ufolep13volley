<?php

require_once "../includes/fonctions_inc.php";

function getColumns() {
    $sql = "show columns from news";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    echo json_encode($results);
}

function getNews() {
    $sql = "SELECT 
        id_news,
        DATE_FORMAT(date_news, '%d/%m/%Y') AS date_news,
        texte_news,
        titre_news
        from news ORDER BY id_news DESC LIMIT 8";
    $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    echo json_encode($results);
}

function saveNews() {
    $message = '';
    $dataJson = file_get_contents('php://input');
    $dataArray = json_decode($dataJson);
    $sql = "UPDATE news
        SET date_news=DATE(STR_TO_DATE('$dataArray->date_news', '%d/%m/%Y')),
            texte_news = \"$dataArray->texte_news\",
            titre_news = \"$dataArray->titre_news\"
            WHERE id_news = $dataArray->id_news";
    $success = mysql_query($sql);
    if ($success) {
        $message = 'Sauvegarde OK';
    } else {
        $message = "Erreur SQL : $sql : " . mysql_error();
    }
    echo json_encode(array(
        'success' => $success,
        'message' => $message,
        'data' => $dataJson
    ));
}

function deleteNews() {
    $message = '';
    $dataJson = file_get_contents('php://input');
    $dataArray = json_decode($dataJson);
    $sql = "DELETE FROM news
            WHERE id_news = $dataArray->id_news";
    $success = mysql_query($sql);
    if ($success) {
        $message = 'Suppression OK';
    } else {
        $message = "Erreur SQL : $sql : " . mysql_error();
    }
    echo json_encode(array(
        'success' => $success,
        'message' => $message,
        'data' => $dataJson
    ));
}

function addNews() {
    $message = '';
    $dataJson = file_get_contents('php://input');
    $dataArray = json_decode($dataJson);
    $sql = "INSERT INTO news (date_news, texte_news, titre_news)
        VALUES (
        DATE(STR_TO_DATE('$dataArray->date_news', '%d/%m/%Y')),
            \"$dataArray->texte_news\",
            \"$dataArray->titre_news\")";
    $success = mysql_query($sql);
    if ($success) {
        $message = 'Sauvegarde OK';
    } else {
        $message = "Erreur SQL : $sql : " . mysql_error();
    }
    echo json_encode(array(
        'success' => $success,
        'message' => $message,
        'data' => $dataJson
    ));
}

conn_db();
/** Format UTF8 pour afficher correctement les accents */
mysql_query("SET NAMES UTF8");
if (!estAdmin()) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $message = utf8_encode("Vous n'avez pas les droits suffisants pour exécuter cette action");
        echo json_encode(array(
            'success' => false,
            'message' => $message
        ));
        exit;
    }
}
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($_REQUEST['GET_COLUMNS'] === 'true') {
            getColumns();
        } else {
            getNews();
        }
        break;
    case 'PUT':
        saveNews();
        break;
    case 'DELETE':
        deleteNews();
        break;
    case 'POST':
        addNews();
        break;
    default:
        break;
}
?>
