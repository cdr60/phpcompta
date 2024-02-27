<?php
require_once ("param.php");
require_once ("lib.php");
session_start ();

$info_user = GetVariableFrom ($_SESSION,'info_user');
$erreur="";
if (isset($_REQUEST['erreur'])) 
$erreur=$_REQUEST['erreur'];

$etat = "";
$html="<html><head><title>La page d'erreur</title><body><BIG><BIG>Une erreur s'est produite !<br></BIG>";
switch (GetVariableFrom($_GET,'db'))
{
    case "db" : 
		$html.="La base de données est inaccessible<br>".GetVariableFrom($_GET,'message')."<br>";
        break;
    case "user" : 
        $html.="Utilisateur inconnu";
        break;
}
if (isset($_SESSION)) unset($_SESSION);
if (isset($_COOKIE)) unset($_COOKIE);
$html.="<p></BIG><br>Veuillez ré-essayer dans quelques minutes : <a href='index.php'>Vous connecter</a><br><br><br></body></html>";
echo $html;
?>