<?php

require_once '../includes/fonctions_inc.php';

function getFieldLabel() {
    return 'Licences dupliquees';
}

function execSqlGetValue($sql) {
    conn_db();
    $req = mysql_query($sql);
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    mysql_close();
    return $results[0]['cnt'];
}

function execSqlGetDetails($sql) {
    conn_db();
    $req = mysql_query($sql);
    $results = array();
    while ($data = mysql_fetch_assoc($req)) {
        $results[] = $data;
    }
    mysql_close();
    return $results;
}

function getSqlValue($sql) {
    return "select count(*) AS cnt from ($sql) t";
}

function getSql() {
    return "SELECT
num_licence, 
COUNT(*) AS nb_duplicats 
FROM joueurs 
GROUP BY num_licence
having count(*) > 1 AND num_licence != 'Encours'";
}

$results = array(
    'fieldLabel' => getFieldLabel(),
    'value' => execSqlGetValue(getSqlValue(getSql())),
    'details' => execSqlGetDetails(getSql())
);
echo json_encode(array('results' => $results));
