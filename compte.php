<?php
require_once ("db.php");
require_once ("lib.php");
require_once ("look.php");
require_once ("libhref.php");

class BaseCompte extends PageWeb
{
    function __construct($idpage,$destination)
    {
        parent::__construct(10*PAGE_COMPTE+$idpage,FALSE,$destination);
    }    

}

class PageCompte extends BaseCompte 
{
    var $Titre = "Gestion des comptes";
    var $Donnees;
	var $destination;
	var $message;
	var $Liste;
	var $CPT;
	var $button;
	var $fonctionvalide;
	var $editcpt;
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
	//Liste des codes CPT qu'il est interdit de supprimer
	$this->button=GetVariableFrom($_POST,"button","");
	$this->fonctionvalide=GetVariableFrom($_POST,"fonctionvalide","");
	$this->editcpt=GetVariableFrom($_POST,"editcpt","");
	$this->dellink=GetVariableFrom($_REQUEST,"dellink","");
	$this->modlink=GetVariableFrom($_REQUEST,"modlink","");
	if ($this->modlink!="")
	{
		$this->button="Modifier";
		$this->editcpt=decode_mdp($this->modlink);
		
	}
	elseif ($this->dellink!="")
	{
		$this->button="Supprimer";
		$this->editcpt=decode_mdp($this->dellink);
	}

    $this->CPT=new stdclass();
	$this->CPT->ID=GetVariableFrom($_POST,"ID","");
	$this->CPT->LIBELLE=GetVariableFrom($_POST,"LIBELLE","");
	$this->CPT->BANQUE=GetVariableFrom($_POST,"BANQUE","");
	$this->CPT->RIB=GetVariableFrom($_POST,"RIB","");
	$this->CPT->SOLDEINIT=GetVariableFrom($_POST,"SOLDEINIT","0.00");
	$this->CPT->DATEOUVERTURE=GetVariableFrom($_POST,"DATEOUVERTURE",date("d/m/Y"));
	$this->CPT->NBEECR="0";

	if ($this->button=="Supprimer")
	{
		//True : suppression si pas d'écritures
		$r = $this->Donnees->DeleteCpt($this->editcpt,True);
		if ($r->CR!="0") $this->message=$r->MSG;
		else $this->message="Suppression effectuée";
	}

	if ($this->button=="Valider")
	{
		$typmodif="";
		if ($this->fonctionvalide=="validemodif") 
		{
			$typemodif="update";
			$this->CPT->COPEMAJ=$this->user_info->USERMODIFYING;
		}
		else
		{
			$typemodif="insert";
			$this->CPT->COPECRE=$this->user_info->USERMODIFYING;
		}
		$msg="";
		if (($this->CPT->ID=="") and ($typemodif!="insert"))
			$msg="Identifiant obligatoire";
		elseif ($this->CPT->LIBELLE=="")
			$msg="Libellé obligatoire";
		elseif ($this->CPT->BANQUE=="")
			$msg="Nom de la banque obligatoire";
		elseif (!Is_Currency($this->CPT->SOLDEINIT,TRUE,TRUE))
			$msg="Solde initial obligatoire";
		elseif (!is_date($this->CPT->DATEOUVERTURE,"<=today")) $this->message="Date d'ouverture incorrecte";


		if ($msg=="")
		{
			if ($this->fonctionvalide=="validenouveau") 
			{
				$test=$this->Donnees->GetInfoCpt($this->CPT->ID);
				if ($test->CR!="0")  $msg=$test->MSG;
				elseif ($test->ID!="") $msg="L'identifiant ".$this->CPT->ID." est déjà utilisé pour un autre compte";
			}
		}
		if ($msg=="")
		{
			if ($this->fonctionvalide=="validenouveau") 
			{
				$test=$this->Donnees->CheckUniqueCPT($this->CPT);
				if ($test->CR!="0")  $msg=$test->MSG;
				elseif (!$test->UNIQUE) $msg="Ce compte existe déjà";
			}
		}
		
		
		
		if ($msg=="")
		{
			if ($this->fonctionvalide=="validmodif") 
			{
				//La date d'ouverture doit être la plus récente
				$r=$this->Donnees->GetDetailECR($this->CPT->ID,"01/01/1900","31/12/9999");
				if ($r->CR!="0") $msg=$r->MSG;
				elseif (isset($r->DATA[0]->DATEECRDEB))
				{
					$d1=date_to_ts($this->CPT->DATEOUVERTURE);
					$d2=date_to_ts($r->DATA[0]->DATEECRDEB);
					if ($d1>$d2) $msg="Date d'ouverture incorrecte : une écriture existe sur ce compte en date du ".$r->DATA[0]->DATEECRDEB;
				}
			}
		}
		if ($msg=="")
		{
			$r = $this->Donnees->UpdateInfoCPT($this->CPT,$typemodif);
			if ($r->CR!="0") $msg=$r->MSG;
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
			
			$this->CPT->ID="";
			$this->CPT->LIBELLE="";
			$this->CPT->BANQUE="";
			$this->CPT->RIB="";
			$this->CPT->COPECRE="";
			$this->CPT->COPEMAJ="";
			$this->CPT->SOLDEINIT="";
			$this->CPT->NBEECR="";
			$this->CPT->DATEOUVERTURE="";
		}
	}
	
