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
        $this->fieldLabel = $fieldLabel;
        $this->sql = $sql;
    }

    function getFieldLabel() {
        return $this->fieldLabel;
    }

    function execSqlGetValue() {
        conn_db();
        $req = mysql_query($this->getSqlValue());
        $results = array();
        while ($data = mysql_fetch_assoc($req)) {
            $results[] = $data;
        }
        mysql_close();
        return $results[0]['cnt'];
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

    function getSqlValue() {
        return "select count(*) AS cnt from ("
                . "$this->sql"
                . ") t";
    }

    function getSql() {
        return $this->sql;
    }

    function getResult() {
        return array(
            'fieldLabel' => utf8_encode($this->getFieldLabel()),
            'value' => $this->execSqlGetValue(),
            'details' => $this->execSqlGetDetails()
        );
    }

}
