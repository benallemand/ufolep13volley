<?php include("includes/fonctions_inc.php"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>


<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>News</title>
<link href="site.css" rel="stylesheet" type="text/css" />
<style type="text/css">
<!--
body {
	background-image: url(images/fond.gif);
	background-repeat: repeat;
}
#fond {
	position:absolute;
	width:470px;
	height:200px;
	z-index:1;
	background-color: #FFFFFF;
	left: 10;
	top: 10;
}
-->
</style></head>


<?php 
//Déclaration des constantes
$server = "clustermysql05.hosteur.com"    ;
$user = "ufolep_volley"    ;
$password = "vietvod@o"; 
$base = "ufolep_13volley" ;

// on se connecte à MySQL 
$db = mysql_connect($clustermysql05.hosteur.com, $user, $password); 
mysql_select_db($base,$db); 

//On récupère l'ID de la news 
$ID=(isset($_GET["ID"])) ? $_GET["ID"] : ""; 

$sql = 'SELECT * FROM news WHERE id_news = '.$ID; 
$req = mysql_query($sql) or die('Erreur SQL !<br>'.$sql.'<br>'.mysql_error()); 
while($data = mysql_fetch_array($req))
	{//2
echo '<div class="calque_news_titre" id="titrenews"><img src="images/puce.gif" width="37" height="10"> '.$data['titre_news'].'</div>';
echo '<div class="calque_news_texte" id="textenews">'.$data['texte_news'].'</div>';
	}//2
?>

<div id="fond"></div>
</body>
</html>
