<?php
require_once ("param.php");
require_once ("db.php");
require_once ("lib.php");

  
define ("PAGE_LOGIN",0);
define ("PAGE_ACCUEIL",1);
define ("PAGE_ADMIN",2);
define ("PAGE_FORGET",3);
define ("PAGE_PROFIL",4);
define ("PAGE_COMPTE",5);
define ("PAGE_POSTE",6);
define ("PAGE_ECRITURE",7);
define ("PAGE_REPART",8);


define ("USER_CLIENT",1);  
define ("USER_TRESORIER",64);
define ("USER_ADMIN",128);

class Panel 
{

function __construct ($PageWEB,$Titre="")
{   
    $this->PageWEB = $PageWEB;
    $this->Titre = $Titre;
}

function HTML()
{
    return "contenu vide....";
}
function MkCONTENU()
{
  return "<h1 style='margin-top: 0px;'>".$this->Titre."</h1>".$this->HTML();
}

}


class PageWeb {

    var $Titre = "";
    var $etat;
    var $Loged,$Id;
    var $user_info;
    
    var $headers =NULL;
    var $BodyOnLoad =NULL;
	var $AlertMsg ="";
    var $Meteo;
    var $DBParam;
	var $ClasseUtilisateur;
	var $destination;
	var $Donnees;


function __construct ($Id,$LoginPage = FALSE,$destination="ecran")
{
    global $_COOKIE,$_SERVER,$REQUEST_URI,$etat,$user_info,$argv,$argc,$ClasseUtilisateur;
    global $_GET;   
	
    $this->destination = $destination;
	if ($this->destination=="") $this->destination="ecran";
	if (!isset($_COOKIE["REQUESTED_URL"]))   $_COOKIE["REQUESTED_URL"]=FALSE;
	if (!isset($_COOKIE["comptaweb_etat"])) $_COOKIE["comptaweb_etat"]=FALSE;
	if (!isset($_COOKIE["user"]))			 $_COOKIE["user"]="";
	if (!isset($_COOKIE["disclamer"]))       $_COOKIE["disclamer"]=FALSE;
	
	$etat=$_COOKIE["comptaweb_etat"];
	$user=$_COOKIE["user"];
	if ((!isset($etat)) or (!isset($_SESSION)))
			{
            session_start();
			}
    $etat  = GetVariableFrom ($_SESSION,'comptaweb_etat');
	if ((!$LoginPage) and (!Is_Entier($_COOKIE["user"],TRUE,FALSE)))
	{
		RedirigeVers ("index.php",$_SERVER["REQUEST_URI"]);
	}
	$this->Donnees=new Donnees();
	
	if ($LoginPage) $this->user_info  = GetVariableFrom ($_SESSION,'user_info');
	else
	{
		$this->user_info=$this->Donnees->GetUser($_COOKIE["user"],"N");
	}
	if ($Id!=PAGE_FORGET)
	{
		// Pas d'info si pas loggé!
		if ((!$LoginPage && $etat != "loged") or (!isset($this->user_info->IDUSER)))
		{
			RedirigeVers ("index.php",$_SERVER["REQUEST_URI"]);
		}
		if ($etat == "loged" && GetVariableFrom($_COOKIE,"REQUESTED_URL")!= "")
		{
			if(!isset($_SESSION)) {  session_start(); }
			setcookie ("REQUESTED_URL");
			RedirigeVers (rawurldecode($_COOKIE["REQUESTED_URL"]));	    
		}	
	}
	$this->Id = $Id;
	$this->Loged = "loged"==$etat;        
	$this->etat = $etat;
	$this->ClasseUtilisateur = USER_CLIENT;
	if ( (isset($this->user_info->ISADMIN)?$this->user_info->ISADMIN:"") == "Y" )
		$this->ClasseUtilisateur |= USER_ADMIN;
	if ( (isset($this->user_info->TRESORIER)?$this->user_info->TRESORIER:"") == "Y" )
		$this->ClasseUtilisateur |= USER_TRESORIER;

        
    $nom=trim((isset($this->user_info->NOM)?$this->user_info->NOM:"")." ".(isset($this->user_info->PRENOM)?$this->user_info->PRENOM:""));

	$this->Id = $Id;
	$this->Loged = "loged"==$etat;        
	$this->etat = $etat;
	
}

    

