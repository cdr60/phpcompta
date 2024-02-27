<?php
// Main program
//$NO_COMPRESSION = TRUE;

require_once ("db.php");
require_once ("param.php");
require_once ("look.php");

class BaseAdmin extends PageWeb
{
function __construct($id,$destination)
{
    parent::__construct(10*PAGE_ADMIN+$id,FALSE,$destination);
}    
}

class Admin extends BaseAdmin 
{
    var $Titre = "Welcome";
    var $Action ;
	var $message;

function __construct($id)
{
	$this->destination=GetVariableFrom($_REQUEST,"destination","");
    parent::__construct($id,$this->destination);
}

function MkCONTENU()
{
	$html="<h1>Interface réservée aux administrateurs</h1>";
	if (!($this->ClasseUtilisateur & (USER_ADMIN)))
	{
		$html.="Cette page est réservée aux administrateurs";
		return $html;
	}
	$html.="<table class='mainpageadmin'>";
	$html.="<tr>";
	$popupmsg="Utilisateurs";
	$popup=" onMouseOver='return overlib(\"".$popupmsg."\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"3\",WIDTH,\"120\");' onMouseOut='return nd();' ";
	$html.="<td ".$popup.">";
	$html.="<a style='text-decoration: none;' href='admin.php?action=users'><table class='elempageadmin'>";
	$html.="<tr><td class='titrepageadmin'>".$popupmsg."</td></tr>";
	$html.="<tr><td><img src='./img/user.jpg'></td></tr>";
	$html.="</table></a>";	
	$html.="</td>";	
	
	$html.="</tr>";
	$html.="</table>";
   return $html;
}   
}



