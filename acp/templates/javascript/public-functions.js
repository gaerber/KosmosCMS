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
