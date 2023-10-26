<?php

require_once __DIR__ . '/Generic.php';

class Indicator extends Generic
{

    private string $fieldLabel;
    private string $sql;
    private string $type;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    function __construct($fieldLabel, $sql, $type = 'info')
    {
        parent::__construct();
        $this->fieldLabel = $fieldLabel;
        $this->sql = $sql;
        $this->type = $type;

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
            'type' => $this->type,
            'value' => count($results),
            'details' => $results
        );
    }

}
