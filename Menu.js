//************ Menu Principal ************//
Color0M  = '#FFFFFF'  //Couleur du texte
Color1M  = '#0099CC'  //Couleur Arrière
Color2M  = '#555555'  //Couleur Arrière Surbrillance
Color3M  = '#FFFFFF'  //Couleur Bordure ????
PosY     = 0		  //Distance entre le haut de l'écran et le menu
LargeurM = 143		  //Largeur
HauteurM =  30       //Hauteur
AlignM   = 'Center'  //'center','right','left'
FontM    = 'Arial'  //Police
SizeM    =  14       //Taille de la Police
WeightM  = 'bold'    //Epaisseur de la Police
CursorM  = 'default' //Curseur-> default,hand...

var menu = new Array;
var content;
var dgt;
var i=0;
//--------[Texte/Html]------------------[ Adresse ]--------------------------//
menu[i++]='Masculins'   ;menu[i++]='#'
menu[i++]='Féminines' ;menu[i++]='#'
menu[i++]='Coupe Isoardi'        ;menu[i++]='#'
menu[i++]='Coupe K. Hanna'        ;menu[i++]='#'
menu[i++]='Portail Equipes'        ;menu[i++]='#'
menu[i++]='Documents' ;menu[i++]='docs.php'
menu[i++]='Forum'        ;menu[i++]='http://ufolep13volley.forumzen.com/'

//************ Sous-Menus ************//
Color0S  = '#FFFFFF' //Couleur du texte
Color1S  = '#0099CC' //Couleur Arrière
Color2S  = '#555555' //Couleur Arrière Surbrillance
Color3S  = '#FFFFFF' //Couleur Bordure
EnLigne  =   1       //1:pour ranger sur une seule ligne  0:en colonne
LargeurS = 150       //Largeur
HauteurS =  30       //Hauteur
AlignS   = 'center'  //'center','right','left'
FontS    = 'Arial'  //Police
SizeS    =  12       //Taille de la Police
WeightS  = 'bold'    //Epaisseur de la Police
FonduS   =   1       //1:Fondu, 0:aucun
CursorS  = 'default' //Curseur-> default,hand...

k=-1; zlien=new Array

//------------[Texte/Html]------------------[ Adresse ]-------------------------------//
i=0; zlien[++k]=new Array //Sous menus pour Masculins
zlien[k][i++]='Division 1';zlien[k][i++]='champ_masc.php?d=1'
zlien[k][i++]='Division 2';zlien[k][i++]='champ_masc.php?d=2'
zlien[k][i++]='Division 3';zlien[k][i++]='champ_masc.php?d=3'
zlien[k][i++]='Division 4';zlien[k][i++]='champ_masc.php?d=4'
zlien[k][i++]='Division 5';zlien[k][i++]='champ_masc.php?d=5'
zlien[k][i++]='Division 6';zlien[k][i++]='champ_masc.php?d=6'
zlien[k][i++]='Division 7';zlien[k][i++]='champ_masc.php?d=7'

i=0; zlien[++k]=new Array //Sous menus pour Féminines
zlien[k][i++]='Division 1';zlien[k][i++]='champ_fem.php?d=1'
zlien[k][i++]='Division 2';zlien[k][i++]='champ_fem.php?d=2'
zlien[k][i++]='Championnats Phase 2';zlien[k][i++]='phase2_fem.php'
zlien[k][i++]='Tournois Féminins';zlien[k][i++]='tournois_fem.php'

i=0; zlien[++k]=new Array //Sous menus pour Coupe Isoardi
zlien[k][i++]='Poule 1'     ;zlien[k][i++]='coupe.php?d=1'
zlien[k][i++]='Poule 2'     ;zlien[k][i++]='coupe.php?d=2'
zlien[k][i++]='Poule 3'     ;zlien[k][i++]='coupe.php?d=3'
zlien[k][i++]='Poule 4'     ;zlien[k][i++]='coupe.php?d=4'
zlien[k][i++]='Poule 5'     ;zlien[k][i++]='coupe.php?d=5'
zlien[k][i++]='Poule 6'     ;zlien[k][i++]='coupe.php?d=6'
zlien[k][i++]='Poule 7'     ;zlien[k][i++]='coupe.php?d=7'
zlien[k][i++]='Poule 8'     ;zlien[k][i++]='coupe.php?d=8'
zlien[k][i++]='Poule 9'     ;zlien[k][i++]='coupe.php?d=9'
zlien[k][i++]='Poule 10'     ;zlien[k][i++]='coupe.php?d=10'
zlien[k][i++]='Poule 11'     ;zlien[k][i++]='coupe.php?d=11'
zlien[k][i++]='Phases Finales'     ;zlien[k][i++]='coupe_pf.php?c=cf'

