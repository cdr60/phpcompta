<?php
/****************************************
** titre          :: html mime mail class
** version        :: 1.0
** auteur         :: sebastien muller <shirker@ifrance.com>
** nom de fichier :: html_mime_mail.inc
** date           :: 05/06/2001
** last change	  :: 21/06/2001
** notes          :: developpee pour permettre l'envoi de mail au format html a partir de formulaire html
**		     permet plusieurs types d'encodage : base64 ou quoted printable
**		     et aussi les fichiers attaches, images embedded, html, ....
**
**		     implementation de la fonction send_to_c pour envoyer les messages
**		     pas par mail() mais par le c ISIONMAilSender073
**
**		     implementation de la taille maximale des fichiers pouvant etre
**		     inclus dans le mail
****************************************/
class html_mime_mail
{
	#-----------------------------------------------------------------------------
	# PROPRIETES
	#-----------------------------------------------------------------------------
	
	var $headers;		# tableau contenant toutes les en-tetes a envoyer
	var $html_images;	# tableau contenant des infos sur les images embedded
	var $att_files;		# tableau contenant des infos sur les fichiers attaches
	
	var $parts;		# tableau contenant toutes les parties du mail
	var $multipart;		# chaine avec toutes les parties du mail (sauf entete et boundary final)
	
	var $html;		# le texte au format html du mail (si presence de texte html)
	var $plain_text;	# le texte en ascii du mail (si format html => texte html non formate)
	var $mime;		# le message mime a envoyer
	
	var $charset;		# le jeu de caractere a utilise pour envoyer le mail (ex: us-ascii, iso-8859-1, ...)

	var $limit_up;		# la limite d'upload pour les images et les fichiers attaches (compris)
	var $msg_att_size;	# var interne a la classe pour savoir la taille de tous les fichiers attaches (images embedded comprises)
	
	
	#-----------------------------------------------------------------------------
	# METHODES PUBLIQUES
	#-----------------------------------------------------------------------------
	
	# CONSTRUCTION DU MAIL -------------------------------------------------------
	
	/*************************************
	** void html_mime_mail(int limit, string charset, string headers)
	** constructeur de la classe html_mime_mail, permet de creer une instance d'un mail
	** en initialisant correctement les variables de la classe
	**
	** entree:
	**	- $limit   : la taille maximale en octet que peut avoir les attachements du mail (images embedded compris) => par defaut 100ko
	**	- $charset : le type de jeu de caractere utilise tout au long du mail (ex: iso-8859-1, us-ascii, ...)
	**	- $headers : chaine contenant des entetes mimes supplementaires (separees par des \n)
	**
	** sortie:
	**	void
	*************************************/
	function __construct($limit=102400, $charset="iso-8859-1", $headers="")
	{
		$this->html_images = array();
		$this->att_files   = array();
		$this->headers     = array();
		$this->parts	   = array();
		
		$this->limit_up    = $limit;
		$this->msg_att_size= 0;

		$this->charset     = $charset;
		$this->html	   = "";
		$this->plain_text  = "";
		$this->multipart   = "";
	
		if(is_string($headers))
			$headers = explode("\n", trim($headers));
		
		for($i=0 ; $i<count($headers) ; $i++)
		{
			if($headers[$i]!="")
				$this->headers[] = $headers[$i];
		}
	}
	
