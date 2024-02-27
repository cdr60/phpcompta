//Topage écriture

//Pad given value to the left with "0"
function AddZero(num) {
    return (num >= 0 && num < 10) ? "0" + num : num + "";
}

function RemoveThousandSeparator(number)
{
	let ThousandSeparator= " ";
	return number.split(ThousandSeparator).join("");
}

function AddThousandSeparatorSeperator(nStr)
{
  let ThousandSeparator= " ";
  nStr += '';
  x = nStr.split('.');
  x1 = x[0];
  x2 = x.length > 1 ? '.' + x[1] : '';
  var rgx = /(\d+)(\d{3})/;
  while (rgx.test(x1)) {
    x1 = x1.replace(rgx, '$1' + ThousandSeparator + '$2');
  }
  return x1 + x2;
}

function top_ecriture(imgid,modifiedrow,idecr,montant,email) 
{
	let SP=document.getElementById('SOLDE_APRES_POINTE');
	let IMG=document.getElementById(imgid);
	let USERNAME=document.getElementById('USERMODIFYING');
	let ROWMODIFIEDBY=document.getElementById(modifiedrow);
	let sens=1;
	if ((IMG!=undefined) && (SP!=undefined) && (USERNAME!=undefined) &&(ROWMODIFIEDBY!=undefined) && (montant!='') && (email!=''))
	{
		
		let srcimg=IMG.src;
		let basename=srcimg.replace(/^.*(\\|\/|\:)/, '');
		//console.log(basename);
		if (basename=="unchecked_checkbox.png")
		{
			sens=1;
			srcimg=srcimg.replace("unchecked_checkbox.png", "checked_checkbox.png");
		}
		else
		{
			sens=-1;
			srcimg=srcimg.replace("checked_checkbox.png", "unchecked_checkbox.png");
		}
		
		var indata = { ID:idecr, EMAIL:email};
		var err=0;
		fetch('service.php', { method: 'POST',headers: {'Content-type': 'application/x-www-form-urlencoded; charset=UTF-8'},  body: Object.entries(indata).map(([k,v])=>{return k+'='+v}).join('&') })
		.then(function(response) 
		{
			if (response.status >= 200 && response.status < 300)
			{
				return response.text()
			}
			else { err=1; }
		})
		.then(function(response)
		{
				if (err!=0) {	alert("Une erreur s'est produite\r\nCode "+err); }
		}
		)
		if (err==0)
		{
			IMG.src=srcimg;
			
			SP.textContent=AddThousandSeparatorSeperator((parseFloat(RemoveThousandSeparator(SP.textContent))+sens*parseFloat(RemoveThousandSeparator(montant))).toFixed(2));
			 var now = new Date();
 			 var dt=[[AddZero(now.getDate()), AddZero(now.getMonth() + 1), now.getFullYear()].join("/"), [AddZero(now.getHours()), AddZero(now.getMinutes()), AddZero(now.getSeconds())].join(":"), ].join(" à ");
			ROWMODIFIEDBY.innerHTML="Par "+USERNAME.textContent+"<br/>le "+dt;
		}
	}
}