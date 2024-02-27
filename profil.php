<?php
require_once ("db.php");
require_once ("lib.php");
require_once ("look.php");

class BaseProfil extends PageWeb
{
    function __construct($id)
    {
        parent::__construct(10*PAGE_PROFIL+$id);
    }    

}

class Profil extends BaseProfil
{
    var $Titre = "Profil";
	var $buttonpassword;
	var $current_password;
	var $confirm_password;
	var $new_password;
	var $buttonprofil;
	var $Oui;
	var $Non;
	var $PROFIL;
	var $message;

function __construct($id)
{
	Global $Parametres;
	$this->message="";
	parent::__construct($id);
	$this->destination=GetVariableFrom($_REQUEST,"destination","ecran");
	$this->buttonpassword=GetVariableFrom($_POST,"buttonpassword","");
	$this->current_password=GetVariableFrom($_POST,"current_password","");
	$this->confirm_password=GetVariableFrom($_POST,"confirm_password","");
	$this->new_password=GetVariableFrom($_POST,"new_password","");
	$this->buttonprofil=GetVariableFrom($_POST,"buttonprofil","");
    $this->Oui = GetVariableFrom($_POST,"oui","");
    $this->Non = GetVariableFrom($_POST,"non","");

	$this->PROFIL=new stdclass();
	$this->PROFIL->IDUSER=$this->user_info->IDUSER;
    $this->PROFIL->NOM=GetVariableFrom($_POST,"nom",$this->user_info->NOM);
	$this->PROFIL->PRENOM=GetVariableFrom($_POST,"prenom",$this->user_info->PRENOM);
	$this->PROFIL->EMAIL=GetVariableFrom($_POST,"email",$this->user_info->EMAIL);
	
	if (($this->current_password=="") or ($this->new_password=="") or ($this->confirm_password=="")) $this->buttonpassword="";
	if ($this->buttonpassword=="Valider") 
	{
		if ($this->current_password=="") $this->message="Le mot de passe actuel est manquant";
		elseif ($this->new_password=="") $this->message="Le nouveau mot de passe est manquant";
		elseif ($this->confirm_password!=$this->new_password) $this->message="Les mots de passes ne concordent pas";
	}
	if (($this->buttonpassword=="Valider") and ($this->message==""))
	{
		$ChgPass = $this->Donnees->ChangePassword( $this->user_info->EMAIL, $this->current_password, $this->new_password);
		$this->message=$ChgPass->MSG;
	}
/********************************************************************************/
	if ($this->buttonprofil=="Valider") 
	{
		if (($this->PROFIL->NOM=="") and ($this->PROFIL->PRENOM==""))
			$this->message="A minima, le nom doit être renseignés";
		elseif ($this->PROFIL->EMAIL=="") $this->message="Email obligatoire";
	}
	if (($this->buttonprofil=="Valider") and ($this->message==""))
	{
		$ChgProfil = $this->Donnees->UpdateUserbyUser( $this->PROFIL);
		$this->message=$ChgProfil->MSG;
	}	
	$this->PROFIL=$this->Donnees->GetUser($this->user_info->IDUSER,"N");
/**********************************************************************************/
}	


function MkCONTENU()
{
	$html="<h1>".$this->Titre."</h1>";
	$html.="<table class='nobordure'><tr><td valign='top'>".$this->MkFORMPROFIL()."<br>".$this->MkFORMPASSWORD()."</td></tr></table>";
	if ($this->message!="")
	{
	  $this->message=htmlentities($this->message,ENT_QUOTES,"UTF-8");
	  $html.="<script>alert(\"".str_replace("\r\n","\\n",html_entity_decode($this->message,ENT_QUOTES,"UTF-8"))."\");</script>";	
	}
	return $html;
}

function MkFORMPASSWORD ()
{
    $form  ="<form method='post'  name='formpassword' action='profil.php'>";
    $form .="<fieldset class='myfieldset'><legend><B>Modififer votre mot de passe</B></legend>";
	$form .="<table class='nobordure'><tr align='center'>";
    $form .="<tr ><td >Mot de passe actuel</td>";
	$form .="<td colspan='2'><input type=text name='current_password' style='width:180px; height:25px;' MAXLENGTH='20' value=\"".$this->current_password."\"></td></tr>";
    $form .="<tr ><td >Nouveau mot de passe</td>";
	$form .="<td colspan='2'><input type=text name='new_password' style='width:180px; height:25px;' MAXLENGTH='20' value=\"".$this->new_password."\"></td></tr>";
    $form .="<tr ><td >Confirmer</td>";
	$form .="<td colspan='2'><input type=text name='confirm_password' style='width:180px; height:25px;' MAXLENGTH='20' value=\"".$this->confirm_password."\"></td></tr>";	
	$form .="<tr><td></td><td><input type=submit style='width:90px; height:30px;'  name='buttonpassword' value='Valider'></td><td><input type=submit style='width:90px; height:30px;' name='buttonpassword' value='Annuler'></td></tr></table>";
	$form .="</fieldset></form>";
	return $form;
}


function MkFORMPROFIL ()
{
    $form  ="<form method='post'  name='formpprofil' action='profil.php'>";
    $form .="<fieldset class='myfieldset'><legend><B>Modififer votre profil</B></legend>";
	$form .="<table class='nobordure'><tr align='center'>";
    $form .="<tr ><td >Nom</td>";
	$form .="<td colspan='2'><input type=text name='nom' style='width:180px; height:25px;' MAXLENGTH='32' value=\"".$this->PROFIL->NOM."\"></td></tr>";
	$form .="<tr ><td >Prénom</td>";
    $form .="<td colspan='2'><input type=text name='prenom' style='width:180px; height:25px;' MAXLENGTH='32' value=\"".$this->PROFIL->PRENOM."\"></td></tr>";
	$form .="<tr><td style='vertical-align:top';>Adresse</td>";
	$form .="<tr ><td valid='top'>Email</td>";
	$form .="<td colspan='2'><input type=text name='email' style='width:180px; height:25px;' MAXLENGTH='256' value=\"".$this->PROFIL->EMAIL."\"></td></tr>";		
	$form .="<tr><td></td><td><input type=submit style='width:90px;  height:30px;'  name='buttonprofil' value='Valider'></td><td><input type=submit style='width:90px; height:30px;' name='buttonprofil' value='Annuler'></td></tr></table>";
	$form .="</fieldset></form>";
	return $form;
}


}

$page = new Profil(0); 
$page->WritePAGE (); 
?>
