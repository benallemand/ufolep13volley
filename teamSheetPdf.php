<?php

function toWellFormatted($string) {
    return iconv('UTF-8', 'windows-1252', $string);
}

require_once './includes/fonctions_inc.php';
require_once './libs/Fpdf/fpdf.php';
$jsonMyTeam = json_decode(getMyTeamSheet());
$jsonMyPlayers = json_decode(getMyPlayersPdf());
$pdf = new FPDF();
$pdf->SetMargins(0, 5);
$pdf->SetLineWidth(0.5);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);
$pdf->Line(20, 5, 20, 45);
$pdf->Line(0, 10, 85, 10);
$pdf->Line(0, 20, 85, 20);
$pdf->Line(0, 35, 85, 35);
$pdf->Cell(20, 5, 'Club: ', 0, 0, 'L');
$pdf->Cell(35, 5, toWellFormatted($jsonMyTeam[0]->club), 0, 1, 'L');
$pdf->Cell(20, 5, 'Championnat: ', 0, 0, 'L');
$pdf->Cell(35, 5, toWellFormatted($jsonMyTeam[0]->championnat), 0, 1, 'L');
$pdf->Cell(20, 5, 'Division: ', 0, 0, 'L');
$pdf->Cell(35, 5, toWellFormatted($jsonMyTeam[0]->division), 0, 1, 'L');
$pdf->Cell(20, 5, 'Responsable: ', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(35, 5, toWellFormatted($jsonMyTeam[0]->leader), 0, 1, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(20, 5, 'Portable: ', 0, 0, 'L');
$pdf->Cell(35, 5, toWellFormatted($jsonMyTeam[0]->portable), 0, 1, 'L');
$pdf->Cell(20, 5, 'Courriel: ', 0, 0, 'L');
$pdf->Cell(35, 5, toWellFormatted($jsonMyTeam[0]->courriel), 0, 1, 'L');
$pdf->Cell(20, 5, 'Créneau: ', 0, 0, 'L');
$pdf->Cell(35, 5, toWellFormatted($jsonMyTeam[0]->creneau), 0, 1, 'L');
$pdf->Cell(20, 5, 'Gymnase: ', 0, 0, 'L');
$pdf->Cell(35, 5, toWellFormatted($jsonMyTeam[0]->gymnase), 0, 1, 'L');
$pdf->Image('images/Ufolep13Volley2.jpg', 100, 5, 50, 30);
$pdf->Image('images/MainVolley.jpg', 175, 5, 20);
$pdf->Image('images/JeuAvantEnjeu.jpg', 175, 25, 20);
$pdf->SetXY(100, 35);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(50, 10, toWellFormatted($jsonMyTeam[0]->equipe), 1, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->SetXY(100, 50);
$pdf->Cell(40, 5, 'Visa CTSD le: ' . toWellFormatted($jsonMyTeam[0]->date_visa_ctsd), 0, 1, 'C');
$pdf->SetXY(150, 30);
$pdf->Cell(50, 15, 'Le: ....../....../......', 0, 1, 'C');
$pdf->SetXY(150, 45);
$pdf->Cell(50, 5, 'Joueurs présents:  ......................', 0, 1, 'R');
$pdf->SetXY(150, 50);
$pdf->Cell(50, 5, 'Joueuses présentes: .....................', 0, 1, 'R');
$pdf->SetXY(150, 60);
$pdf->Cell(50, 5, 'Adversaire: ..................................', 0, 1, 'R');
$pdf->SetXY(0, 70);
$NbByColumns = 6;
$widthPhoto = 20;
$offsetYPlayers = 70;
$offsetXPlayers = 60;
foreach ($jsonMyPlayers as $index => $jsonPlayer) {
    $pdf->SetXY(5 + $offsetXPlayers * floor($index / $NbByColumns), $offsetYPlayers + 35 * ($index % $NbByColumns));
    $pdf->Rect(2 + $offsetXPlayers * floor($index / $NbByColumns), $offsetYPlayers - 2 + 35 * ($index % $NbByColumns), $offsetXPlayers - 2, 32);
    $pdf->Image(toWellFormatted($jsonPlayer->path_photo), null, null, $widthPhoto);
    $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($index / $NbByColumns), $offsetYPlayers + 35 * ($index % $NbByColumns));
    $pdf->Cell(50, 5, toWellFormatted($jsonPlayer->nom), 0, 1, 'L');
    $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($index / $NbByColumns), $offsetYPlayers + 5 + 35 * ($index % $NbByColumns));
    $pdf->Cell(50, 5, toWellFormatted($jsonPlayer->prenom), 0, 1, 'L');
    $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($index / $NbByColumns), $offsetYPlayers + 10 + 35 * ($index % $NbByColumns));
    $pdf->Cell(50, 5, toWellFormatted($jsonPlayer->num_licence) . ' /' . toWellFormatted($jsonPlayer->sexe), 0, 1, 'L');
    $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($index / $NbByColumns), $offsetYPlayers + 15 + 35 * ($index % $NbByColumns));
    $pdf->Cell(16, 5, 'Présent(e) : ', 0, 0, 'L');
    $pdf->SetFont('ZapfDingbats', '', 18);
    $pdf->Cell(5, 5, 'o', 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $roles = array();
    if (toWellFormatted($jsonPlayer->is_captain) === "1") {
        $roles[] = 'CAP';
    }
    if (toWellFormatted($jsonPlayer->is_leader) === "1") {
        $roles[] = 'RESP';
    }
    if (toWellFormatted($jsonPlayer->is_vice_leader) === "1") {
        $roles[] = 'SUPP';
    }
    if (count($roles) > 0) {
        $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($index / $NbByColumns), $offsetYPlayers + 20 + 35 * ($index % $NbByColumns));
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(50, 5, implode('/', $roles), 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
    }
}
$pdf->Output();
