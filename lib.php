<?php
require_once ("param.php");

function MkMois()
{
   return array("","Janv","Févr","Mars","Avr","Mai","Juin","Juil","Août","Sept","Oct","Nov","Déc");
}		




function CreatePath($path) 
{
	$ds          = DIRECTORY_SEPARATOR;  
	$thepath=$path;
	$endpath=substr($path, strlen($thepath)-1,1);
	if ($endpath==$ds) $thepath=substr($path, 0, strlen($thepath)-1);
    if (is_dir($thepath)) return true;
    $prev_path = substr($thepath, 0, strrpos($thepath, $ds, -2) + 1 );
    $return = createPath($prev_path);
    return ($return && is_writable($prev_path)) ? mkdir($thepath) : false;
}


function InitUser($post=0)
{
    $result=new stdclass();
	
	if ($post==0)
	{
		$result->IDUSER="";
		$result->NOM="";
		$result->PRENOM="";
		$result->EMAIL="";
		$result->PASS="";
		$result->CONFIRMPASS="";
		$result->LASTCONNECT="";
		$result->ISADMIN="N";
		$result->TRESORIER="N";
	}
	else
	{
		$result->IDUSER=GetVariableFrom($_POST,"IDUSER","");
		$result->NOM=GetVariableFrom($_POST,"NOM","");
		$result->PRENOM=GetVariableFrom($_POST,"PRENOM","");		
		$result->EMAIL=GetVariableFrom($_POST,"EMAIL","");
		$result->PASS=GetVariableFrom($_POST,"PASS","");
		$result->CONFIRMPASS=GetVariableFrom($_POST,"CONFIRMPASS","");
		$result->LASTCONNECT=GetVariableFrom($_POST,"LASTCONNECT","");
		$result->ISADMIN=GetVariableFrom($_POST,"ISADMIN","N");
		$result->TRESORIER=GetVariableFrom($_POST,"TRESORIER","N");
	}
	return $result;
}

function MkLinkEditUser($row)
{
	$a =$row->IDUSER."&&:&&".$row->NOM."&&:&&".$row->PRENOM."&&:&&".$row->EMAIL."&&:&&";
	$a.=$row->LASTCONNECT."&&:&&".$row->ISADMIN."&&:&&".$row->TRESORIER;
	return $a;
}

function GetEditUserByLink($link)
{
	$t=explode("&&:&&",decode_mdp($link));
	$t=explode("&&:&&",decode_mdp($link));
	$result=InitUser(0);
	$result->IDUSER=(isset($t[0])?$t[0]:"");
	$result->NOM=(isset($t[1])?$t[1]:"");
	$result->PRENOM=(isset($t[2])?$t[2]:"");
	$result->EMAIL=(isset($t[3])?$t[3]:"");
	$result->LASTCONNECT=(isset($t[4])?$t[4]:"");
	$result->ISADMIN=(isset($t[5])?$t[5]:"");
	$result->TRESORIER=(isset($t[6])?$t[6]:"");
	return $result;
}

/**********************************************************************/

function Is_Entier($val,$nul,$neg)
// $nul indique si $val peut prendre la valeur 0
// $neg indique si $val peut $etre négatif
{
	$result=is_numeric($val);
	if (!$result) return $result;

	$result=(!(($val==0) and (!$nul)));
	if (!$result) return $result;
		

	$result=(!(($val < 0) and (!$neg)));
	if (!$result) return $result;
	
	
	$result=(round($val)==$val);
	
	return $result;
}



function SQLInteger($String,$nullable)
{
	$String=($String ?? "");
	if ((trim($String)=="") and ($nullable==TRUE))
	{
		$resultat="null";
	}
	elseif ((trim($String)=="") and ($nullable==FALSE))
	{
		$resultat="0";
	}
	Else
		$resultat=$String;
    return $resultat;
}


function SQLDate($String)
{
	if ($String=="") 
		$resultat="null";
	Else
	{
		$d=explode("/",$String);
		$resultat="'".$d[2]."-".$d[1]."-".$d[0]."'";
	}
	return $resultat;
}

function SQLTimeStamp($String)
{
	$tbts=array("00","00","00");
	if ($String=="") 
		$resultat="null";
	else
	{
		$tb=explode(" ",str_replace(" à "," ",trim($String)));
		$d=explode("/",$tb[0]);
		if (count($d)!=3) $resultat="null";
		$dt=$d[2]."-".$d[1]."-".$d[0];
		if (isset($tb[1])) $tbts=explode(":",$tb[1]);
		if (count($tbts)!=3) $tbts=array("00","00","00");
		$ts=$tbts[0].":".$tbts[1].":".$tbts[2];
		$resultat="'".$dt." ".$ts."'";
	}
	return $resultat;
}

