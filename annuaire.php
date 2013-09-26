<?php include("includes/fonctions_inc.php");?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

<HEAD>
  <TITLE>Annuaire Equipes - UFOLEP 13 VOLLEY</TITLE>
  <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
</HEAD>

<BODY>
<div id="general">
	<div id="banniere"></div>
	<div id="menu"><SCRIPT src="Menu.js"></SCRIPT></div>
    	<div id="contenu">
			<div id="titre"><H1>Annuaire Equipes</H1></div>

			<div id="annuaire"><div id="details_equipe"><?php	if(isset($_GET['id'])&&isset($_GET['c'])) {affich_details_equipe($_GET['id'],$_GET['c']);}
			else {affich_annuaire();}?>
			</div></div>
		</div>
</div>
</BODY>

</HTML>