	/*************************************
	** boolean add_img(string fichier, string nom, string c_type)
	** permet d'ajouter une image affichee dans le corps du mail (embedded)
	**
	** entree:
	**	- $fichier : le contenu de l'image a attacher => buffer du fread(fopen("img_file.gif", "r"), buffer)
	**	- $nom     : nom original de l'image => basename(img_file)
	**	- $c_type  : type mime de l'image (ex: image/gif, image/jpg, image/pjpeg)
	**
	** sortie:
	**	true en cas de reussite de l'attachement, false si erreur ... (limite autorisee depassee)
	*************************************/
	function add_img($fichier, $nom, $c_type="application/octet-stream")
	{
		$rtval = true;
		# test de la taille maximale pouvant etre contenue dans le mail
		if(($this->msg_att_size + strlen($fichier)) > $this->limit_up)
		{	# erreur
			printf("<font color=\"#ff0000\"><b>error</b></font> :: cannot attach <b>%s</b> (limited to 100ko/mail) !!!<br>\n", $nom);
			$rtval = false;
		}
		else
		{	# on attache l'image et met a jour la nouvelle taille du mail
			$this->msg_att_size = $this->msg_att_size + strlen($fichier);
			$this->html_images[] = array(
		                       "contenu" => $fichier, 
		                       "nom"     => $nom,
		                       "c_type"  => $c_type,
		                       "cid"     => md5(uniqid(time()))
		                       );
		}
		
		return($rtval);
	}
	
	/*************************************
	** boolean add_attach(string fichier, string nom, string c_type)
	** permet d'attacher un fichier au mail
	**
	** entree:
	**	- $fichier : le contenu du fichier a attacher => buffer du fread(fopen("att_file.xxx", "r"), buffer)
	**	- $nom     : nom du fichier a attacher => basename(att_file)
	**	- $c_type  : type mime du fichier (si inconnu, alors 'application/octet-stream' convient tres bien)
	**
	** sortie:
	**	true en cas de reussite de l'attachement, false si erreur ... (limite autorisee depassee)
	*************************************/
	function add_attach($fichier, $nom, $c_type="application/octet-stream")
	{
		$rtval = true;
		# test de la taille maximale pouvant etre contenue dans le mail
		if(($this->msg_att_size + strlen($fichier)) > $this->limit_up)
		{	# erreur
			printf("<font color=\"#ff0000\"><b>error</b></font> :: cannot attach <b>%s</b> (limited to 100ko/mail) !!!<br>\n", $nom);
			$rtval = false;
		}
		else
		{	# on attache le fichier et met a jour la nouvelle taille du mail
			$this->msg_att_size = $this->msg_att_size + strlen($fichier);
			$c_type = ($c_type=="")?"application/octet-stream":$c_type;
			$this->att_files[] = array(
				     "contenu" => $fichier,
				     "nom"     => $nom,
				     "c_type"  => $c_type
				     );
		}
		
		return($rtval);
	}	
	
	/*************************************
	** void add_body(string text)
	** permet d'ajouter le contenu-texte du mail (html ou non)
	** le texte entre est stocke deux fois dans le mail :
	**	- normal : pour le contenu text/html
	**	- plain  : pour le contenu text/plain -> on vire les balises html pour que si le
	**	client mail n'est pas compatible html il affiche un texte <un peu> comprehensible
	**
	** A LANCER APRES L'AJOUT DE TOUTES LES IMAGES CONTENUES DANS LE CORPS DU MAIL => add_img()
	** (a cause du remplacement de la source de l'image par le cid)
	**
	** entree:
	**	- $text : le texte (ou code html) a mettre dans le corps du mail
	**
	** sortie:
	**	void
	*************************************/
	function add_body($text)
	{
		# a cause de eudora ! (plante si le dernier tag est une image)
		$this->html = sprintf("%s<!-- -->", $text);
		
		# on remplace les images du corps du mail par leurs cid
		if((is_array($this->html_images)) && (count($this->html_images)>0))
		{
			for($i=0 ; $i<count($this->html_images) ; $i++)
			{
				$cid_img = sprintf("cid:%s", $this->html_images[$i]["cid"]);
				$this->html = preg_replace("@".$this->html_images[$i]["nom"]."@i", $cid_img, $this->html);
			}
		}
			
		# on vire les attributs html ...
		$img_files        = $this->detect_img_file_in_tag($this->html);
		$plained          = $this->switch_img_tag($this->html, $img_files);
		$this->plain_text = strip_tags($plained);
	}
	
