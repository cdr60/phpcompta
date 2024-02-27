<?php
// Librairie de gestion des graphique

//un chart avion
function MkPngChartAvion($filename,$avion,$width,$height,$color,$majorx=1)
{
	//legende en abscisse : les jours
	$todayd=date("d");
	$todaym=date("m");
	$todayy=date("Y");
	$legendday=array();
	$legenddaypos=array();
	for ($i=0;$i<15;$i++)
	{
		$legendday[$i]="\"\"";
		if ((($i%$majorx==0)) or ($majorx==1)) $legendday[$i]="\"".date("d/m",mktime(0,0,0,$todaym,($i+$todayd),$todayy))."\"";
		$legenddaypos[$i]=$i;
	}
		
	$result=new stdclass();
	$result->msg="";
	$result->data="";
	if (file_exists($filename))
	{
		 @unlink($filename);
	}

	if (!isset($avion->POT_CYCLE_MIN)) return $result;
	$ymin=-10;
	$ymax=50;
	$xmin=0;
	$xmax=14;

	$key="HDV_DEPUIS_VIS_MIN";
	$val=($avion->POT_CYCLE_MIN-$avion->{$key})/60;
	if ($val>$ymax)  $ymax=$val;
	if ($val<$ymin)  $ymin=$val;
	for ($i=1;$i<15;$i++)
	{
	$key="HDV_DEPUIS_VIS_".$i."J_MIN";
	$val=$avion->{$key}/60;
	if ($val>$ymax)  $ymax=$val;
	if ($val<$ymin)  $ymin=$val;  
	}

	if (intval($ymin)!=floatval($ymin)) $ymin=intval($ymin);
	$ymin=(round(2*$ymin,-1)/2);
	$ymax=$avion->POT_CYCLE_MIN/60;

	$sourcestring="";
	$sourcestring.="<?php \r\n";
	$sourcestring.="require_once ('../chart2/jpgraph.php');\r\n";
	$sourcestring.="require_once ('../chart2/jpgraph_line.php');\r\n";
	$sourcestring.="require_once ('../chart2/jpg-config.inc.php');\r\n";

	$tabx=array();
	$taby=array();
	$t=array();
	for ($i=0;$i<15;$i++)
	{
	  $key="HDV_DEPUIS_VIS_MIN";
	  if ($i>0) $key="HDV_DEPUIS_VIS_".$i."J_MIN";
	  $val=($avion->POT_CYCLE_MIN-$avion->{$key})/60;
	  $tabx[$i]=$i;
	  $t[$i]=$val;
	}
	$taby[0]=$t[0];
	//si 2 valeurs de suite hors grpahique : on ne trace pas
	for ($i=1;$i<count($t);$i++)
	{
		if ((($t[$i]<$ymin) or ($t[$i]>$ymax)) and (($t[$i-1]<$ymin) or ($t[$i-1]>$ymax)))	$taby[$i]="\"\"";
		else $taby[$i]=$t[$i];
	}

	$sourcestring.="\$datax = array(".implode(",",$tabx).");\r\n";
	$sourcestring.="\$datay = array(".implode(",",$taby).");\r\n";

	$sourcestring.="//taille en pixel du graphic\r\n";
	$sourcestring.="\$graph = new Graph(".$width.",".$height.");\r\n";
	
	$sourcestring.="//échelle\r\n";
	$sourcestring.="\$graph->SetScale('linlin',".$ymin.",".$ymax.",".$xmin.",".$xmax."); \r\n";
	$sourcestring.="\$graph->SetShadow();\r\n";

	$sourcestring.="//marges en pixel du graphic\r\n";
	$sourcestring.="\$graph->img->SetMargin(45,20,20,30);\r\n";
	$sourcestring.="\$graph->img->SetAntiAliasing(false);\r\n";

	//legende des axes

	$sourcestring.="\$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);\r\n";
	$sourcestring.="\$graph->xaxis->HideFirstTicklabel();\r\n";	
	$sourcestring.="\$graph->xaxis->HideTicks(false,false);\r\n";	
	$sourcestring.="\$graph->xaxis->SetColor('black');\r\n";
	$sourcestring.="\$graph->xaxis->SetFont(FF_FONT1,FS_NORMAL);\r\n";
	$sourcestring.="\$graph->xaxis->SetWeight(2);\r\n";
	$sourcestring.="\$graph->xaxis->SetMajTickPositions(array(".implode(",",$legenddaypos)."),array(".implode(",",$legendday)."));\r\n";
	$sourcestring.="\$graph->yaxis->title->Set('Pot. Restant');\r\n";
	$sourcestring.="\$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);\r\n";
	$sourcestring.="\$graph->yaxis->SetColor('black');\r\n";
	$sourcestring.="\$graph->yaxis->SetFont(FF_FONT1,FS_NORMAL);\r\n";
	$sourcestring.="\$graph->yaxis->SetWeight(2);\r\n";
	
	
// Create the plot line
	$sourcestring.="\$sp1 = new LinePlot(\$datay,\$datax);\r\n";
	$sourcestring.="\$sp1->SetFillColor('".$color."');\r\n";
	$sourcestring.="\$sp1->value->SetColor('black');\r\n";
	
	$sourcestring.="\$graph->Add(\$sp1);\r\n";

	$sourcestring.="\$graph->Stroke();\r\n";
	$sourcestring.="?>\r\n";

	$f_handle=fopen( $filename,'wb+');
	if (!$f_handle)
		{
		 $result->msg="Impossible d'ouvrir le fichier ".$filename." en écriture";
		}
	else
		{
			if (fputs($f_handle, $sourcestring) === FALSE) 
			{
			 $result->msg="Impossible d'écrire l'image dans le fichier ".$filename;
			}
		} 	
	fclose($f_handle);
	$result->data=$sourcestring;
	return $result;
}


?>