i=0; zlien[++k]=new Array //Sous menus pour Coupe Koury Hanna
zlien[k][i++]='Poule 1'     ;zlien[k][i++]='coupe_kh.php?d=1'
zlien[k][i++]='Poule 2'     ;zlien[k][i++]='coupe_kh.php?d=2'
zlien[k][i++]='Poule 3'     ;zlien[k][i++]='coupe_kh.php?d=3'
zlien[k][i++]='Poule 4'     ;zlien[k][i++]='coupe_kh.php?d=4'
zlien[k][i++]='Poule 5'     ;zlien[k][i++]='coupe_kh.php?d=5'
zlien[k][i++]='Phase Finale'     ;zlien[k][i++]='coupe_pf.php?c=kf'

i=0; zlien[++k]=new Array //Sous menus Portail Equipes
zlien[k][i++]='Connexion Portail';zlien[k][i++]='portail.php'
zlien[k][i++]='Annuaire Equipes';zlien[k][i++]='annuaire.php'
zlien[k][i++]='La Commission';zlien[k][i++]='commission.php'
zlien[k][i++]='Ecrire au Webmaster';zlien[k][i++]='mailto:laurent.gorlier@ufolep13volley.org'

i=0; zlien[++k]=new Array //Sous menu Documents officiels
zlien[k][i++]='À telecharger';zlien[k][i++]='docs.php'
zlien[k][i++]='Informations essentielles';zlien[k][i++]='docs.php'
zlien[k][i++]='Archives';zlien[k][i++]='docs.php'

//************ Fin des paramètres, Début du programme ************//

document.write('<style>')
document.write('.ejmenu  {background:'+Color1M+';text-align:'+AlignM+';font:'+WeightM+' '+SizeM+' '+FontM+';color:'+Color0M+';cursor:'+CursorM+'}')
document.write('.ejsmenu {background:'+Color1S+';text-align:'+AlignS+';font:'+WeightS+' '+SizeS+' '+FontS+';color:'+Color0S+';cursor:'+CursorS+'}')
document.write('</style>')

function fadeIn(obj)

{ obj.style.filter="blendTrans(duration=1)"
  if(obj.filters.blendTrans.status!=1)
  { obj.filters.blendTrans.Apply()
    obj.style.visibility="visible"
    obj.filters.blendTrans.Play()
  }
}

//document.onclick     = function() { skn.visibility='hidden' }
//document.onmousemove = function() { dgt.top=document.body.scrollTop+PosY; dgt.visibility='visible' }
//window.onscroll      = function() { dgt.visibility=skn.visibility='hidden' }
function pop(msg,pos)
{ skn.visibility="hidden"
  skn.top=document.body.scrollTop+PosY+HauteurM
  if(!msg.length) return
  if(EnLigne)
  { content="<TABLE style='border-collapse:collapse;'WIDTH="+LargeurM*menu.length/2+" bordercolor="+Color3S+" BORDER=1><TR>"
    for(pass=0;pass<msg.length;pass+=2) content+="<TD onMouseDown='location.href=\""+msg[pass+1]+"\"' onMouseOver=\"this.style.background='"+Color2S+"'\" onMouseOut=\"this.style.background='"+Color1S+"'\" HEIGHT="+HauteurS+" CLASS=ejsmenu>"+msg[pass]+"</TD>"
  } else
  { skn.left=pos-1
    content="<TABLE style='border-collapse:collapse;'WIDTH="+LargeurS+" bordercolor="+Color3S+" BORDER=1>"    
    for(pass=0;pass<msg.length;pass+=2) content+="<TR><TD WIDTH="+LargeurS+" onMouseDown='location.href=\""+msg[pass+1]+"\"' onMouseOver=\"this.style.background='"+Color2S+"'\" onMouseOut=\"this.style.background='"+Color1S+"'\" HEIGHT="+HauteurS+" CLASS=ejsmenu>"+msg[pass]+"</TD></TR>"
  }
  document.getElementById("topdeck").innerHTML=content+"</TR></TABLE>"
  if(document.all && FonduS)  fadeIn(topdeck); else skn.visibility="visible";
}

document.write('<DIV style="position:relative"><DIV style="POSITION:absolute;VISIBILITY:hidden;z-index:15" id=topdeck></DIV><TABLE ID=topmenu style="position:absolute;border-collapse:collapse;" bordercolor='+Color3M+' BORDER=1 WIDTH='+LargeurM*menu.length/2 +' HEIGHT='+HauteurM+'><tr>')
skn=document.getElementById('topdeck').style
dgt=document.getElementById('topmenu').style
for(pass=0;pass<menu.length/2;pass++) document.write("<TD WIDTH="+LargeurM+" onMouseDown='location.href=\""+menu[pass*2+1]+"\"' onMouseOver='this.style.background=\""+Color2M+"\";pop(zlien["+pass+"],this.offsetLeft)' onMouseOut='this.style.background=\""+Color1M+"\"' CLASS=ejmenu>"+menu[pass*2]+"</TD>")
document.write('</TR></TABLE></DIV>')
dgt.top=document.body.scrollTop