	/*************************************
	** void build_mail(string encode, int priorite)
	** permet de coder correctement le mail en fonction de son contenu
	** (presence image, fichier attache, ...) -> creation du mail au format mime
	** a appeler avant l'envoi du mail ...
	**
	** entree:
	**	- $encode   : type d'encodage (Content-Transfer-Encoding) du html et du plain text (peut etre soit base64 soit quoted printable)
	**	- $priorite : priorite de l'email (entre 1 et 5) 1 etant le plus important ... si chaine vide alors pas de definition de priorite pour le mail ...
	**
	** sortie:
	**	void
	*************************************/
	function build_mail($encode="base64", $priorite="")
	{
		$boundary = sprintf("=_FIRST_%s", md5(uniqid(time())));
		
		# entete du mail
		$this->headers[] = sprintf("MIME-Version: 1.0");
		
		$this->headers[] = "X-Mailer: ISION Mail Sender";
						
		if($priorite!="")
			$this->headers[] = sprintf("X-Priority: %d", $priorite);
		
		# on check le type mime a utiliser
		if(count($this->att_files) > 0)
			$this->headers[] = sprintf("Content-Type: multipart/mixed;\n\tboundary=\"%s\"", $boundary);
		else
		{
			if(count($this->html_images) > 0)
				$this->headers[] = sprintf("Content-Type: multipart/related;\n\ttype=\"multipart/alternative\";\n\tboundary=\"%s\"", $boundary);
			else
				$this->headers[] = sprintf("Content-Type: multipart/alternative;\n\tboundary=\"%s\"", $boundary);
		}
		
		# avant le corps
		$this->multipart = "Ceci est un message encode au format MIME.\n\n";
		
		# on construit le html (si il faut)
		$this->build_html($boundary, $encode);
		
		# les fichiers attaches
		for($i=0 ; $i<count($this->att_files) ; $i++)
			$this->multipart .= sprintf("--%s\n%s", $boundary, $this->build_attach($i));
		
		# on fini le mail
		$this->mime = sprintf("%s--%s--\n", $this->multipart, $boundary);
	}
	
	# ENVOI DU MAIL --------------------------------------------------------------
	
	/*************************************
	** void send_to_c(string from_mail, string from_name, string subject, string file_name)
	** permet de formater le mail afin qu'il soit comprehensible du ISIONMailSender073.c (envoi de mail en rafale)
	** et ecrit le mail dans le fichier passe en parametre
	**
	** entree:
	**	- $from_mail : adresse e-mail de l'expediteur
	**	- $from_name : nom de l'expediteur
	**	- $subject   : sujet du mail
	**	- $file_name : nom du fichier ou ecrire le mail (~/mailersender_files/messages/*)
	**
	** sortie:
	**	void
	*************************************/
	function send_to_c($from_mail, $from_name="", $subject="", $file_name="")
	{
		# on formate le mail pour le message
		$to_write = sprintf("[#FROM]\n\"%s <%s>\"\n[#ADMIN]\n\n[#SUJET]\nSubject: %s\n", $from_name, $from_mail, $subject);
		for($i=0 ; $i<count($this->headers) ; $i++)
			$to_write .= sprintf("%s\n", $this->headers[$i]);
		$to_write .= sprintf("[/#SUJET]\n[#CORPS]\n%s[/#CORPS]\n", $this->mime);
		
		# on ecrit le fichier dans /usr/local/php/mailersender_files/messages/
		$fd = fopen($file_name, "w");
		fwrite($fd, $to_write);
		fclose($fd);
	}
	
