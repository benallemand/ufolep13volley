<?php

require_once __DIR__ . '/Generic.php';

class Indicator extends Generic
{

    private $fieldLabel;
    private $sql;

    function __construct($fieldLabel, $sql)
    {
        parent::__construct();
        $this->fieldLabel = $fieldLabel;
        $this->sql = $sql;
    }

    function execSqlGetDetails()
    {
        return $this->sql_manager->execute($this->sql);
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
