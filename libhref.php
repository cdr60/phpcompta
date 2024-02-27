<?php

class HRefPost
{
  protected $url;
  protected $target;
  protected $title;
  protected $texte;
  protected $texteclass;
  protected $textestyle;
  protected $imgfile;
  protected $imgwidth;
  protected $imgheight;
  protected $imgclass;
  protected $imgstyle;
  protected $postdata;
	
  public function __construct ( $url ) 
{
    $this->url = $url;
	$this->target = "";
	$this->title = "";
	$this->texte = "";
	$this->texteclass = "";
	$this->textestyle = "";
	$this->imgfile = "";
	$this->imgwidth = "";
	$this->imgheight = "";
	$this->imgclass = "";
	$this->imgstyle = "";
	$this->postdata=array();
}

function GetUrl()
{
	return $this->url;
}
function SetUrl($url)
{
	$this->url=$url;
	return true;
}
function GetTarget()
{
	return $this->target;
}
function SetTarget($target)
{
	$this->target=$target;
	return true;
}
function GetTitle()
{
	return $this->title;
}
function SetTitle($title)
{
	$this->title=$title;
	return true;
}
/*************************************************/
function GetTexte()
{
	return $this->text;
}
function SetTexte($texte)
{
	$this->texte=$texte;
	return true;
}
function GetTexteClass()
{
	return $this->texteclass;
}
function SetTexteClass($texteclass)
{
	$this->texteclass=$texteclass;
	return true;
}
function GetTexteStyle()
{
	return $this->textestyle;
}
function SetTexteStyle($textestyle)
{
	$this->textestyle=$textestyle;
	return true;
}
/***********************************************/
function GetImgFile()
{
	return $this->imgfile;
}
function SetImgFile($imgfile)
{
	$this->imgfile=$imgfile;
	return true;
}
function GetImgClass()
{
	return $this->imgclass;
}
function SetImgClass($imgclass)
{
	$this->imgclass=$imgclass;
	return true;
}
function GetImgStyle()
{
	return $this->imgstyle;
}
function SetImgStyle($imgstyle)
{
	$this->imgstyle=$imgstyle;
	return true;
}
function GetImgWidth()
{
	return $this->imgwidth;
}
function SetImgWidth($imgwidth)
{
	$this->imgwidth=$imgwidth;
	return true;
}
function GetImgHeight()
{
	return $this->imgheight;
}
function SetImgHeight($imgheight)
{
	$this->imgheight=$imgheight;
	return true;
}
function GetIndPostData($name)
{
	$i=0;
	$found=false;
	while (($found===FALSE) and ($i<count($this->postdata)))
	{
		if (isset($this->postdata[$i]->NAME))
		{
			$found=($this->postdata[$i]->NAME==$name);
		}
		if ($found===FALSE) $i++;
	}
	if ($found===TRUE) return $i; else return -1;
}

function GetDataValue($name)
{
	$i=$this->GetIndPostData($name);
	if ($i<0) return "";
	if (!isset($this->postdata[$i]->VALUE)) return "";
	return $this->postdata[$i]->VALUE;
}

function GetDataId($name)
{
	$i=$this->GetIndPostData($name);
	if ($i<0) return "";
	if (!isset($this->postdata[$i]->ID)) return "";
	return $this->postdata[$i]->ID;
}

function AddData($name,$value="",$id="")
{
	if($name=="") return -1;
	$n=count($this->postdata);
	$this->postdata[$n]=new stdclass();
	$this->postdata[$n]->NAME=$name;
	$this->postdata[$n]->VALUE=$value;
	$this->postdata[$n]->ID=$id;
	return $n;
}

//ajouter un ensemble de données de type &a=1&b=2 etc.....
function AddString($str="",$id="")
{
	$r=0;
	if ($str=="") return -1;
	$tb=explode("&",$str);
	foreach($tb as $doublet)
	{
		$tv=explode("=",$doublet);
		$n=(isset($tv[0])?strval($tv[0]):"");
		$v=(isset($tv[1])?strval($tv[1]):"");
		if ($n!="") $r=$this->AddData($n,$v,$id);
	}
	return $r;
}

function RemoveData($name)
{
	if ($name=="") return -1;
	$i=$this->GetIndPostData($name);
//	echo("i=".$i."<br>");
	if ($i<0) return -1;
	array_splice($this->postdata,$i,1);
	return 1;
}

function SetDataValue($name,$value)
{
	if ($name=="") return -1;
	$i=$this->GetIndPostData($name);
	if ($i<0) $i=$this->AddData($name,$value,"");
	else
	{
		$this->postdata[$i]->VALUE=$value;
	}
	return $this->postdata[$i]->VALUE;
}

function SetDataId($name,$id)
{
	if ($name=="") return -1;
	$i=$this->GetIndPostData($name);
	if ($i<0) $i=$this->AddData($name,"",$id);
	else
	{
		$this->postdata[$i]->ID=$id;
	}
	return $this->postdata[$i]->VALUE;
}

//Construit une chaine de caractere au hasard
function MakeRandomString($max=6) 
{
    $i = 0; //Reset the counter.
    $possible_keys = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $keys_length = strlen($possible_keys);
    $str = ""; //Let's declare the string, to add later.
    while($i<$max) 
	{
        $rand = mt_rand(1,$keys_length-1);
        $str.= $possible_keys[$rand];
        $i++;
    }
    return $str;
}

//Construit un lien href n'exploitant que des méthodes post
//text peut contenir du texte ou un tag img
function MkHRefPost()
{
	$fname="form_".$this->MakeRandomString(4);
	$html="";
	$html.="\r\n<form style='display:none;' id='".$fname."' action='".$this->url."' method=POST ";
	$html.="target='".($this->target==""?"_self":$this->target)."'>\r\n";
	if (is_array($this->postdata))
	{
		foreach( $this->postdata as $d)
		{
			if ($d->NAME!="")
			{
               $html.="<input type='hidden' name=\"".$d->NAME."\" ";
			   $html.="value=\"".(isset($d->VALUE)?$d->VALUE:"")."\" ";
			    if (isset($d->ID)) {if ($d->ID!="") $html.="id=\"".$d->ID."\" ";}
			   $html.="></input>\r\n";
			}
		}
	}
	$html.="</form>\r\n";
	//$html.="<a href='#' ";
	$js="";
	if (($this->imgfile=="") and ($this->texte!="")) 
	{
		$html.="<a href='#' ";
	}
	else $html.="<span ";
	$html.=($this->title!=""?"title=\"".$this->title."\"":"")." ";
	$html.=($this->texteclass!=""?"class='".$this->texteclass."'":"")." ";
	$html.=($this->textestyle!=""?"style='".$this->textestyle."'":"")." ";
	$html.="onclick=\"document.getElementById('".$fname."').submit(); return false;\">";
	$html.=$this->texte;
	if ($this->imgfile!="")
	{
		$html.="<img ";
		$html.=($this->imgclass!=""?"class='".$this->imgclass."' ":"");
		if ($this->imgstyle=="") $this->SetImgStyle("border:none; cursor:pointer;");
		$html.=($this->imgstyle!=""?"style='".$this->imgstyle."' ":"");
		$html.=($this->imgwidth!=""?"width='".$this->imgwidth."px' ":"");
		$html.=($this->imgheight!=""?"height='".$this->imgheight."px' ":"");
		$html.="src='".$this->imgfile."'>";
	}
	if (($this->imgfile=="") and ($this->texte!="")) $html.="</a>";
	else $html.="</span>";
	$html.="\r\n";	
	return $html;
}
}
?>
