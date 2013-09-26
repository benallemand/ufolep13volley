<?php include("includes/fonctions_inc.php");?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

<HEAD>
  <TITLE>UFOLEP 13 VOLLEY</TITLE>
  <META http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <LINK href="includes/main.css" rel="stylesheet" type="text/css" media="screen" />
</HEAD>

<BODY>
  <div id="change_g">
  <div id="change_c">
<?php
if (isset($_GET['a'])&&!empty($_GET['a'])) 
  {
  if ($_GET['a']=="me") {modif_equipe($_GET['i'],$_GET['c']);}
  if ($_GET['a']=="ms") {modif_score($_GET['m']);}
  if ($_GET['a']=="ie") {liste_equipe($_GET['c'],$_GET['d']);}
  }
?>
  </div>
  </div>
</BODY>

</HTML>
