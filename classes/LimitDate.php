<?php
require_once __DIR__ . '/Generic.php';

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 26/02/2018
 * Time: 17:02
 */
class LimitDate extends Generic
{

    /**
     * LimitDateManager constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'dates_limite';
        $this->id_name = 'id_date';
    }

    /**
     * @throws Exception
     */
    public function saveLimitDate(
        $code_competition,
        $date_limite,
        $id_date = null,
        $dirtyFields = null,
    )
    {
        $inputs = array(
            'dirtyFields' => $dirtyFields,
            'id_date' => $id_date,
            'code_competition' => $code_competition,
            'date_limite' => $date_limite,
        );
        return $this->save($inputs);
    }

    public function save($inputs): array|int|string|null
    {
        $bindings = array();
        if (empty($inputs['id_date'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " dates_limite SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id_date':
                case 'dirtyFields':
                    break;
                default:
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = ?,";
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($inputs['id_date'])) {
            $bindings[] = array('type' => 'i', 'value' => $inputs['id_date']);
            $sql .= " WHERE id_date = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function getLimitDates()
    {
        $sql = "SELECT
                    d.id_date,
                    d.code_competition,
                    c.libelle AS libelle_competition,
                    d.date_limite
                FROM dates_limite d
                JOIN competitions c ON c.code_competition = d.code_competition";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function getLimitDate($compet)
    {
        $sql = "SELECT date_limite FROM dates_limite WHERE code_competition = '$compet'";
        $results = $this->sql_manager->execute($sql);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer la date limite de la compétition $compet !");
        }
        $data = $results[0];
        return $data['date_limite'];
    }

}