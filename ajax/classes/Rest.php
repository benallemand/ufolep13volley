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
        $sql = "SELECT * from " . $this->fileName;
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
        foreach ($dataArray as $key => $value) {
            if ($key === $primaryKey) {
                continue;
            }
            $sql .= "$key = '$value',";
        }
        $sql = rtrim($sql, ",");
        $sql .= " WHERE $primaryKey='" . $dataArray[$primaryKey] . "';";
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
        $dataArray = json_decode($dataJson);
        $primaryKey = $this->getPrimaryKey();
        if ($primaryKey === null) {
            return json_encode(array(
                'success' => false,
                'message' => 'Pas de cle primaire sur cette table',
                'data' => $dataJson
            ));
        }
        $sql = "DELETE FROM " . $this->fileName . "
        WHERE $primaryKey='" . $dataArray[$primaryKey] . "';";
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
        $dataArray = json_decode($dataJson);
        $primaryKey = $this->getPrimaryKey();
        if ($primaryKey === null) {
            return json_encode(array(
                'success' => false,
                'message' => 'Pas de cle primaire sur cette table',
                'data' => $dataJson
            ));
        }
        $sql = "INSERT INTO " . $this->fileName . " (";
        foreach ($dataArray as $key => $value) {
            if ($key === $primaryKey) {
                continue;
            }
            $sql .= "$key,";
        }
        $sql = rtrim($sql, ",");
        $sql.= ") VALUES (";
        foreach ($dataArray as $key => $value) {
            if ($key === $primaryKey) {
                continue;
            }
            $sql .= "'$value',";
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
        mysql_query("SET NAMES UTF8");
        if (!estAdmin()) {
            $message = utf8_encode("Vous n'avez pas les droits suffisants pour executer cette action");
            echo json_encode(array(
                'success' => false,
                'message' => $message
            ));
            exit;
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
