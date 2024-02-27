<?php 

$NO_COMPRESSION=true;

// Main program
require_once ( "lib.php" );
require_once ( "param.php" );
require_once ( "db.php" );
require_once ( "look.php" );

class Login extends PageWeb {
    var $Titre = "Restricted Access";
    var $Message;

    function __construct( $etat, $message )
    {
        $this->Message = $message;
        parent::__construct(PAGE_LOGIN, true );
		setcookie("testcookie", "testcookie");
    } 


    function TagBODY()
    {
        global $Parametres;
		$fm_remember_me=GetVariableFrom($_COOKIE,"fm_remember_me","");
		$fm_Utilisateur="";
		$fm_Mdp="";
		if ($fm_remember_me!="")
		{
			$fm_Utilisateur=GetVariableFrom($_COOKIE,"fm_Utilisateur","");
			$fm_Mdp=GetVariableFrom($_COOKIE,"fm_Mdp","");
		}
		else
		{
			$fm_Utilisateur=GetVariableFrom($_POST,"fm_Utilisateur","");
			$fm_Mdp=GetVariableFrom($_POST,"fm_Mdp","");
		}
		$html ="<body>\r\n";
		//Mettre le id nav pour ne pas faire planter le code java du menu sur la page d'index
        $html .= "<div id='nav'></div><FORM METHOD=POST >\n";
        $html .= "<table align='center' width='500px'><tr valign='top'><td align='center' colspan='2'><h1>".$Parametres->NomDuSite."</h1></td></tr>";
		$html .= "<tr><td align='left' style='width:180px;'>&nbsp;</td>";
		$html .= "<td align='center'><img src='".$Parametres->index_image."' width='".$Parametres->index_image_width."px'></td></tr>";
		$html .= "<tr height='10px'><td colspan='2'></td></tr>";
		$html .= "</table><table align='center' style='width:500px;'>";

        $html .= "<tr><td align='left' class='logintext' style='width:180px;'>Email</td><td>";
		$html .= "<INPUT TYPE=TEXT NAME=fm_Utilisateur class='logintext' style='width:305px;' maxlength='256' value=\"".$fm_Utilisateur."\" autocomplete='new-password'></td></td></tr>";
		$html .= "<tr><td align='left' class='logintext'>Mot de passe</td><td>";
		$html .= "<INPUT TYPE=PASSWORD NAME=fm_Mdp class='logintext' style='width:305px;' maxlength='32'  value=\"".$fm_Mdp."\" autocomplete='new-password'></td></td></tr>";
		$html .= "<tr><td colspan=2><br /></td></tr>";
		
		
		$html .= "<tr><td><br></td><td>";
		$html .= "<INPUT TYPE=SUBMIT NAME=fm_submit VALUE='Connexion'   class='logintext' style='width:305px;'><br>";
		$html .= "<tr><td></td>";
		$html .= "<td class='logintext'><INPUT TYPE=checkbox NAME='fm_remember_me' style='transform: scale(1.7); -webkit-transform: scale(1.7); ' ".($fm_remember_me!=""?"checked":"").">&nbsp;Enregistrer</td></tr>";
	    $html .= "<tr><td><br></td><td></td><br><br></tr>";
		$html .= "</table></form><br><br><br><br>";
        if ( $this->Message != "" ) 
            $html .= "<script>alert(\"".str_replace("<br>","\\n",$this->Message)."\");</script>";
		$html .="</body>";
        return $html;
    } 
}

session_start();
global $Parametres;
$Erreur = "";
$password = "";
$user = "";
$etat="";	
$message="";
//vider le cache
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");


$user_info = CreateUser();
$_SESSION["user_info"]=$user_info;
if (GetVariableFrom($_POST,'fm_Utilisateur')!= "") 
{
	CreatePath($Parametres->CheminLog);
	CreatePath($Parametres->CheminTemp);
    if (isset($_COOKIE["posttimer"]))
	{
		$diff=time() - $_COOKIE["posttimer"];
		if ($diff<=1)
		{
		
			$Erreur="ON webbrowser, double-clic is forbidden !";
		}
		else $Erreur="";
		setcookie("posttimer",time());
	}
	if ($Erreur=="")
	{
	setcookie("posttimer",time());
	if ($_POST['fm_Utilisateur']!=(isset($user_info->CODE_ADH)?$user_info->CODE_ADH:""))	
	    $etat="";
    if ( $etat != "loged" && $_POST['fm_Utilisateur'] != "" )
	{
        if ( $_POST['fm_Utilisateur'] == "" )
             $Erreur .= "Email is required<br>";
        if ( GetVariableFrom($_POST,'fm_Mdp', '') == "" )
             $Erreur .= "Password is required<br>";
		if (GetVariableFrom ($_COOKIE,"testcookie")=="")
			{
             $Erreur .= "You have to accept cookies from this site";
			}
        $user = $_POST['fm_Utilisateur'];
        $password = GetVariableFrom($_POST,'fm_Mdp', '');
		CreatePath($Parametres->CheminLog);
        
        tick_stopwatch ( "Connexion base" );
		$datas = new Donnees();

		if ($Erreur == "" )
		{
           $ChkAdh = $datas->CheckLogin( $user, $password );

		   tick_stopwatch ( "Check login" );
		   if ($ChkAdh->CR!=0) $Erreur=$ChkAdh->MSG;
		}

        if ( $Erreur == "" ) 
			{
			    $user_info = $ChkAdh;
                tick_stopwatch ( "GetUser" );
                $etat = "loged";
				
                $_SESSION['user_info'] = $user_info;
                $_SESSION['comptaweb_etat'] = $etat;
				//pour le popup
				setcookie("comptaweb_etat",$etat);
				$fm_remember_me=GetVariableFrom($_POST,"fm_remember_me","");
				$fm_Utilisateur=GetVariableFrom($_POST,"fm_Utilisateur","");
				$fm_Mdp=GetVariableFrom($_POST,"fm_Mdp","");
				
				if ($fm_remember_me!="")
				{
					setcookie("fm_Mdp", $fm_Mdp,time()+60*60*24*30);				
					setcookie("fm_Utilisateur", $fm_Utilisateur,time()+60*60*24*30);				
					setcookie("fm_remember_me", $fm_remember_me,time()+60*60*24*30);	
				}
				else
				{
					setcookie("fm_Mdp", "");				
					setcookie("fm_Utilisateur", "");				
					setcookie("fm_remember_me", "");				
				}
			} 
		else
			{
			$_POST['fm_Mdp']="";
			$_POST['fm_Utilisateur']="";
			setcookie("comptaweb_etat", "");
			$etat="";
			}
        $datas->Close();
        tick_stopwatch ( "Log ..." );
		} 
}
}
if (!isset($_SERVER["HTTP_REFERER"])) $_SERVER["HTTP_REFERER"]="";
if (( $etat == "loged" ) and ($_SERVER["HTTP_REFERER"]!=""))
{
		// Ca marche !
		setcookie("user",$user_info->IDUSER);		
		$url="accueil.php";
        RedirigeVers ( $url );
        exit( 0 );
} 

$clientos=GetClientOS();
if (($clientos=="WINXP") or ($clientos=="WIN98") or ($clientos=="WIN2K") or ($clientos=="WIN2000") or ($clientos=="WIN2003") or ($clientos=="WIN2K3") or ($clientos=="WIN95"))
{
	RedirigeVers ( "os-not-supported.php" );
    exit( 0 );
}		
$page = new Login ( FALSE, $Erreur) ;
$page->WritePAGE ();
?>
