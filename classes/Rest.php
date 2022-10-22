<?php

require_once __DIR__ . "/Generic.php";

class Rest extends Generic
{

    protected $fileName;

    function __construct($fileName)
    {
        parent::__construct();
        $this->fileName = preg_replace('/\.php$/', '', basename($fileName));
    }

    /**
     * @throws Exception
     */
    function getColumns()
    {
        $sql = "SHOW columns FROM " . $this->fileName;
        return $this->sql_manager->execute($sql);
    }

    function getPrimaryKey()
    {
        $columns = $this->getColumns();
        foreach ($columns as $column) {
            if ($column['Key'] === 'PRI') {
                return $column['Field'];
            }
        }
        return null;
    }

    function getData()
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS ";
        $columns = $this->getColumns();
        $queribles = array();
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
                    $queribles[] = $column['Field'];
                    break;
            }
        }
        $sql = rtrim($sql, ",");
        $sql .= " FROM " . $this->fileName;
        $whereClause = filter_input(INPUT_GET, 'query');
        if ($whereClause !== null) {
            $sql .= " WHERE ";
            foreach ($queribles as $index => $querible) {
                if ($index > 0) {
                    $sql .= " OR ";
                }
                $sql .= " $querible LIKE '%$whereClause%' ";
            }
        }
        $startParam = filter_input(INPUT_GET, 'start');
        $limitParam = filter_input(INPUT_GET, 'limit');
        if ($startParam !== null) {
            $sql .= " limit $startParam,$limitParam";
        }
        try {
            $results = $this->sql_manager->execute($sql);
            $sql2 = "SELECT FOUND_ROWS() AS total";
            $results2 = $this->sql_manager->execute($sql2);
        } catch (Exception $exception) {
            print_r($sql);
            exit(-1);
        }
        return json_encode(array(
            'results' => $results,
            'totalCount' => $results2[0]['total']));
    }

    function saveData()
    {
        $bindings = array();
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
        $columns = $this->getColumns();
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
                        $bindings[] = array(
                            'type' => 'i',
                            'value' => $value
                        );
                        $sql .= "$key = ?,";
                        break;
                    case 'date' :
                        $bindings[] = array(
                            'type' => 's',
                            'value' => $value
                        );
                        $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                        break;
                    default :
                        $bindings[] = array(
                            'type' => 's',
                            'value' => $value
                        );
                        $sql .= "$key = ?,";
                        break;
                }
                break;
            }
        }
        $sql = rtrim($sql, ",");
        $sql .= " WHERE $primaryKey=\"" . $dataArray[$primaryKey] . "\";";
        $this->sql_manager->execute($sql, $bindings);
        return json_encode(array(
            'success' => true,
            'message' => 'Sauvegarde OK',
            'data' => $dataJson
        ));
    }

    function deleteData()
    {
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
        $this->sql_manager->execute($sql);
        return json_encode(array(
            'success' => true,
            'message' => 'Suppression OK',
            'data' => $dataJson
        ));
    }

    function addData()
    {
        $bindings = array();
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
        foreach ($dataArray as $key => $value) {
            if ($key === $primaryKey) {
                continue;
            }
            $sql .= "$key,";
        }
        $sql = rtrim($sql, ",");
        $sql .= ") VALUES (";
        $columns = $this->getColumns();
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
                        $bindings[] = array(
                            'type' => 'i',
                            'value' => $value
                        );
                        $sql .= "?,";
                        break;
                    case 'date' :
                        $bindings[] = array(
                            'type' => 's',
                            'value' => $value
                        );
                        $sql .= "DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                        break;
                    default :
                        $bindings[] = array(
                            'type' => 's',
                            'value' => $value
                        );
                        $sql .= "?,";
                        break;
                }
                break;
            }
        }
        $sql = rtrim($sql, ",");
        $sql .= ");";
        $this->sql_manager->execute($sql, $bindings);
        return json_encode(array(
            'success' => true,
            'message' => 'Sauvegarde OK',
            'data' => $dataJson
        ));
    }

    function parseRequest()
    {
        if (!UserManager::isAdmin()) {
            if ($this->fileName === 'comptes_acces') {
                $message = "Vous n'avez pas les droits suffisants pour executer cette action";
                echo json_encode(array(
                    'success' => false,
                    'message' => $message
                ));
                exit;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $message = "Vous n'avez pas les droits suffisants pour executer cette action";
                echo json_encode(array(
                    'success' => false,
                    'message' => $message
                ));
                exit;
            }
        }
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if (filter_input(INPUT_GET, 'GET_COLUMNS') === 'true') {
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
