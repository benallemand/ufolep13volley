<?php include("includes/fonctions_inc.php");?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>


<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>D&eacute;tails d'une &eacute;quipe - UFOLEP 13 VOLLEY</title>
<link href="site.css" rel="stylesheet" type="text/css" />
<style type="text/css">
<!--
body {
	background-image: url(images/fond.gif);
	background-repeat: repeat;
}
#fond {
	position:absolute;
	width:740px;
	height:260px;
	z-index:1;
	left: 10;
	top: 10;
	visibility: visible;
}
#Titre {
	position:absolute;
	width:730px;
	height:36px;
	z-index:1;
	left: 5px;
	top: 5px;
	visibility: visible;
	background-image: url(images/bandeau_titre_page.gif);
}
#contenu {
	position:absolute;
	width:730px;
	height:210px;
	z-index:2;
	left: 5px;
	top: 45px;
	background-color: #FFFFFF;
}
-->
</style>
</head>
<?php
$id_equipe = $_GET['id'];
$id_table = $_GET['t'];
?>

<div id="fond">
  <div class="titre_journee" id="Titre">
    <div align="center" class="titre_tableau_accueil">Informations Equipe </div>
  </div>
  <div id="contenu">
<?php affich_details_equipe($id_equipe,$id_table); ?>
  </div>
</div>
</body>
</html>
