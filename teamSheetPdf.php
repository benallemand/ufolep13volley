<?php

function toWellFormatted($string)
{
    return !empty($string) ? iconv('UTF-8', 'windows-1252', $string) : '';
}

require_once __DIR__ . '/vendor/autoload.php';

use Fpdf\Fpdf;

require_once __DIR__ . '/classes/Team.php';
require_once __DIR__ . '/classes/Players.php';

try {
    $id = filter_input(INPUT_GET, 'id');
    if ($id === NULL) {
        @session_start();
        $id = $_SESSION['id_equipe'];
    }
    $team_manager = new Team();
    $player = new Players();
    $teamSheet = $team_manager->getTeamSheet($id);
    $playersPdf = $player->getPlayersPdf($id);
    if (empty($playersPdf)) {
        die('Erreur durant la recuperation des joueurs !');
    }
    $jsonPlayers = $playersPdf;
    $pdf = new FPDF();
    $pdf->SetMargins(0, 5);
    $pdf->SetLineWidth(0.5);
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, 'Club: ', 0, 0, 'L');
    $pdf->MultiCell(40, 5, toWellFormatted($teamSheet['club']), 'L', 'L');
    $pdf->Cell(20, 5, 'Championnat: ', 'T', 0, 'L');
    $pdf->Cell(40, 5, toWellFormatted($teamSheet['championnat']), 'TL', 1, 'L');
    $pdf->Cell(20, 5, 'Division: ', 0, 0, 'L');
    $pdf->Cell(40, 5, toWellFormatted($teamSheet['division']), 'L', 1, 'L');
    $pdf->Cell(20, 5, 'Responsable: ', 'T', 0, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(40, 5, toWellFormatted($teamSheet['leader']), 'TL', 1, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, 'Portable: ', 0, 0, 'L');
    $pdf->Cell(40, 5, toWellFormatted($teamSheet['portable']), 'L', 1, 'L');
    $pdf->Cell(20, 5, 'Courriel: ', 0, 0, 'L');
    $pdf->Cell(40, 5, toWellFormatted($teamSheet['courriel']), 'L', 1, 'L');
    $pdf->Cell(20, 5, toWellFormatted('Créneau(x): '), 0, 0, 'L');
    $pdf->MultiCell(35, 5, toWellFormatted($teamSheet['gymnasiums_list']), 'L', 'L');
    $pdf->Cell(20, 5, 'Visa CTSD: ', 'T', 0, 'L');
    $pdf->Cell(40, 5, toWellFormatted($teamSheet['date_visa_ctsd']), 'TL', 1, 'L');
    $pdf->Cell(20, 5, 'Nota: ', 'T', 0, 'L');
    $pdf->MultiCell(40, 5, toWellFormatted("Les joueurs en rose n'ont pas été validés par la CTSD"), 'TL', 'L');
    $offsetYPlayers = $pdf->GetY() + 5;
    $pdf->Image('images/Ufolep13Volley2.jpg', 80, 5, 50, 30);
    $pdf->Image('images/MainVolley.jpg', 150, 5, 20);
    $pdf->Image('images/JeuAvantEnjeu.jpg', 150, 25, 20);
    $pdf->SetXY(80, 40);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->MultiCell(50, 7, toWellFormatted($teamSheet['equipe']), 0, 'C');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(150, 30);
    $pdf->Cell(30, 5, 'Le:', 0, 0, 'L');
    $pdf->Cell(30, 5, '............/............/............', 0, 1, 'R');
    $pdf->SetXY(150, 35);
    $pdf->Cell(30, 5, toWellFormatted('Joueurs présents: '), 0, 0, 'L');
    $pdf->Cell(30, 5, '..............................', 0, 1, 'R');
    $pdf->SetXY(150, 40);
    $pdf->Cell(30, 5, toWellFormatted('Joueuses présentes: '), 0, 0, 'L');
    $pdf->Cell(30, 5, '..............................', 0, 1, 'R');
    $pdf->SetXY(150, 50);
    $pdf->Cell(30, 5, 'Adversaire: ', 0, 0, 'L');
    $pdf->Cell(30, 5, '...................................', 0, 1, 'R');
    $pdf->SetXY(150, 55);
    $pdf->Cell(50, 5, toWellFormatted('Fiche équipe adverse consultée : '), 0, 0, 'L');
    $pdf->SetFont('ZapfDingbats', 'B', 18);
    $pdf->Cell(5, 5, 'o', 0, 0, 'R');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(150, 60);
    $pdf->Cell(30, 5, toWellFormatted("Signatures des responsables d'équipes: "), 0, 0, 'L');
    $pdf->SetXY(150, 70);
    $pdf->Cell(30, 5, '...................................', 0, 1, 'L');
    $offsetYPlayers = $pdf->GetY() + 5;
    $NbByColumns = 6;
    $widthPhoto = 15;
    $offsetXPlayers = 50;
    $heightPlayer = 30;
    foreach ($jsonPlayers as $index => $jsonPlayer) {
        $currentIndex = $index;
        $pdf->SetXY(5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + $heightPlayer * ($currentIndex % $NbByColumns));
        if ($jsonPlayer['est_actif'] === 1) {
            $pdf->Rect(2 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers - 2 + $heightPlayer * ($currentIndex % $NbByColumns), $offsetXPlayers - 2, $heightPlayer - 2);
        } else {
            $pdf->SetFillColor(255, 192, 203);
            $pdf->Rect(2 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers - 2 + $heightPlayer * ($currentIndex % $NbByColumns), $offsetXPlayers - 2, $heightPlayer - 2, 'DF');
            $pdf->SetFillColor(0, 0, 0);
        }
        if (!empty($jsonPlayer['path_photo'])) {
            $key_photo = 'path_photo';
            if (!file_exists($jsonPlayer['path_photo_low'])) {
                $player->generateLowPhoto($jsonPlayer['path_photo']);
            }
            if (file_exists($jsonPlayer['path_photo_low'])) {
                $key_photo = 'path_photo_low';
            }
            $pdf->Image(toWellFormatted($jsonPlayer[$key_photo]),
                null,
                null,
                $widthPhoto);
        }
        $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + $heightPlayer * ($currentIndex % $NbByColumns));
        $pdf->Cell(50, 5, toWellFormatted($jsonPlayer['prenom']), 0, 1, 'L');
        $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + 5 + $heightPlayer * ($currentIndex % $NbByColumns));
        $pdf->Cell(50, 5, toWellFormatted($jsonPlayer['nom']), 0, 1, 'L');
        $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + 10 + $heightPlayer * ($currentIndex % $NbByColumns));
        $pdf->Cell(50, 5, toWellFormatted($jsonPlayer['num_licence_ext']) . ' /' . toWellFormatted($jsonPlayer['sexe']), 0, 1, 'L');
        $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + 15 + $heightPlayer * ($currentIndex % $NbByColumns));
        $pdf->Cell(16, 5, toWellFormatted('Présent(e) : '), 0, 0, 'L');
        $pdf->SetFont('ZapfDingbats', 'B', 18);
        $pdf->Cell(5, 5, 'o', 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $roles = array();
        if ($jsonPlayer['is_captain'] == "1") {
            $roles[] = 'CAP';
        }
        if ($jsonPlayer['is_leader'] == "1") {
            $roles[] = 'RESP';
        }
        if ($jsonPlayer['is_vice_leader'] == "1") {
            $roles[] = 'SUPP';
        }
        if (count($roles) > 0) {
            $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + 20 + $heightPlayer * ($currentIndex % $NbByColumns));
            $pdf->SetTextColor(255, 0, 0);
            $pdf->Cell(50, 5, implode('/', $roles), 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
        }
    }
    $pdf->Output('I', toWellFormatted($teamSheet['equipe'] . '.pdf'));
} catch (Exception $e) {
    echo "Erreur ! " . $e->getMessage();
}