function SQLBool($String,$nullable)
{
	if ((trim($String)=="") and ($nullable==TRUE))
	{
		$resultat="null";
	}
	elseif ((trim($String)=="") and ($nullable==FALSE))
	{
		$resultat="0";
	}
	elseif ((trim($String)=="on") or (trim($String)=="1"))
	{
		$resultat="1";
	}
	else $resultat=$String;
    return $resultat;
}



function SQLCurrency($String,$nullable)
{
	if ((strval($String)=="") and ($nullable==TRUE))
	{
		$resultat="null";
	}
	elseif ((strval($String)=="") and ($nullable==FALSE))
	{
		$resultat="0";
	}
	Else
		$resultat=str_replace(array(","," "),array(".",""),$String);
    return $resultat;
}


//S'utilise avec dans le head : setInputFilter.js
function MkFilterInteger($id)
{
	$html="";
    $html.="<script type='text/javascript' language='JavaScript' >\r\n";
	$html.="setInputFilter(document.getElementById('".$id."'), function(value) {return /^-?\d*$/.test(value); });\r\n";
	$html.="</script>";
	return $html;
}

//S'utilise avec dans le head : setInputFilter.js
//nbdec : nombre de décimale
function MkFilterFloat($id,$nbdec=2)
{
	$html="";
	if (!is_int($nbdec)) $nbdec=2;
    $html.="<script type='text/javascript' language='JavaScript' >\r\n";
	$html.="setInputFilter(document.getElementById('".$id."'), function(value) {return /^-?\d*[.,]?\d{0,".$nbdec."}$/.test(value); });\r\n";
	$html.="</script>";
	return $html;
}


function CurrencyString($curr,$nullable=False)
{
	$curr=str_replace(array(" ",","),array("","."),$curr??"");
	if (!is_numeric($curr)) $curr="";
	if ((trim($curr)=="") and ($nullable==True)) return "";
	elseif ((trim($curr)=="") and ($nullable==False)) return "0.00";
	$curr=round($curr,2);
	if ($curr=="-0") $curr="0";
	$v=explode(".",$curr);
	if (count($v)== 0) $v[0]=FALSE;
	if (count($v) < 2) $v[1]=FALSE;
	if (!$v[0]) $v[0]="0";
	if (!$v[1]) $v[1]="00";
	if (strlen($v[1])<2) $v[1].="0";
	if (strlen($v[1])<2) $v[1].="0";
    if (strlen($v[1])>2) $v[1]=substr($v[1],0,2);
	$res=$v[0].".".$v[1];
	$res=number_format($res, 2, '.', ' ');
	return $res;
}



function PourcString($pourc)
{
	if (trim($pourc)=="") return "";
	$v=explode(".",$pourc);
	if (count($v)== 0) $v[0]=FALSE;
	if (count($v) < 2) $v[1]=FALSE;
	if (!$v[0]) $v[0]="0";
	if (!$v[1]) $v[1]="00";
	if (strlen($v[1])<2) $v[1].="0";
	if (strlen($v[1])<2) $v[1].="0";
	$res=$v[0].".".$v[1]." %";
	return $res;
}

function DBTimestamp_to_WebTimestamp($int_timestamp="")
{
  if ($int_timestamp=="") 
	{
	  return "";
	}
  $dt = explode (" ", $int_timestamp);
  if ((count($dt)==0) or (count($dt)>2)) return "";

  if (count($dt)==1)
	{
	$d = explode ("-", $dt[0]);
	$t = array("00","00","00");
	}
   elseif (count($dt)==2)
	{
	$d = explode ("-", $dt[0]);
	$t = explode (":", $dt[1]);
	if (count($t)<>3)
		$t = array("00","00","00");
	}
	if (count($d)<>3) return "";
	$result=$d[2]."/".$d[1]."/".$d[0];
	$hms=$t[0].":".$t[1].":".$t[2];
	if ($hms!="00:00:00") $result.=" à ".$hms;
    return $result;
}



