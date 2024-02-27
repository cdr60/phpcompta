<?php
require_once ("param.php");
require_once ("lib.php");

function sql_md5($s) 
{
    return md5($s);
}


class Donnees {
var $db;

function __construct()
{
	$errmsg="";
	date_default_timezone_set("Europe/Paris");
	$dbfile = "./db/comptaweb.db";
	$ok=FALSE;
	foreach(PDO::getAvailableDrivers() as $driver) 
    {
	 $ok=($ok or (strtoupper($driver)=="SQLITE"));
    }  
	if (!$ok)
	{
	   $errmsg="Extension PDO-SQLITE manquante";
	   PageErreur("db", "comptaweb",$errmsg);			   
	 }

	  
	if ($errmsg=="")
	{
		try
		{
			$this->db = new PDO("sqlite:".$dbfile);
			$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES , FALSE);
			$this->db->sqliteCreateFunction('md5', 'sql_md5', 1);
		}
		catch(PDOException $e) 
		{
			$errmsg=$e->getMessage();
			PageErreur("db", "comptaweb",$errmsg);
		}
	}
	if ($errmsg=="") 
	{
	   $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	   $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	}
	return $errmsg;
} 

function Close()
{
	if ($this->db) 
	{
		$inttrans=$this->db->inTransaction();
		if ($inttrans==1) $this->db->commit();
		$this->db=NULL;
	}
}



function execute_query($stmt,$timestampformat="%d/%m/%Y")
{
	$err="";
	try 
	{ 
		$st = @$this->db->prepare($stmt);
	}
	catch(PDOException $e)  
	{
		$err = 'ERREUR PDO dans ' . $e->getFile() . ' L.' . $e->getLine() . ' : ' . $e->getMessage();
	}
	if (!$st) 
	{
		$tber=$this->db->errorInfo();
		$err=(isset($tber[2])?$tber[2]:"");
	}	
	if (($err=="") and ($st))
	{
		try 
		{	
			$st->execute();   
		}
		catch(PDOException $e)  
		{
			$err = 'ERREUR PDO dans ' . $e->getFile() . ' L.' . $e->getLine() . ' : ' . $e->getMessage(); 
		}		
	}
	if (($err=="") and (!$st)) $err = 'ERREUR SQL ';
	if ($err!="") 
	{
		if (strripos($err,"deadlock update conflicts with concurrent")) 
			$err="Transaction déjà lancée ! Merci de ne pas double cliquer !";
		return $err;
	}
	return $st;
}


function GetUser($userid="")
{
	$result=new stdclass();
	$result->CR=0;		
	$result->MSG="";
	if ($userid!="")	{
		$result->IDUSER="";
		$result->PRENOM="";
		$result->NOM="";
		$result->EMAIL="";
		$result->DCRE="";
		$result->COPECRE="";
		$result->DMAJ="";
		$result->COPEMAJ="";
		$result->PASS="";
		$result->USERMODIFYING="";
		$result->ISADMIN="N";
		$result->TRESORIER="N";
		
	}
	else $result->DATA=array();

	$where="";
	$sql = "SELECT IDUSER,PRENOM,NOM,EMAIL,ISADMIN,TRESORIER, DCRE,COPECRE, ";
    $sql.= "       DMAJ, COPEMAJ,PASS,LASTCONNECT ";
    $sql.= "       FROM TBUSER ";
	if ($userid!="") $where.=($where==""?" WHERE ":" AND ")."IDUSER=".SQLInteger($userid,FALSE);
	$sql.= $where;
	$sql.=";";
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	if ($userid!="")
	{
		if ($row = $sth->fetchObject())
		{
			$result->IDUSER=$row->IDUSER;
			$result->PRENOM=$row->PRENOM;
			$result->NOM=$row->NOM;
			$result->EMAIL=$row->EMAIL;
			$result->ISADMIN=$row->ISADMIN;
			$result->TRESORIER=$row->TRESORIER;
			$result->DCRE=$row->DCRE;
			$result->PASS=$row->PASS;
			$result->COPECRE=$row->COPECRE;
			$result->DMAJ=$row->DMAJ;
			$result->COPEMAJ=$row->COPEMAJ;
			$result->LASTCONNECT=$row->LASTCONNECT;
			$result->USERMODIFYING=(trim($result->PRENOM." ".$result->NOM)!=""?$row->PRENOM." ".$result->NOM:substr($user,0,65));
		}
		else 
		{
			$result->CR=-2;
			$result->MSG="Incorrect userid";
		}
	}
	else
	{
		$n=0;
		while ($row = $sth->fetchObject())
		{
			if (!isset($result->DATA[$n])) $result->DATA[$n]=new stdclass();
			$result->DATA[$n]->IDUSER=$row->IDUSER;
			$result->DATA[$n]->PRENOM=$row->PRENOM;
			$result->DATA[$n]->NOM=$row->NOM;
			$result->DATA[$n]->EMAIL=$row->EMAIL;
			$result->DATA[$n]->ISADMIN=$row->ISADMIN;
			$result->DATA[$n]->TRESORIER=$row->TRESORIER;
			$result->DATA[$n]->PASS=$row->PASS;
			$result->DATA[$n]->DCRE=$row->DCRE;
			$result->DATA[$n]->COPECRE=$row->COPECRE;
			$result->DATA[$n]->DMAJ=$row->DMAJ;
			$result->DATA[$n]->COPEMAJ=$row->COPEMAJ;
			$result->DATA[$n]->LASTCONNECT=$row->LASTCONNECT;
			$result->DATA[$n]->USERMODIFYING=(trim($result->DATA[$n]->PRENOM." ".$result->DATA[$n]->NOM)!=""?$result->DATA[$n]->PRENOM." ".$result->DATA[$n]->NOM:substr($user,0,65));
			$n++;
		}			
	}
	return $result;
}


function GetUserByEmail($email="")
{
	$result=new stdclass();
	$result->CR=0;		
	$result->MSG="";
	$result->PRENOM="";
	$result->NOM="";
	$result->EMAIL="";
	$result->DCRE="";
	$result->COPECRE="";
	$result->DMAJ="";
	$result->COPEMAJ="";
	$result->PASS="";
	$result->USERMODIFYING="";
	$result->ISADMIN="N";
	$result->TRESORIER="N";

	$where="";
	$sql = "SELECT IDUSER,PRENOM,NOM,EMAIL,ISADMIN,TRESORIER, DCRE,COPECRE, ";
    $sql.= "       DMAJ, COPEMAJ,PASS,LASTCONNECT ";
    $sql.= "       FROM TBUSER ";
	if ($email!="") $where.=($where==""?" WHERE ":" AND ")."EMAIL=".SQLString($email,FALSE);
	$sql.= $where;
	$sql.=";";
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	if ($row = $sth->fetchObject())
	{
		$result->IDUSER=$row->IDUSER;
		$result->PRENOM=$row->PRENOM;
		$result->NOM=$row->NOM;
		$result->EMAIL=$row->EMAIL;
		$result->ISADMIN=$row->ISADMIN;
		$result->TRESORIER=$row->TRESORIER;
		$result->DCRE=$row->DCRE;
		$result->PASS=$row->PASS;
		$result->COPECRE=$row->COPECRE;
		$result->DMAJ=$row->DMAJ;
		$result->COPEMAJ=$row->COPEMAJ;
		$result->LASTCONNECT=$row->LASTCONNECT;
		$result->USERMODIFYING=(trim($result->PRENOM." ".$result->NOM)!=""?$row->PRENOM." ".$result->NOM:substr($user,0,65));
	}
	else 
	{
		$result->CR=-2;
		$result->MSG="Incorrect userid";
	}
	return $result;
}


