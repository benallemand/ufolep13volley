<?php
/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 26/02/2018
 * Time: 14:28
 */
require_once __DIR__ . '/Generic.php';

class HallOfFame extends Generic
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'hall_of_fame';
    }

    public function getHallOfFame()
    {
        $sql = "SELECT 
        id, 
        title, 
        team_name,
        league,
        period
        FROM hall_of_fame
        ORDER BY period";
        return $this->sql_manager->execute($sql);
    }


    public function getHallOfFameDisplay()
    {
        $sql = "SELECT
                      hof.period,
                      IF(hof.title LIKE '%Division%', SUBSTRING_INDEX(hof.title, 'Division ', -1), '')                 AS division,
                      IF(hof.title LIKE '%mi-saison%', 1, 2)                  AS demi_saison,
                      hof_champion.team_name      AS champion,
                      hof_vice_champion.team_name AS vice_champion,
                      hof.league
                FROM hall_of_fame hof
                  JOIN  hall_of_fame hof_champion ON    hof_champion.league = hof.league AND
                                                        hof_champion.period = hof.period AND
                                                        (IF(hof_champion.title LIKE '%Division%', 
                                                            SUBSTRING_INDEX(hof_champion.title, 'Division ', -1), 
                                                            '')) = (IF( hof.title LIKE '%Division%', 
                                                                        SUBSTRING_INDEX(hof.title, 'Division ', -1), 
                                                                                                  '')) AND
                        (IF(hof_champion.title LIKE '%mi-saison%', 1, 2)) = (IF(hof.title LIKE '%mi-saison%', 1, 2)) AND
                        (hof_champion.title NOT LIKE '%Vice%' AND
                        hof_champion.title NOT LIKE '%Finaliste%')
                  JOIN  hall_of_fame hof_vice_champion ON
                                                hof_vice_champion.league = hof.league AND
                        hof_vice_champion.period = hof.period AND
                        (IF(hof_vice_champion.title LIKE '%Division%', SUBSTRING_INDEX(hof_vice_champion.title, 'Division ', -1), '')) = (IF(hof.title LIKE '%Division%', SUBSTRING_INDEX(hof.title, 'Division ', -1), '')) AND
                        (IF(hof_vice_champion.title LIKE '%mi-saison%', 1, 2)) = (IF(hof.title LIKE '%mi-saison%', 1, 2)) AND
                        (hof_vice_champion.title LIKE '%Vice%' OR
                         hof_vice_champion.title LIKE '%Finaliste%')
                GROUP BY
                                    hof.league,
                  hof.period,
                  IF(hof.title LIKE '%Division%', SUBSTRING_INDEX(hof.title, 'Division ', -1), ''),
                  IF(hof.title LIKE '%mi-saison%', 1, 2)
                                ORDER BY hof.league,
                  IF(hof.title LIKE '%mi-saison%', 1, 2),
                  IF(hof.title LIKE '%Division%', SUBSTRING_INDEX(hof.title, 'Division ', -1), '')";
        return $this->sql_manager->execute($sql);
    }


    /**
     * @param $title
     * @param $team_name
     * @param $period
     * @param $league
     * @return int|string
     * @throws Exception
     */
    public function insert($title, $team_name, $period, $league): int|string
    {
        $sql = "INSERT INTO hall_of_fame SET 
                title = ?, 
                team_name = ?, 
                period = ?,
                league = ?";
        $bindings = array(
            array('type' => 's', 'value' => $title),
            array('type' => 's', 'value' => $team_name),
            array('type' => 's', 'value' => $period),
            array('type' => 's', 'value' => $league),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function saveHallOfFame(
        $id,
        $title,
        $team_name,
        $period,
        $league,
        $dirtyFields = null
    ): int|array|string|null
    {
        $inputs = array(
            'id' => $id,
            'title' => $title,
            'team_name' => $team_name,
            'period' => $period,
            'league' => $league,
            'dirtyFields' => $dirtyFields,
        );
        return $this->save($inputs);
    }

    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " hall_of_fame SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                default:
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = ?,";
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (empty($inputs['id'])) {
        } else {
            $bindings[] = array('type' => 'i', 'value' => $inputs['id']);
            $sql .= " WHERE id = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * for each competition id
     * - get competition date
     * - for each division
     * -- get leader
     * -- insert into hall of fame the leader
     * -- get vice-leader
     * -- insert into hall of fame the vice-leader
     * @throws Exception
     */
    public function generateHallOfFame($ids)
    {
        if (empty($ids)) {
            throw new Exception("Aucune compétition sélectionnée !");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            require_once __DIR__ . '/../classes/Competition.php';
            $competition_manager = new Competition();
            $competitions = $competition_manager->getCompetitions("c.id = $id");
            if (count($competitions) !== 1) {
                throw new Exception("Une seule compétition doit être trouvée !");
            }
            if (!$competition_manager->isCompetitionOver($competitions[0]['id'])) {
                throw new Exception("La compétition n'est pas terminée !!!");
            }
            $competition_date = DateTime::createFromFormat("d/m/Y", $competitions[0]['start_date']);
            require_once __DIR__ . '/../classes/Rank.php';
            $rank_manager = new Rank();
            $divisions = $rank_manager->getDivisionsFromCompetition($competitions[0]['code_competition']);
            foreach ($divisions as $division) {
                $leader = $rank_manager->getLeader($competitions[0]['code_competition'], $division['division']);
                $vice_leader = $rank_manager->getViceLeader($competitions[0]['code_competition'], $division['division']);
                require_once __DIR__ . '/../classes/HallOfFame.php';
                if (intval($competition_date->format('m')) >= 9) {
                    $title_season = " mi-saison ";
                    $period = $competition_date->format('Y') . "-" . (intval($competition_date->format('Y')) + 1);
                } else {
                    $title_season = " Dept. ";
                    $period = (intval($competition_date->format('Y')) - 1) . "-" . $competition_date->format('Y');
                }
                $this->insert(
                    "Championne" . $title_season . "de Division " . $division['division'],
                    $leader['equipe'],
                    $period,
                    $competitions[0]['libelle']
                );
                $this->insert(
                    "Vice-championne" . $title_season . "de Division " . $division['division'],
                    $vice_leader['equipe'],
                    $period,
                    $competitions[0]['libelle']
                );
            }
        }
    }


}