function SQLString($String,$Upper)
{
	$String =str_replace(chr(160),chr(32),$String);
    $String = trim($String);
	if ($String=="") return "null";
    $String = strip_tags ($String);
    $String = html_entity_decode($String,ENT_QUOTES);
	$String = str_replace ("\"", "'", $String);
	$String = str_replace ("\"", "'", $String);
	$String = str_replace ("\'", "'", $String);
	$String = str_replace ("&#8216;", "'", $String);
    $String = str_replace ("&#8217;", "'", $String);
	$String = str_replace ("\\", "", $String);
    $String = str_replace ("''", "'", $String);
    $String = str_replace ("'", "''", $String);
    $String = rtrim($String);
	If ($Upper==TRUE)
		$String=strtoupper($String);
    return "'".$String."'";
}


function GetVariableFrom ($from,$name,$default = '') 
{
	if (!is_array($from))  $from=array();
	elseif (!isset($from[$name])) return $default;
	else return $from[$name];
}

function _print_r ($v,$exit=FALSE)
{
    echo ("<pre>");
    echo ("<pre>");
    print_r ($v);
    echo ("</pre>\n");
    if ($exit)
        exit(0);
}

function date_to_ts($dt)
{
	$result=is_date($dt);
	if ($result==FALSE) return $result;
	$t=explode("/",$dt);
	//limitation par mktime à l'année 2038
	if (intval($t[2].$t[1].$t[0])>=20380101) $result=mktime(0,0,0,01,01,2038);	
	else $result=mktime(0,0,0,$t[1],$t[0],$t[2]);
	return $result;
}

function is_date($dt="",$comp="")
{
	$result=TRUE;
	if ($dt=="")
	{
		$result=FALSE;
		return $result;
	}

	$d=explode("/",$dt);
	if (count($d)!=3)
		$result=FALSE;
	elseif ((!is_numeric($d[0])) or (!is_numeric($d[1])) or (!is_numeric($d[2])))
		$result=FALSE;
	elseif ((strlen($d[0])!=2) or (strlen($d[1])!=2) or (strlen($d[2])!=4)) 
		$result=FALSE;
	elseif ((intval($d[1])<1) or (intval($d[1])>12)) 
		$result=FALSE;
	elseif ((intval($d[0])<1) or (intval($d[0])>31)) 
		$result=FALSE;
	elseif (intval($d[0])>cal_days_in_month(CAL_GREGORIAN, intval($d[1]), intval($d[2]))) 
		$result=FALSE;
	elseif ($comp=="<=today")
	{
		if (mktime(0,0,0,$d[1],$d[0],$d[2]) > mktime(0,0,0,date("m"),date("d"),date("Y")))
			$result=FALSE;
	}
	elseif ($comp==">today")
	{
		if (mktime(0,0,0,$d[1],$d[0],$d[2]) <= mktime(0,0,0,date("m"),date("d"),date("Y")))
			$result=FALSE;
	}	
	return $result;
}	





function str_pray ($data, $functions=0)
{
    ob_start();
    pray ($data, $functions);
    $s = ob_get_contents();
    ob_end_clean();
    return $s;
}

function pray ($data, $functions=0) {
if($functions!=0) { $sf=1; } else { $sf=0 ;} 
if (isset ($data)) {
   if (is_array($data) || is_object($data)) {
       if (@count ($data)) {
           echo "<OL>\n";
		   foreach($data as $key => $value)
			  {
              $type=gettype($value);
               if ($type=="array" || $type == "object") {
                   printf ("<li>(%s) <b>%s</b>:\n",$type, $key);
                  pray ($value,$sf);
               } elseif (preg_match ("@function@i", $type)) {
                   if ($sf) {
                      printf ("<li>(%s) <b>%s</b> </LI>\n",$type, $key, $value);
                 }
               } else {
                   if (!$value) { $value="(none)"; }
                   printf ("<li>(%s) <b>%s</b> = %s</LI>\n",$type, $key, htmlentities($value));
           } }
           echo "</OL>end.\n";
       } else {
           echo "(empty)";
       } } }
} 



function tick_stopwatch($step)
{
   global $stop_watch;
   $stop_watch[$step] = microtime();
}

$stop_watch['Start'] = microtime();