	/*************************************
	** void send(string from_name, string from_mail, string to_name, string to_mail, string subject)
	** permet de formater le mail et de l'envoyer par la fonction php -> mail()
	**
	** entree:
	**	- $from_mail : adresse e-mail de l'expediteur
	**	- $to_mail   : adresse e-mail du destinataire
	**	- $subject   : sujet du mail
	**
	** sortie:
	**	void
	*************************************/
	function send($from_mail,$to_mail, $subject)
	{
		$rtval= true;
		
		# on check la validite du mail cible
		if(!$this->check_mail($to_mail))
			$rtval = false;
		# formate les noms - mails
		$to = $to_mail;
		$from = $from_mail;
		
		# formate les headers
		$f_headers = sprintf("From: %s\n", $from);
		for($i=0 ; $i<count($this->headers) ; $i++)
			$f_headers .= $this->headers[$i]."\n";
		
		# envoi du mail
                $rtval=mail($to, $subject, $this->mime, $f_headers,"-f".$from);
                return($rtval);
	}
	
	/*************************************
	** string get_rfc(string from_name, string from_mail, string to_name, string to_mail, string subject)
	** permet de formater le mail et de le retourner selon le rfc822 ...
	** (pour par exemple joindre un mail en tant que fichier attache d'un autre, c'est le format
	** message/rfc822 qui est pris en compte)
	**
	** entree:
	**	- $to_mail   : adresse e-mail du destinataire
	**	- $subject   : sujet du mail
	**
	** sortie:
	**	le mail formate selon le type mime message/rfc822
	*************************************/
	function get_rfc822($from_mail, $to_mail, $subject)
	{
		# la date dans le format rfc822
		$d = sprintf("Date: %s", date("D, d M y H:i:s"));
		
		# on formate les noms - mails
		$to = sprintf("To: %s", $to_mail);
		$from = sprintf("From: %s", $from_mail);
		# mise en place du sujet
		if(is_string($subject))
			$sujet = sprintf("Subject: %s", $subject);
		
		# le message au format rfc822
		$rfc822 = sprintf("%s\n%s\n%s\n%s\n%s\n\n%s", $d, $from, $to, $sujet, implode("\n", $this->headers), $this->mime);
		return($rfc822);
	}
	
	
	#-----------------------------------------------------------------------------
	# METHODES PRIVEES (appelees par les methodes publiques de la classe)
	#-----------------------------------------------------------------------------
	
	# CONCERNANT LE CODAGE MIME --------------------------------------------------
	
	/*************************************
	** string build_attach(int id)
	** renvoie la chaine de caractere contenant le codage mime du {$id}ieme fichier
	** etant contenu dans le tableau des fichiers attaches
	**
	** entree:
	**	- $id : identifiant du tableau $this->att_files ...
	**
	** sortie:
	**	la chaine de caractere contenant le codage mime et le fichier attache en base64
	*************************************/
	function build_attach($id)
	{
		# l'encodage
		$msg_part = sprintf("Content-Type: %s", $this->att_files[$id]["c_type"]);
		
		# le nom du fichier
		if($this->att_files[$id]["nom"]!="")
			$msg_part .= sprintf(";\n\tname=\"%s\"\n", basename($this->att_files[$id]["nom"]));
		else
			$msg_part .= "\n";
		
		# on inclus le fichier en base 64
		$msg_part .= "Content-Transfer-Encoding: base64\n";
		$msg_part .= sprintf("Content-Disposition: attachment;\n\tfilename=\"%s\"\n\n", basename($this->att_files[$id]["nom"]));
		$msg_part .= sprintf("%s\n", chunk_split(base64_encode($this->att_files[$id]["contenu"])));

		return($msg_part);
	}

