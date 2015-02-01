/**
 * Table Row Effect (JQuery version)
 * Date 01.02.2015
 * By swiss-webdesign
 */
$(document).ready(function() {
	/* Add mouse click event of all table rows */
	$('table tr').mousedown(function() {
		if (!$(this).hasClass('table_title')) {
			if ($(this).hasClass('tre-select')) {
				$(this).removeClass('tre-select');
			}
			else {
				$(this).addClass('tre-select');
			}
		}
	});
	
	/* Add mouse over event of all table rows */
	$('table tr').mouseover(function() {
		if (!$(this).hasClass('table_title')) {
			$(this).addClass('tre-hover');
		}
	});
	
	/* Add mouse out event of all table rows */
	$('table tr').mouseout(function() {
		if (!$(this).hasClass('table_title')) {
			$(this).removeClass('tre-hover');
		}
	});
});


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
$(document).ready(function() {
	var loadingImage = new Image();
	loadingImage.src = 'img/loading.gif';
});

function fileuploadStart() {
	popup = document.createElement('div');
	popup.className = 'popup';
	popup.innerHTML = '<img src="img/loading.gif" alt="" /><h1>Datei wird Übertragen</h1>Dieser Vorgang kann einige Minuten in Anspruch nehmen.';
	document.body.appendChild(popup);
}
