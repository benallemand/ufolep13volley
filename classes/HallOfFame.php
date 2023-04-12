<?php
/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 26/02/2018
 * Time: 14:28
 */
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/../libs/fpdf184/fpdf.php';

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
                      hof.league,
                      CONCAT(hof_champion.id, ',', hof_vice_champion.id) AS ids
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

    /**
     * @param $ids
     * @throws Exception
     */
    public function download_diploma($ids): void
    {
        if (empty($ids)) {
            throw new Exception("Aucune ligne sélectionnée !");
        }
        $ids = explode(',', $ids);
        // L = landscape
        $pdf = new FPDF('L');
        foreach ($ids as $id) {
            $this->add_diploma($id, $pdf);
        }
        $pdf->Output('I', 'diplomes.pdf');
        exit(0);
    }

    /**
     * @param $id
     * @param FPDF $pdf
     * @return void
     * @throws Exception
     */
    private function add_diploma($id, FPDF &$pdf): void
    {
        $diploma_data = $this->get_by_id($id);
        foreach ($diploma_data as $key => $value) {
            $diploma_data[$key] = utf8_decode($value);
        }
        // Ajout d'une nouvelle page
        $pdf->AddPage();
        // Définition des coordonnées des coins de la page
        $top_left_x = 10;
        $top_left_y = 10;
        $top_right_x = $pdf->GetPageWidth() - 50;
        $top_right_y = 10;
        $bottom_left_x = 10;
        $bottom_left_y = $pdf->GetPageHeight() - 50;
        $bottom_right_x = $pdf->GetPageWidth() - 50;
        $bottom_right_y = $pdf->GetPageHeight() - 50;

        // Placement des images dans chaque coin de la page
        $pdf->Image(__DIR__ . '/../images/hall-of-fame/Coin_01_hautgauche.png', $top_left_x, $top_left_y, 40);
        $pdf->Image(__DIR__ . '/../images/hall-of-fame/Coin_01_hautdroite.png', $top_right_x, $top_right_y, 40);
        $pdf->Image(__DIR__ . '/../images/hall-of-fame/Coin_01_basgauche.png', $bottom_left_x, $bottom_left_y, 40);
        $pdf->Image(__DIR__ . '/../images/hall-of-fame/Coin_01_basdroite.png', $bottom_right_x, $bottom_right_y, 40);

        // Placement de la photo en haut à gauche, juste à côté de l'image déjà dessinée
        $photo_x = $top_left_x + 50;
        $photo_y = $top_left_y;
        $pdf->Image(__DIR__ . '/../images/hall-of-fame/logo-ufolep-13-volley.png', $photo_x, $photo_y, 100);

        // Ajout de texte en haut à droite, juste à côté de l'image déjà dessinée
        $pdf->SetFont('Arial', '', 24);
        $texte_x = $top_right_x - 70;
        $texte_y = $top_right_y + 10;
        $pdf->Text($texte_x, $texte_y, "Saison " . $diploma_data['period']);

        // Ajout de texte au centre de la page
        $pdf->SetXY(0, $top_left_y +50);
        $determinant = self::starts_with(strtolower($diploma_data['league']), "championnat") ? 'le' : 'la';
        $centered_text = implode(PHP_EOL, array(
            "Les membres de la Commission Technique",
            "de l'UFOLEP Volley-Ball des Bouches-du-Rhône",
            "décernent, pour $determinant " . $diploma_data['league'] . ", le titre de",
        ));
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->MultiCell($pdf->GetPageWidth(), 10, $centered_text, 0, 'C');
        $this->pdf_add_separator($pdf);
        $centered_text = implode(PHP_EOL, array(
            $diploma_data['title'],
        ));
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->MultiCell($pdf->GetPageWidth(), 10, $centered_text, 0, 'C');
        $this->pdf_add_separator($pdf);
        $centered_text = implode(PHP_EOL, array(
            "à l’équipe de",
        ));
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->MultiCell($pdf->GetPageWidth(), 10, $centered_text, 0, 'C');
        $this->pdf_add_separator($pdf);
        $centered_text = implode(PHP_EOL, array(
            $diploma_data['team_name'],
        ));
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->MultiCell($pdf->GetPageWidth(), 10, $centered_text, 0, 'C');
        $this->pdf_add_separator($pdf);
    }

    private function pdf_add_separator(FPDF &$pdf): void
    {
        // séparateur
        $image_width = 50; // Largeur de l'image en pixels
        $image_x = ($pdf->GetPageWidth() - $image_width) / 2; // Position horizontale de l'image
        $image_y = $pdf->GetY(); // Position verticale de l'image
        $pdf->Image(__DIR__ . '/../images/hall-of-fame/Separateur_01.png', $image_x, $image_y, $image_width);
        $pdf->Ln(15);
        $pdf->SetX(0);
    }
}