function RegistreredNewPassword($useremail,$newpass)
{
	$result=new stdclass();
	$result->MSG="";
	$result->CR=0;
	$sql="UPDATE TBUSER SET PASS=MD5(".SQLString($newpass,FALSE).") WHERE EMAIL=".SQLString($useremail,FALSE).";";
	$stmt = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($stmt))
	{
		$result->CR="-1";
		$result->MSG=$sql."  ".$stmt;
		return $result;
	}
	return $result;
}


function UpdateUserbyUser($user)
{
	$result=new stdclass();
	$result->MSG="";
	$result->CR=0;
	$user->USERMODIFYING=(trim($user->PRENOM." ".$user->NOM)!=""?$user->PRENOM." ".$user->NOM:substr($user,0,65));
	
    //mise à jour profil par user
	$sql="UPDATE TBUSER SET ";
	$sql.="NOM = ".SQLString($user->NOM,FALSE).",";
	$sql.="PRENOM = ".SQLString($user->PRENOM,FALSE).",";
	$sql.="EMAIL = ".SQLString($user->EMAIL,FALSE).", ";
	$sql.="COPEMAJ = ".SQLString($user->USERMODIFYING,FALSE).", ";
	$sql.="DMAJ = CURRENT_TIMESTAMP ";
	$sql.="WHERE IDUSER=".SQLInteger($user->IDUSER,FALSE).";";
	$stmt = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($stmt))
	{
		$result->CR="-1";
		$result->MSG=$sql."  ".$stmt;
		return $result;
	}
	return $result;
}	



/*********************************************************************/
function GetListeTypePaie($tri)
{ 
	$result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$result->DATA=array();
	
	
	if ($tri=="") $tri="ID";
    $stmt="select ID, LIBELLE, RONLY, COPECRE,DCRE,COPEMAJ,DMAJ ";
	$stmt.="FROM TBTYPEPAIE ";
	$stmt.="ORDER BY ".$tri;
	
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	while ($row = $sth->fetchObject())
	{
		$row->DCRE=DBTimestamp_to_WebTimestamp($row->DCRE);
		$row->DMAJ=DBTimestamp_to_WebTimestamp($row->DMAJ);
        $result->DATA[] = $row;
	} 
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
}

