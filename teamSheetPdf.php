<?php

function toWellFormatted($string)
{
    return iconv('UTF-8', 'windows-1252', $string);
}

require_once './includes/fonctions_inc.php';
require_once './libs/Fpdf/fpdf.php';
$id = filter_input(INPUT_GET, 'id');
if ($id === NULL) {
    $id = $_SESSION['id_equipe'];
}
$teamSheet = getTeamSheet($id);
if ($teamSheet === false) {
    die('Erreur durant la recuperation des informations !');
}
$jsonTeam = json_decode($teamSheet);
$playersPdf = getPlayersPdf($id, '', false);
if ($playersPdf === false) {
    die('Erreur durant la recuperation des joueurs !');
}
$jsonPlayers = json_decode($playersPdf);
$pdf = new FPDF();
$pdf->SetMargins(0, 5);
$pdf->SetLineWidth(0.5);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(20, 5, 'Club: ', 0, 0, 'L');
$pdf->MultiCell(40, 5, toWellFormatted($jsonTeam[0]->club), 'L', 'L');
$pdf->Cell(20, 5, 'Championnat: ', 'T', 0, 'L');
$pdf->Cell(40, 5, toWellFormatted($jsonTeam[0]->championnat), 'TL', 1, 'L');
$pdf->Cell(20, 5, 'Division: ', 0, 0, 'L');
$pdf->Cell(40, 5, toWellFormatted($jsonTeam[0]->division), 'L', 1, 'L');
$pdf->Cell(20, 5, 'Responsable: ', 'T', 0, 'L');
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(40, 5, toWellFormatted($jsonTeam[0]->leader), 'TL', 1, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(20, 5, 'Portable: ', 0, 0, 'L');
$pdf->Cell(40, 5, toWellFormatted($jsonTeam[0]->portable), 'L', 1, 'L');
$pdf->Cell(20, 5, 'Courriel: ', 0, 0, 'L');
$pdf->Cell(40, 5, toWellFormatted($jsonTeam[0]->courriel), 'L', 1, 'L');
$pdf->Cell(20, 5, toWellFormatted('Créneau(x): '), 0, 0, 'L');
$pdf->MultiCell(35, 5, toWellFormatted($jsonTeam[0]->gymnasiums_list), 'L', 'L');
$pdf->Cell(20, 5, 'Visa CTSD: ', 'T', 0, 'L');
$pdf->Cell(40, 5, toWellFormatted($jsonTeam[0]->date_visa_ctsd), 'TL', 1, 'L');
$pdf->Cell(20, 5, 'Nota: ', 'T', 0, 'L');
$pdf->MultiCell(40, 5, toWellFormatted("Les joueurs en rose n'ont pas été validés par la CTSD"), 'TL', 'L');
$offsetYPlayers = $pdf->GetY() + 5;
$pdf->Image('images/Ufolep13Volley2.jpg', 80, 5, 50, 30);
$pdf->Image('images/MainVolley.jpg', 150, 5, 20);
$pdf->Image('images/JeuAvantEnjeu.jpg', 150, 25, 20);
$pdf->SetXY(80, 40);
$pdf->SetFont('Arial', '', 16);
$pdf->MultiCell(50, 7, toWellFormatted($jsonTeam[0]->equipe), 0, 'C');
$pdf->SetFont('Arial', '', 8);
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
$NbByColumns = 6;
$widthPhoto = 15;
$offsetXPlayers = 50;
$heightPlayer = 30;
//$nbGirls = 0;
//$isFirstMaleReached = false;
//$isSortMaleFemaleNeeded = false;
//if (($jsonTeam[0]->code_competition === 'kh') || ($jsonTeam[0]->code_competition === 'kf')) {
//    $isSortMaleFemaleNeeded = true;
//}
foreach ($jsonPlayers as $index => $jsonPlayer) {
    $currentIndex = $index;
//    if ($isSortMaleFemaleNeeded) {
//        if ($jsonPlayer->sexe === 'M') {
//            $isFirstMaleReached = true;
//            $currentIndex = $index + ($NbByColumns - $nbGirls);
//        }
//        if (!$isFirstMaleReached) {
//            $nbGirls++;
//        }
//    }
    $pdf->SetXY(5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + $heightPlayer * ($currentIndex % $NbByColumns));
    if (toWellFormatted($jsonPlayer->est_actif) === "1") {
        $pdf->Rect(2 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers - 2 + $heightPlayer * ($currentIndex % $NbByColumns), $offsetXPlayers - 2, $heightPlayer - 2);
    } else {
        $pdf->SetFillColor(255, 192, 203);
        $pdf->Rect(2 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers - 2 + $heightPlayer * ($currentIndex % $NbByColumns), $offsetXPlayers - 2, $heightPlayer - 2, 'DF');
        $pdf->SetFillColor(0, 0, 0);
    }
    $pdf->Image(toWellFormatted($jsonPlayer->path_photo), null, null, $widthPhoto);
    $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + $heightPlayer * ($currentIndex % $NbByColumns));
    $pdf->Cell(50, 5, toWellFormatted($jsonPlayer->prenom), 0, 1, 'L');
    $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + 5 + $heightPlayer * ($currentIndex % $NbByColumns));
    $pdf->Cell(50, 5, toWellFormatted($jsonPlayer->nom), 0, 1, 'L');
    $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + 10 + $heightPlayer * ($currentIndex % $NbByColumns));
    $pdf->Cell(50, 5, toWellFormatted($jsonPlayer->num_licence_ext) . ' /' . toWellFormatted($jsonPlayer->sexe), 0, 1, 'L');
    $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + 15 + $heightPlayer * ($currentIndex % $NbByColumns));
    $pdf->Cell(16, 5, toWellFormatted('Présent(e) : '), 0, 0, 'L');
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
        $pdf->SetXY($widthPhoto + 5 + $offsetXPlayers * floor($currentIndex / $NbByColumns), $offsetYPlayers + 20 + $heightPlayer * ($currentIndex % $NbByColumns));
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(50, 5, implode('/', $roles), 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
    }
}
$pdf->Output(toWellFormatted($jsonTeam[0]->equipe . '.pdf'), 'D');
