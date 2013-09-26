<?php include("includes/fonctions_inc.php");

// On récupère l'ID de la division 
$div=(isset($_GET["d"])) ? $_GET["d"] : ""; 
if ($div=="4o") {$div_nom = "Play-offs - Division 4 Masculine";}
if ($div=="4d") {$div_nom = "Play-downs - Division 4 Masculine";}
if ($div=="5o") {$div_nom = "Play-offs - Division 5 Masculine";}
if ($div=="5d") {$div_nom = "Play-downs - Division 5 Masculine";}

if($div=="") {die ('<META HTTP-equiv="refresh" content=0;URL=index.php>'); }
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

<HEAD>
  <TITLE>Championnat Masculin - UFOLEP 13 VOLLEY</TITLE>
  <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
</HEAD>

<BODY>
  <div id="general">
  	<div id="banniere"></div>
	<div id="menu"><SCRIPT src="Menu.js"></SCRIPT></div>
    <div id="contenu">
	  <div id="titre"><H1><?php echo $div_nom;?></H1></div>
<?php affich_connecte();?>
	  <div id="classement"><?php affich_classement("pf",$div);?></div>
  	  <div id="matches"><?php affich_journee("pf",$div);?></div>
<?php affich_admin_site();?>
	</div>
  </div>
</BODY>

</HTML>