function echo_stopwatch()
{
   global $stop_watch;
    
    echo "\n\n<!--\n";
    echo "Timing ***************************************************\n";
    
    $total_elapsed = 0;
    list($usec, $sec) = explode(" ",$stop_watch['Start']);
    $t_end = ((float)$usec + (float)$sec);
    
    foreach( $stop_watch as $key => $value )
    {
        list($usec, $sec) = explode(" ",$value);
        $t_start = ((float)$usec + (float)$sec);
        
        $elpased = abs($t_end - $t_start);
        $total_elapsed += $elpased;
        
        echo ( str_pad($key, 30, ' ', STR_PAD_LEFT).": ".number_format($elpased,3).' '.number_format($total_elapsed,3))."\n";
        $t_end = $t_start;
    }
    echo "\n";
    echo( str_pad("Elapsed time", 30, ' ', STR_PAD_LEFT).": ".number_format($total_elapsed,3))."\n";
    echo "\n-->";
}
    

function MkTag ($Tag,$Text ="",$Attributes="",$CloseTag=TRUE,$Quote="'")
{
    $html = "<$Tag";
    if (is_string ($Attributes))
    {
        if ($Attributes != "")
           $html .= " $Attributes";
    }
    else
    {
        foreach ($Attributes as $Attribut => $Value)
            $html .= " $Attribut=".$Quote.$Value.$Quote;  
    }
    
    $html .= ">";
    
    $html .= $Text;   
    if ($CloseTag)    
        $html .= "</$Tag>";
    return $html;
}





function RedirigeVers ($url,$from="")
{
    session_write_close();
    header ("Location: $url");
    header( 'refresh: 0; url='.$url );
    setcookie ("REQUESTED_URL",rawurlencode($from));
    echo ("<html><body>La page est déplacée!<p><a href='$url'>Cliquez ici</a></body></html>");
    exit (0);
}

function PageErreur($db,$Message="")
{
    session_write_close();	
	$url="erreur.php?db=".$db."&message=".decode_mdp($Message);
	header ("Location: ".$url);
	header( "'refresh: 0; url=".$url."'");
    exit(0);
}

		
  

function truncate($str, $len, $el = '...')
{
   if (strlen($str) > $len) {
     $xl = strlen($el);
     if ($len < $xl) {
         return substr($str, 0, $len);
     }
     $str = substr($str, 0, $len-$xl);
     $spc = strrpos($str, ' ');
     if ($spc > 0) {
         $str = substr($str, 0, $spc);
     }
     return $str . $el;
   }
   return $str;
}


function BaseToHtml ($s) 
{
	$s = htmlentities($s,ENT_QUOTES,"UTF-8");
    $s = nl2br ($s);
    $s = addslashes ($s);
    return $s;
}

function HtmlToBase ($s) 
{
	//striptags supprime toutes les balises html et php de s
    $s = strip_tags ($s);
    $s = html_entity_decode($s,ENT_QUOTES);
	//Remplace &#8217; par '
    $s = str_replace ("&#8217;", "'", $s);
	//Remplace &#8216; par '
    $s = str_replace ("&#8216;", "'", $s);
	//Remplace " par '
    $s = str_replace ("\"", "'", $s);
	//Remplace \' par '
    $s = str_replace ("\'", "'", $s);
    $s = str_replace ("\\", "", $s);
	//Remplace ' par ''
    $s = str_replace ("''", "'", $s);
    $s = rtrim($s);
   return $s;
}


    
function ResizeMyPhoto($inwidth="0",$inheight="0",$maxwidth="0",$maxheight="0")
{
	$width=$inwidth;
	$height=$inheight;
	$result=new stdclass();
	if (($maxwidth!="0") and ($maxheight!="0") and ($inwidth!="0") and ($inheight!="0"))
	{
		if ($width>$maxwidth)
		{
			$height=intval($height/$width*$maxwidth);
			$width=$maxwidth;
		}
		if ($height>$maxheight)
		{
			$width=intval($width/$height*$maxheight);
			$height=$maxheight;
		}
	}
	$result->width=$width;
	$result->height=$height;
	return $result;
}

function MakeDayOfWeek($zeroday="sunday")
{
	if ($zeroday=="sunday")
	{
		$tab[0]="Dimanche ";
		$tab[1]="Lundi ";
		$tab[2]="Mardi ";
		$tab[3]="Mercredi ";
		$tab[4]="Jeudi ";
		$tab[5]="Vendredi ";
		$tab[6]="Samedi ";
	}
	else
	{
		$tab[0]="Lundi ";
		$tab[1]="Mardi ";
		$tab[2]="Mercredi ";
		$tab[3]="Jeudi ";
		$tab[4]="Vendredi ";
		$tab[5]="Samedi ";
		$tab[6]="Dimanche ";		
	}
		
	return $tab;
}

