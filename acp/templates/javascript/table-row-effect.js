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