class PageUsers extends BaseAdmin
{
    var $Titre = "Comptes utilisateurs";
	var $message;
	var $affichedata;
	var $currentuser;
	var $isadmin;
	var $tresorier;
	var $button;
	var $fonctionvalide;
	var $dellink;
	var $modlink;
	var $edituserid;
	var $Liste;
	var $USER;

function __construct($id)
{
	$this->message="";
	$this->destination=GetVariableFrom($_REQUEST,"destination","ecran");	
	parent::__construct($id,$this->destination);
	$this->affichedata=TRUE;
	$this->currentuser=$this->user_info->IDUSER;
	if ($this->ClasseUtilisateur & (USER_ADMIN)) $this->isadmin=1; else $this->isadmin=0;
	
	$this->button=GetVariableFrom($_POST,"button","");
	$this->fonctionvalide=GetVariableFrom($_POST,"fonctionvalide","");	
	$this->dellink=GetVariableFrom($_REQUEST,"dellink","");
	$this->modlink=GetVariableFrom($_REQUEST,"modlink","");
	$this->edituserid="";
	
	if ($this->dellink!="") 
	{
		$a=GetEditUserByLink($this->dellink);
        if ($a->IDUSER!="")	
		{
			$this->button="Supprimer";
			$this->edituserid=$a->IDUSER;
		}
	}
	elseif ($this->modlink!="") 
	{
		$a=GetEditUserByLink($this->modlink);
		if ($a->IDUSER!="")
		{
			$this->button="Modifier";
			$this->edituserid=$a->IDUSER;
		}
	}
	if ($this->button=="Annuler") 
	{
		$this->button=="";
		$this->fonctionvalide="";
	}
	$this->affichedata=(($this->button!="Nouveau") and ($this->button!="Modifier"));
	if ($this->button=="Nouveau") 
	{
		$this->USER=InitUser(0);
	}
	if ($this->message=="")
	{
		if ($this->button=="Supprimer")
		{
			$rc = $this->Donnees->DeleteUser($this->edituserid);
			if ($rc->CR!=0)	$this->message=$rc->MSG;
			$this->button="";

		}	
		elseif ($this->button=="Modifier") 
		{
			$this->fonctionvalide="validemodif";	
			$this->USER=$this->Donnees->GetUser($this->edituserid,"N");		
			$this->USER->PASS="";
			$this->USER->CONFIRMPASS="";
		}

		if ($this->button=="Valider")
		{
			$this->USER=InitUser(1);
			$typmodif="";
			if ($this->fonctionvalide=="validemodif") 
			{
				$typemodif="update";
				$this->USER->COPEMAJ=$this->user_info->USERMODIFYING;
			}
			else
			{
				$typemodif="insert";
				$this->USER->COPECRE=$this->user_info->USERMODIFYING;
			}
			$msg="";
			if ($this->USER->EMAIL=="")
				$msg="Email est manquant";
			elseif (trim($this->USER->NOM." ".$this->USER->PRENOM)=="")
				$msg="Il faut au moins fournir un nom";
			elseif (($typemodif=="update") and ($this->USER->IDUSER==""))
				$msg="Identifiant unique manquant";
			elseif (($typemodif=="insert") or (trim($this->USER->PASS.$this->USER->CONFIRMPASS)!=""))
			{
				if (($this->fonctionvalide=="validenouveau") and ($this->USER->PASS==""))
					$msg="Mot de passe manquant";
				elseif (($this->fonctionvalide=="validenouveau") and  ($this->USER->CONFIRMPASS==""))
					$msg="Confirmez le smots de passe";
				elseif ($this->USER->CONFIRMPASS!=$this->USER->PASS)
					$msg="Les mots de passe ne correspondent pas";
			}
			if ($msg=="")
				{
					$cr = $this->Donnees->UpdateUser($this->USER,$typemodif);
					if ($cr->CR==0)	$this->USER=InitUser(0);
					else $msg=$cr->MSG;
				}
			if ($msg!="")
			{
				$this->message=$msg;
				$this->affichedata=FALSE;
				if ($this->fonctionvalide=="validemodif") $this->button="Modifier";
				if ($this->fonctionvalide=="validenouveau") $this->button="Nouveau";
			}
		}
		//que les sous utilisateurs de l'utilisateur en cours
		$this->Liste=$this->Donnees->GetUser("","N");
		if ($this->Liste->CR!=0) 
		{
			$this->message=$this->Liste->MSG;		
			$this->affichedata=FALSE;
		}
	}
}

function MkCONTENU()
{
	$html="<br><h1>".$this->Titre."</h1>";
	$html.=$this->MkFORM();
	$html.=$this->MkTBDONNEE();
	if ($this->message!="")
	{
	  $this->message=htmlentities($this->message,ENT_QUOTES,"UTF-8");
	  $html.="<script>alert(\"".str_replace("\r\n","\\n",html_entity_decode($this->message,ENT_QUOTES,"UTF-8"))."\");</script>";	
	}
	return $html;
}




function MkFORM ()
{
	if ($this->isadmin==0) return "";
    $form  ="<form method='post' action='admin.php?action=users'>";
    $form .="<fieldset><legend><B>Comptes utilisateurs</B></legend>";
	if ($this->affichedata===FALSE)
	{	
		$form .="<input type=hidden name='IDUSER' value=\"".$this->USER->IDUSER."\">";
		$form .="<table class='nobordure'><tr align='center'>";
		$form .="<tr align='center'><td class='bg2'>Email - Identité</td><td class='bg2'>Connexion</td></tr>";
		$form .="<tr>";
		$form .="<td><table class='nobordure'>";
		$form .="<tr><td class='bg1' style='width:100px;'>Email : </td><td><input type=text name='EMAIL' style='width:280px; height:25px;' MAXLENGTH='256' value=\"".$this->USER->EMAIL."\"></td></tr>";
		$form .="<tr><td class='bg1'>Est Administrateur : </td>";
				$form .="<td><select name='ISADMIN' style='width:280px; height:25px;'>";
		$form .="<option value='Y' ".($this->USER->ISADMIN=="Y"?" selected":"").">Oui</option>";
		$form .="<option value='N' ".($this->USER->ISADMIN!="Y"?" selected":"").">Non</option>";
		$form .="</select></td></tr>";
		$form .="<tr><td class='bg1'>Est Trésorier : </td>";
				$form .="<td><select name='TRESORIER' style='width:280px; height:25px;'>";
		$form .="<option value='Y' ".($this->USER->TRESORIER=="Y"?" selected":"").">Oui</option>";
		$form .="<option value='N' ".($this->USER->TRESORIER!="Y"?" selected":"").">Non</option>";
		$form .="</select></td></tr>";
		$form .="<tr><td class='bg1'>Nom : </td><td><input type=text name='NOM' style='width:280px; height:25px;' MAXLENGTH='32' value=\"".$this->USER->NOM."\"></td></tr>";
		$form .="<tr><td class='bg1'>Prénom : </td><td><input type=text name='PRENOM' style='width:280px; height:25px;' MAXLENGTH='32' value=\"".$this->USER->PRENOM."\"></td></tr>";
		$form .="</table></td>";
		
		$form .="<td valign='top'><table class='nobordure'>";
		$form .="<tr><td class='bg1' style='width:100px; vertical-align:top;'>Mot de passe :</td>";
		$form .="<td><input type=text name='PASS' ".($this->currentuser==$this->USER->IDUSER?" DISABLED ":" ")."style='width:180px; height:25px;' MAXLENGTH='32' value=\"".$this->USER->PASS."\"></td>";
		$form .="</tr>";
		$form .="<tr><td class='bg1'>Confirmation :</td>";
		$form .="<td><input type=text name='CONFIRMPASS' ".($this->currentuser==$this->USER->IDUSER?" DISABLED ":" ")." style='width:180px; height:25px;' MAXLENGTH='32' value=\"".$this->USER->CONFIRMPASS."\"></td>";
		$form .="</tr>";
		$form .="</table></td>";
		
	}
	$form.="<table><tr>";
	if ($this->affichedata===TRUE)
	{
		$form.="<td><input type=submit name='button' style='width:91px; height:30px;' value='Nouveau'></td>";
		$form.="<td><input type=submit name='button' style='width:91px; height:30px;' value='Rafraichir'></td>";
	}
	else
	{
		$form.="<td style='width:110px; vertical-align:top;'>";
		$form.="<td><input type=submit style='width:91px; height:30px;' name='button' value='Valider'></td>";
		$form.="<td><input type=submit style='width:91px; height:30px;' name='button' value='Annuler'></td>";
		if (($this->isadmin==1) and ($this->currentuser!=$this->USER->IDUSER) and ($this->fonctionvalide=="validemodif"))
			$form.="<td><button onclick='self.location.href=\"admin.php?action=users&dellink=".encode_mdp($this->USER->IDUSER)."\"' style='width:91px; height:30px; background-color:red; color:white;' form='deleteform' name='delbutton' >Supprimer</button></td>";
	}
	$form.="</tr></table></fieldset>";
	$form.="<input type=hidden name='fonctionvalide' ";
	if ($this->button=="Modifier") $form.="value='validemodif'>";
	elseif ($this->button=="Nouveau") $form.="value='validenouveau'>";
	else $form.="value=''>";
	return $form;
}



function MkTBDONNEE()
{
	Global $Parametres;
	if ($this->affichedata===FALSE) return "";
	if ($this->isadmin==0) return "Page réservée aux administrateurs";
	$canedit=($this->destination=="ecran");
	$html="";
	$html .= "<table class='tsc_tables'>";		
	$html.="<thead><tr >";
	
    $html.="<th colspan='".($canedit===TRUE?2:1)."' scope='col' class='rounded-head-left'><b>Id</b></th>";
    $html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Email</b></th>";
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Identité</b></th>";
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Connexion</b></th>";
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Création</b></th>";
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head-right' ":"")."><b>Modification</b></th>";
	$html.="</tr></thead><tbody>";	
	
	Foreach($this->Liste->DATA as $row)
		{
		$tdedit="";
		if ($canedit===TRUE)
			{
			$a =MkLinkEditUser($row);
			$tdedit="<td>";
			$tdedit.="<a  onMouseOver='return overlib(\"Modifier\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' href='admin.php?action=users&modlink=".encode_mdp($a)."'><img border='0' src='./img/editer.gif'></a>";
			if ($row->IDUSER!=$this->currentuser) 
				$tdedit.="<a  onMouseOver='return overlib(\"Supprimer\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' href='admin.php?action=users&dellink=".encode_mdp($a)."'><img border='0' src='./img/supprimer.gif'></a>";
			else
				$tdedit.="<img onMouseOver='return overlib(\"Pour supprimer ce compte, connectez-vous avec un autre compte\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"180\");' onMouseOut='return nd();' border='0' src='./img/forbidden.png'>";
			$tdedit.="</td>";
			}
		$html .= "<tr>".$tdedit;
		$html .= "<td style='width:70px;'>Id ".$row->IDUSER."<br>".trim(($row->ISADMIN=="Y"?"Administrateur":"")." ".($row->TRESORIER=="Y"?"Trésorier":"")." ".(($row->ISADMIN=="N") and ($row->TRESORIER=="N")?"Consultation":""))."</td>";
		$html .= "<td >".$row->EMAIL."</td>";
		$html .= "<td>".($row->NOM.$row->PRENOM!=""?$row->PRENOM." ".$row->NOM:"")."</td>";
		$html .="<td>".($row->LASTCONNECT!=""?"Dernière: ".DBTimestamp_to_WebTimestamp($row->LASTCONNECT):"")."</td>";
		$html .= "<td >".$row->COPECRE."<br>".DBTimestamp_to_WebTimestamp($row->DCRE)."</td>";
		$html .= "<td >".($row->COPEMAJ!=""?$row->COPEMAJ."<br>".DBTimestamp_to_WebTimestamp($row->DMAJ):"")."</td>";
		$html .= "</tr>";
		}
	$html .= "</tbody></table>";
    return $html;     
} 
}



$action=GetVariableFrom($_REQUEST,"action","");
switch ($action)
{
    default:
	$page = new Admin(0);
	break;
    case "users" :  $page = new PageUsers(2); 
	break;	
}
$page->WritePAGE ();
?>