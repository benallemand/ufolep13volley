<?php include("includes/fonctions_inc.php");

// On récupère l'ID de la division 
$div=(isset($_GET["d"])) ? $_GET["d"] : ""; 
if($div=="") {die ('<META HTTP-equiv="refresh" content=0;URL=index.php>'); }
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

<HEAD>
  <TITLE>Championnat Féminin - UFOLEP 13 VOLLEY</TITLE>
  <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
</HEAD>

<BODY>
  <div id="general">
  	<div id="banniere"></div>
	<div id="menu"><SCRIPT src="Menu.js"></SCRIPT></div>
    <div id="contenu">
	  <div id="titre"><H1>Championnat Féminin</H1></div>
<?php affich_connecte();?>
      <div id="classement"><?php affich_classement("f",$div);?></div>
 	  <div id="infos"><?php  echo "Date limite des matches : "; affich_infos("f",$div);?></div> 
 	  <div id="matches"><?php affich_journee("f",$div);?></div>
<?php affich_admin_site();?>
	</div>
  </div>
</BODY>

</HTML>
