<?php
require_once ("db.php");
require_once ("lib.php");
require_once ("look.php");
require_once ("libhref.php");

class BasePoste extends PageWeb
{
    function __construct($idpage,$destination)
    {
        parent::__construct(10*PAGE_POSTE+$idpage,FALSE,$destination);
    }    

}

class PagePoste extends BasePoste 
{
    var $Titre = "Gestion des postes de recettes et de dépenses";
    var $Donnees;
	var $destination;
	var $message;
	var $Liste;
	var $POSTE;
	var $button;
	var $fonctionvalide;
	var $editposte;
	var $dellink;
	var $modlink;
	var $affichedata;
	var $isadmin;
	var $istresorier;

function __construct($idpage)
{
	$this->destination=GetVariableFrom($_REQUEST,"destination","ecran");
	parent::__construct($idpage,$this->destination);

	if ($this->ClasseUtilisateur & (USER_ADMIN)) $this->isadmin=1; else $this->isadmin=0;
	if ($this->ClasseUtilisateur & (USER_TRESORIER)) $this->istresorier=1; else $this->istresorier=0;

	$test="";
	$this->message="";
	$msg="";
	$this->button=GetVariableFrom($_POST,"button","");
	$this->fonctionvalide=GetVariableFrom($_POST,"fonctionvalide","");
	$this->editposte=GetVariableFrom($_POST,"editposte","");
	$this->dellink=GetVariableFrom($_REQUEST,"dellink","");
	$this->modlink=GetVariableFrom($_REQUEST,"modlink","");
	if ($this->modlink!="")
	{
		$this->button="Modifier";
		$this->editposte=decode_mdp($this->modlink);
		
	}
	elseif ($this->dellink!="")
	{
		$this->button="Supprimer";
		$this->editposte=decode_mdp($this->dellink);
	}
	$this->POSTE=new stdclass();
	$this->POSTE->ID=GetVariableFrom($_POST,"ID","");
	$this->POSTE->LIBELLE=GetVariableFrom($_POST,"LIBELLE","");
	$this->POSTE->SENS=GetVariableFrom($_POST,"SENS","C");
	$this->POSTE->BUDGET=GetVariableFrom($_POST,"BUDGET","0.00");
	$this->POSTE->HIDDEN="N";
	$this->POSTE->READONLY="N";

	if ($this->button=="Supprimer")
	{
		//True : suppression si pas d'écritures
		$r = $this->Donnees->DeletePoste($this->editposte,True);
		if ($r->CR!="0") $this->message=$r->MSG;
		else $this->message="Suppression effectuée";
	}

	if ($this->button=="Valider")
	{
		$typmodif="";
		if ($this->fonctionvalide=="validemodif") 
		{
			$typemodif="update";
			$this->POSTE->COPEMAJ=$this->user_info->USERMODIFYING;
		}
		else
		{
			$typemodif="insert";
			$this->POSTE->COPECRE=$this->user_info->USERMODIFYING;
		}
		$msg="";
		if (($this->POSTE->ID=="") and ($typemodif!="insert"))
			$msg="Identifiant obligatoire";
		elseif ($this->POSTE->LIBELLE=="")
			$msg="Libellé obligatoire";
		elseif (!Is_Currency($this->POSTE->BUDGET,TRUE,TRUE))
			$msg="Budget initial obligatoire";
		elseif (($this->POSTE->SENS!="C") and ($this->POSTE->SENS!="D"))
			$msg="Sens (recette ou dépense) incorrect";

		if ($msg=="")
			{
			if ($this->fonctionvalide=="validenouveau") 
				{
					$test=$this->Donnees->CheckUniquePoste($this->POSTE);
					if ($test->CR!="0")  $msg=$test->MSG;
					elseif (!$test->UNIQUE) $msg="L'identifiant ".$this->POSTE->LIBELLE." est déjà utilisé pour un autre compte";
				}
			if ($msg=="")
				{
				$r = $this->Donnees->UpdateInfoPoste($this->POSTE,$typemodif);
				if ($r->CR!="0") $msg=$r->MSG;
				}
			}
		if ($msg!="")
		{
			$this->message=$msg;
			if ($this->fonctionvalide=="validemodif") $this->button="Modifier";
			if ($this->fonctionvalide=="validenouveau") $this->button="Nouveau";
		}
		else
		{
			if ($this->fonctionvalide=="validemodif") $this->message="Modification ";
			if ($this->fonctionvalide=="validenouveau") $this->message="Insertion ";
			$this->message.="effectuée";
			
			$this->POSTE->ID="";
			$this->POSTE->LIBELLE="";
			$this->POSTE->SENS="";
			$this->POSTE->BUDGET="";
			$this->POSTE->HIDDEN="";
			$this->POSTE->READONLY="";
			$this->POSTE->COPECRE="";
			$this->POSTE->COPEMAJ="";
		}
	}
	
	if ($this->button=="Modifier")
		{
			if (($this->editposte=="") and ($this->fonctionvalide=="")) $this->button="Annuler";
			//Si fonctionvalide<>'' alors on était en cours d emodif donc il ne faut par relire les données
			if ($this->fonctionvalide=="") 
				{
					$this->POSTE = $this->Donnees->GetInfoPoste($this->editposte);
					if ($this->POSTE->CR!="0") $this->message=$this->POSTE->MSG;
				}
		}
	elseif ($this->button=="Valider")
		{
		if ($this->fonctionvalide=="") $this->button="Annuler";
		}
	if ($this->button=="Annuler")
	{
			$this->POSTE->ID="";
			$this->POSTE->LIBELLE="";
			$this->POSTE->SENS="";
			$this->POSTE->BUDGET="";
			$this->POSTE->HIDDEN="";
			$this->POSTE->READONLY="";
			$this->POSTE->COPECRE="";
			$this->POSTE->COPEMAJ="";
	}	
	//toutes rubriques sans distinction
	$r=$this->Donnees->GetListePoste("SENS DESC,LIBELLE",FALSE,TRUE);
	if ($r->CR!="0") $this->message=$r->MSG;
	$this->Liste=$r->DATA;
	$this->affichedata=(($this->button!="Nouveau") and ($this->button!="Modifier"));
}


function MkFORM ()
{
	Global $Parametres;
    $form  ="<form method='post' action='poste.php'>";
    $form .="<fieldset class=myfieldset><legend><B>Postes de recettes et de dépenses</B></legend>";
	$form.="<input type=hidden name='editposte' value=''>";
	if ($this->affichedata===FALSE)
	{	
		$form.="<input type=hidden name='ID' value='".$this->POSTE->ID."'>";
		$form .="<table class='nobordure'><tr align='center'>";
		$form .="<td class='bg2'>Id</td><td class='bg2'>Libellé</td></tr>";
		$form .="<tr>";
		$form .="<td><input type=text style='text-transform:uppercase; width:90px; height:25px;  text-align:right; ' MAXLENGTH='4' DISABLED name='ID' value='".$this->POSTE->ID."'></td>";
		$form .="<td><input type=text name='LIBELLE' style='width:310px;  height:25px;' MAXLENGTH='32' value=\"".$this->POSTE->LIBELLE."\"></td>";
		$form .="</tr>";
		$form .="<tr align='center'>";
		$form .="<td class='bg2'>Sens</td><td class='bg2'>Budget</td>";
		$form .="</tr>";
		$form .="<tr>";
		$form .="<td>";
		$form .="<select name='SENS'>";
		$form .="<option value='' ".($this->POSTE->SENS==""?"selected":"")."></option>";
		$form .="<option value='C' ".($this->POSTE->SENS=="C"?"selected":"").">Recette</option>";
		$form .="<option value='D' ".($this->POSTE->SENS=="D"?"selected":"").">Dépense</option>";
		$form .="</select>";
		$form .="</td>";
		$form .="<td><input type=text name='BUDGET' id='BUDGET' style='width:200px;  text-align:right; height:25px;' MAXLENGTH='12' value=\"".$this->POSTE->BUDGET."\"></td>";
		$form .=MkFilterFloat("BUDGET");
		$form .="</tr>";
		$form .="</table>";
	}
	$form.="<p><table><tr>";
	if ($this->affichedata===TRUE)
	{
		$form.="<td><input type=submit name='button' style='width:90px; height:30px;' value='Nouveau'></td>";
	}
	else
	{
		$form.="<td><input type=submit name='button' style='width:90px; height:30px;' value='Valider'></td>";
		$form.="<td><input type=submit name='button' style='width:90px; height:30px;' value='Annuler'></td>";
		if ($this->button=="Modifier")
		{
			$form.="<td><button name='delbutton' form='delform' onclick='self.location.href=\"poste.php?dellink=".encode_mdp($this->POSTE->ID)."\"' style='width:90px; height:30px; color:#fff; background-color:red;'>Supprimer</button></td>";	
		}
	}
	$form.="</tr></table></fieldset>";
	$form.="<input type=hidden name='fonctionvalide' ";
	if ($this->button=="Modifier") $form.="value='validemodif'>";
	elseif ($this->button=="Nouveau") $form.="value='validenouveau'>";
	else $form.="value=''>";
	$form.="</form><br>";
	return $form;
}



function MkCONTENU ()
{
	Global $Parametres;
	$form_modif="";
    if ($this->destination == "ecran")
    {
		if (($this->istresorier+$this->isadmin)>=1)
		{
			$form_modif="<p>".$this->MkFORM();
		}
	}
	$html = "<h1>Liste des Postes de recettes et de dépenses</h1>";
	$html .=$form_modif;

	if ((count($this->Liste)==0) and ($this->affichedata===TRUE)) $html.="<br>Aucune données à afficher";
	$html .=$this->TBDONNEE();

	$html.=ShowMessage($this->message);	
    return $html;
}


function TBDONNEE()
{
    if ($this->affichedata===FALSE) return "";
	if (count($this->Liste)==0) return "";
	global $Parametres;
	$canedit=(($this->destination=="ecran") and (($this->istresorier+$this->isadmin)>=1)) ;
	$html="";
	if ($this->destination=="impression") 
		$html .= "<table class='bordure' style='border-collapse: collapse;'>";
	else
		$html .= "<table class='tsc_tables'>";		
	$html.="<thead><tr ".($this->destination=="impression"?"class='bg2'":"").">";
	
	$html.="<th colspan='".($canedit===TRUE?2:1)."'".($this->destination == "ecran"?"scope='col' class='rounded-head-left' ":"")."><b>Id</b></th>";
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Libellé</b></th>";
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Sens</b></th>";
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Budget</b></th>";	
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head-right' ":"")."><b>Modifié</b></th>";
	$html.= "<tr></thead><tbody>";
	Foreach($this->Liste as $row)
		{
		$editcell="";
		if (($canedit===TRUE) and ($row->RONLY=="N") and ($row->HIDDEN=="N"))
		{
			$editcell.="<td><a style='border:none;'  onMouseOver='return overlib(\"Supprimer\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' href='poste.php?dellink=".encode_mdp($row->ID)."'><img border='0' src='".$Parametres->CheminImages."supprimer.gif'></a>";
			$editcell.="<a style='border:none;' onMouseOver='return overlib(\"Modifier\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' href='poste.php?modlink=".encode_mdp($row->ID)."'><img border='0' src='".$Parametres->CheminImages."editer.gif'></a></td>";
		}
		elseif (($canedit===TRUE) and (($row->RONLY=="Y") or ($row->HIDDEN=="Y")))
		{
			$editcell.="<td><img onMouseOver='return overlib(\"Suppression interdite\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' border='0' src='".$Parametres->CheminImages."forbidden.png'>";
			$editcell.="<img onMouseOver='return overlib(\"Modification interdite\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' border='0' src='".$Parametres->CheminImages."forbidden.png'></td>";
		}
		
		$html .= "<tr>".$editcell;
		$html .= "<td align='right'>".$row->ID."</td>";;
		$html .= "<td align='left'>".$row->LIBELLE."</td>";
		$html .= "<td align='left'>".($row->SENS=="D"?"Dépense":($row->SENS=="C"?"Recette":""))."</td>";
		$html .= "<td align='right'>".$row->BUDGET."</td>";
		$html .= "<td align='left'>".($row->COPEMAJ!=""?"Par ".$row->COPEMAJ." le ".$row->DMAJ:"Par ".$row->COPECRE." le ".$row->DCRE)."</td>";
		$html .= "</tr>";
		}
	$html .= "</tbody></table>";
    return $html;     
}   
}

$page = new PagePoste(0); 
$page->WritePAGE (); 
?>