 function TagHEAD($CSSOnly=FALSE)
  {
     global $Parametres;    
     $html = "<link REL='STYLESHEET' HREF='./css/aero.css?ts=".date("YmdHis")."' TYPE='text/css'>\n";
    switch ($this->destination)
    {
        case "ecran":
			$this->headers[] = MkTag("script","<!-- overLIB (c) Erik Bosrup -->",
						array(  "type"=>"text/javascript",
							"language"=>"JavaScript",
							"src" => "./overlib/overlib_mini.js"));
			$this->headers[] = MkTag("script","
			 if (self.parent.frames.length != 0)
						self.parent.location=document.location;",
						array(  "type"=>"text/javascript",
							"language"=>"JavaScript"));
			$this->headers[] = MkTag( "script", "",
				array( "type" => "text/javascript",
					"language" => "JavaScript",
					"src" => "./canvaschart/Chart.bundle.js" ));
			$this->headers[] = MkTag( "script", "",
				array( "type" => "text/javascript",
					"language" => "JavaScript",
					"src" => "./canvaschart/utils.js" ));
			$this->headers[] = MkTag("script", "",array("type" => "text/javascript","language" => "JavaScript","src" => "./calendar/calendar.js?ts=".date("YmdHis")));
			$this->headers[] = MkTag("script", "",array("type" => "text/javascript","language" => "JavaScript","src" => "./calendar/lang/calendar-fr.js?ts=".date("YmdHis")));
			$this->headers[] = MkTag("script", "",array("type" => "text/javascript","language" => "JavaScript","src" => "./calendar/cal.js?ts=".date("YmdHis")));
			$this->headers[] = MkTag ("link", "",array("rel" => "stylesheet","type" => "text/css","media" => "all","href" => "./calendar/calendar-win2k-2.css?ts=".date("YmdHis"),"title" => "win2k-2" ),false);
			$this->headers[] = MkTag("script", "",array("type" => "text/javascript","language" => "JavaScript","src" => "./js/setInputFilter.js" ));
			$this->headers[] = MkTag("script", "",array("type" => "text/javascript","language" => "JavaScript","src" => "./js/db.js?ts=".date("YmdHis")));
						
			// Les headers communs
			$this->headers[] = "<!-- ComptaWeb- C. Derenne 2024 -->";
			$this->headers[] = MkTag("meta", "", array ("http-equiv" => "X-UA-Compatible","content" => "IE=7"),FALSE,"\"");			
			$this->headers[] = MkTag("meta", "", array ("http-equiv" => "Content-Type","content" => "text/html; charset=UTF-8"),FALSE,"\"");
			$this->headers[] = MkTag("title",$this->Titre);
			break;
		default:
			break;
    }         
     if (!$CSSOnly)
     {
         foreach ($this->headers as $header)     
            $html .= $header."\n";
     } 
     return MkTag ("head",$html)."\n";                    
  }



function MkLEFTMENU()
{
/********************************************************************************/
/* Création des variables permettant de connaitre précisement le profil         */
/********************************************************************************/
Global $Parametres;
$menuwidth="55";
/********************************************************************************/
$pagesel=($this->Id-($this->Id%10))/10;
/********************************************************************************/
$leftmenu="\r\n<!-- LEFTMENU --><table border='0' width='".$menuwidth."px' >";
$leftmenu.="<tr><td valign='middle' style='height:50px;'><a href='profil.php' onMouseOver='return overlib(\"".htmlentities("Votre profil utilisateur",ENT_QUOTES,"UTF-8")."\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"160\");' onMouseOut='return nd();' ><img class='hover_animation_15' src='".$Parametres->CheminImages."profil.png'></a></a></td></tr>";
if ($this->ClasseUtilisateur & (USER_ADMIN))
	$leftmenu.="<tr><td valign='middle' style='height:50px;'><a href='admin.php'  onMouseOver='return overlib(\"".htmlentities("Accès Administrateur",ENT_QUOTES,"UTF-8")."\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"160\");' onMouseOut='return nd();' ><img class='hover_animation_15' src='".$Parametres->CheminImages."admin.png'></a></td></tr>";
$leftmenu.="<tr><td valign='middle' style='height:50px;'><a href='accueil.php'  onMouseOver='return overlib(\"".htmlentities("Consultation des bannissements",ENT_QUOTES,"UTF-8")."\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"120\");' onMouseOut='return nd();' ><img class='hover_animation_15' src='".$Parametres->CheminImages."home.png'></a></td></tr>";
$leftmenu.="<tr><td valign='middle' style='height:50px;'><a href='compte.php'  onMouseOver='return overlib(\"".htmlentities("Comptes",ENT_QUOTES,"UTF-8")."\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"120\");' onMouseOut='return nd();' ><img class='hover_animation_15' src='".$Parametres->CheminImages."bank_48.png'></a></td></tr>";
$leftmenu.="<tr><td valign='middle' style='height:50px;'><a href='poste.php'  onMouseOver='return overlib(\"".htmlentities("Postes de dépenses et de recettes",ENT_QUOTES,"UTF-8")."\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"120\");' onMouseOut='return nd();' ><img class='hover_animation_15' src='".$Parametres->CheminImages."poste_48.png'></a></td></tr>";
$leftmenu.="<tr><td valign='middle' style='height:50px;'><a href='ecriture.php'  onMouseOver='return overlib(\"".htmlentities("Ecritures",ENT_QUOTES,"UTF-8")."\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"120\");' onMouseOut='return nd();' ><img class='hover_animation_15' src='".$Parametres->CheminImages."ecr_48.png'></a></td></tr>";
$leftmenu.="<tr><td valign='middle' style='height:50px;'><a href='repart.php'  onMouseOver='return overlib(\"".htmlentities("Répartition par poste",ENT_QUOTES,"UTF-8")."\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"120\");' onMouseOut='return nd();'><img class='hover_animation_15' src='".$Parametres->CheminImages."repart_48.png'></a></td></tr>";
$leftmenu.="</table>\r\n";
return $leftmenu;
}


function MkCONTENU()
{
    return "Il faut surcharger MkCONTENU !!";
}



function MkTOP()
{
	Global $Parametres;
	$totalflagwidth=50;
	if (trim($this->user_info->PRENOM.$this->user_info->NOM)!="")
		$name=($this->user_info->PRENOM.$this->user_info->NOM!=""?$this->user_info->PRENOM." ".$this->user_info->NOM:$this->user_info->EMAIL);
	else 
		$name="Unknown";
	$html="<table border='0' style='border-collapse:collapse; border-style:none; width:100%; height:50px;'  >";
	$html.="<tr>";
	$html.="<td align='middle' width='".$totalflagwidth."'>";
	$html.="<a href='quitter.php'  onMouseOver='return overlib(\"Se déconnecter\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"120\");' onMouseOut='return nd();'><img class='hover_animation_15' src='".$Parametres->CheminImages."exit.png'></a>";
	$html.="</td><td style='width:5px;'>&nbsp;</td>";
	$html.="<td align='left' class='bienvenue'>Bienvenue ".($name!=""?$name:"Unknown")."</td>";
	$html.="<td align='right' ><img height='50px' src='./img/logo-haut.png'></td>";
	$html.="<td style='width:10px;'>&nbsp;</td>";
	$html.="</tr></table>\r\n";		
	return $html;
}   


function TagBODY ()
{
    tick_stopwatch ("Connexion/Database");
	$this->user_info=$this->Donnees->GetUser($_COOKIE["user"],"N");
	if (!isset($this->user_info->ACCEPT)) $this->user_info->ACCEPT="N";
	$_SESSION['user_info']=$this->user_info;
    $html = "<body ".$this->BodyOnLoad.">";
    $html.="<div id='overDiv' style='position:absolute; visibility:hidden; z-index:1000;'></div>";
    $html.="<table class='mainTable' width='100%' cellpadding='0' cellspacing='0'>";
    $html.="<tr>";
	$html.="<td colspan='3' class='top'>";
	$html.=$this->MkTOP();
	$html.="</TD></tr>\r\n";
    $html.="<tr><td class='menu'>\r\n";
    $html.= $this->MkLEFTMENU();
	$html.= "</td>\r\n";
    $html.="<td class='contenu'>\r\n";
    $html.= $this->MkCONTENU();
    $html.= "</td>\r\n";
    $html.="<td style='width:0px;'></td>\r\n";
	
    $html.="</tr></table>";
	$html.="</body>";
	if ($this->AlertMsg!="")
	{
	  $msg=htmlentities($this->AlertMsg,ENT_QUOTES,"UTF-8");
	  $html.="<script>function ShowMsg() { ";
	  $html.="alert(\"".str_replace("\r\n","\\n",html_entity_decode($msg,ENT_QUOTES,"UTF-8"))."\"); ";
	  $html.=" } window.onload=ShowMsg; ";
	  $html.="</script>";		
	}	
    return $html;
}

function TagBODY_NonFerme ()
{
    $html = "<body>";
    $html .= $this->MkCONTENU();
    return $html;
}

function WritePAGE ()
{
    global $Parametres;
    switch($this->destination)
    {
        case "ecran":
        default:
            $html="<!DOCTYPE HTML><html>";
            $html.=$this->TagHEAD();
			$html.=$this->TagBODY();
            tick_stopwatch ("Génération HTML");    
            tick_stopwatch ("Envoi de la page ".number_format(strlen($html))." bytes");
            echo $html;
            echo_stopwatch();  
            echo "</html>";
            break;
    }
}

	
	function DisposeAll()
{
}
} 

?>
