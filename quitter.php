<?php
// Main program
session_start ();
$etat = "";
session_unset();
session_destroy();
header ("Location: index.php");
?>