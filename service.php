<?php
// Main program
//$NO_COMPRESSION = TRUE;
require_once ("db.php");
require_once ("lib.php");
ini_set('display_errors', 0);

$ECR=new stdclass();
$ECR->ID=GetVariableFrom($_POST,"ID","");
$ECR->EMAIL=GetVariableFrom($_POST,"EMAIL","");


if ($ECR->ID=="")
{
	header("HTTP/1.0 400 Bad Request");
	return;
}
if ($ECR->EMAIL=="")
{
	header("HTTP/1.0 401 Bad Request");
	return;
}


$err="";	
$Donnees=new Donnees();
if (is_string($Donnees)) if ($Donnees!="") { header("HTTP/1.0 403 Forbidden"); return; }

$ChkAdh = $Donnees->GetUserByEmail($ECR->EMAIL);
if ($ChkAdh->CR!="0") 
{
	header("HTTP/1.0 403 Forbidden");
	$Donnees->Close();
	return;
}
if ($ChkAdh->USERMODIFYING=="") 
{
	header("HTTP/1.0 404 Forbidden");
	$Donnees->Close();
	return;
}
$ECR->COPEMAJ=$ChkAdh->USERMODIFYING;

$r=$Donnees->PointeDepointe($ECR);
if ($r->CR!="0") $err=$r->MSG;
error_log($r->MSG,0);

$Donnees->Close();
if ($err!="") {  header("HTTP/1.0 405 Bad Request"); }
else { echo($r->CR); return; }
?>