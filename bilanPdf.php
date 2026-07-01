<?php
/**
 * Genere le "Bilan d'activites" annuel au format PDF.
 *
 * Les chiffres sont RECALCULES cote serveur a partir de la saison (non
 * falsifiables) ; seuls les commentaires libres proviennent du formulaire.
 * Reserve aux administrateurs.
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/UserManager.php';
require_once __DIR__ . '/classes/Bilan.php';

use Fpdf\Fpdf;

/** Convertit une chaine UTF-8 vers l'encodage attendu par FPDF (cp1252). */
function toWellFormatted($string): string
{
    return !empty($string) ? iconv('UTF-8', 'windows-1252//TRANSLIT', $string) : '';
}

if (!UserManager::isAdmin()) {
    http_response_code(403);
    die('Acces refuse.');
}

try {
    $saison = filter_input(INPUT_POST, 'saison', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $responsable = filter_input(INPUT_POST, 'responsable', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: ($_SESSION['login'] ?? '');
    $types_public = filter_input(INPUT_POST, 'types_public') ?: '';
    $formations = filter_input(INPUT_POST, 'formations') ?: '';
    $reunions = filter_input(INPUT_POST, 'reunions') ?: '';
    $impression_generale = filter_input(INPUT_POST, 'impression_generale') ?: '';
    $coupe_nationale = filter_input(INPUT_POST, 'coupe_nationale') ?: '';
    $axes_amelioration = filter_input(INPUT_POST, 'axes_amelioration') ?: '';

    $bilan = new Bilan();
    $data = $bilan->getBilanData($saison);

    $pdf = new FPDF();
    $pdf->SetMargins(15, 12, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // --- helpers de mise en page --------------------------------------------
    $sectionTitle = function (string $title) use ($pdf) {
        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(0, 7, toWellFormatted($title), 0, 1, 'L', true);
        $pdf->Ln(1);
    };
    $paragraph = function (string $text) use ($pdf) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 5, toWellFormatted($text !== '' ? $text : 'Néant'));
    };

    // --- en-tete ------------------------------------------------------------
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, toWellFormatted("BILAN D'ACTIVITÉS " . $data['saison']), 0, 1, 'C');
    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, toWellFormatted('DISCIPLINE : Volley-ball'), 0, 1, 'L');
    $pdf->Cell(0, 6, toWellFormatted('RESPONSABLE DE LA COMMISSION : ' . $responsable), 0, 1, 'L');

    // --- manifestations, matchs ---------------------------------------------
    $sectionTitle('MANIFESTATIONS, MATCHS, RENCONTRES');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(130, 7, toWellFormatted('Compétition'), 1, 0, 'L');
    $pdf->Cell(0, 7, toWellFormatted('Nombre de matchs'), 1, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    foreach ($data['matchs'] as $ligne) {
        $pdf->Cell(130, 6, toWellFormatted($ligne['competition']), 1, 0, 'L');
        $pdf->Cell(0, 6, (int)$ligne['nb_matchs'], 1, 1, 'C');
    }
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(130, 7, 'TOTAL', 1, 0, 'L');
    $pdf->Cell(0, 7, (int)$data['total_matchs'], 1, 1, 'C');

    // --- clubs / licencies --------------------------------------------------
    $sectionTitle('NOMBRE DE CLUBS PARTICIPANT');
    $paragraph($data['nb_clubs'] . ' clubs');

    $sectionTitle('NOMBRE DE LICENCIÉS PARTICIPANT');
    $paragraph($data['nb_licencies'] . ' licenciés');

    // --- sections libres ----------------------------------------------------
    $sectionTitle('TYPES DE PUBLIC PARTICIPANT');
    $paragraph($types_public);

    $sectionTitle('FORMATIONS EFFECTUÉES');
    $paragraph($formations);

    $sectionTitle('RÉUNIONS STATUTAIRES / PARTICIPATION');
    $paragraph($reunions);

    $sectionTitle('IMPRESSION GÉNÉRALE SUR LA SAISON / BESOINS');
    $paragraph($impression_generale);

    // --- championnat departemental (chiffres base) --------------------------
    $sectionTitle('CHAMPIONNAT DÉPARTEMENTAL');
    $paragraph($data['nb_recompenses'] . ' équipes récompensées (1er et 2e de chaque division, '
        . 'à mi-saison et en fin de saison, pour les 3 championnats)');
    $paragraph($data['nb_coupes'] . ' coupe(s) décernée(s) :');
    $pdf->SetFont('Arial', '', 10);
    foreach ($data['coupes'] as $coupe) {
        $pdf->Cell(6);
        $pdf->MultiCell(0, 5, toWellFormatted('- ' . $coupe['recompense'] . ' : ' . $coupe['vainqueur']));
    }

    // --- coupe nationale / axes --------------------------------------------
    $sectionTitle('COUPE NATIONALE');
    $paragraph($coupe_nationale);

    $sectionTitle("AXES D'AMÉLIORATION");
    $paragraph($axes_amelioration);

    $pdf->Output('D', 'Bilan ' . $data['saison'] . '.pdf');
} catch (Exception $e) {
    http_response_code(500);
    echo 'Erreur lors de la génération du bilan : ' . $e->getMessage();
}
