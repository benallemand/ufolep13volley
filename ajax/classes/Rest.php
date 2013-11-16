<?php

require_once "../includes/fonctions_inc.php";

class Rest {

    protected $fileName;

    function __construct($fileName) {
        $this->fileName = preg_replace('/\.php$/', '', basename($fileName));
    }

    function getColumns() {
        $sql = "show columns from " . $this->fileName;
        $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
        $results = array();
        while ($data = mysql_fetch_assoc($req)) {
            $results[] = $data;
        }
        return json_encode($results);
    }

    function getPrimaryKey() {
        $columns = json_decode($this->getColumns(), true);
        foreach ($columns as $column) {
            if ($column['Key'] === 'PRI') {
                return $column['Field'];
            }
        }
        return null;
    }

    function getData() {
        $sql = "SELECT ";
        $columns = json_decode($this->getColumns(), true);
        foreach ($columns as $column) {
            switch ($column['Type']) {
                case 'tinyint(1)' :
                    $sql .= $column['Field'] . "+0 AS " . $column['Field'] . ",";
                    break;
                case 'date' :
                    $sql .= " DATE_FORMAT(" . $column['Field'] . ", '%d/%m/%Y') AS " . $column['Field'] . ",";
                    break;
                default :
                    $sql .= " " . $column['Field'] . ",";
                    break;
            }
        }
        $sql = rtrim($sql, ",");
        $sql .= " from " . $this->fileName;
        $req = mysql_query($sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysql_error());
        $results = array();
        while ($data = mysql_fetch_assoc($req)) {
            $results[] = $data;
        }
        return json_encode($results);
    }

    function saveData() {
        $message = '';
        $dataJson = file_get_contents('php://input');
        $dataArray = json_decode($dataJson, true);
        $primaryKey = $this->getPrimaryKey();
        if ($primaryKey === null) {
            return json_encode(array(
                'success' => false,
                'message' => 'Pas de cle primaire sur cette table',
                'data' => $dataJson
            ));
        }
        $sql = "UPDATE " . $this->fileName . " SET ";
        $columns = json_decode($this->getColumns(), true);
        foreach ($dataArray as $key => $value) {
            if ($key === $primaryKey) {
                continue;
            }
            foreach ($columns as $column) {
                if ($column['Field'] !== $key) {
                    continue;
                }
                switch ($column['Type']) {
                    case 'tinyint(1)' :
                        $sql .= "$key=BIT(\"$value\"),";
                        break;
                    case 'date' :
                        $sql .= "$key=DATE(STR_TO_DATE(\"$value\", '%d/%m/%Y')),";
                        break;
                    default :
                        $sql .= "$key = \"$value\",";
                        break;
                }
                break;
            }
        }
        $sql = rtrim($sql, ",");
        $sql .= " WHERE $primaryKey=\"" . $dataArray[$primaryKey] . "\";";
        $success = mysql_query($sql);
        if ($success) {
            $message = 'Sauvegarde OK';
        } else {
            $message = "Erreur SQL : $sql : " . mysql_error();
        }
        return json_encode(array(
            'success' => $success,
            'message' => $message,
            'data' => $dataJson
        ));
    }

    function deleteData() {
        $message = '';
        $dataJson = file_get_contents('php://input');
        $dataArray = json_decode($dataJson, true);
        $primaryKey = $this->getPrimaryKey();
        if ($primaryKey === null) {
            return json_encode(array(
                'success' => false,
                'message' => 'Pas de cle primaire sur cette table',
                'data' => $dataJson
            ));
        }
        $sql = "DELETE FROM " . $this->fileName;
        $sql .= " WHERE $primaryKey=\"" . $dataArray[$primaryKey] . "\";";
        $success = mysql_query($sql);
        if ($success) {
            $message = 'Suppression OK';
        } else {
            $message = "Erreur SQL : $sql : " . mysql_error();
        }
        return json_encode(array(
            'success' => $success,
            'message' => $message,
            'data' => $dataJson
        ));
    }

    function addData() {
        $message = '';
        $dataJson = file_get_contents('php://input');
        $dataArray = json_decode($dataJson, true);
        $primaryKey = $this->getPrimaryKey();
        if ($primaryKey === null) {
            return json_encode(array(
                'success' => false,
                'message' => 'Pas de cle primaire sur cette table',
                'data' => $dataJson
            ));
        }
        $sql = "INSERT INTO " . $this->fileName . " (";
        $columns = json_decode($this->getColumns(), true);
        foreach ($dataArray as $key => $value) {
            if ($key === $primaryKey) {
                continue;
            }
            $sql .= "$key,";
        }
        $sql = rtrim($sql, ",");
        $sql.= ") VALUES (";
        $columns = json_decode($this->getColumns(), true);
        foreach ($dataArray as $key => $value) {
            if ($key === $primaryKey) {
                continue;
            }
            foreach ($columns as $column) {
                if ($column['Field'] !== $key) {
                    continue;
                }
                switch ($column['Type']) {
                    case 'tinyint(1)' :
                        $sql .= "BIT(\"$value\"),";
                        break;
                    case 'date' :
                        $sql .= "DATE(STR_TO_DATE(\"$value\", '%d/%m/%Y')),";
                        break;
                    default :
                        $sql .= "\"$value\",";
                        break;
                }
                break;
            }
        }
        $sql = rtrim($sql, ",");
        $sql.= ");";
        $success = mysql_query($sql);
        if ($success) {
            $message = 'Sauvegarde OK';
        } else {
            $message = "Erreur SQL : $sql : " . mysql_error();
        }
        return json_encode(array(
            'success' => $success,
            'message' => $message,
            'data' => $dataJson
        ));
    }

    function parseRequest() {
        conn_db();
        if ($_SERVER['SERVER_NAME'] !== 'localhost') {
            mysql_query("SET NAMES UTF8");
        }
        if (!estAdmin()) {
            if ($this->fileName === 'comptes_acces') {
                $message = utf8_encode("Vous n'avez pas les droits suffisants pour executer cette action");
                echo json_encode(array(
                    'success' => false,
                    'message' => $message
                ));
                exit;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $message = utf8_encode("Vous n'avez pas les droits suffisants pour executer cette action");
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
                    echo $this->getColumns();
                } else {
                    echo $this->getData();
                }
                break;
            case 'PUT':
                echo $this->saveData();
                break;
            case 'DELETE':
                echo $this->deleteData();
                break;
            case 'POST':
                echo $this->addData();
                break;
            default:
                break;
        }
    }

}
