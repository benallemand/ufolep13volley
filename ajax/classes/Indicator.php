<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Indicator
 *
 * @author Ben
 */
require_once '../includes/fonctions_inc.php';

class Indicator {

    private $fieldLabel;
    private $sql;

    function __construct($fieldLabel, $sql) {
        $this->fieldLabel = utf8_encode_mix($fieldLabel);
        $this->sql = $sql;
    }

    function execSqlGetDetails() {
        conn_db();
        $req = mysql_query($this->sql);
        $results = array();
        while ($data = mysql_fetch_assoc($req)) {
            $results[] = $data;
        }
        mysql_close();
        return $results;
    }

    function getResult() {
        $results = $this->execSqlGetDetails();
        return array(
            'fieldLabel' => $this->fieldLabel,
            'value' => count($results),
            'details' => $results
        );
    }

}