function ts_to_date($ts,$typ)
{

	$dayofweek=MakeDayOfWeek();
	$result=date("d/m/Y",$ts);
	if ($typ==1) 
		$result=$dayofweek[date("w",$ts)].$result;
	elseif ($typ==2) 
		$result=substr($dayofweek[date("w",$ts)],0,3)." ".$result;
	return $result;
}




function FloatString($curr,$precision=2)
{
	$curr=round($curr,$precision);
	if ($curr=="-0") $curr="0";
	$v=explode(".",$curr);
	if (count($v)== 0) $v[0]=FALSE;
	if (count($v) < 2) $v[1]=FALSE;
	if (!$v[0]) $v[0]="0";
	if (!$v[1]) for ($i=0;$i<$precision;$i++) $v[1].="0";
	for ($i=strlen($v[1]);$i<$precision;$i++) $v[1].="0";
    if (strlen($v[1])>$precision) $v[1]=substr($v[1],0,$precision);
	$res=$v[0].".".$v[1];
	return $res;
}


function iso_spec_chars($chaine)
{
   //remplacement saut de ligne
   $chaine = preg_replace("/\r\n/", "&#10;", $chaine);
   for ($i = 161; $i < 255; $i++)
   $chaine = preg_replace("/".chr($i)."/", "&#".$i.";", $chaine);
   return $chaine;
}

function CreateUser () 
{
	$user_info=new stdclass();
	$user_info->IDUSER=0;
	$user_info->PRENOM="";
	$user_info->NOM="";
	$user_info->EMAIL="";
	$user_info->USERMODIFYING="";
	$user_info->DCRE="";
	$user_info->COPECRE="";
	$user_info->DMAJ="";
	$user_info->COPEMAJ="";
	$user_info->PASS="";
	$user_info->LASTCONNECT="";
	$user_info->ISADMIN="N";		
	return $user_info;                
}
	
function Is_Currency($val,$nul,$neg)
// $nul indique si $val peut prendre la valeur 0
// $neg indique si $val peut $etre négatif
// si espace de séparateurs de milliers, je les retire
{
	$val=str_replace(array(","," "),array(".",""),$val);
	$result=is_numeric($val);
	if (!$result) return $result;

	$result=(!(($val==0) and (!$nul)));
	if (!$result) return $result;

	$result=(!(($val < 0) and (!$neg)));
	if (!$result) return $result;

	$result=(round($val,8)==$val);
	return $result;
}

function encode_mdp($a="")
{
   $result="";
   $i=strlen($a)-1;
   while ($i >= 0)
      {
         $n=ord(substr($a,$i,1));
         $s=strval($n);
         if ($n < 10)  
            $result.="1".$s;
         elseif ($n < 100)
            $result.="2".substr($s,1,1).substr($s,0,1);
         else
            $result.="3".substr($s,2,1).substr($s,1,1).substr($s,0,1);
		 $i-=1;
      }
   return $result;
}



function decode_mdp($a="")
{
  $i=0;
  $result='';
  While ($i<strlen($a))
     {
        $l=intval(substr($a,$i,1));
        if (($l < 1) or ($l > 3))
           return $result;
        $i++;
        if ($l == 1) 
           $s=substr($a,$i,$l);
        elseif ($l == 2) 
           $s=substr($a,$i+1,1).substr($a,$i,1);
        elseif ($l == 3) 
           $s=substr($a,$i+2,1).substr($a,$i+1,1).substr($a,$i,1);
        $n=intval($s);
        if (($n < 0) or ($n > 255)) 
           return $result;
        $result=chr($n).$result;
        $i=$i+$l;
     }
  return $result;
}

//tri sur col total desc
function cmp_total_desc($a, $b)
{
	return -1*strcmp($a->TOTAL, $b->TOTAL);
}

function isodatetoFR($day)
{
	if ($day=="") return "";
	$m=sprintf("%02d",intval(substr($day,6,2)));
	$d=sprintf("%02d",intval(substr($day,8,2)));
	$y=sprintf("%02d",intval(substr($day,0,4)));
	$result=$d."/".$m."/".$y;
	if (!is_date($result)) return "";
	return $result;
}


function incdaydate($nday,$dt)
{
	$m=intval(substr($dt,3,2));
	$d=intval(substr($dt,0,2));
	$y=intval(substr($dt,6,4));
	$ts=mktime(0,0,0,$m,$d+intval($nday),$y);
	$result=date("d",$ts)."/".date("m",$ts)."/".date("Y",$ts);
	return $result;
}