	/*************************************
	** void build_images(int id)
	** complete le mail avec la chaine de caractere contenant le codage mime de la {$id}ieme
	** images etant contenu dans le tableau des images attachees dans le corps du mail
	** (cad complete le mail par le codage mime de l'image et l'image en base64)
	**
	** entree:
	**	- $id : identifiant du tableau $this->html_images ...
	**
	** sortie:
	**	void
	*************************************/
	function build_images($id)
	{
		if($this->html_images[$id]["nom"]=="")
			$mime_name = "\n";
		else
			$mime_name = sprintf(";\n\tname=\"%s\"\n", basename($this->html_images[$id]["nom"]));
		
		$this->multipart .= sprintf("Content-Type: %s%s", $this->html_images[$id]["c_type"], $mime_name);
		$this->multipart .= sprintf("Content-Transfer-Encoding: base64\nContent-ID: <%s>\n", $this->html_images[$id]["cid"]);
		$this->multipart .= sprintf("Content-Disposition: inline;\n\tfilename=\"%s\"\n\n", basename($this->html_images[$id]["nom"]));
		
		$this->multipart .= sprintf("%s", chunk_split(base64_encode($this->html_images[$id]["contenu"])));
	}
	
	/*************************************
	** void build_html(string orig_bound, string encode)
	** complete le mail avec toute la partie html/plain + images embedded
	** detecte le type d'encodage en fonction du contenu du mail (presence ou non images/fichiers)
	**
	** entree:
	**	- $orig_bound : le premier boundary (delimiteur) qu'on recupere dans build_mail()
	**	- $encode     : le type d'encodage defini dans build_mail() :: cad "base64" ou "quoted printable"
	**
	** sortie:
	**	void
	*************************************/
	function build_html($orig_bound, $encode)
	{
		# on attribue les deux boundarys pouvant etre utilise
		$sec_bound = sprintf("=_SECOND_%s", md5(uniqid(time())));
		$thi_bound = sprintf("=_THIRD_%s", md5(uniqid(time())));
		
		# detecte le type d'encodage :: base64 ou quoted printable
		switch($encode)
		{
			case "base64":
				$encoded_plain = chunk_split(base64_encode($this->plain_text));
				$encoded_html  = chunk_split(base64_encode($this->html));
				break;
			case "quoted printable":
				$encoded_plain = $this->encode_header($this->plain_text);
				$encoded_html  = $this->encode_header($this->html);
				break;
			default:
				# par defaut :: base64
				$encode = "base64";
				$encoded_plain = chunk_split(base64_encode($this->plain_text));
				$encoded_html  = chunk_split(base64_encode($this->html));
		}
		
		# encodage mime
		if(count($this->att_files) > 0)
		{
			if(count($this->html_images) > 0)
			{
				// c'est un message mixed avec un related puis un alternative
				$this->multipart .= sprintf("--%s\nContent-Type: multipart/related;\n\ttype=\"multipart/alternative\";\n\tboundary=\"%s\"\n\n", $orig_bound, $sec_bound);
				$this->multipart .= sprintf("--%s\nContent-Type: multipart/alternative;\n\tboundary=\"%s\"\n\n", $sec_bound, $thi_bound);
				$this->multipart .= sprintf("--%s\nContent-Type: text/plain;\n\tcharset=\"%s\";\n\tformat=\"flowed\"\nContent-Transfer-Encoding: %s\n\n%s\n", $thi_bound, $this->charset, $encode, $encoded_plain);
				$this->multipart .= sprintf("--%s\nContent-Type: text/html;\n\tcharset=\"%s\"\nContent-Transfer-Encoding: %s\n\n%s\n--%s--\n\n", $thi_bound, $this->charset, $encode, $encoded_html, $thi_bound);
				
				# on construit les images
				for($i=0 ; $i<count($this->html_images) ; $i++)
				{
					$this->multipart .= sprintf("--%s\n", $sec_bound);
					$this->build_images($i);
				}
				$this->multipart .= sprintf("\n--%s--\n\n", $sec_bound);
			}
			else
			{
				// c'est un message mixed avec un alternative
				$this->multipart .= sprintf("--%s\nContent-Type: multipart/alternative;\n\tboundary=\"%s\"\n\n", $orig_bound, $sec_bound);
				$this->multipart .= sprintf("--%s\nContent-Type: text/plain;\n\tcharset=\"%s\";\n\tformat=\"flowed\"\nContent-Transfer-Encoding: %s\n\n%s\n", $sec_bound, $this->charset, $encode, $encoded_plain);
				$this->multipart .= sprintf("--%s\nContent-Type: text/html;\n\tcharset=\"%s\"\nContent-Transfer-Encoding: %s\n\n%s\n--%s--\n\n", $sec_bound, $this->charset, $encode, $encoded_html, $sec_bound);
			}
		}
		else
		{
			if(count($this->html_images) > 0)
			{
				// c'est un message related et un alternative
				$this->multipart .= sprintf("--%s\nContent-Type: multipart/alternative;\n\tboundary=\"%s\"\n\n", $orig_bound, $sec_bound);
				$this->multipart .= sprintf("--%s\nContent-Type: text/plain;\n\tcharset=\"%s\";\n\tformat=\"flowed\"\nContent-Transfer-Encoding: %s\n\n%s\n", $sec_bound, $this->charset, $encode, $encoded_plain);
				$this->multipart .= sprintf("--%s\nContent-Type: text/html;\n\tcharset=\"%s\"\nContent-Transfer-Encoding: %s\n\n%s\n--%s--\n\n", $sec_bound, $this->charset, $encode, $encoded_html, $sec_bound);
				
				# on construit les images
				for($i=0 ; $i<count($this->html_images) ; $i++)
				{
					$this->multipart .= sprintf("--%s\n", $orig_bound);
					$this->build_images($i);
				}
			}
			else
			{
				// c'est un alternative
				$this->multipart .= sprintf("--%s\nContent-Type: text/plain;\n\tcharset=\"%s\";\n\tformat=\"flowed\"\nContent-Transfer-Encoding: %s\n\n%s\n", $orig_bound, $this->charset, $encode, $encoded_plain);
				$this->multipart .= sprintf("--%s\nContent-Type: text/html;\n\tcharset=\"%s\"\nContent-Transfer-Encoding: %s\n\n%s\n--%s--\n\n", $orig_bound, $this->charset, $encode, $encoded_html, $orig_bound);
			}
		}
	}

