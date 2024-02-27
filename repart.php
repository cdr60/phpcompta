<?php 
// Main program
if(!isset($_SESSION)) {  session_start(); }
require_once ("param.php");
require_once ("db.php");
require_once ("look.php");
require_once ("lib.php");


class Repart extends PageWeb {
    function __construct($id,$destination="ecran")
    {
        parent::__construct(10*PAGE_REPART+$id,FALSE,FALSE,$destination);
    } 
} 


class PageRepart extends Repart 
{
    var $Titre = "Accueil";
	var $destination;
	var $fm_annee;
	var $fm_compte;
	var $fm_button;
	var $message;
	var $Annee;
	var $ListeCompte;
	var $ListeCredit;
	var $ListeDebit;
	var $ListePoste;
	var $fm_uniqpointe;
	var $ChartCredit;
	var $ChartDebit;

function __construct($id)
{
	$this->destination=GetVariableFrom($_REQUEST,"destination","ecran");
    parent::__construct($id,$this->destination);
	$this->fm_annee=GetVariableFrom($_POST,"fm_annee","");
	$this->fm_compte=GetVariableFrom($_POST,"fm_compte","");
	$this->fm_button=GetVariableFrom($_POST,"fm_button","");
	$this->fm_uniqpointe=GetVariableFrom($_POST,"fm_uniqpointe","N");
	

	$this->ListeCompte=array();
	$r=$this->Donnees->GetListeCPT("LIBELLE");
	if ($r->CR!="0") $this->message=$r->MSG;
	$this->ListeCompte=$r->DATA;
	
	
	$Annee=$this->Donnees->GetYearData();
	$j=0;
	$this->Annee=array();
	if (count($this->Annee)==0) $this->Annee[]=date("Y");
	for ($i=0;$i<count($Annee->DATA);$i++) 
	{
		
		if (!in_array($Annee->DATA[$i]->ANNEE,$this->Annee))
		{	$j++;		$this->Annee[$j]=$Annee->DATA[$i]->ANNEE; }
	}
	$this->ListeDebit=array();
	$this->ListeCredit=array();
	$this->ChartCredit="";
	$this->ChartDebit="";

	if ($this->fm_button!="")
	{
		$r=$this->Donnees->GetRepart($this->fm_annee,$this->fm_compte,$this->fm_uniqpointe,"C");
		if ($r->CR!="0") $this->message=$r->MSG;
		$this->ListeCredit=$r->DATA;
		$r=$this->Donnees->GetRepart($this->fm_annee,$this->fm_compte,$this->fm_uniqpointe,"D");
		if ($r->CR!="0") $this->message=$r->MSG;
		$this->ListeDebit=$r->DATA;
		$this->ChartCredit=$this->MkHtmlChart("credit",$this->ListeCredit,$this->fm_annee,$this->fm_compte,$this->fm_uniqpointe);
		$this->ChartDebit=$this->MkHtmlChart("debit",$this->ListeDebit,$this->fm_annee,$this->fm_compte,$this->fm_uniqpointe);
	}
	
}

function MkHtmlChart($type,$liste,$annee,$compte,$uniqpointe)
{
	if (count($liste)==0) return "";
	$width="470px";
	$height="300px";
	
	$stotal=0;
	$ttotal=0;

	$libcompte="";
	$i=-1;
	if ($this->fm_compte!="") $i=search_in_array_of_object($compte,"ID",$this->ListeCompte);
	if ($i!=-1) $libcompte=tronque($this->ListeCompte[$i]->LIBELLE,32);
	
	$title="Répartition ";
	$title.=($type=="credit"?" des recettes ":" des dépenses ").($libcompte==""?"tous comptes ":$libcompte)." ".($uniqpointe?"toutes écritures":"écritures pointées");
	
	$mydata=array();
	$nbemax=10;
	$i=0;
	foreach($liste as $row)
	{
		if ($i<$nbemax) 
		{
			$mydata[$i]=$row;
			$stotal+=floatval(str_replace(array(" ",","),array("","."),$row->POSTE_TOTAL??""));
		}
		$ttotal+=floatval(str_replace(array(" ",","),array("","."),$row->POSTE_TOTAL??""));
		$i++;
	}
	$result="\r\n";
	$BasicColor=array("blue","red","blueviolet","green","yellow","brown","burlywood","cadetblue","crimson","darkred","deeppink","forestgreen","goldenrod");
	$idcanvas="'chart_".$type."_".date("s")."'";
	$result.="<fieldset class='myfieldset' style='width:".$width."; height:".$height.";'><legend>".$title."</legend>\r\n";
	$result.="<canvas id=".$idcanvas."></canvas>\r\n";
	$result.="</fieldset>\r\n";
	$result.="<script type='text/javascript'>\r\n";
	$result.="function mk_canvas_".$type."() \r\n";
	$result.="{ \r\n";
	$result.="var color = Chart.helpers.color;\r\n";
	$result.="var myPie = \r\n";
	$result.="{\r\n";
	$result.="datasets: [{\r\n";
	$result.="data: [\r\n";
	$dt="";
	foreach($mydata as $row)
	{
		$dt.=floatval(str_replace(array(" ",","),array("","."),$row->POSTE_TOTAL??"")).",";
	}			
	$result.=substr($dt,0,strlen($dt)-1);
	$result.="],\r\n";
	$col="backgroundColor: [\r\n";
	$numcolor=0;
	foreach($mydata as $row)
	{
		$col.="'".$BasicColor[$numcolor]."',";
		$numcolor=($numcolor+1)%(count($BasicColor));
	}
	$result.=substr($col,0,strlen($col)-1)."\r\n";
	$result.="],\r\n";
	$result.="label: 'Dataset 1'\r\n";
	$result.="}],\r\n";
	$result.="labels: [\r\n";
	$l="";
    foreach($mydata as $row)
    {
		$l.="'".$row->POSTE_LIBELLE."',";
    }
	$result.=substr($l,0,strlen($l)-1);
	$result.="]\r\n";
	$result.="};\r\n";
	$result.="var ctx = document.getElementById(".$idcanvas.").getContext('2d'); \r\n";
	$result.="window.myPie = new Chart(ctx, \r\n";
	$result.="{\r\n";
	$result.="type: 'pie', \r\n";
	$result.="data: myPie, \r\n";
	$result.="options: \r\n";
	$result.="{ \r\n";
	$result.="	elements: {	rectangle: { borderWidth: 1, }	}, \r\n";
	$result.="	responsive: true, legend: { position: 'top', }, \r\n";
	$result.="	title: { display: false } \r\n";
	$result.="} \r\n";
	$result.="});  \r\n";
	$result.="}; \r\n";
	$result.="</script>	\r\n";
	return $result;
}


function MKForm()
{
	$html="<form method=POST>";
	$html.="<fieldset class='myfieldset'>";
	$html.="<table><tr>";
	$html.="<th style='padding-right:10px;'>Année</th>";
	$html.="<th style='padding-right:10px;'>Compte</th>";
	$html.="<th style='padding-right:10px;'>Sélection</th>";
	$html.="<th style='padding-right:10px;'></th>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<td style='padding-right:10px;'>";
	$html.="<select name='fm_annee'  style='height:25px;'>";
	for ($i=0;$i<count($this->Annee);$i++) 
	{
		$html.="<option value='' ".(""==$this->Annee?" SELECTED ":"").">Toutes</option>";
		$html.="<option value='".$this->Annee[$i]."' ".(intval($this->Annee[$i])==intval($this->fm_annee)?" SELECTED ":"").">".$this->Annee[$i]."</option>";
	}
	$html.="</select>";
	$html.="</td>";
	$html.="<td style='padding-right:10px;'>";
	$html.="<select name='fm_compte' style='height:25px;'>";
	$html.="<option value='' ".(""==$this->fm_compte?" SELECTED ":"").">Tous</option>";
	foreach ($this->ListeCompte as $CPT)
	{
		$html.="<option value='".$CPT->ID."' ".($CPT->ID==$this->fm_compte?" SELECTED ":"").">".trim($CPT->BANQUE." ".$CPT->LIBELLE)."</option>";
	}
	$html.="</select>";
	$html.="</td>";
	$html.="<td style='padding-right:10px; '>";
	$html.="<select name='fm_uniqpointe'  style='height:25px;'>";
		$html.="<option value='N' ".($this->fm_uniqpointe!="Y"?"SELECTED ":"").">Toutes</option>";
		$html.="<option value='Y' ".($this->fm_uniqpointe=="Y"?"SELECTED ":"").">Seulement les écritures pointées</option>";
	$html.="</select>";
	$html.="</td>";


	$html.="<td style='padding-right:10px;'>";
	$html.="<input type=submit name='fm_button' style='height:30px;' class='button-sel' value='Sélectionner'>";
	$html.="</td>";

	$html.="</tr>";
	
	$html.="</table>";
	$html.="</fieldset>";
	$html.="</form>";
	return $html;
}



function MkCONTENU ()
{
	Global $Parametres;
	$html="<h1>Accueil</h1>";
	$html.=$this->MKForm();
	$html.="<br>";
	$html.="<script type='text/javascript'>window.onload = function() {\r\n";
	if ($this->ChartCredit!="") $html.="mk_canvas_credit();\r\n";
	if ($this->ChartDebit!="") $html.="mk_canvas_debit();\r\n";
	$html.="}</script>\r\n<br>";
	
	$html .= "<div class='flex-container-repart'>";
	if (count($this->ListeCredit)>0)
	{
		$html .= "<div class='flex-content-repart'>";
		$html .= "<H1>Postes de recette</h1>";
		$html.=$this->ChartCredit;
		
		$html .= "<br><table class='tsc_tables' style='width:500px;'>";	
		$html.="<thead><tr>";
		$html.="<th  scope='col' class='rounded-head-left'><b>Année</b></th>";
		$html.="<th  scope='col' class='rounded-head'><b>Compte</b></th>";	
		$html.="<th  scope='col' class='rounded-head'><b>Poste</b></th>";
		$html.="<th  scope='col' class='rounded-head'><b>Budget Initial</b></th>";
		$html.="<th  scope='col' class='rounded-head-right' ><b>Montant</b></th>";
		$html.="</tr></thead>";
		$html.="<tbody>";
		$tot=0;
		foreach ($this->ListeCredit as $row)
		{
			$html .= "<tr>";
			$html .= "<td style='border-left:0px; text-align:right;'>".$row->ANNEE."</td>";
			$html .= "<td style='border-left:0px;  text-align:left;'>".tronque($row->CPT_LIBELLE,20)."</td>";
			$html .= "<td style='border-left:0px; text-align:left;''>".tronque($row->POSTE_LIBELLE,20)."</td>";
			$html .= "<td nowrap style='border-left:0px; text-align:right;''>".$row->POSTE_BUDGET."</td>";
			$html .= "<td nowrap style='border-left:0px; text-align:right;''>".$row->POSTE_TOTAL."</td>";
			$html.="</tr>";
			$totalligne=floatval(str_replace(array(" ",","),array("","."),$row->POSTE_TOTAL??""));
			$tot=$tot+$totalligne;
		}
		$html.="</tbody>";	
		$html.="<tfoot><tr>";
		$html.="<th  scope='col' colspan=4 class='rounded-foot-left' style='text-align:left;'><b>Total Postes de recette</b></th>";
		$html.="<th  scope='col' class='rounded-foot-right' style='text-align:right;' ><b>".CurrencyString($tot)."</b></th>";
		$html.="</tr></tfoot>";
		$html.="</table>";
		$html .= "</div>";
	}
	
	if (count($this->ListeDebit)>0)
	{
		$html .= "<div class='flex-content-repart'>";
		$html .= "<H1>Postes de dépenses</h1>";
		$html.=$this->ChartDebit;
		$html .= "<br><table class='tsc_tables style='width:500px;'>";	
		$html.="<thead><tr>";
		$html.="<th  scope='col' class='rounded-head-left' ><b>Année</b></th>";
		$html.="<th  scope='col' class='rounded-head'><b>Compte</b></th>";	
		$html.="<th  scope='col' class='rounded-head'><b>Poste</b></th>";
		$html.="<th  scope='col' class='rounded-head'><b>Budget</b></th>";
		$html.="<th  scope='col' class='rounded-head-right' ><b>Montant</b></th>";
		$html.="</tr></thead>";
		$html.="<tbody>";
		$tot=0;
		foreach ($this->ListeDebit as $row)
		{
			$html .= "<tr>";
			$html .= "<td style='border-left:0px; text-align:right;'>".$row->ANNEE."</td>";
			$html .= "<td style='border-left:0px;  text-align:left;'>".tronque($row->CPT_LIBELLE,20)."</td>";
			$html .= "<td style='border-left:0px; text-align:left;''>".tronque($row->POSTE_LIBELLE,20)."</td>";
			$html .= "<td nowrap style='border-left:0px; text-align:right;''>".$row->POSTE_BUDGET."</td>";
			$html .= "<td nowrap style='border-left:0px; text-align:right;''>".$row->POSTE_TOTAL."</td>";
			$html.="</tr>";
			$totalligne=floatval(str_replace(array(" ",","),array("","."),$row->POSTE_TOTAL??""));
			$tot=$tot+$totalligne;
		}
		$html.="</tbody>";	
		$html.="<tfoot><tr>";
		$html.="<th  scope='col' colspan=4 class='rounded-foot-left' style='text-align:left;'><b>Total Postes de dépense</b></th>";
		$html.="<th  scope='col' class='rounded-foot-right' style='text-align:right;' ><b>".CurrencyString($tot)."</b></th>";
		$html.="</tr></tfoot>";
		$html.="</table>";
		$html .= "</div>";
	}
	$html .= "</div>";
	$html.=ShowMessage($this->message);
    return $html; 
}
}

$page = new PageRepart(0); 
$page->WritePAGE ();