/*********************************************************************/
//Vérifie que pour le poste, le libellé est unique
function CheckUniquePoste($POSTE)
{
	$result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$result->UNIQUE=FALSE;
    $stmt="select COUNT(*) AS NBE FROM TBPOSTE WHERE UPPER(LIBELLE)=".SqlString($POSTE->LIBELLE,TRUE)." AND SENS=".SqlString($POSTE->SENS,TRUE);
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	if ($row = $sth->fetchObject())
	{
		$result->UNIQUE=($row->NBE==0);
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
}

function GetInfoPoste($id)
{ 
    $result=new stdclass();
	$result->ID="";
	$result->LIBELLE="";
	$result->SENS="";
	$result->BUDGET="";
	$result->HIDDEN="";
	$result->RONLY="";
	$result->COPECRE="";
	$result->COPEMAJ="";
	$result->DCRE="";
	$result->DMAJ="";
	$result->CR="0";	
	$result->MSG="";
    $stmt="select C.ID, C.LIBELLE, C.SENS, C.BUDGET, C.HIDDEN, C.RONLY, C.COPECRE,C.DCRE,C.COPEMAJ,C.DMAJ ";
	$stmt .="FROM TBPOSTE AS C ";
	$stmt .="WHERE C.ID = ".SQLInteger($id,False)." ";
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	if ($row = $sth->fetchObject())
	{
		$row->DCRE=DBTimestamp_to_WebTimestamp($row->DCRE);
		$row->DMAJ=DBTimestamp_to_WebTimestamp($row->DMAJ);
		$row->BUDGET=CurrencyString($row->BUDGET);
		$result=$row;
		$result->CR="0";
		$result->MSG="";
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
} 



function GetListePoste($tri,$withidden=False,$withreadonly=True)
{ 
	$result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$result->DATA=array();
	
	
	if ($tri=="") $tri="ID";
    $stmt="select ID, LIBELLE, SENS, BUDGET, HIDDEN, RONLY, COPECRE,DCRE,COPEMAJ,DMAJ ";
	$stmt.="FROM TBPOSTE ";
	$where="";
	if ($withidden==FALSE) $where.=($where==""?"WHERE ":"AND ")."HIDDEN='N' ";
	if ($withreadonly==FALSE) $where.=($where==""?"WHERE ":"AND ")."RONLY='N' ";
	$stmt.=$where;
	$stmt.="ORDER BY ".$tri;
	
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	while ($row = $sth->fetchObject())
	{
		$row->DCRE=DBTimestamp_to_WebTimestamp($row->DCRE);
		$row->DMAJ=DBTimestamp_to_WebTimestamp($row->DMAJ);
		$row->BUDGET=CurrencyString($row->BUDGET);
        $result->DATA[] = $row;
	} 
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
}


//Si des écritures sont de ce poste, elles se verront automatiquement appliquée le poste 0
function DeletePoste($id)
{
	$result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$stmt="SELECT RONLY,HIDDEN FROM TBPOSTE WHERE ID = ".SQLInteger($id,FALSE);
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$ronly="";
	$hidden="";
	if ($row = $sth->fetchObject())
	{
		$ronly=$row->RONLY;
		$hidden=$row->HIDDEN;
	} 
	$sth->closeCursor(); 
	$sth=NULL;

	if (($ronly=="Y") or ($hidden=="Y"))
	{
		$result->CR="-1";
		$result->MSG="Suppression impossible ce poste est soit en lecture seul, soit indispensable au système";
		return $result;
	}
	$stmt="DELETE FROM TBPOSTE WHERE ID = ".SQLInteger($id,FALSE);
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
} 


function UpdateInfoPoste($POSTE,$typ)
{
    $result=new stdclass;
	$result->MSG="";
	$result->CR="0";
	
	if (($typ!="update") and ($typ!="insert"))
	{
		$result->MSG="Type d'update inconnu";
		$result->CR="-1";
		return $result;
	}
	
	if ($typ=="insert")
	{
		$stmt="INSERT INTO TBPOSTE ";
		$stmt.="(LIBELLE, SENS, BUDGET, COPECRE,DCRE)";
		$stmt.=" VALUES ( ";
		$stmt.=SQLString($POSTE->LIBELLE,FALSE).",";
		$stmt.=SQLString($POSTE->SENS,TRUE).",";
		$stmt.=SQLCurrency($POSTE->BUDGET,FALSE).",";
		$stmt.=SQLString($POSTE->COPECRE,FALSE).",";
		$stmt.="datetime(CURRENT_TIMESTAMP, 'localtime') ";
		$stmt.=");";
	}
	elseif ($typ=="update")
	{
		$stmt="UPDATE TBPOSTE SET ";
		$stmt.="LIBELLE       = ".SQLString($POSTE->LIBELLE,FALSE).", ";
		$stmt.="SENS      = ".SQLString($POSTE->SENS,TRUE).", ";
		$stmt.="BUDGET    = ".SQLCurrency($POSTE->BUDGET,FALSE).", ";
		$stmt.="COPEMAJ = ".SQLString($POSTE->COPEMAJ,FALSE).", ";
	    $stmt.="DMAJ    = datetime(CURRENT_TIMESTAMP, 'localtime') ";
		$stmt.="WHERE ID    = ".SQLInteger($POSTE->ID,FALSE);
	}
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	
	if ($typ=="insert")
	{
		$stmt="SELECT last_insert_rowid() AS ID;";
		$sth = $this->execute_query($stmt,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-1";
			$result->MSG=$stmt."  ".$sth;
			return $result;
		}
		if ($row = $sth->fetchObject()) $POSTE->ID=$row->ID;
		$sth->closeCursor(); 
		$sth=NULL;
	}
	return $result;
} 

/*********************************************************************/
function GetInfoCPT($cpt)
{ 
    $result=new stdclass();
	$result->ID="";
	$result->LIBELLE="";
	$result->BANQUE="";
	$result->RIB="";
	$result->DATEOUVERTURE="";
	$result->COPECRE="";
	$result->COPEMAJ="";
	$result->DCRE="";
	$result->DMAJ="";
	$result->CR="0";
	$result->MSG="";
    $stmt="select C.ID, C.LIBELLE, C.BANQUE, C.RIB, C.SOLDEINIT, C.DATEOUVERTURE, C.COPECRE,C.DCRE,C.COPEMAJ,C.DMAJ ";
	$stmt .="FROM TBCOMPTE AS C ";
	$stmt .="WHERE C.ID = ".SQLInteger($cpt,False)." ";
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	if ($row = $sth->fetchObject())
	{
		$row->DCRE=DBTimestamp_to_WebTimestamp($row->DCRE);
		$row->DMAJ=DBTimestamp_to_WebTimestamp($row->DMAJ);
		$row->DATEOUVERTURE=DBTimestamp_to_WebTimestamp($row->DATEOUVERTURE);
		$row->SOLDEINIT=CurrencyString($row->SOLDEINIT);
		$result=$row;
		$result->CR="0";
		$result->MSG="";
	}
	$sth->closeCursor(); 
	$sth=NULL;
	
    $stmt="select COUNT(*) AS NBEECR ";
	$stmt .="FROM TBECR  ";
	$stmt .="WHERE IDCOMPTE = ".SQLInteger($cpt,False)." ";
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	if ($row = $sth->fetchObject())
	{
		$result->NBEECR=$row->NBEECR;
		$result->CR="0";
		$result->MSG="";
	}
	$sth->closeCursor(); 
	$sth=NULL;
	
	return $result;
} 


/*********************************************************************/
//Vérifie que pour le comte est unique
function CheckUniqueCPT($CPT)
{
	$result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$result->UNIQUE=FALSE;
    $stmt="select COUNT(*) AS NBE FROM TBCOMPTE ";
	$stmt.="WHERE UPPER(COALESCE(LIBELLE,''))=COALESCE(".SqlString($CPT->LIBELLE,TRUE).",'') ";
	$stmt.="AND UPPER(COALESCE(BANQUE,''))=COALESCE(".SqlString($CPT->BANQUE,TRUE).",'') ";
	$stmt.="AND UPPER(COALESCE(RIB,''))=COALESCE(".SqlString($CPT->RIB,TRUE).",'') ";
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	if ($row = $sth->fetchObject())
	{
		$result->UNIQUE=($row->NBE==0);
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
}

function GetListeCPT($tri)
{ 
	$result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$result->DATA=array();
	
	
	if ($tri=="") $tri="ID";
    $stmt="select ID, LIBELLE, BANQUE, RIB, SOLDEINIT, DATEOUVERTURE, COPECRE,DCRE,COPEMAJ,DMAJ ";
	$stmt.="FROM TBCOMPTE ";
	$stmt.="ORDER BY ".$tri;
	
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	while ($row = $sth->fetchObject())
	{
		$row->DCRE=DBTimestamp_to_WebTimestamp($row->DCRE);
		$row->DMAJ=DBTimestamp_to_WebTimestamp($row->DMAJ);
		$row->SOLDEINIT=CurrencyString($row->SOLDEINIT);
		$row->DATEOUVERTURE=DBTimestamp_to_WebTimestamp($row->DATEOUVERTURE);
        $result->DATA[] = $row;
	} 
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
} 


function DeleteCpt($id,$check=False)
{
	$result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$stmt="SELECT COUNT(*) AS NBECR FROM TBECR WHERE IDCOMPTE = ".SQLInteger($id,FALSE)." AND IDPOSTE!=1";
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$nbe=0;
	if ($row = $sth->fetchObject())
	{
		$nbe=$row->NBECR;
	} 
	$sth->closeCursor(); 
	$sth=NULL;

	if (($nbe > 0) and ($check===True))
	{
		$result->CR="-1";
		$result->MSG="Suppression impossible car ".$nbe." mouvements appartiennent à ce compte";
		return $result;
	}
	$stmt="DELETE FROM TBCOMPTE WHERE ID = ".SQLInteger($id,FALSE);
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
} 


function UpdateInfoCPT($CPT,$typ)
{
    $result=new stdclass;
	$result->MSG="";
	$result->CR="0";
	
	if (($typ!="update") and ($typ!="insert"))
	{
		$result->MSG="Type d'update inconnu";
		$result->CR="-1";
		return $result;
	}
	
	if ($typ=="insert")
	{
		$stmt="INSERT INTO TBCOMPTE ";
		$stmt.="(LIBELLE, BANQUE, RIB, SOLDEINIT, DATEOUVERTURE, COPECRE,DCRE)";
		$stmt.=" VALUES ( ";
		$stmt.=SQLString($CPT->LIBELLE,FALSE).",";
		$stmt.=SQLString($CPT->BANQUE,TRUE).",";
		$stmt.=SQLString($CPT->RIB,TRUE).",";
		$stmt.=SQLCurrency($CPT->SOLDEINIT,FALSE).",";
		$stmt.=SQLDate($CPT->DATEOUVERTURE).",";
		$stmt.=SQLString($CPT->COPECRE,FALSE).",";
		$stmt.="datetime(CURRENT_TIMESTAMP, 'localtime') ";
		$stmt.=");";
	}
	elseif ($typ=="update")
	{
		$stmt="UPDATE TBCOMPTE SET ";
		$stmt.="LIBELLE       = ".SQLString($CPT->LIBELLE,FALSE).", ";
		$stmt.="BANQUE      = ".SQLString($CPT->BANQUE,TRUE).", ";
		$stmt.="RIB = ".SQLString($CPT->RIB,TRUE).", ";
		$stmt.="DATEOUVERTURE    = ".SQLDate($CPT->DATEOUVERTURE).", ";
		$stmt.="SOLDEINIT    = ".SQLCurrency($CPT->SOLDEINIT,FALSE).", ";
		$stmt.="COPEMAJ = ".SQLString($CPT->COPEMAJ,FALSE).", ";
	    $stmt.="DMAJ    = datetime(CURRENT_TIMESTAMP, 'localtime') ";
		$stmt.="WHERE ID    = ".SQLInteger($CPT->ID,FALSE);
	}
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	
	if ($typ=="insert")
	{
		$stmt="SELECT last_insert_rowid() AS IDCOMPTE;";
		$sth = $this->execute_query($stmt,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-1";
			$result->MSG=$stmt."  ".$sth;
			return $result;
		}
		if ($row = $sth->fetchObject()) $CPT->ID=$row->IDCOMPTE;
		$sth->closeCursor(); 
		$sth=NULL;
	}
	elseif ($typ=="update")
	{
		$stmt="DELETE FROM TBECR WHERE IDCOMPTE=".SqlInteger($CPT->ID,FALSE)." AND IDPOSTE=1;";
		$sth = $this->execute_query($stmt,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-1";
			$result->MSG=$stmt."  ".$sth;
			return $result;
		}
		$sth->closeCursor(); 
		$sth=NULL;
	}
	
	$ECR=new stdclass();
	$ECR->IDCOMPTE=$CPT->ID;
	$ECR->IDPOSTE="1";
	$ECR->LIBELLE="Solde Initial";
	$ECR->DATEECR=$CPT->DATEOUVERTURE;
	$ECR->IDTYPEPAIE="0";
	$ECR->BANQUE="";
	$ECR->REFERENCE="";
	$ECR->SENS=(floatval($CPT->SOLDEINIT)<0?"D":"C");
	$ECR->MONTANT=abs(floatval($CPT->SOLDEINIT));
	$ECR->IDTRANS="";
	$ECR->POINTE="N";
	$ECR->COPECRE=($typ=="insert"?$CPT->COPECRE:$CPT->COPEMAJ);
	$result=$this->UpdateInfoECR($ECR,"insert");
	return $result;
} 

/*********************************************************************/

function GetInfoECR($id)
{ 
    $result=new stdclass();
	$result->ID="";
	$result->DATEECR="";
	$result->IDCOMPTE="";
	$result->IDPOSTE="";
	$result->LIBELLE="";
	$result->IDTYPEPAIE="";
	$result->BANQUE="";
	$result->REFERENCE="";
	$result->MONTANT="";
	$result->POINTE="";
	$result->IDTRANS="";
	$result->CPT_ID="";
	$result->CPT_BANQUE="";
	$result->CPT_LIBELLE="";
	$result->TYPEPAIE_LIBELLE="";
	$result->POSTE_LIBELLE="";
	$result->POSTE_SENS="";
	$result->COPECRE="";
	$result->COPEMAJ="";
	$result->DCRE="";
	$result->DMAJ="";
	$result->SENS="";
	
	$result->CR="0";
	$result->MSG="";
    $stmt="select IDCOMPTE, ID , DATEECR, DCRE, DMAJ, COPECRE, COPEMAJ, IDPOSTE, LIBELLE , IDTYPEPAIE ,BANQUE,  ";
	$stmt .="REFERENCE, MONTANT, POINTE, IDTRANS,  ";
	$stmt .="CPT_ID, CPT_BANQUE, CPT_LIBELLE, CPT_DATEOUVERTURE,";
	$stmt .="TYPEPAIE_LIBELLE, POSTE_LIBELLE, POSTE_SENS ";
	$stmt .="FROM VECR ";
	$stmt .="WHERE ID = ".SQLInteger($id,False)." ";
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	if ($row = $sth->fetchObject())
	{
		$row->DCRE=DBTimestamp_to_WebTimestamp($row->DCRE);
		$row->DMAJ=DBTimestamp_to_WebTimestamp($row->DMAJ);
		$row->DATEECR=DBTimestamp_to_WebTimestamp($row->DATEECR);
		$row->CPT_DATEOUVERTURE=DBTimestamp_to_WebTimestamp($row->CPT_DATEOUVERTURE);
		$row->SENS=($row->MONTANT<0?"D":"C");
		$row->MONTANT=CurrencyString(abs($row->MONTANT));
		$result=$row;
		$result->CR="0";
		$result->MSG="";
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
} 


//Date 1er et dernière écriture, somme débit et crédit
function GetDetailECR($idcpt,$datedeb,$datefin)
{ 
    $result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$result->DATA=array();
	
    $stmt="select IDCOMPTE, MIN(DATEECR) AS DATEECRDEB, MAX(DATEECR) AS DATEECRFIN, ";
	$stmt .="SUM(CASE WHEN POINTE='Y' THEN MONTANT ELSE 0 END) AS SOLDEPOINTE,  ";
	$stmt .="SUM(MONTANT) AS SOLDE, COUNT(*) AS NBEECR  ";
	$stmt .="FROM TBECR ";
	$where="";
	if ($idcpt!="") $where.=($where==""?"WHERE ":"AND ")."IDCOMPTE=".SQLInteger($idcpt,False)." ";
	if ($datedeb!="") $where.=($where==""?"WHERE ":"AND ")."DATEECR>=".SQLDate($datedeb)." ";
	if ($datefin!="") $where.=($where==""?"WHERE ":"AND ")."DATEECR<=".SQLDate($datefin)." ";
	$stmt.=$where;
	$stmt.="GROUP BY IDCOMPTE";

	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	while ($row = $sth->fetchObject())
	{
		$row->DATEECRDEB=DBTimestamp_to_WebTimestamp($row->DATEECRDEB);
		$row->DATEECRFIN=DBTimestamp_to_WebTimestamp($row->DATEECRFIN);
		$row->SOLDE=CurrencyString($row->SOLDE);
		$row->SOLDEPOINTE=CurrencyString($row->SOLDEPOINTE);
		$row->NBEECR=CurrencyString($row->NBEECR);
		$result->DATA[]=$row;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
}



function GetListeECR($idcpt,$idposte,$idtypepaie,$datedeb,$datefin,$uniqpointe)
{ 
    $result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$result->TOTAL_CREDIT=0;
	$result->TOTAL_CREDIT_POINTE=0;
	$result->TOTAL_DEBIT=0;
	$result->TOTAL_DEBIT_POINTE=0;
	$result->DATA=array();
	
    $stmt="select IDCOMPTE, ID , DATEECR, DCRE, DMAJ, COPECRE, COPEMAJ, IDPOSTE, LIBELLE , IDTYPEPAIE ,BANQUE,  ";
	$stmt .="REFERENCE, MONTANT, POINTE, IDTRANS,  ";
	$stmt .="CPT_ID, CPT_BANQUE, CPT_LIBELLE, ";
	$stmt .="TYPEPAIE_LIBELLE, POSTE_LIBELLE, POSTE_SENS ";
	$stmt .="FROM VECR ";
	$where="";
	if ($idcpt!="") $where.=($where==""?"WHERE ":"AND ")."IDCOMPTE=".SQLInteger($idcpt,False)." ";
	if ($idposte!="") $where.=($where==""?"WHERE ":"AND ")."IDPOSTE=".SQLInteger($idposte,False)." ";
	if ($idtypepaie!="") $where.=($where==""?"WHERE ":"AND ")."IDTYPEPAIE=".SQLInteger($idtypepaie,False)." ";
	if ($datedeb!="") $where.=($where==""?"WHERE ":"AND ")."DATEECR>=".SQLDate($datedeb)." ";
	if ($datefin!="") $where.=($where==""?"WHERE ":"AND ")."DATEECR<=".SQLDate($datefin)." ";
	if ($uniqpointe=="Y") $where.=($where==""?"WHERE ":"AND ")."POINTE='Y' ";
	$stmt.=$where;
	$stmt.="ORDER BY IDCOMPTE, DATEECR DESC";

	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	while ($row = $sth->fetchObject())
	{
		$row->DCRE=DBTimestamp_to_WebTimestamp($row->DCRE);
		$row->DMAJ=DBTimestamp_to_WebTimestamp($row->DMAJ);
		$row->DATEECR=DBTimestamp_to_WebTimestamp($row->DATEECR);
		$row->SENS=($row->MONTANT<0?"D":"C");
		$row->MONTANT=CurrencyString(abs($row->MONTANT));
		$row->CREDIT=($row->SENS=="C"?$row->MONTANT:"");
		$row->DEBIT=($row->SENS=="D"?$row->MONTANT:"");
		$result->DATA[]=$row;
		$mt=str_replace(array(" ",","),array("","."),$row->MONTANT);
		$result->TOTAL_CREDIT=$result->TOTAL_CREDIT+($row->SENS=="C"?$mt:0);
		$result->TOTAL_CREDIT_POINTE=$result->TOTAL_CREDIT_POINTE+(($row->SENS=="C") and ($row->POINTE=="Y")?$mt:0);
		$result->TOTAL_DEBIT=$result->TOTAL_DEBIT+($row->SENS!="C"?$mt:0);
		$result->TOTAL_DEBIT_POINTE=$result->TOTAL_DEBIT_POINTE+(($row->SENS!="C") and ($row->POINTE=="Y")?$mt:0);

	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
} 

function DeleteECR($id)
{
	$result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$stmt="DELETE FROM TBECR WHERE ID = ".SQLInteger($id,FALSE);
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
} 

function UpdateInfoECR($ECR,$typ)
{
    $result=new stdclass;
	$result->MSG="";
	$result->CR="0";
	
	if (($typ!="update") and ($typ!="insert"))
	{
		$result->MSG="Type d'update inconnu";
		$result->CR="-1";
		return $result;
	}
	
	if ($typ=="insert")
	{
		$stmt="INSERT INTO TBECR ";
		$stmt.="( IDCOMPTE, DATEECR,  IDPOSTE, LIBELLE, IDTYPEPAIE,  BANQUE, REFERENCE, MONTANT, POINTE, IDTRANS, COPECRE, DCRE )";
		$stmt.=" VALUES ( ";
		$stmt.=SQLInteger($ECR->IDCOMPTE,TRUE).",";
		$stmt.=SQLDate($ECR->DATEECR).",";
		$stmt.=SQLInteger($ECR->IDPOSTE,TRUE).",";
		$stmt.=SQLString($ECR->LIBELLE,FALSE).",";
		$stmt.=SQLInteger($ECR->IDTYPEPAIE,TRUE).",";
		$stmt.=SQLString($ECR->BANQUE,TRUE).",";
		$stmt.=SQLString($ECR->REFERENCE,TRUE).",";
		$stmt.=SQLCurrency(($ECR->SENS=="D"?-1*$ECR->MONTANT:$ECR->MONTANT),FALSE).",";
		$stmt.=SQLString($ECR->POINTE,TRUE).",";
		$stmt.=SQLInteger($ECR->IDTRANS,TRUE).",";
		$stmt.=SQLString($ECR->COPECRE,FALSE).",";
		$stmt.="datetime(CURRENT_TIMESTAMP, 'localtime') ";
		$stmt.=");";
	}
	elseif ($typ=="update")
	{
		$stmt="UPDATE TBECR SET ";
		$stmt.="IDCOMPTE     = ".SQLInteger($ECR->IDCOMPTE,TRUE).", ";
		$stmt.="DATEECR     = ".SQLDate($ECR->DATEECR).", ";
		$stmt.="IDPOSTE     = ".SQLInteger($ECR->IDPOSTE,TRUE).", ";
		$stmt.="LIBELLE     = ".SQLString($ECR->LIBELLE,FALSE).", ";
		$stmt.="IDTYPEPAIE  = ".SQLInteger($ECR->IDTYPEPAIE,TRUE).", ";
		$stmt.="BANQUE      = ".SQLString($ECR->BANQUE,TRUE).", ";
		$stmt.="REFERENCE   = ".SQLString($ECR->REFERENCE,TRUE).", ";
		$stmt.="MONTANT     = ".SQLCurrency(($ECR->SENS=="D"?-1*$ECR->MONTANT:$ECR->MONTANT),FALSE).", ";
		$stmt.="BANQUE      = ".SQLString($ECR->BANQUE,TRUE).", ";
		$stmt.="IDTRANS     = ".SQLInteger($ECR->IDTRANS,TRUE).", ";
		$stmt.="POINTE      = ".SQLString($ECR->POINTE,FALSE).", ";
		$stmt.="COPEMAJ      = ".SQLString($ECR->COPEMAJ,FALSE).", ";
	    $stmt.="DMAJ    = datetime(CURRENT_TIMESTAMP, 'localtime') ";
		$stmt.="WHERE ID    = ".SQLInteger($ECR->ID,FALSE);
	}
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
}


/******************************************************************************/
//Pointer / dépointer
/******************************************************************************/
function PointeDepointe($ECR)
{
    $result=new stdclass;
	$result->MSG="";
	$result->CR="0";

	$stmt="UPDATE TBECR SET ";
	$stmt.="POINTE     = CASE WHEN POINTE='N' THEN 'Y' ELSE 'N' END, ";
	$stmt.="COPEMAJ      = ".SQLString($ECR->COPEMAJ,FALSE).", ";
	$stmt.="DMAJ    = datetime(CURRENT_TIMESTAMP, 'localtime') ";
	$stmt.="WHERE ID    = ".SQLInteger($ECR->ID,FALSE);
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
}

/******************************************************************************/
/* Suppression de Transfert , saloperie de FK qui ne fonctionne pas
/******************************************************************************/
function DeleteTransfert($id)
{
	$result=new stdclass();
	$result->CR="0";
	$result->MSG="";
	$stmt="DELETE FROM TBECR WHERE IDTRANS = ".SQLInteger($id,FALSE);
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	
	$stmt="DELETE FROM TBTRANSFERT WHERE ID = ".SQLInteger($id,FALSE);
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
} 


function GetInfoTransfert($id)
{ 
    $result=new stdclass();
	$result->ID="";
	$result->DATEECR="";
	$result->IDCOMPTEDE="";
	$result->IDCOMPTEA="";
	$result->IDPOSTE="";
	$result->IDTYPEPAIE="";
	$result->LIBELLE="";
	$result->MONTANT="";
	$result->COPECRE="";
	$result->COPEMAJ="";
	$result->DCRE="";
	$result->DMAJ="";
	
	$result->CR="0";
	$result->MSG="";
	
    $stmt="select ID, IDCOMPTEDE, IDCOMPTEA , DATEECR, DCRE, DMAJ, COPECRE, COPEMAJ, IDPOSTE, LIBELLE , IDTYPEPAIE ,  ";
	$stmt .="MONTANT ";
	$stmt .="FROM TBTRANSFERT ";
	$stmt .="WHERE ID = ".SQLInteger($id,False)." ";
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	if ($row = $sth->fetchObject())
	{
		$row->DCRE=DBTimestamp_to_WebTimestamp($row->DCRE);
		$row->DMAJ=DBTimestamp_to_WebTimestamp($row->DMAJ);
		$row->DATEECR=DBTimestamp_to_WebTimestamp($row->DATEECR);
		$row->MONTANT=CurrencyString($row->MONTANT);
		$result=$row;
		$result->CR="0";
		$result->MSG="";
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
} 

//Récupérer les ID des ECR dans le cas d'un update de transfert
function GetInfoECRByIDTRANS($TRANSFERT)
{
    $result=new stdclass;
	$result->MSG="";
	$result->CR="0";
	$result->IDECRDE="";
	$result->POINTEECRDE="";
	$result->IDECRA="";
	$result->POINTEECRA="";
	
    $stmt="select ID, IDCOMPTE, POINTE FROM TBECR ";
	$stmt .="WHERE IDTRANS = ".SQLInteger($TRANSFERT->ID,False)." AND IDCOMPTE IN (".SQLInteger($TRANSFERT->IDCOMPTEDE,False).",".SQLInteger($TRANSFERT->IDCOMPTEA,False).")";
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	while ($row = $sth->fetchObject())
	{
		if ($row->IDCOMPTE==$TRANSFERT->IDCOMPTEDE) 
		{
			$result->IDECRDE=$row->ID;
			$result->POINTEECRDE=$row->POINTE;
		}
		elseif ($row->IDCOMPTE==$TRANSFERT->IDCOMPTEA) 
		{
			$result->IDECRA=$row->ID;
			$result->POINTEECRA=$row->POINTE;
		}
		$result->CR="0";
		$result->MSG="";
	}
	$sth->closeCursor(); 
	$sth=NULL;
	return $result;
}

function UpdateInfoTransfert($TRANSFERT,$typ)
{
    $result=new stdclass;
	$result->MSG="";
	$result->CR="0";
	$TRANSFERT->IDECRDE="";
	$TRANSFERT->IDECRA="";
	$TRANSFERT->POINTEECRDE="N";
	$TRANSFERT->POINTEECRA="N";
	
	if (($typ!="update") and ($typ!="insert"))
	{
		$result->MSG="Type d'update inconnu";
		$result->CR="-1";
		return $result;
	}
	
	//J'ai besoin des ID des ECR Concernés
	if ($typ=="update") 
	{
		$r=$this->GetInfoECRByIDTRANS($TRANSFERT);
		if ($r->CR!="0")
		{
			$result->MSG=$r->MSG;
			$result->CR=$r->CR;
			return $result;
		}
		$TRANSFERT->IDECRDE=$r->IDECRDE;
		$TRANSFERT->IDECRA=$r->IDECRA;
		$TRANSFERT->POINTEECRDE=$r->POINTEECRDE;
		$TRANSFERT->POINTEECRA=$r->POINTEECRA;
	}
	
	if ($typ=="insert")
	{
		$stmt="INSERT INTO TBTRANSFERT ";
		$stmt.="( IDCOMPTEDE, IDCOMPTEA, DATEECR,  IDPOSTE, IDTYPEPAIE, LIBELLE, MONTANT, COPECRE, DCRE )";
		$stmt.=" VALUES ( ";
		$stmt.=SQLInteger($TRANSFERT->IDCOMPTEDE,TRUE).",";
		$stmt.=SQLInteger($TRANSFERT->IDCOMPTEA,TRUE).",";
		$stmt.=SQLDate($TRANSFERT->DATEECR).",";
		$stmt.=SQLInteger($TRANSFERT->IDPOSTE,TRUE).",";
		$stmt.=SQLInteger($TRANSFERT->IDTYPEPAIE,TRUE).",";
		$stmt.=SQLString($TRANSFERT->LIBELLE,FALSE).",";
		$stmt.=SQLCurrency($TRANSFERT->MONTANT,FALSE).",";
		$stmt.=SQLString($TRANSFERT->COPECRE,FALSE).",";
		$stmt.="datetime(CURRENT_TIMESTAMP, 'localtime') ";
		$stmt.=");";
	}
	elseif ($typ=="update")
	{
		$stmt="UPDATE TBTRANSFERT SET ";
		$stmt.="IDCOMPTEDE     = ".SQLInteger($TRANSFERT->IDCOMPTEDE,TRUE).", ";
		$stmt.="IDCOMPTEA      = ".SQLInteger($TRANSFERT->IDCOMPTEA,TRUE).", ";
		$stmt.="DATEECR     = ".SQLDate($TRANSFERT->DATEECR).", ";
		$stmt.="IDPOSTE     = ".SQLInteger($TRANSFERT->IDPOSTE,TRUE).", ";
		$stmt.="IDTYPEPAIE  = ".SQLInteger($TRANSFERT->IDTYPEPAIE,TRUE).", ";
		$stmt.="LIBELLE     = ".SQLString($TRANSFERT->LIBELLE,FALSE).", ";
		$stmt.="MONTANT     = ".SQLCurrency($TRANSFERT->MONTANT,FALSE).", ";
		$stmt.="COPEMAJ      = ".SQLString($TRANSFERT->COPEMAJ,FALSE).", ";
	    $stmt.="DMAJ    = datetime(CURRENT_TIMESTAMP, 'localtime') ";
		$stmt.="WHERE ID    = ".SQLInteger($TRANSFERT->ID,FALSE);
	}
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	$sth->closeCursor(); 
	$sth=NULL;
	if ($typ=="insert")
	{
		$stmt="SELECT last_insert_rowid() AS ID;";
		$sth = $this->execute_query($stmt,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-1";
			$result->MSG=$stmt."  ".$sth;
			return $result;
		}
		if ($row = $sth->fetchObject()) $TRANSFERT->ID=$row->ID;
		$sth->closeCursor(); 
		$sth=NULL;
	}
	$ECRDE=new stdclass();
	$ECRDE->IDCOMPTE=$TRANSFERT->IDCOMPTEDE;
	$ECRDE->IDPOSTE=$TRANSFERT->IDPOSTE;
	$ECRDE->IDTYPEPAIE=$TRANSFERT->IDTYPEPAIE;
	$ECRDE->LIBELLE=$TRANSFERT->LIBELLE;
	$ECRDE->DATEECR=$TRANSFERT->DATEECR;
	$ECRDE->POINTE=$TRANSFERT->POINTEECRDE;
	$ECRDE->SENS="D";
	$ECRDE->BANQUE="";
	$ECRDE->REFERENCE="";
	$ECRDE->MONTANT=abs(floatval($TRANSFERT->MONTANT));
	$ECRDE->IDTRANS=$TRANSFERT->ID;
	$ECRDE->ID=$TRANSFERT->IDECRDE;
	if ($typ=="insert") $ECRDE->COPECRE=$TRANSFERT->COPECRE;
	elseif ($typ=="update") $ECRDE->COPEMAJ=$TRANSFERT->COPEMAJ;
	
	$ECRA=new stdclass();
	$ECRA->IDCOMPTE=$TRANSFERT->IDCOMPTEA;
	$ECRA->IDPOSTE=$TRANSFERT->IDPOSTE;
	$ECRA->IDTYPEPAIE=$TRANSFERT->IDTYPEPAIE;
	$ECRA->LIBELLE=$TRANSFERT->LIBELLE;
	$ECRA->DATEECR=$TRANSFERT->DATEECR;
	$ECRA->POINTE=$TRANSFERT->POINTEECRA;
	$ECRA->SENS="C";
	$ECRA->BANQUE="";
	$ECRA->REFERENCE="";
	$ECRA->MONTANT=abs(floatval($TRANSFERT->MONTANT));
	$ECRA->IDTRANS=$TRANSFERT->ID;
	$ECRA->ID=$TRANSFERT->IDECRA;
	if ($typ=="insert") $ECRA->COPECRE=$TRANSFERT->COPECRE;
	elseif ($typ=="update") $ECRA->COPEMAJ=$TRANSFERT->COPEMAJ;
	
	$r=$this->UpdateInfoECR($ECRDE,$typ);
	if ($r->CR!="0")
	{
		$result->CR=$r->CR;
		$result->MSG=$r->MSG;
		return $result;
	}
	$r=$this->UpdateInfoECR($ECRA,$typ);
	if ($r->CR!="0")
	{
		$result->CR=$r->CR;
		$result->MSG=$r->MSG;
		return $result;
	}
	return $result;
}
/******************************************************************************/
function GetPays()
{
	$result=new stdclass();
	$result->DATA=array();
	$result->MSG="";
	$result->CR="0";
	$where="";
	$sql ="SELECT COUNTRY_CODE,COUNTRY_NAME FROM TBBANNED WHERE COALESCE(COUNTRY_NAME,'')!='' \r\n ";
	$sql.="GROUP BY COUNTRY_CODE,COUNTRY_NAME ORDER BY COUNTRY_CODE,COUNTRY_NAME";
	$stmt = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($stmt))
	{
		$result->CR="-1";
		$result->MSG=$sql."  ".$stmt;
		return $result;
	}
	$i=0;
    while ($row = $stmt->fetchObject()) 
	{
		$result->DATA[$i]=$row;
		$i++;
	}
    return $result;
}


/************************************************/

function CheckLogin($user,$pass)
{
	Global $Parametres;
	$result=new stdclass();
	$result->MSG="";
	$result->CR=0;
	$result->IDUSER=0;
	$result->PRENOM=0;
	$result->NOM="";
	$result->EMAIL="";
	$result->USERMODIFYING="";
	if( trim($user)=="" ) 
	{
		$result->CR=1;
		$result->MSG="Email absent";
		return $result;
	}
	if( trim($pass)=="" ) 
	{
		$result->CR=1;
		$result->MSG="Mot de passe absent";
		return $result;
	}
	$sql="SELECT COUNT(*) AS NBE FROM TBUSER;";
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	$NBE=0;
	if ($row = $sth->fetchObject()) $NBE=$row->NBE;
	if ($NBE==0)
	{
		$sql="INSERT INTO TBUSER (PRENOM,NOM,EMAIL,COPECRE,ISADMIN,TRESORIER,PASS) VALUES ('Admin','Admin','admin@admin.admin','Auto','Y','N', md5('admin'));";
		$sth = $this->execute_query($sql,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-2";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
	}
	$sql="SELECT COUNT(*) AS NBE FROM TBUSER  WHERE UPPER(TRIM(EMAIL)) = UPPER(TRIM(".SQLString($user,FALSE).")) AND PASS=md5(".SQLString($pass,FALSE).");";
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-3";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	$NBE=0;
	if ($row = $sth->fetchObject()) $NBE=$row->NBE;
	if ($NBE==0)
	{
		$result->CR=2;
		$result->MSG="Incorrect email and/or password";
		return $result;
	}
	if ($NBE>1)
	{
		$result->CR=2;
		$result->MSG="This email/password is using for more than one user";
		return $result;
	}
	$sql="SELECT IDUSER,PRENOM,NOM,ISADMIN,TRESORIER FROM TBUSER WHERE UPPER(TRIM(EMAIL)) = UPPER(TRIM(".SQLString($user,FALSE).")) AND PASS=md5(".SQLString($pass,FALSE).");";
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-3";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	if ($row = $sth->fetchObject())
	{
		$result->IDUSER=$row->IDUSER;
		$result->PRENOM=$row->PRENOM;
		$result->NOM=$row->NOM;
		$result->ISADMIN=$row->ISADMIN;
		$result->TRESORIER=$row->TRESORIER;
		$result->CR="0";
		$result->MSG="Connexion OK";
		$result->EMAIL=$user;
		$result->USERMODIFYING=(trim($result->PRENOM." ".$result->NOM)!=""?$result->PRENOM." ".$result->NOM:substr($user,0,65));
	}
	$sql="UPDATE TBUSER SET LASTCONNECT = datetime(CURRENT_TIMESTAMP, 'localtime') WHERE IDUSER = ".SQLInteger($result->IDUSER,FALSE);      
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-3";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}

	return $result;
}

function ChangePassword($useremail,$currentpass,$newpass)
{
	$result=new stdclass();
	$result->MSG="";
	$result->CR=0;
	if( trim($useremail)=="" ) 
	{
		$result->CR=1;
		$result->MSG="Email absent";
		return $result;
	}
	if( trim($currentpass)=="" ) 
	{
		$result->CR=1;
		$result->MSG="Ancien mot de passe absent";
		return $result;
	}
	if( trim($newpass)=="" ) 
	{
		$result->CR=1;
		$result->MSG="Nouveau mot de passe absent";
		return $result;
	}
	$sql="SELECT COUNT(*) AS NBE FROM TBUSER  WHERE UPPER(TRIM(EMAIL)) = UPPER(TRIM(".SQLString($useremail,FALSE).")) AND PASS=md5(".SQLString($currentpass,FALSE).");";
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-3";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	$NBE=0;
	if ($row = $sth->fetchObject()) $NBE=$row->NBE;
	if ($NBE==0)
	{
		$result->CR=2;
		$result->MSG="Incorrect email and/or old password";
		return $result;
	}
	$sql= "UPDATE TBUSER SET PASS=md5(".SQLString($newpass,FALSE)."), DMAJ=datetime(CURRENT_TIMESTAMP, 'localtime'), COPEMAJ=".SQLString($useremail,FALSE)." ";
	$sql.="WHERE UPPER(TRIM(EMAIL)) = UPPER(TRIM(".SQLString($useremail,FALSE).")) ";
	$sql.="AND PASS=md5(".SQLString($currentpass,FALSE).");"; 

	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-3";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	
	$result->MSG = "Password changed successfully";
	return $result;
}

function DeleteUser($userid)
{
	$result=new stdclass();
	$result->MSG="";
	$result->CR=0;
	if( trim($userid)=="" ) 
	{
		$result->CR=1;
		$result->MSG="userid absent";
		return $result;
	}
	$sql="SELECT COUNT(*) AS NBE FROM TBUSER  WHERE IDUSER = ".SQLInteger($userid,FALSE).";";
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-3";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	$NBE=0;
	if ($row = $sth->fetchObject()) $NBE=$row->NBE;
	if ($NBE==0)
	{
		$result->CR=2;
		$result->MSG="userid unknown";
		return $result;
	}
	$sql="SELECT COUNT(*) AS NBE FROM TBUSER;";
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-3";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	$NBE=0;
	if ($row = $sth->fetchObject()) $NBE=$row->NBE;
	if ($NBE<=1)
	{
		$result->CR=2;
		$result->MSG="There must be at least one user";
		return $result;
	}
	$sql="SELECT COUNT(*) AS NBE FROM TBUSER WHERE IDUSER<>".SQLInteger($userid,FALSE)." AND ISADMIN='Y'";
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-3";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	$NBE=0;
	if ($row = $sth->fetchObject()) $NBE=$row->NBE;
	if ($NBE<1)
	{
		$result->CR=2;
		$result->MSG="There must be at least one user";
		return $result;
	}
	$sql="DELETE FROM TBUSER  WHERE IDUSER=".SQLInteger($userid,FALSE);
	$sth = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-3";
		$result->MSG=$sql."  ".$sth;
		return $result;
	}
	$result->MSG="User deleted";
	return $result;
}	


function UpdateUser($user,$typemodif)
{
	$result=new stdclass();
	$result->MSG="";
	$result->CR=0;
	if (trim($user->PRENOM)=="")
	{
		$result->MSG="Prénom absent";
		$result->CR=1;
		return $result;
	}
	if (trim($user->NOM)=="")
	{
		$result->MSG="NOM absent";
		$result->CR=1;
		return $result;
	}
	if (trim($user->EMAIL)=="")
	{
		$result->MSG="Mail absent";
		$result->CR=1;
		return $result;
	}
	if (($typemodif!="insert") and ($typemodif!="update"))
	{
		$result->MSG="Incorrect modif type";
		$result->CR=1;
		return $result;
	}
	if (($typemodif=="insert") and (trim($user->PASS)==""))
	{
		$result->MSG="Mot de passe absent";
		$result->CR=1;
		return $result;
	}
	if (($typemodif=="update") and (trim($user->IDUSER)==""))
	{
		$result->MSG="IDUSER absent";
		$result->CR=1;
		return $result;
	}
	if ((trim($user->ISADMIN)!="Y") and (trim($user->ISADMIN)!="N"))
	{
		$result->MSG="ISADMIN incorrect";
		$result->CR=1;
		return $result;
	}
	if ((trim($user->TRESORIER)!="Y") and (trim($user->TRESORIER)!="N"))
	{
		$result->MSG="TRESORIER incorrect";
		$result->CR=1;
		return $result;
	}
	if ($typemodif=="insert")
	{
		$sql="SELECT COUNT(*) AS NBE FROM TBUSER WHERE UPPER(TRIM(EMAIL)) = UPPER(TRIM(".SQLString($user->EMAIL,FALSE)."))";
		$sth = $this->execute_query($sql,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-3";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$NBE=0;
		if ($row = $sth->fetchObject()) $NBE=$row->NBE;
		if ($NBE>0)
		{
			$result->CR=2;
			$result->MSG="Email déjà utilisé";
			return $result;
		}
		$sql="INSERT INTO TBUSER (PRENOM,NOM,EMAIL,ISADMIN,TRESORIER,COPECRE,PASS) VALUES (";
		$sql.=SQLString($user->PRENOM,FALSE).",".SQLString($user->NOM,FALSE).",";
		$sql.=SQLString($user->EMAIL,FALSE).",".SQLString($user->ISADMIN,TRUE).",".SQLString($user->TRESORIER,TRUE).",".SQLString($user->COPECRE,FALSE).",";
		$sql.="md5(".SQLString($user->PASS,FALSE)."));";
		$sth = $this->execute_query($sql,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-3";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$sql="SELECT IDUSER FROM TBUSER WHERE UPPER(TRIM(EMAIL)) = UPPER(TRIM(".SQLString($user->EMAIL,FALSE)."))";
		$sth = $this->execute_query($sql,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-3";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		if ($row = $sth->fetchObject()) $result->IDUSER=$row->IDUSER;
		$result->MSG="User created";
	}
	
	//si pass non fournis (vide) alors pas de mise à jour du mot de pass, c'est dans la proc
	if ($typemodif=="update")
	{
		$sql="SELECT COUNT(*) AS NBE FROM TBUSER WHERE IDUSER = ".SQLInteger($user->IDUSER,FALSE);
		$sth = $this->execute_query($sql,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-3";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$NBE=0;
		if ($row = $sth->fetchObject()) $NBE=$row->NBE;
		if ($NBE==0)
		{
			$result->CR=2;
			$result->MSG="User inconnu ";
			return $result;
		}
		$sql="SELECT COUNT(*) AS NBE FROM TBUSER WHERE IDUSER <> ".SQLInteger($user->IDUSER,FALSE)." AND UPPER(EMAIL)=".SQLString($user->EMAIL,FALSE);
		$sth = $this->execute_query($sql,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-3";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$NBE=0;
		if ($row = $sth->fetchObject()) $NBE=$row->NBE;
		if ($NBE>0)
		{
			$result->CR=2;
			$result->MSG="MAil déjà utilisé";
			return $result;
		}
		$sql="UPDATE TBUSER SET ";
		$sql.="PRENOM = ".SQLString($user->PRENOM,FALSE).",";
		$sql.="NOM = ".SQLString($user->NOM,FALSE).",";
		$sql.="EMAIL = ".SQLString($user->EMAIL,FALSE).",";
		$sql.="DMAJ = datetime(CURRENT_TIMESTAMP, 'localtime'),";
		$sql.="COPEMAJ = ".SQLString($user->COPEMAJ,FALSE).",";
		if (trim($user->PASS)!="") $sql.="PASS = md5(".SQLString($user->PASS,FALSE)."),";
		$sql.="ISADMIN = ".SQLString($user->ISADMIN,TRUE).",";
		$sql.="TRESORIER = ".SQLString($user->TRESORIER,TRUE)." ";
		$sql.="WHERE IDUSER=".SQLInteger($user->IDUSER,FALSE);
		$sth = $this->execute_query($sql,"%d/%m/%Y");
		if (is_string($sth))
		{
			$result->CR="-3";
			$result->MSG=$sql."  ".$sth;
			return $result;
		}
		$result->MSG="Mise à jour effectuée";
	}
	return $result;
}	
/************************************************/

function GetYearData()
{
	$result=new stdclass();
	$result->DATA=array();
	$result->MSG="";
	$result->CR="0";
	$sql ="SELECT strftime('%Y',DATEECR) AS ANNEE FROM TBECR ";
    $sql.=" GROUP BY strftime('%Y',DATEECR) "; 
	$sql.=" ORDER BY strftime('%Y',DATEECR) DESC "; 
	$stmt = $this->execute_query($sql,"%d/%m/%Y");
	if (is_string($stmt))
	{
		$result->CR="-1";
		$result->MSG=$sql."  ".$stmt;
		return $result;
	}
	$i=0;
    while ($row = $stmt->fetchObject()) 
	{
		$result->DATA[$i]=$row;
		$i++;
	}
    return $result;
}
function GetRepart($annee,$compte,$uniqpointe,$sens)
{
	$result=new stdclass();
	$result->DATA=array();
	$result->MSG="";
	$result->CR="0";
	$where="";
	$sql ="SELECT strftime('%Y',DATEECR) AS ANNEE, IDPOSTE,POSTE.LIBELLE AS POSTE_LIBELLE, POSTE.BUDGET AS POSTE_BUDGET, POSTE.SENS AS POSTE_SENS, IDCOMPTE,CPT.BANQUE AS CPT_BANQUE,CPT.LIBELLE AS CPT_LIBELLE, SUM(ECR.MONTANT) AS POSTE_TOTAL \r\n ";
	$sql.="FROM TBPOSTE AS POSTE\r\n";
	$sql.="INNER JOIN TBECR AS ECR ON ECR.IDPOSTE=POSTE.ID\r\n";
	$sql.="INNER JOIN TBCOMPTE AS CPT ON ECR.IDCOMPTE=CPT.ID\r\n";
	if ($compte!="") $sql.="AND CPT.ID=".SQLInteger($compte,FALSE)."\r\n";
	$where="";
	//Pas les soldes initiaux
	$where.=($where==""?" WHERE ":" AND ")."ECR.IDPOSTE!=1\r\n";
	if ($annee!="") $where.=($where==""?" WHERE ":" AND ")."ECR.DATEECR>='".$annee."-01-01' AND ECR.DATEECR<'".($annee+1)."-01-01'\r\n";
	if ($uniqpointe=="Y") $where.=($where==""?" WHERE ":" AND ")."ECR.POINTE='Y'\r\n";
	if ($sens!="") $where.=($where==""?" WHERE ":" AND ")."POSTE.SENS= ".SQLString($sens,TRUE)."\r\n";
	$sql.=$where;
	$sql.="GROUP BY strftime('%Y',DATEECR), IDPOSTE,POSTE.LIBELLE,IDCOMPTE,CPT.BANQUE,CPT.LIBELLE,POSTE.BUDGET,POSTE.SENS\r\n";
	$sql.="ORDER BY strftime('%Y',DATEECR), IDPOSTE,POSTE.LIBELLE,IDCOMPTE,CPT.BANQUE,CPT.LIBELLE";
	$stmt = $this->execute_query($sql,"%d/%m/%Y");

	if (is_string($stmt))
	{
		$result->CR="-1";
		$result->MSG=$stmt." ".$sql;
		return $result;
	}
	$i=0;
    while ($row = $stmt->fetchObject()) 
	{
		$row->POSTE_BUDGET=($row->POSTE_BUDGET!=""?CurrencyString(abs($row->POSTE_BUDGET)):"");
		$row->POSTE_TOTAL=($row->POSTE_TOTAL!=""?CurrencyString(abs($row->POSTE_TOTAL)):"");
		$result->DATA[$i]=$row;	
		$i++;
	}
    return $result;
}
}
?>