	# OUTILS ---------------------------------------------------------------------
	
	/*************************************
	** string encode_header(string str)
	** permet d'encoder une chaine (header ou corps du mail) selon le rfc1522
	** pour le cas ou il y a des textes 8-bit qui doivent etre encodes ...
	**
	** entree:
	**	- $str : la chaine de caractere a encoder
	**
	** sortie:
	**	la chaine de caractere encodee
	*************************************/
	function encode_header($str)
	{
		# encode seulement si 8-bit ou ?=
		if (ereg("([\200-\377]|=\\?)", $str))
		{
			# d'abord les caracteres speciaux
			$str = str_replace("=", "=3D", $str);
			$str = str_replace("?", "=3F", $str);
			$str = str_replace("_", "=5F", $str);
			$str = str_replace(" ", "_", $str);
			for ($ch=127 ; $ch<=255 ; $ch++)
			{
				$replace = chr($ch);
				$insert  = sprintf("=%02X", $ch);
				$str     = str_replace($replace, $insert, $str);
			}
			
			# formatage selon le rfc1522
			$newstring = "=?".$this->charset."?Q?".$str."?=";
			$str = $newstring;
		}
		
		return($str);
	}
	
	/*************************************
	** boolean chek_mail(string adr)
	** permet de tester la validite de l'adresse email entree en parametre
	**
	** entree:
	**	- $adr : l'adresse email a checker
	**
	** sortie:
	**	true si l'adresse email est correcte, sinon false
	*************************************/
	function check_mail($adr)
	{
		return(preg_match("&^[_a-z0-9-]+(\.[_a-z0-9-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+[a-z]{2}[mtgvu]?$&", $adr));
		
	}
	
