<?php
  ini_set('memory_limit', '1024M');
  DEFINE("TOKEN","n732xd35jsl74e4");
  //doit être dans $token
  
/*****************************************************/
function compress($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }
	if (file_exists($destination)) unlink($destination);
    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', realpath($file));

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}

/*****************************************************/
function backup_launcher($dossier)
{
	$today = date("Ymd");
	$dossier_dest="./FTP";
	if (!is_dir($dossier_dest)) 
	{
		if (!mkdir($dossier_dest, 0777, true))
		{
			echo ("can not create ".$dossier_dest);
		}
	}
	$dest=$dossier_dest.'/'.$dossier.'_'.$today.'.zip';
	compress('../'.$dossier, $dest);
	return $dest;
}


function ftp_send($ftp_server,$ftp_user_name,$ftp_user_pass,$file,$remote_dir,$remote_file,$passive_mode)
{
 // set up basic connection
 echo($file);
 $conn_id = ftp_connect($ftp_server);

 // login with username and password
 if (ftp_login($conn_id, $ftp_user_name, $ftp_user_pass)===False)
	 return False;

 if (($remote_dir!="") and ($remote_dir!="./"))
 {
	 $ret=True;
	 if (@ftp_chdir($conn_id, $remote_dir)===False) 
	 {
		 ftp_mkdir($conn_id, $remote_dir);
		 $ret=ftp_chdir($conn_id, $remote_dir);
	 }
	 if ($ret===False) return False;
	 echo("Dir is now ".$remote_dir."<br>");
 }
 ftp_pasv($conn_id, $passive_mode);
 // upload a file
 if (ftp_put($conn_id, $remote_file, $file, FTP_BINARY )===False) 
    return False;
 // close the connection
 ftp_close($conn_id);
 return true;
}

/*****************************************************/

  $token=(isset($_REQUEST["token"])?$_REQUEST["token"]:"");
  if ($token!=TOKEN) { header("HTTP/1.0 403 Forbidden"); return; }
	  
  echo "Starting compression at ".date('h:i:s') . "<br>";
  $today = date("Y-m-d");

  $dossier = 'comptaweb';
  $zipName = backup_launcher($dossier);
  echo "Ending compression at ".date('h:i:s') . "<br>";
  echo 'folder '.$dossier.' zipped dans '.$zipName.'<br>';
  echo "Start sending via FTP at ".date('h:i:s') . "<br>";
  if (ftp_send("cderenne.cd-ii.fr","cderenne","467im5gu",$zipName,"CMB_BACKUP",basename($zipName),True)===True)
  {
	  echo "successfully uploaded ".$zipName."<br>";
	  echo "End sending via FTP at ".date('h:i:s') . "<br>";
  }
   else
	echo "There was a problem while uploading ".$file."<br>";
	 
  
  
?>
