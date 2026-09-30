<?php
/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 26/02/2018
 * Time: 14:28
 */
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/PdfText.php';
require_once __DIR__ . '/Rank.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Fpdf\Fpdf;

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


    /**
     * @throws Exception
     */
    public function getHallOfFameDisplay()
    {
        $sql = file_get_contents(__DIR__ . '/../sql/get_hall_of_fame.sql');
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
        $league
    ): int|array|string|null
    {
        $inputs = array(
            'id' => $id,
            'title' => $title,
            'team_name' => $team_name,
            'period' => $period,
            'league' => $league,
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
                    break;
                default:
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = ?,";
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($inputs['id'])) {
            $bindings[] = array('type' => 'i', 'value' => $inputs['id']);
            $sql .= " WHERE id = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Deux premiers de chaque division sur la période, au barème de la
     * compétition et de la période (issue #347).
     * @throws Exception
     */
    public function getTop2ByDivision($code_competition, $date_debut, $date_fin): array
    {
        // Requête pour obtenir les 2 premiers de chaque division
        $sql = file_get_contents(__DIR__ . '/../sql/get_top2_by_division.sql');
        // Barème de la période : FFVB pour un championnat à partir de 2026-2027
        // (issue #347), UFOLEP sinon — régénérer une saison passée ne la
        // réécrit pas avec le nouveau barème.
        if (Rank::uses_ffvb_scale($code_competition, $date_fin)) {
            $points = "SUM(CASE WHEN forfait = 1 THEN -1
                                WHEN score_pour = 3 AND score_contre <= 1 THEN 3
                                WHEN score_pour = 3 THEN 2
                                WHEN score_contre = 3 AND score_pour = 2 THEN 1
                                ELSE 0 END)";
            $order = "points DESC, gagnes DESC,
                      CASE WHEN sets_contre > 0 THEN sets_pour / sets_contre WHEN sets_pour > 0 THEN 1e9 ELSE 0 END DESC,
                      CASE WHEN pts_contre > 0 THEN pts_pour / pts_contre WHEN pts_pour > 0 THEN 1e9 ELSE 0 END DESC";
        } else {
            $points = "SUM(IF(score_pour = 3, 3, 0)) + SUM(IF(score_contre = 3 AND forfait = 0, 1, 0))";
            $order = "points DESC, diff DESC, sets_pour DESC";
        }
        $sql = str_replace(array('{POINTS}', '{ORDER}'), array($points, $order), $sql);

        $bindings = array(
            array('type' => 's', 'value' => $code_competition),
            array('type' => 's', 'value' => $date_debut),
            array('type' => 's', 'value' => $date_fin),
            array('type' => 's', 'value' => $code_competition),
            array('type' => 's', 'value' => $date_debut),
            array('type' => 's', 'value' => $date_fin),
        );

        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Génère le palmarès à partir des matchs certifiés entre deux dates
     * @param string $code_competition Code de la compétition (m, f, mo)
     * @param string $date_debut Date de début (format Y-m-d)
     * @param string $date_fin Date de fin (format Y-m-d)
     * @param string $period Période à afficher (ex: "2025-2026")
     * @param string $title_season Titre de la saison (ex: "mi-saison" ou "Dept.")
     * @throws Exception
     */
    public function generateHallOfFameFromMatches($code_competition, $date_debut, $date_fin, $period, $title_season)
    {
        if (empty($code_competition) || empty($date_debut) || empty($date_fin)) {
            throw new Exception("Les paramètres code_competition, date_debut et date_fin sont obligatoires !");
        }

        // Récupérer le libellé de la compétition
        require_once __DIR__ . '/Competition.php';
        $competition_manager = new Competition();
        // paramètre lié (issue #355)
        $competitions = $competition_manager->getCompetitions("c.code_competition = ?",
            array(array('type' => 's', 'value' => (string)$code_competition)));
        if (count($competitions) === 0) {
            throw new Exception("Compétition non trouvée pour le code: $code_competition");
        }
        $libelle_competition = $competitions[0]['libelle'];

        $results = $this->getTop2ByDivision($code_competition, $date_debut, $date_fin);

        if (empty($results)) {
            throw new Exception("Aucun résultat trouvé pour cette période !");
        }

        $count = 0;
        foreach ($results as $row) {
            $title = ($row['rang'] == 1 ? "Championne" : "Vice-championne") 
                   . " " . $title_season . " de Division " . $row['division'];
            $this->insert($title, $row['equipe'], $period, $libelle_competition);
            $count++;
        }

        return $count;
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
     * Convertit de l'UTF-8 vers l'encodage des polices « core » de FPDF.
     *
     * Visait l'ISO-8859-1, qui ne connaît pas l'apostrophe courbe : le nom
     * d'équipe « Les Jeu’nettes » perdait la sienne sur son diplôme. Le détail
     * est dans `PdfText`.
     *
     * @param mixed $value
     * @return string
     */
    private function pdf_text($value): string
    {
        return PdfText::encode($value);
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
            $diploma_data[$key] = $this->pdf_text($value);
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
        $pdf->SetXY(0, $top_left_y + 50);
        $determinant = self::starts_with(strtolower($diploma_data['league']), "championnat") ? 'le' : 'la';
        $centered_text = implode(PHP_EOL, array(
            $this->pdf_text("Les membres de la Commission Technique"),
            $this->pdf_text("de l'UFOLEP Volley-Ball des Bouches-du-Rhône"),
            $this->pdf_text("décernent, pour $determinant ") . $diploma_data['league'] . $this->pdf_text(", le titre de"),
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
        $centered_text = $this->pdf_text(implode(PHP_EOL, array(
            "à l'équipe de",
        )));
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