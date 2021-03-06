<?php

require_once __DIR__ . '/../../includes/fonctions_inc.php';

class Indicator
{

    private $fieldLabel;
    private $sql;

    function __construct($fieldLabel, $sql)
    {
        $this->fieldLabel = $fieldLabel;
        $this->sql = $sql;
    }

    function execSqlGetDetails()
    {
        global $db;
        conn_db();
        mysqli_query($db, "SET SESSION group_concat_max_len = 1000000");
        $req = mysqli_query($db, $this->sql);
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        disconn_db();
        return $results;
    }

    function getResult()
    {
        $results = $this->execSqlGetDetails();
        return array(
            'fieldLabel' => $this->fieldLabel,
            'value' => count($results),
            'details' => $results
        );
    }

}