	if ($this->button=="Modifier")
		{
			if (($this->editcpt=="") and ($this->fonctionvalide=="")) $this->button="Annuler";
			//Si fonctionvalide<>'' alors on était en cours d emodif donc il ne faut par relire les données
			if ($this->fonctionvalide=="") 
				{
					$this->CPT = $this->Donnees->GetInfoCpt($this->editcpt);
					if ($this->CPT->CR!="0") $this->message=$this->CPT->MSG;
				}
		}
	elseif ($this->button=="Valider")
		{
		if ($this->fonctionvalide=="") $this->button="Annuler";
		}
	if ($this->button=="Annuler")
	{
			$this->CPT->ID="";
			$this->CPT->LIBELLE="";
			$this->CPT->BANQUE="";
			$this->CPT->RIB="";
			$this->CPT->COPECRE="";
			$this->CPT->COPEMAJ="";
			$this->CPT->DATEOUVERTURE="";
	}	
	//toutes rubriques sans distinction
	$r=$this->Donnees->GetListeCPT("LIBELLE");
	if ($r->CR!="0") $this->message=$r->MSG;
	$this->Liste=$r->DATA;
	$this->affichedata=(($this->button!="Nouveau") and ($this->button!="Modifier"));
}


function MkFORM ()
{
	Global $Parametres;
    $form  ="<form method='post' action='compte.php'>";
    $form .="<fieldset class=myfieldset><legend><B>Comptes bancaires</B></legend>";
	$form.="<input type=hidden name='editcodecpt' value=''>";
	if ($this->affichedata===FALSE)
	{	
		$form.="<input type=hidden name='ID' value='".$this->CPT->ID."'>";
		$form .="<table class='nobordure'><tr align='center'>";
		$form .="<td class='bg2'>Id</td><td class='bg2'>Libellé</td></tr>";
		$form .="<tr>";
		$form .="<td><input type=text style='text-transform:uppercase; width:90px; height:25px;  text-align:right; ' MAXLENGTH='4' DISABLED name='ID' value='".$this->CPT->ID."'></td>";
		$form .="<td><input type=text name='LIBELLE' style='width:310px;  height:25px;' MAXLENGTH='32' value=\"".$this->CPT->LIBELLE."\"></td>";
		$form .="</tr>";
		$form .="<tr align='center'>";
		$form .="<td class='bg2'>Banque</td><td class='bg2'>IBAN / RIB</td>";
		$form .="</tr>";
		$form .="<tr>";
		$form .="<td><input type=text name='BANQUE' style='text-transform:uppercase; width:90px; height:25px;' MAXLENGTH='32' value=\"".$this->CPT->BANQUE."\"></td>";
		$form .="<td><input type=text name='RIB' style='text-transform:uppercase; width:310px;  height:25px;' MAXLENGTH='32' value=\"".$this->CPT->RIB."\"></td>";
		$form .="</tr>";
		$form .="</table>";
		$form .="<table class='nobordure'><tr align='center'>";
		$form .="<td class='bg2'>Solde Initial</td><td class='bg2'>Date d'ouverture</td>";
		$form .="</tr>";
		$form .="<tr>";
		$form .="<td><input type=text name='SOLDEINIT' id='SOLDEINIT' style='width:200px;  text-align:right; height:25px;' MAXLENGTH='12' value=\"".$this->CPT->SOLDEINIT."\"></td>";
		$form .=MkFilterFloat("SOLDEINIT");
		$form.="<td><input type=text style='width:90px; height:25px;' MAXLENGTH='10' name='DATEOUVERTURE' id='DATEOUVERTURE' ";
		$form.="value='".$this->CPT->DATEOUVERTURE."'>&nbsp;";
		$form.="<button type=reset style='width:30px; height:25px;' onclick='return showCalendar(\"DATEOUVERTURE\", \"dd/mm/y\");'>...</button></td>";

		$form .="</tr>";
		$form.="</table>";
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
			$form.="<td><button name='delbutton' form='delform' onclick='self.location.href=\"compte.php?dellink=".encode_mdp($this->CPT->ID)."\"' style='width:90px; height:30px; color:#fff; background-color:red;'>Supprimer</button></td>";	
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
	$html = "<h1>Liste des Comptes en banque</h1>";
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
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Ouverture le</b></th>";
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Libellé</b></th>";
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Nom de<br>la banque</b></th>";
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Numéro<br>IBAN</b></th>";	
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Solde Initial</b></th>";	
	$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head-right' ":"")."><b>Modifié</b></th>";
	$html.= "<tr></thead><tbody>";
	Foreach($this->Liste as $row)
		{
		$editcell="";
		if ($canedit===TRUE)
		{
			$editcell.="<td><a style='border:none;'  onMouseOver='return overlib(\"Supprimer\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' href='compte.php?dellink=".encode_mdp($row->ID)."'><img border='0' src='".$Parametres->CheminImages."supprimer.gif'></a>";
			$editcell.="<a style='border:none;' onMouseOver='return overlib(\"Modifier\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' href='compte.php?modlink=".encode_mdp($row->ID)."'><img border='0' src='".$Parametres->CheminImages."editer.gif'></a></td>";
			}
		$html .= "<tr>".$editcell;
		$html .= "<td align='left'>".$row->ID."</td>";
		$html .= "<td align='left'>".$row->DATEOUVERTURE."</td>";
		$html .= "<td align='left'>".$row->LIBELLE."</td>";
		$html .= "<td align='left'>".$row->BANQUE."</td>";
		$html .= "<td align='center'>".$row->RIB."</td>";
		$html .= "<td align='right'>".$row->SOLDEINIT."</td>";
		$html .= "<td align='left'>".($row->COPEMAJ!=""?"Par ".$row->COPEMAJ." le ".$row->DMAJ:"Par ".$row->COPECRE." le ".$row->DCRE)."</td>";
		$html .= "</tr>";
		}
	$html .= "</tbody></table>";
    return $html;     
}   
}

$page = new PageCompte(0); 
$page->WritePAGE (); 
?>
