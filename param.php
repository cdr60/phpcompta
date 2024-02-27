<?php
date_default_timezone_set("Europe/Paris");
ini_set("default_charset","UTF-8");
$Parametres=new stdclass();
$Parametres->NomDuSite="COMPTA WEB - CDII";
$Parametres->site="CDII - Compta Web";
$Parametres->chemin_photos="./photo/";
$Parametres->CheminLog="./logs/";
$Parametres->CheminTemp="./temp/";
$Parametres->CheminImages="./img/";
$Parametres->index_image=$Parametres->CheminImages."logo.jpg";
$Parametres->index_image_width="300";
?>
