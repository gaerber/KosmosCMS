function __deprecated__clip(id) {
  if (document.getElementById(id).style.display == 'none') {
    document.getElementById(id).style.display = 'block';
  }
  else {
    document.getElementById(id).style.display = 'none';
  }
}


/**
 * Bestaetigung vor dem loeschen
 */
function confirmDeletion(link, nachricht) {
    var eingabe = confirm(nachricht)
    if(eingabe == true) {
        window.location.href = link;
    }
}


/**
 * Popup Fenster oeffnen
 */
function popup(url) {
    popupWindow = window.open(url, "Popupfenster", "width=580,height=320,menubar=no,titlebar=no,toolbar=no,resizable=no");
    if (popupWindow) {
    	return true;
   	}
    popupWindow.focus();
    return false;
}

/**
 * Relogin Check beim Abspeichern von Aenderungen
 */
var relogin_time_load = new Date();

function checkRelogin() {
	var relogin_time_submit = new Date();
	if (((relogin_time_submit.getTime() - relogin_time_load.getTime()) / 1000) > 600) {
		MyWindow=window.open('index.php?page=relogin','relogin','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=670,height=280');
		return false;
	}
	else {
		return true;
	}
}


/**
 * Wartefenster bei Dateiupload
 */
function fileuploadStart() {
	popup = document.createElement('div');
	popup.className = 'popup';
	popup.innerHTML = '<img src="img/loading.gif" alt="" /><h1>Datei wird Übertragen</h1>Dieser Vorgang kann einige Minuten in Anspruch nehmen.';
	document.body.appendChild(popup);
}


/**
 * Selektieren des ersten Feldes beim Login
 */
function autoFocus(){
	var error_control = document.getElementById('__error_control');
	if(error_control && error_control.value!='') {
		var element = document.getElementById(error_control.value);
		if( element && !element.disabled
				&& ((element.type=='text') || (element.type=='password'))) {
			var err_flag = 0;
			try {element.focus()}
			catch(err){err_flag=1};
			return true;
		}
	}

	if(document.forms.length){
		outer_loop:
		for(var i=0; i<document.forms.length; i++){
			for(var j=0; j<document.forms[i].elements.length; j++){
			var element = document.forms[i].elements[j];
				if (element){
					if((element.name == '_disable_autofocus')
							&& (element.type == 'hidden')
							&& (element.value == 'yes'))
						return false;

					if((element.name) && (element.type=='text')
							|| (element.type=='password')){
						if (!element.disabled){
							var err_flag = 0;
							try {element.focus()}
							catch(err){err_flag=1};
							if(!err_flag){
								element.focus();
								break outer_loop;
							}
						}
					}
				}
			}
		}
	}
}