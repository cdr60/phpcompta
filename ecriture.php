<?php
require_once ("db.php");
require_once ("lib.php");
require_once ("look.php");
require_once ("libhref.php");

class BaseEcriture extends PageWeb
{
    function __construct($idpage,$destination)
    {
        parent::__construct(10*PAGE_ECRITURE+$idpage,FALSE,$destination);
    }    

}

class PageEcriture extends BaseEcriture 
{
    var $Titre = "Gestion des écritures";
    var $Donnees;
	var $destination;
	var $message;
	var $Liste;
	var $ECR;
	var $TRANSFERT;
	var $button;
	var $buttonsel;
	var $fonctionvalide;
	var $editid;
	var $dellinkecr;
	var $modlinkecr;
	var $dellinktrans;
	var $modlinktrans;
	var $affichedata;
	var $isadmin;
	var $istresorier;
	var $ListeCompte;
	var $ListePoste;
	var $ListeTypePaie;
	var $datedeb;
	var $datefin;
	var $comptechoisis;
	var $typepaiechoisis;
	var $uniqpointe;
	var $typeecr;
	var $SOLDE_AVANT;
	var $SOLDE_AVANT_POINTE;
	var $SOLDE_APRES;
	var $SOLDE_APRES_POINTE;
	var $TOTAL_CREDIT;
	var $TOTAL_CREDIT_POINTE;
	var $TOTAL_DEBIT;
	var $TOTAL_DEBIT_POINTE;
	
function __construct($idpage)
{
	$this->destination=GetVariableFrom($_REQUEST,"destination","ecran");
	parent::__construct($idpage,$this->destination);

	if ($this->ClasseUtilisateur & (USER_ADMIN)) $this->isadmin=1; else $this->isadmin=0;
	if ($this->ClasseUtilisateur & (USER_TRESORIER)) $this->istresorier=1; else $this->istresorier=0;

	$test="";
	$this->message="";
	$msg="";
	
	$this->SOLDE_AVANT="";
	$this->SOLDE_AVANT_POINTE="";
	$this->SOLDE_APRES="";
	$this->SOLDE_APRES_POINTE="";
	
	//Récupérations des donnée spour les listbox
	$this->MkLists();
	
	//Initialisation variables de sélection
	$this->buttonsel=GetVariableFrom($_POST,"buttonsel","");
    $this->datefin=GetVariableFrom($_POST,"datefin",date("d/m/Y"));
    $this->datedeb=GetVariableFrom($_POST,"datedeb","01/01/".date("Y"));
	$this->comptechoisis=GetVariableFrom($_POST,"comptechoisis","");
	
	if ((isset($this->ListeCompte[0]->ID)) and ($this->comptechoisis=="")) $this->comptechoisis=$this->ListeCompte[0]->ID;
	$this->typepaiechoisis=GetVariableFrom($_POST,"typepaiechoisis","");
	$this->uniqpointe=GetVariableFrom($_POST,"uniqpointe","");
	$this->typeecr=GetVariableFrom($_POST,"typeecr","D");
	

	//Initialisation variables de modification
	$this->button=GetVariableFrom($_POST,"button","");
	$this->fonctionvalide=GetVariableFrom($_POST,"fonctionvalide","");
	$this->editid=GetVariableFrom($_POST,"editid","");
	$this->dellinkecr=GetVariableFrom($_REQUEST,"dellinkecr","");
	$this->modlinkecr=GetVariableFrom($_REQUEST,"modlinkecr","");
	$this->dellinktrans=GetVariableFrom($_REQUEST,"dellinktrans","");
	$this->modlinktrans=GetVariableFrom($_REQUEST,"modlinktrans","");
	
	//Vérirications variables de sélection
	if ($this->button!="") $this->buttonsel="Sélectionner";
	if ($this->buttonsel!="")
	{
		$d1=date_to_ts($this->datedeb);
		$d2=date_to_ts($this->datefin);
		if (!Is_Entier($this->comptechoisis,False,False)) $this->message="Veuillez d'abord choisir un compte";
		elseif (!is_date($this->datedeb)) $this->message="Date de début incorrecte";
		elseif (!is_date($this->datefin)) $this->message="Date de début incorrecte";
		elseif ($d1>$d2) $this->message="Période incorrecte";
	}
	
	if ($this->message!="")
	{
		$this->buttonsel="";
		$this->button="";
		$this->fonctionvalide="";
		$this->dellinkecr="";
		$this->modlinkecr="";
		$this->dellinktrans="";
		$this->modlinktrans="";
	}
	else $this->GetSoldes($this->comptechoisis,$this->datedeb,$this->datefin);

	if ($this->modlinkecr!="")
	{
		$this->button="Modifier";
		$this->editid=decode_mdp($this->modlinkecr);
		$this->typeecr="D";
		
	}
	elseif ($this->dellinkecr!="")
	{
		$this->button="Supprimer";
		$this->editid=decode_mdp($this->dellinkecr);
		$this->typeecr="D";
	}
	elseif ($this->modlinktrans!="")
	{
		$this->button="Modifier";
		$this->editid=decode_mdp($this->modlinktrans);
		$this->typeecr="T";
		
	}
	elseif ($this->dellinktrans!="")
	{
		$this->button="Supprimer";
		$this->editid=decode_mdp($this->dellinktrans);
		$this->typeecr="T";
	}
	
	//Nécessite que comptechoisis, typepaiechoisis et typeecr soient valorisés
	$this->ECR=$this->InitECR("post");
	$this->TRANSFERT=$this->InitTransfert("post");


	if ($this->button=="Supprimer")
	{
		if ($this->typeecr!="T") $r = $this->Donnees->DeleteECR($this->editid);
		else $r = $this->Donnees->DeleteTransfert($this->editid);
		if ($r->CR!="0") $this->message=$r->MSG;
		else $this->message="Suppression effectuée";
	}

	if ($this->button=="Valider")
	{
		if ($this->typeecr!="T") $this->ValideModifEcr();
		else $this->ValideModifTransfert();
	}
	
	if ($this->button=="Modifier")
	{
		if ($this->typeecr!="T") $this->ReadECR();
		else $this->ReadTransfert();
	}
	elseif ($this->button=="Valider")
	{
		if ($this->fonctionvalide=="") $this->button="Annuler";
	}
	if ($this->button=="Annuler")
	{
		$this->ECR=$this->InitECR("");
		$this->TRANSFERT=$this->InitTransfert("");
		$this->typeecr="";
	}
	$r=$this->Donnees->GetListeECR($this->comptechoisis,"",$this->typepaiechoisis,$this->datedeb,$this->datefin,$this->uniqpointe);
	if ($r->CR!="0") $this->message=$r->MSG;
	$this->Liste=$r->DATA;
	$this->TOTAL_CREDIT=$r->TOTAL_CREDIT;
	$this->TOTAL_CREDIT_POINTE=$r->TOTAL_CREDIT_POINTE;
	$this->TOTAL_DEBIT=$r->TOTAL_DEBIT;
	$this->TOTAL_DEBIT_POINTE=$r->TOTAL_DEBIT_POINTE;
	$this->affichedata=(($this->button!="Nouveau") and ($this->button!="Modifier"));
}


//Lecture des données pour affichage
function ReadECR()
{
	if (($this->editid=="") and ($this->fonctionvalide=="")) $this->button="Annuler";
	//Si fonctionvalide<>'' alors on était en cours d emodif donc il ne faut par relire les données
	if ($this->fonctionvalide=="") 
	{
		$this->ECR = $this->Donnees->GetInfoECR($this->editid);
		if ($this->ECR->CR!="0") $this->message=$this->ECR->MSG;
	}
}
function ReadTransfert()
{
	if (($this->editid=="") and ($this->fonctionvalide=="")) $this->button="Annuler";
	//Si fonctionvalide<>'' alors on était en cours d emodif donc il ne faut par relire les données
	if ($this->fonctionvalide=="") 
	{
		$this->TRANSFERT = $this->Donnees->GetInfoTransfert($this->editid);
		if ($this->TRANSFERT->CR!="0") $this->message=$this->TRANSFERT->MSG;
	}
}

//Validation / Contrôle des modifications pour un mouvement
function ValideModifEcr()
{
	$typmodif="";
	if ($this->fonctionvalide=="validemodif") 
	{
		$typemodif="update";
		$this->ECR->COPEMAJ=$this->user_info->USERMODIFYING;
	}
	else
	{
		$typemodif="insert";
		$this->ECR->COPECRE=$this->user_info->USERMODIFYING;
	}
	$msg="";
	if (($this->ECR->ID=="") and ($typemodif!="insert"))
		$msg="Identifiant obligatoire";
	elseif ($this->ECR->LIBELLE=="")
		$msg="Libellé obligatoire";
	elseif (!Is_Currency($this->ECR->MONTANT,FALSE,FALSE))
		$msg="Montant incorrect";
	elseif (($this->ECR->SENS!="C") and ($this->ECR->SENS!="D"))
		$msg="Sens (recette ou dépense) incorrect";
	elseif ($this->ECR->IDCOMPTE=="")
		$msg="Compte obligatoire";
	elseif ($this->ECR->IDPOSTE=="")
		$msg="Poste obligatoire";
	elseif ($this->ECR->IDTYPEPAIE=="")
		$msg="Type de paiement obligatoire";
	elseif (!is_date($this->ECR->DATEECR,"<=today")) $msg="Date d'écriture incorrecte";
	if ($msg=="")
	{
		//La date d'ouverture doit être la plus récente
		$r=$this->Donnees->GetInfoCPT($this->ECR->IDCOMPTE);
		if ($r->CR!="0") $msg=$r->MSG;
		elseif (isset($r->DATEOUVERTURE))
		{
			$d1=date_to_ts($r->DATEOUVERTURE);
			$d2=date_to_ts($this->ECR->DATEECR);
			if ($d1>$d2) $msg="Date d'écriture incorrecte: ce compte a été ouvert le ".$r->DATEOUVERTURE;
		}
	}
	if ($msg=="")
	{
		$r = $this->Donnees->UpdateInfoECR($this->ECR,$typemodif);
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
		$this->ECR=$this->InitECR("");
	}
}

//Validation modification transfert
function ValideModifTransfert()
{
	if ($this->fonctionvalide=="validemodif") 
	{
		$typemodif="update";
		$this->TRANSFERT->COPEMAJ=$this->user_info->USERMODIFYING;
	}
	else
	{
		$typemodif="insert";
		$this->TRANSFERT->COPECRE=$this->user_info->USERMODIFYING;
	}
	$msg="";
	if (($this->TRANSFERT->ID=="") and ($typemodif!="insert"))
		$msg="Identifiant obligatoire";
	elseif ($this->TRANSFERT->LIBELLE=="")
		$msg="Libellé obligatoire";
	elseif (!Is_Currency($this->TRANSFERT->MONTANT,FALSE,FALSE))
		$msg="Montant incorrect";
	elseif ($this->TRANSFERT->IDCOMPTEDE=="")
		$msg="Compte à débiter obligatoire";
	elseif ($this->TRANSFERT->IDCOMPTEA=="")
		$msg="Compte à créditer obligatoire";
	elseif ($this->TRANSFERT->IDCOMPTEDE==$this->TRANSFERT->IDCOMPTEA)
		$msg="Les comptes à débiter et à Créditer ne doivent pas être les mêmes";
	elseif ($this->TRANSFERT->IDPOSTE=="")
		$msg="Poste obligatoire";
	elseif ($this->TRANSFERT->IDTYPEPAIE=="")
		$msg="Type de paiement obligatoire";
	elseif (!is_date($this->TRANSFERT->DATEECR,"<=today")) $msg="Date d'écriture incorrecte";
	
	if ($msg=="")
	{
		//La date d'ouverture doit être la plus récente
		$r=$this->Donnees->GetInfoCPT($this->TRANSFERT->IDCOMPTEDE);
		if ($r->CR!="0") $msg=$r->MSG;
		elseif (isset($r->DATEOUVERTURE))
		{
			$d1=date_to_ts($r->DATEOUVERTURE);
			$d2=date_to_ts($this->TRANSFERT->DATEECR);
			if ($d1>$d2) $msg="Date d'écriture incorrecte: le compte ".trim($r->BANQUE." ".$r->LIBELLE)." a été ouvert le ".$r->DATEOUVERTURE;
		}
		$r=$this->Donnees->GetInfoCPT($this->TRANSFERT->IDCOMPTEA);
		if ($r->CR!="0") $msg=$r->MSG;
		elseif (isset($r->DATEOUVERTURE))
		{
			$d1=date_to_ts($r->DATEOUVERTURE);
			$d2=date_to_ts($this->TRANSFERT->DATEECR);
			if ($d1>$d2) $msg="Date d'écriture incorrecte: le compte ".trim($r->BANQUE." ".$r->LIBELLE)." a été ouvert le ".$r->DATEOUVERTURE;
		}
	}
	if ($msg=="")
	{
		$r = $this->Donnees->UpdateInfoTransfert($this->TRANSFERT,$typemodif);
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
		$this->TRANSFERT=$this->InitTransfert();
	}
}

//Récupération des soldes
function GetSoldes($comptechoisis,$datedeb,$datefin)
{
	if ($comptechoisis=="") return;
	$r=$this->Donnees->GetDetailECR($comptechoisis,"",incdaydate(-1,$datedeb));
	if( isset($r->DATA[0]->SOLDE)) $this->SOLDE_AVANT=$r->DATA[0]->SOLDE;
	if( isset($r->DATA[0]->SOLDEPOINTE)) $this->SOLDE_AVANT_POINTE=$r->DATA[0]->SOLDEPOINTE;
	$r=$this->Donnees->GetDetailECR($comptechoisis,"",$datefin);
	if( isset($r->DATA[0]->SOLDE)) $this->SOLDE_APRES=$r->DATA[0]->SOLDE;
	if( isset($r->DATA[0]->SOLDEPOINTE)) $this->SOLDE_APRES_POINTE=$r->DATA[0]->SOLDEPOINTE;
}

//Initialisaiton variables ECR
//type=post ou vide
function InitECR($type="")
{
	$result=new stdclass();
	if ($type=="post")
	{
		$result->ID=GetVariableFrom($_POST,"ID","");
		$result->IDCOMPTE=GetVariableFrom($_POST,"IDCOMPTE",$this->comptechoisis);
		$result->IDPOSTE=GetVariableFrom($_POST,"IDPOSTE","");
		$result->IDTYPEPAIE=GetVariableFrom($_POST,"IDTYPEPAIE",$this->typepaiechoisis);
		if ($result->IDTYPEPAIE=="") $result->IDTYPEPAIE="0";
		$result->LIBELLE=GetVariableFrom($_POST,"LIBELLE","");
		$result->BANQUE=GetVariableFrom($_POST,"BANQUE","");
		$result->REFERENCE=GetVariableFrom($_POST,"REFERENCE","");
		$result->MONTANT=GetVariableFrom($_POST,"MONTANT","0.00");
		$result->POINTE=GetVariableFrom($_POST,"POINTE","N");
		$result->IDTRANS=GetVariableFrom($_POST,"IDTRANS","");
		$result->DATEECR=GetVariableFrom($_POST,"DATEECR","");
		$result->SENS=GetVariableFrom($_POST,"SENS",$this->typeecr);
	}
	else
	{
		$result->ID="";
		$result->IDCOMPTE="";
		$result->IDPOSTE="";
		$result->IDTYPEPAIE="";
		$result->LIBELLE="";
		$result->BANQUE="";
		$result->REFERENCE="";
		$result->MONTANT="";
		$result->POINTE="";
		$result->IDTRANS="";
		$result->COPECRE="";
		$result->COPEMAJ="";
		$result->DCRE="";
		$result->DMAJ="";
		$result->SENS="";
		$result->DATEECR="";
	}
	return $result;
}

//Initialisaiton variables TRANSFERT
//type=post ou vide
function InitTransfert($type="")
{
	$result=new stdclass();
	if ($type=="post")
	{
		$result->ID=GetVariableFrom($_POST,"ID","");
		$result->IDCOMPTEDE=GetVariableFrom($_POST,"IDCOMPTEDE",$this->comptechoisis);
		$result->IDCOMPTEA=GetVariableFrom($_POST,"IDCOMPTEA",$this->comptechoisis);
		$result->IDPOSTE=GetVariableFrom($_POST,"IDPOSTE","");
		$result->IDTYPEPAIE=GetVariableFrom($_POST,"IDTYPEPAIE",$this->typepaiechoisis);
		if ($result->IDTYPEPAIE=="") $result->IDTYPEPAIE="1";
		$result->LIBELLE=GetVariableFrom($_POST,"LIBELLE","");
		$result->MONTANT=GetVariableFrom($_POST,"MONTANT","0.00");
		$result->DATEECR=GetVariableFrom($_POST,"DATEECR","");
	}
	else
	{
		$result->ID="";
		$result->IDCOMPTEDE="";
		$result->IDCOMPTEA="";
		$result->IDPOSTE="";
		$result->IDTYPEPAIE="";
		$result->LIBELLE="";
		$result->MONTANT="";
		$result->DATEECR="";
	}
	return $result;
}


function MkLists()
{
	$this->ListeCompte=array();
	$r=$this->Donnees->GetListeCPT("LIBELLE");
	if ($r->CR!="0") $this->message=$r->MSG;
	$this->ListeCompte=$r->DATA;
	
	$this->ListePoste=array();
	$r=$this->Donnees->GetListePoste("SENS DESC, LIBELLE",TRUE,TRUE);
	if ($r->CR!="0") $this->message=$r->MSG;
	$this->ListePoste=$r->DATA;
	
	$this->ListeTypePaie=array();
	$r=$this->Donnees->GetListeTypePaie("LIBELLE");
	if ($r->CR!="0") $this->message=$r->MSG;
	$this->ListeTypePaie=$r->DATA;
}


function MkFORMSEL ()
{
	Global $Parametres;
	$form="";
	if ($this->affichedata===FALSE) return $form;
	$form .="<form name='formsel' method='post' action='ecriture.php?action=ecr'>";
	$form .="<fieldset class=myfieldset><legend><B>Filtres</B></legend>";
	$form .="<table class='nobordure'><tr align='center'>";
	$form .="<td class='bg2'>Compte</td><td class='bg2'>Entre le</td><td class='bg2'>Et le</td><td class='bg2'>Ecritures</td></tr>";
	$form .="<tr>";
	$form .="<td>";
	$form .="<select name='comptechoisis' style='width:150px; height:25px;'>";
	foreach ($this->ListeCompte as $cpt)
	{
		$form .="<option value='".$cpt->ID."' ".($this->comptechoisis==$cpt->ID?"selected":"").">".$cpt->LIBELLE."</option>";
	}
	$form .="</select>";
	$form .="</td>";
	$form.="<td><input type=text style='width:90px; height:25px;' MAXLENGTH='10' name='datedeb' id='datedeb' ";
	$form.="value='".$this->datedeb."'>&nbsp;";
	$form.="<button type=reset style='width:30px; height:25px;' onclick='return showCalendar(\"datedeb\", \"dd/mm/y\");'>...</button></td>";
	$form.="<td><input type=text style='width:90px; height:25px;' MAXLENGTH='10' name='datefin' id='datefin' ";
	$form.="value='".$this->datefin."'>&nbsp;";
	$form.="<button type=reset style='width:30px; height:25px;' onclick='return showCalendar(\"datefin\", \"dd/mm/y\");'>...</button></td>";
	$form .="<td>";
	$form .="<select name='uniqpointe' style='width:150px; height:25px;'>";
	$form .="<option value='' ".($this->uniqpointe==""?"selected":"").">Toutes</option>";
	$form .="<option value='Y' ".($this->uniqpointe=="Y"?"selected":"").">Pointées seulement</option>";
	$form .="</select>";
	$form .="</td>";
	
	$form .="</tr>";
	$form .="</table>";
	$form .="<table class='nobordure'><tr align='center'>";
	$form .="<td class='bg2'>Type</td><td></td><td></td></tr>";
	$form .="<tr>";
	$form .="<td>";
	$form .="<select name='typepaiechoisis' style='width:260px; height:25px;'>";
	$form .="<option value='' ".($this->typepaiechoisis==""?"selected":"")."></option>";
	foreach ($this->ListeTypePaie as $typepaie)
	{
		$form .="<option value='".$typepaie->ID."' ".($this->typepaiechoisis==$typepaie->ID?"selected":"").">".$typepaie->LIBELLE."</option>";
	}
	$form .="</select>";
	$form .="</td>";
	$form.="<td><input type=submit name='buttonsel' style='width:150px; height:30px;' value='Filtrer'></td>";

	$form .="</table>";
	
	$form .="</fieldset>";
	$form .="</form>\r\n";
	return $form;
}


function MkFORMBTNNEW ()
{
	Global $Parametres;
	$form="";
	if ($this->affichedata===FALSE) return $form;

    $form  ="<form name='formbtnnew' method='post' action='ecriture.php?action=ecr'>";
    $form .="<fieldset class=myfieldset><legend><B>Ecritures</B></legend>";
	$form.="<input type=hidden name='editid' value=''>";
	$form.="<input type=hidden name='datedeb' value=\"".$this->datedeb."\">";
	$form.="<input type=hidden name='datefin' value=\"".$this->datefin."\">";
	$form.="<input type=hidden name='comptechoisis' value=\"".$this->comptechoisis."\">";
	$form.="<input type=hidden name='typepaiechoisis' value=\"".$this->typepaiechoisis."\">";
	$form.="<input type=hidden name='uniqpointe' value=\"".$this->uniqpointe."\">";
	$form.="<input type=hidden name='buttonsel' value='Sélectionner'>";
	$form.="<input type=hidden name='typeecr' value=''>";
	
	$form.="<p><table><tr>";
	$form.="<td><button name='button' style='width:170px; height:30px;' value='Nouveau' onclick=\"typeecr.value='C'; button.value='Nouveau'; submit();\">Nouvelle recette</td>";
	$form.="<td><button name='button' style='width:170px; height:30px;' value='Nouveau' onclick=\"typeecr.value='D'; button.value='Nouveau'; submit();\">Nouvelle dépense</td>";
	$form.="<td><button name='button' style='width:170px; height:30px;' value='Nouveau' onclick=\"typeecr.value='T'; button.value='Nouveau'; submit();\">Nouveau transfert</td>";
	$form.="</tr></table></fieldset>";
	$form.="</form>";
	return $form;
}

function MkFORMMODIFECR ()
{
	Global $Parametres;
	$form="";
	if ($this->typeecr=="T") return $form;
	if ($this->affichedata===TRUE) return $form;
	
    $form.="<form name='formmodif' method='post' action='ecriture.php?action=ecr'>";
    $form.="<fieldset class=myfieldset><legend><B>Ecritures</B></legend>";
	$form.="<input type=hidden name='editid' value=''>";
	$form.="<input type=hidden name='datedeb' value=\"".$this->datedeb."\">";
	$form.="<input type=hidden name='datefin' value=\"".$this->datefin."\">";
	$form.="<input type=hidden name='comptechoisis' value=\"".$this->comptechoisis."\">";
	$form.="<input type=hidden name='typepaiechoisis' value=\"".$this->typepaiechoisis."\">";
	$form.="<input type=hidden name='uniqpointe' value=\"".$this->uniqpointe."\">";
	$form.="<input type=hidden name='buttonsel' value='Sélectionner'>";
	$form.="<input type=hidden name='typeecr' value=\"".$this->typeecr."\">";
	
	$form.="<input type=hidden name='ID' value='".$this->ECR->ID."'>";
	$form.="<input type=hidden name='IDCOMPTE' value='".$this->ECR->IDCOMPTE."'>";
	$form.="<input type=hidden name='SENS' value='".$this->ECR->SENS."'>";
	$form .="<table class='nobordure'><tr align='center'>";
	$form .="<td class='bg2' style='width:80px;'>Id</td><td class='bg2' style='width:180px;'>Libellé</td><td class='bg2' style='width:140px;'>Date</td></tr>";
	$form .="<tr>";
	$form .="<td><input type=text style='text-transform:uppercase; width:100%; height:25px;  text-align:right; ' DISABLED name='ID' value='".$this->ECR->ID."'></td>";
	$form .="<td><input type=text name='LIBELLE' style='width:100%;  height:25px;' MAXLENGTH='32' value=\"".$this->ECR->LIBELLE."\"></td>";
	$form.="<td><input type=text style='width:70%; height:25px;' MAXLENGTH='10' name='DATEECR' id='DATEECR' ";
	$form.="value='".$this->ECR->DATEECR."'>&nbsp;";
	$form.="<button type=reset style='width:20%; height:25px;' onclick='return showCalendar(\"DATEECR\", \"dd/mm/y\");'>...</button></td>";
	$form .="</tr>";
	$form .="</table>";
	/**************************************/
	$form .="<table class='nobordure'><tr align='center'>";
	$form .="<tr align='center'>";
	$form .="<td class='bg2' style='width:150px;'>Compte</td><td class='bg2' style='width:150px;'>Poste</td><td class='bg2' style='width:100px;'>Sens</td>";
	$form .="</tr>";
	$form .="<tr>";
	$form .="<td>";
	$form .="<select name='IDCOMPTE' DISABLED style='width:100%; height:25px;'>";
	foreach ($this->ListeCompte as $cpt)
	{
		$form .="<option value='".$cpt->ID."' ".($this->ECR->IDCOMPTE==$cpt->ID?"selected":"").">".$cpt->LIBELLE."</option>";
	}
	$form .="</select>";
	$form .="</td>";
	$form .="<td>";
	$form .="<select name='IDPOSTE' style='width:100%; height:25px;'>";
	$form .="<option value='' ".($this->ECR->IDPOSTE==""?"selected":"")."></option>";
	foreach ($this->ListePoste as $poste)
	{
		if (($this->ECR->IDPOSTE==$poste->ID) or (($poste->HIDDEN=="N") and ($poste->SENS==$this->ECR->SENS)))
			$form .="<option value='".$poste->ID."' ".($this->ECR->IDPOSTE==$poste->ID?"selected":"").">".$poste->LIBELLE."</option>";
	}
	$form .="</select>";
	$form .="</td>";
	$form .="<td>";
	$form .="<select name='SENS' DISABLED style='width:100%; height:25px;'>";
	$form .="<option value='C' ".($this->ECR->SENS!="D"?"selected":"").">Recette</option>";
	$form .="<option value='D' ".($this->ECR->SENS=="D"?"selected":"").">Dépense</option>";
	$form .="</select>";
	$form .="</td>";
	$form .="</tr>";
	$form .="</table>";
	/**************************************/
	$form .="<table>";
	$form .="<tr align='center'>";
	$form .="<td class='bg2' style='width:160px;'>Montant</td><td class='bg2' style='width:250px;'>Type de paiement</td>";
	$form .="</tr>";
	$form .="<tr>";
	$form .="<td><input type=text name='MONTANT' id='MONTANT' style='width:100%;  text-align:right; height:25px;' MAXLENGTH='12' value=\"".$this->ECR->MONTANT."\"></td>";
	$form .=MkFilterFloat("MONTANT");
	$form .="<td>";
	$form .="<select name='IDTYPEPAIE' style='width:100%; height:25px;'>";
	foreach ($this->ListeTypePaie as $paiement)
	{
		$form .="<option value='".$paiement->ID."' ".($this->ECR->IDTYPEPAIE==$paiement->ID?"selected":"").">".$paiement->LIBELLE."</option>";
	}
	$form .="</select>";
	$form .="</td>";
	$form .="</tr>";
	$form .="</table>";
	/**************************************/
	$form .="<table>";
	$form .="<tr align='center'>";
	$form .="<td class='bg2' style='width:120px;'>Banque</td><td class='bg2' style='width:180px;'>Référence</td><td class='bg2' style='width:110px;'>Pointé</td>";
	$form .="</tr>";
	$form .="<tr>";
	$form .="<td><input type=text name='BANQUE' id='BANQUE' style='width:100%;  text-align:left; height:25px;' MAXLENGTH='32' value=\"".$this->ECR->BANQUE."\"></td>";
	$form .="<td><input type=text name='REFERENCE' id='REFERENCE' style='width:100%;  text-align:left; height:25px;' MAXLENGTH='32' value=\"".$this->ECR->REFERENCE."\"></td>";
	$form .="<td>";
	$form .="<select name='POINTE' style='width:100%; height:25px;'>";
	$form .="<option value='N' ".($this->ECR->POINTE!="Y"?"selected":"").">Non</option>";
	$form .="<option value='Y' ".($this->ECR->POINTE=="Y"?"selected":"").">Oui</option>";
	$form .="</select>";
	$form .="</td>";
	$form .="</tr>";
	
	$form .="</table>";

	$form.="<p><table><tr>";
	$form.="<td><input type=submit name='button' style='width:90px; height:30px;' value='Valider'></td>";
	$form.="<td><input type=submit name='button' style='width:90px; height:30px;' value='Annuler'></td>";
	if ($this->button=="Modifier")
	{
		$form.="<td><button name='delbutton' form='delform' onclick='self.location.href=\"ecriture.php?action=ecr&dellinkecr=".encode_mdp($this->ECR->ID)."\"' style='width:90px; height:30px; color:#fff; background-color:red;'>Supprimer</button></td>";	
	}
	$form.="</tr></table></fieldset>";
	$form.="<input type=hidden name='fonctionvalide' ";
	if ($this->button=="Modifier") $form.="value='validemodif'>";
	elseif ($this->button=="Nouveau") $form.="value='validenouveau'>";
	else $form.="value=''>";
	$form.="</form><br>\r\n";
	return $form;
}

function MkFORMMODIFTRANSFERT ()
{
	Global $Parametres;
	$form="";
	if ($this->affichedata===TRUE) return $form;
	if ($this->typeecr!="T") return $form;
    $form  ="<form name='formmodif' method='post' action='ecriture.php?action=ecr'>";
    $form .="<fieldset class=myfieldset><legend><B>Transferts</B></legend>";
	$form.="<input type=hidden name='editecr' value=''>";
	$form.="<input type=hidden name='datedeb' value=\"".$this->datedeb."\">";
	$form.="<input type=hidden name='datefin' value=\"".$this->datefin."\">";
	$form.="<input type=hidden name='comptechoisis' value=\"".$this->comptechoisis."\">";
	$form.="<input type=hidden name='typepaiechoisis' value=\"".$this->typepaiechoisis."\">";
	$form.="<input type=hidden name='uniqpointe' value=\"".$this->uniqpointe."\">";
	$form.="<input type=hidden name='typeecr' value=\"".$this->typeecr."\">";
	
	$form.="<input type=hidden name='ID' value='".$this->TRANSFERT->ID."'>";
	$form .="<table class='nobordure'><tr align='center'>";
	$form .="<td class='bg2' style='width:80px;'>Id</td><td class='bg2' style='width:230px;'>Libellé</td><td class='bg2' style='width:140px;'>Date</td></tr>";
	$form .="<tr>";
	$form .="<td><input type=text style='text-transform:uppercase; width:100%; height:25px;  text-align:right; ' DISABLED name='ID' value='".$this->TRANSFERT->ID."'></td>";
	$form .="<td><input type=text name='LIBELLE' style='width:100%;  height:25px;' MAXLENGTH='32' value=\"".$this->TRANSFERT->LIBELLE."\"></td>";
	$form.="<td><input type=text style='width:70%; height:25px;' MAXLENGTH='10' name='DATEECR' id='DATEECR' ";
	$form.="value='".$this->TRANSFERT->DATEECR."'>&nbsp;";
	$form.="<button type=reset style='width:20%; height:25px;' onclick='return showCalendar(\"DATEECR\", \"dd/mm/y\");'>...</button></td>";
	$form .="</tr>";
	$form .="</table>";
	/**************************************/
	$form .="<table class='nobordure'><tr align='center'>";
	$form .="<tr align='center'>";
	$form .="<td class='bg2' style='width:150px;'>Du compte</td><td class='bg2' style='width:150px;'>Vers le compte</td><td class='bg2' style='width:150px;'>Poste</td>";
	$form .="</tr>";
	$form .="<tr>";
	$form .="<td>";
	$form .="<select name='IDCOMPTEDE' style='width:100%; height:25px;'>";
	foreach ($this->ListeCompte as $cpt)
	{
		$form .="<option value='".$cpt->ID."' ".($this->TRANSFERT->IDCOMPTEDE==$cpt->ID?"selected":"").">".$cpt->LIBELLE."</option>";
	}
	$form .="</select>";
	$form .="</td>";
	$form .="<td>";
	$form .="<select name='IDCOMPTEA' style='width:100%; height:25px;'>";
	foreach ($this->ListeCompte as $cpt)
	{
		$form .="<option value='".$cpt->ID."' ".($this->TRANSFERT->IDCOMPTEA==$cpt->ID?"selected":"").">".$cpt->LIBELLE."</option>";
	}
	$form .="</select>";
	$form .="</td>";
	$form .="<td>";
	$form .="<select name='IDPOSTE' style='width:100%; height:25px;'>";
	$form .="<option value='' ".($this->TRANSFERT->IDPOSTE==""?"selected":"")."></option>";
	foreach ($this->ListePoste as $poste)
	{
		if (($this->TRANSFERT->IDPOSTE==$poste->ID) or (($poste->HIDDEN=="N") and ($poste->SENS=="D")))
			$form .="<option value='".$poste->ID."' ".($this->TRANSFERT->IDPOSTE==$poste->ID?"selected":"").">".$poste->LIBELLE."</option>";
	}
	$form .="</select>";
	$form .="</td>";
	$form .="</tr>";
	$form .="</table>";
	/**************************************/
	$form .="<table>";
	$form .="<tr align='center'>";
	$form .="<td class='bg2' style='width:120px;'>Montant</td><td class='bg2' style='width:190px;'>Type de paiement</td>";
	$form .="</tr>";
	$form .="<tr>";
	$form .="<td><input type=text name='MONTANT' id='MONTANT' style='width:100%;  text-align:right; height:25px;' MAXLENGTH='12' value=\"".$this->TRANSFERT->MONTANT."\"></td>";
	$form .=MkFilterFloat("MONTANT");
	$form .="<td>";
	$form .="<select name='IDTYPEPAIE' style='width:100%; height:25px;'>";
	foreach ($this->ListeTypePaie as $paiement)
	{
		$form .="<option value='".$paiement->ID."' ".($this->TRANSFERT->IDTYPEPAIE==$paiement->ID?"selected":"").">".$paiement->LIBELLE."</option>";
	}
	$form .="</select>";
	$form .="</td>";
	$form .="</tr>";
	$form .="</table>";
	/**************************************/
	$form .="<table>";
	$form .="<tr align='center'>";
	$form .="";
	$form .="</tr>";
	$form .="<tr>";

	$form .="</tr>";
	
	$form .="</table>";

	$form.="<p><table><tr>";
	$form.="<td><input type=submit name='button' style='width:90px; height:30px;' value='Valider'></td>";
	$form.="<td><input type=submit name='button' style='width:90px; height:30px;' value='Annuler'></td>";
	if ($this->button=="Modifier")
	{
		$form.="<td><button name='delbutton' form='delform' onclick='self.location.href=\"ecriture.php?action=ecr&dellinktrans=".encode_mdp($this->TRANSFERT->ID)."\"' style='width:90px; height:30px; color:#fff; background-color:red;'>Supprimer</button></td>";	
	}
	$form.="</tr></table></fieldset>";
	$form.="<input type=hidden name='fonctionvalide' ";
	if ($this->button=="Modifier") $form.="value='validemodif'>";
	elseif ($this->button=="Nouveau") $form.="value='validenouveau'>";
	else $form.="value=''>";
	$form.="</form><br>\r\n";
	return $form;
}

function MkCONTENU ()
{
	Global $Parametres;
	$form_modif="";
	$form_sel="";
    if ($this->destination == "ecran")
    {
		$form_sel="<p>".$this->MkFORMSEL();
		if (($this->istresorier+$this->isadmin)>=1)
		{
			$form_modif.="<p>".$this->MkFORMBTNNEW();
			$form_modif.="<p>".$this->MkFORMMODIFECR();
			$form_modif.="<p>".$this->MkFORMMODIFTRANSFERT();
		}
	}
	$html = "<h1>Liste des écritures</h1>";
	$html .=$form_sel.$form_modif;

	if ((count($this->Liste)==0) and ($this->affichedata===TRUE)) $html.="<br>Aucune données à afficher";
	$html .=$this->TBDONNEE();

	$html.=ShowMessage($this->message);	
    return $html;
}


//Les liens de suppression/modification
function MkActionLink($id,$texte,$typeecr)
{
	global $Parametres;
	$result="";
	$link=new HRefPost('ecriture.php?action=ecr');
	$link->SetTexte("");
	$link->SetTitle($texte);
	if (($typeecr!="T") and  (strtolower($texte)) =="supprimer") 
		$link->AddData("dellinkecr",encode_mdp($id),"");
	elseif (($typeecr!="T") and  (strtolower($texte)) =="modifier") 
		$link->AddData("modlinkecr",encode_mdp($id),"");
	elseif (($typeecr=="T") and  (strtolower($texte)) =="supprimer") 
		$link->AddData("dellinktrans",encode_mdp($id),"");
	elseif (($typeecr=="T") and  (strtolower($texte)) =="modifier") 
		$link->AddData("modlinktrans",encode_mdp($id),"");

	$link->AddData("comptechoisis",$this->comptechoisis,"");
	$link->AddData("typepaiechoisis",$this->typepaiechoisis,"");
	$link->AddData("uniqpointe",$this->uniqpointe,"");
	$link->AddData("datedeb",$this->datedeb,"");
	$link->AddData("datefin",$this->datefin,"");
	if (strtolower($texte)=="supprimer")
		$link->SetImgFile($Parametres->CheminImages."supprimer.gif");
	else
		$link->SetImgFile($Parametres->CheminImages."editer.gif");
	$result=$link->MkHRefPost();
	return $result;
}

function TBDONNEE()
{
	$html="";
    global $Parametres;
	if ($this->affichedata===FALSE) return $html;
		///Pour le js
	$canedit=(($this->destination=="ecran") and (($this->istresorier+$this->isadmin)>=1)) ;

	if ($this->SOLDE_AVANT!="")
	{
		$html .= "<fieldset class='myfieldset'><legend><b>Soldes antérieurs</b></legend>";
		$html .= "<table class='nobordure soldes'  style='padding:5px;'><tr><th style='padding-right:10px;'>Banque :</th><td style='padding-right:50px;'>".$this->SOLDE_AVANT_POINTE."</td><th style='padding-right:10px;'>Relevé :</th><td style='padding-right:50px;'>".$this->SOLDE_AVANT."</td></tr></table>";
		$html .= "</fieldset><br>";
	}
	if (count($this->Liste)>0)
	{
		if ($this->destination=="impression") 
			$html .= "<table class='bordure' style='border-collapse: collapse;'>";
		else
		{
			$html .= "<table class='tsc_tables'>";
			$html .="<div id='USERMODIFYING' style='visibility:hidden;'>".$this->user_info->USERMODIFYING."</div>";
		}

		$html.="<thead><tr ".($this->destination=="impression"?"class='bg2'":"").">";
		
		$html.="<th colspan='".($canedit===TRUE?2:1)."'".($this->destination == "ecran"?"scope='col' class='rounded-head-left' ":"")."><b>Id</b></th>";
		$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Date</b></th>";
		$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Libellé</b></th>";
		$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Compte</b></th>";
		$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Poste</b></th>";	
		$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Type de paiement</b></th>";
		$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Crédit</b></th>";
		$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Débit</b></th>";
		$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head' ":"")."><b>Pointé</b></th>";
		$html.="<th ".($this->destination == "ecran"?"scope='col' class='rounded-head-right' ":"")."><b>Modifié</b></th>";
		$html.= "<tr></thead><tbody>";
		$i=0;
		Foreach($this->Liste as $row)
			{
			$editcell="";
			if (($canedit===TRUE) and ($row->IDPOSTE!="1"))
			{
				/*
				$modiflink="ecriture.php?action=ecr&".($row->IDTRANS==""?"modlinkecr=".encode_mdp($row->ID):"modlinktrans=".encode_mdp($row->IDTRANS));
				$deletelink="ecriture.php?action=ecr&".($row->IDTRANS==""?"dellinkecr=".encode_mdp($row->ID):"dellinktrans=".encode_mdp($row->IDTRANS));
				$editcell.="<td><a style='border:none;'  onMouseOver='return overlib(\"Supprimer\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' href='".$deletelink."'><img border='0' src='".$Parametres->CheminImages."supprimer.gif'></a>";
				$editcell.="<br><a style='border:none;' onMouseOver='return overlib(\"Modifier\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' href='".$modiflink."'><img border='0' src='".$Parametres->CheminImages."editer.gif'></a></td>";
				*/
				$modiflink=$this->MkActionLink(($row->IDTRANS==""?$row->ID:$row->IDTRANS),"Modifier",($row->IDTRANS==""?"DC":"T"));
				$deletelink=$this->MkActionLink(($row->IDTRANS==""?$row->ID:$row->IDTRANS),"Supprimer",($row->IDTRANS==""?"DC":"T"));
				$editcell.="<td>".$modiflink;
				$editcell.="<br>".$deletelink;
				$editcell.="</td>";

			}
			elseif (($canedit===TRUE) and ($row->IDPOSTE=="1"))
			{
				$editcell.="<td><img onMouseOver='return overlib(\"Suppression interdite\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' border='0' src='".$Parametres->CheminImages."forbidden.png'>";
				$editcell.="<br><img onMouseOver='return overlib(\"Modification interdite\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"100\");' onMouseOut='return nd();' border='0' src='".$Parametres->CheminImages."forbidden.png'></td>";
			}
			$html .= "<tr>".$editcell;
			$html .= "<td align='right'>".$row->ID."</td>";
			$html .= "<td align='right'>".$row->DATEECR."</td>";
			$html .= "<td align='left'>".$row->LIBELLE."</td>";
			$html .= "<td align='left'>".$row->CPT_LIBELLE."<br>".$row->CPT_BANQUE."</td>";
			$html .= "<td align='left'>".$row->POSTE_LIBELLE."</td>";
			$html .= "<td align='left'>".$row->TYPEPAIE_LIBELLE."<br>".$row->BANQUE." ".$row->REFERENCE."</td>";
			$html .= "<td align='right' nowrap>".$row->CREDIT."</td>";
			$html .= "<td align='right' nowrap>".$row->DEBIT."</td>";
			//DEV JAVASCRIPT A FAIRE !
			$montant=($row->CREDIT==""?-1*$row->DEBIT:$row->CREDIT);
			$html .= "<td align='center'>";
			$onclick=($canedit===FALSE?"":" onclick=\"top_ecriture('img_id_".$i."','modified_row_".$i."',".$row->ID.",'".$montant."','".$this->user_info->EMAIL."');\" onMouseOver='return overlib(\"Pointer / dépointer\",FGCOLOR,\"YELLOW\",TEXTSIZE,\"2\",WIDTH,\"150\");' onMouseOut='return nd();' ");
			$html .= "<img id='img_id_".$i."' ".$onclick." src='".$Parametres->CheminImages.($row->POINTE=="Y"?"checked_checkbox.png":"unchecked_checkbox.png")."'.>";
			$html .= "</td>";
			
			$html .= "<td align='left' id='modified_row_".$i."'>".($row->COPEMAJ!=""?"Par ".$row->COPEMAJ."<br>le ".$row->DMAJ:"Par ".$row->COPECRE."<br>le ".$row->DCRE)."</td>";
			$html .= "</tr>";
			$i++;
			}
		$html .= "</tbody>";
		$html .= "<tfoot><tr>";
		$html .= "<th colspan=".($canedit===TRUE?7:6)." align='left' ".($this->destination == "ecran"?"scope='col' class='rounded-foot-left' style='text-align:left;' ":"")."><b>Total pour cette sélection</b></th>";
		$html .= "<th align='right' nowrap ".($this->destination == "ecran"?"scope='col' class='rounded-foot' style='text-align:right;' ":"")."><b>".CurrencyString($this->TOTAL_CREDIT)."</b></th>";
		$html .= "<th align='right' nowrap ".($this->destination == "ecran"?"scope='col' class='rounded-foot' style='text-align:right;' ":"")."><b>".CurrencyString($this->TOTAL_DEBIT)."</b></th>";
		$html .= "<th colspan='2' ".($this->destination == "ecran"?"scope='col' class='rounded-foot-right' ":"")."></th>";
		$html .= "</tr></tfoot>";
		$html .= "</table>";
	}
	if ($this->SOLDE_APRES!="")
	{
		$html .= "<br><fieldset class='myfieldset'><legend><b>Soldes fin de période</b></legend>";
		$html .= "<table class='nobordure soldes' ><tr><th>Banque :</th><td id='SOLDE_APRES_POINTE' style='padding-right:50px;'>".$this->SOLDE_APRES_POINTE."</td><th style='padding-right:10px;'>Relevé :</th><td style='padding-right:50px;'>".$this->SOLDE_APRES."</td></tr></table>";
		$html .= "</fieldset><br>";
	}
    return $html;     
}   
}


$action=GetVariableFrom($_REQUEST,"action","");
switch ($action)
{
	case "ecr" :
		default:
		$page = new PageEcriture(0); 
		break;
}
$page->WritePAGE (); 
?>