	/*************************************
	** array detect_img_file_in_tag(string code_html)
	** renvoie les chemins d'acces aux images contenues dans les tags html <img>
	**
	** entree:
	**	- $code_html : le code html ou l'on doit recuperer les fichiers sources des tags <img>
	**
	** sortie:
	**	array[string] (un tableau de chaines de caracteres)
	** 	contenant les chemins d'acces aux images contenues dans le corps html
	*************************************/
	function detect_img_file_in_tag($code_html)
	{
		$detected_tags  = array();
		$detected_files = array();
		
		# on recupere tous les tags img
		# le premier element du tableau n'en contient pas ...
		$detected_tags = explode("<img", $code_html);


		for($i=1 ; $i<count($detected_tags) ; $i++)
		{
			//eregi("src=(.*).*(>)", $detected_tags[$i], $src);
			preg_match("@src=(.*).*(>)@i", $detected_tags[$i], $src);
			# et la on recupere le nom de fichier cad le premier espace ou la premiere cote
			# selon qu'il y ait ou non une cote ...
			if(substr($src[1], 0, 1) == "\"")
			{
				$offset = strpos($src[1], "\"", 1);
				$detected_files[] = substr($src[1], 1, $offset-1);
			}
			else
			{
				# d'abord tout le tag img
				$offset = strpos($src[1], ">");
				if ($offset!==FALSE) $tmp = substr($src[1], 0, $offset);
				else $tmp=$src[1];
				# et apres juste un autre parametre (<img src="test.jpg" alt"test">)
				$offset = strpos($tmp, " ");
				if ($offset!==FALSE)  $tmp = substr($src[1], 0, $offset);
				
				$detected_files[] = $tmp;
			}
		}
		return($detected_files);
	}

	/*************************************
	** string switch_img_tag(string code_html, array tab_image)
	** permet de remplacer les tags html <img> en information de presence images
	** par ex: <img src="test.jpg"> sera transforme en [img :: test.jpg]
	** utilise pour transformer un code html en plain texte ... avant un strip_tags()
	**
	** entree:
	**	- $code_html : le code_html a modifier
	**	- $tab_image : un tableau de nom d'image classe dans le meme ordre d'apparition que celui des tags <img> du $code_html
	**		       (le tableau renvoye par detect_img_file_in_tag)
	**
	** sortie:
	**	le $code_html modifie avec le changement de tags <img>
	*************************************/
	function switch_img_tag($code_html, $tab_image)
	{
		$detected_tags  = array();
		
		# on recupere tous les tags img
		# le premier element du tableau n'en contient pas ...
		$detected_tags = explode("<img", $code_html);
		$rtval = $detected_tags[0];

		for($i=1 ; $i<count($detected_tags) ; $i++)
		{
			$offset = strpos($detected_tags[$i], ">");
			$rtval .= substr_replace($detected_tags[$i], sprintf("[img :: %s]", $tab_image[$i-1]), 0, $offset+1);
		}
		
		return($rtval);
	}

	/*************************************
	** array detect_local_img_file_in_tag(string code_html)
	** pour detecter les images contenus dans les tags html <img> du code
	** et ne se trouvant pas sur le reseau (cad : ni sur ftp ni sur http)
	** afin de les uploader {et/ou} attaches ensuite sur le serveur avant le mail
	**
	** entree:
	**	- $code_html : le code html ou on doit detecter les images en local
	**
	** sortie:
	**	array[string] (un tableau de chaines de caracteres)
	**	contenant les chemins d'acces aux images situees en local
	*************************************/
	function detect_local_img_file_in_tag($code_html)
	{
		$detected_files = array();
		$detected_file  = $this->detect_img_file_in_tag($code_html);
		
		# on check si les fichiers sont en local ou non
		for($i=0 ; $i<count($detected_file) ; $i++)
		{
			if((substr($detected_file[$i], 0, 4) != "http") && (substr($detected_file[$i], 0, 3) != "ftp"))
				$detected_files[] = $detected_file[$i];
		}
		
		return($detected_files);
	}
}
?>