function incweekdate($nweek,$dt)
{
	$m=intval(substr($dt,3,2));
	$d=intval(substr($dt,0,2));
	$y=intval(substr($dt,6,4));
	$ts=mktime(0,0,0,$m,$d+intval($nweek)*7,$y);
	$result=date("d",$ts)."/".date("m",$ts)."/".date("Y",$ts);
	return $result;
}

function incmonthdate($nmonth,$dt)
{
	$m=intval(substr($dt,3,2));
	$d=intval(substr($dt,0,2));
	$y=intval(substr($dt,6,4));
	$ts=mktime(0,0,0,$m+intval($nmonth),$d,$y);
	$result=date("d",$ts)."/".date("m",$ts)."/".date("Y",$ts);
	return $result;
}


function DiffDate($datedeb,$datefin)
{
  $d1 = DBDate_to_unixtimestamp($datedeb,"d/m/Y");
  $d2 = DBDate_to_unixtimestamp($datefin,"d/m/Y");
  if (($d1=="") or ($d2=="")) return "";
  return floatval(($d2-$d1)/(60*60*24));
}

function DBDate_to_unixtimestamp($int_timestamp="",$format="d/m/Y")
{
  if ($int_timestamp=="") return "";
  if ($format=="d/m/Y")
	{
	  $d = explode ("/", $int_timestamp);
	  if (count($d)!=3) return "";
	  $d[2]=substr($d[2],0,4);
      return mktime (0,0,0,$d[1], $d[0], $d[2]);
	}
  if ($format=="Y-m-d")
	{
	  $d = explode ("-", $int_timestamp);
	  if (count($d)!=3) return "";
	  $d[2]=substr($d[2],0,2);
      return mktime (0,0,0,$d[1], $d[2], $d[0]);
	}
}


//is array est un tableau d'objects
function search_by_key($array, $key, $value) 
{
   $result = -1;
   $i=0;
   while ($i<count($array) and ($result<0))
   {
      if($array[$i]->$key == $value) $result=$i;
	  $i++;
   }
   return $result;
}

function dos2unix($filename)
{
	$file = file_get_contents($filename);
	$file = str_replace("\r", "", $file);
	$file = str_replace(";;", ";NULL;", $file);
	file_put_contents($filename, $file);
}



function ShowMessage($msg,$onload=TRUE)
{
	$result="";	
	$msg=trim(strip_tags(str_replace(array("\"","<br>","\n","\r"),array("'","\\n","\\n",""),$msg??"")));
	if ($msg=="") return $result="";
	$result="<script>";
	if ($onload===TRUE) $result.="window.onload = function() {";
	$result.="alert(\"".$msg."\");";
	if ($onload===TRUE) $result.="} ";
	$result.="</script>";
	return $result;
}


function GetClientOS()
{
	$browser = @get_browser(null, true);
	return strtoupper(isset($browser["platform"])?$browser["platform"]:"");
}

function clean($string) {
   $string = str_replace(array(' ','-'), '_', $string);
   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}


//"d/m/Y" ou "Y-m-d"
function getWeekday($dt,$format="Y-m-d") 
{
	//0 = Dimancche, 1 = Lundi, ...
    return date('w', DBDate_to_unixtimestamp(substr($dt,0,10),$format));
}

//"d/m/Y" ou "Y-m-d"
function getMonthday($dt,$format="Y-m-d") 
{
	//0 = Dimancche, 1 = Lundi, ...
    return date('d', DBDate_to_unixtimestamp(substr($dt,0,10),$format));
}

//"d/m/Y" ou "Y-m-d"
function getYearday($dt,$format="Y-m-d") 
{
	//0 = Dimancche, 1 = Lundi, ...
    return date('y', DBDate_to_unixtimestamp(substr($dt,0,10),$format));
}


function search_in_array_of_object($val,$colname,$table)
{
    $i=0;
    $found=false;
    while ((!$found) and ($i<count($table)))
    {
         try
         {
            $found=($table[$i]->{$colname}==$val);
         }
         catch (Exception $E)
         {
              $found=false;
         }
         if (!$found)  $i++;
    }
    if (!$found) return -1; else return $i;
}


function tronque($s,$maxl)
{
	if (strlen($s)<=$maxl) return $s;
	return substr($s,0,($maxl-3))."...";
}

?>
