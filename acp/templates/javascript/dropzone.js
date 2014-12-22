$(document).ready(function() {

	// Makes sure the dataTransfer information is sent when we
	// Drop the item in the drop box.
	jQuery.event.props.push('dataTransfer');

	// Get all of the remaining data URIs and put them in an array
	var dataArray = [];

	// jQuery on an empty object, we are going to use this as our queue
	var dataQueue = $({});

	// Counter of all droped files
	var dropedFilesCtr = 0;
	// Counter of all upladed files
	var uploadedFilesCtr = 0;
	// Boolean with first transaction
	var firstTransaction = true;

	// Switch to the drag and drop file uploader if javascript is enabled and the user has chaisee
	if ($('#showNewForm').val() == 1) {
		$('#dropzone').addClass('form_acp_fileupload');
		$('#dropzone input.input_file').parent().css({'display' : 'none'});
		$('#dropzone input.input_btn').parent().css({'display' : 'none'});
		$('#dropzone input.input_file').attr('multiple', 'multiple');
	}

	// User can chaise the old version
	$('#drop-files div.fallback').bind('click', function(e) {
		e.stopPropagation();
		$('#dropzone').removeClass('form_acp_fileupload');
		$('#dropzone input.input_file').parent().css({'display' : 'block'});
		$('#dropzone input.input_btn').parent().css({'display' : 'block'});
		$('#dropzone input.input_file').removeAttr('multiple');
		$('#dropzone div.drop-infos').css({'display' : 'none'});

		// Set the user choises for the next upload
		$('#showNewForm').val(0);
	});

	// Bind the click event to the dropzone.
	$('#drop-files').bind('click', function(e) {
		$('#dropzone input.input_file').click();
	});

	// Bind the on change event of the clickable version
	$('#dropzone input.input_file').bind('change', function(e) {
		if ($('#showNewForm').val() == 1) {
			transferFiles($('#dropzone input.input_file').get(0).files);
			// Reset the file form
			$('#dropzone input.input_file').replaceWith($('#dropzone input.input_file').clone(true));
		}
	});

	// Bind the drop event to the dropzone.
	$('#drop-files').bind('drop', function(e) {
		// Stop the default action, which is to redirect the page
		// To the dropped file
		e.preventDefault();
		transferFiles(e.dataTransfer.files);
	});

	// Event handler to transfer the droped or selected files
	function transferFiles(files) {

		var isErrorMsgSent = false;

		// For each file
		$.each(files, function(index, file) {
			// TODO Read checked files types from form
			var attrAccept = $('#dropzone input.input_file').attr('accept');
			if (attrAccept && attrAccept.match('image.*')) {
				if (!files[index].type.match(attrAccept)) {
					alert('Der Datentype ' + file.type + ' ist nicht erlaubt. Es können nur Bilder vom Type ' + attrAccept + ' hochgeladen werden.');
					return false;
				}
			}

			if (firstTransaction) {
				// Adding the informations box
				$('#dropzone').append('<div class="drop-infos"><div id="drop-progress"><div class="slider-bar slider-bar-animated"> </div><p> </p></div><div id="dropped-files"></div><p class="form_end"></p></div>');
				firstTransaction = false;
			}

			// Start a new instance of FileReader
			var fileReader = new FileReader();

				// When the filereader loads initiate a function
				fileReader.onload = (function(file) {

					return function(e) {
						// Invrement the dropped files counter
						dropedFilesCtr++;

						if ('size' in file) {
							var fileSize = file.size;
						}
						else {
							var fileSize = file.fileSize;
						}

						// Show the preview
						if (file.type.match('image/*')) {
							$('#dropped-files').append('<div id="previewid_'+dropedFilesCtr+'" class="waiting"><div class="image" style="background:url('+this.result+');background-size:cover;"></div><div class="progress">'+BinaryMultiples(fileSize)+'</div><div class="filename">'+file.name+'</div></div>');
						}
						else {
							var preview_image = 'img/filetypes/' + file.name.split('.').pop().toLowerCase() + '.png';

							$('<img/>').attr('src', preview_image).load({preview_id:dropedFilesCtr}, function(event) {
								$('#previewid_'+event.data.preview_id+' div.image').css('background-image', 'url('+preview_image+')');
								$(this).remove(); // prevent memory leaks
							});

							$('#dropped-files').append('<div id="previewid_'+dropedFilesCtr+'" class="waiting"><div class="image" style="background:url(\'img/filetypes/default.png\');background-size:contain;background-position:center;background-repeat:no-repeat;"></div><div class="progress">'+BinaryMultiples(fileSize)+'</div><div class="filename">'+file.name+'</div></div>');
						}

						// Push the file data into a queue
						$.dataQueue({name : file.name, rawdata : file, queueid : dropedFilesCtr});

						// Update the progress bar
						updateProgressBar(uploadedFilesCtr, dropedFilesCtr);
					};

				})(files[index]);

			// For data URI purposes
			fileReader.readAsDataURL(file);

		});
	}

	$.dataQueue = function(param) {
		// Queue our ajax request.
		dataQueue.queue(function(next) {
			// Display the uploading file
			$('#previewid_'+param.queueid).removeClass('waiting');
			$('#previewid_'+param.queueid).addClass('uploading');

			// Preparing the file upload
			var formData = new FormData();
			var formNameOfFile = "defaultFile";
			$.each($('#dropzone input'), function() {
				if (this.type == 'file') {
					formNameOfFile = this.name;
				}
				else if (this.type == 'checkbox') {
					if (this.checked) {
						formData.append(this.name, this.value);
					}
				}
				else {
					formData.append(this.name, this.value);
				}
			});
			formData.append(formNameOfFile, param.rawdata);

			// Upload file with ajax
			$.ajax({
				url: $('#dropzone').attr('action'),  //Server script to process data
				type: $('#dropzone').attr('method'),
				headers: { 'Connection' : 'close' },
				data: formData,
				cache: false,
				processData: false, // Don't process the files
				contentType: false, // Set content type to false as jQuery will tell the server its a query string request
				timeout: 60000,

				//Ajax events
				success : function(d) {
					//alert('New Complete: '+d);

					if ($('div.box', d).hasClass('ok')) {
						$('#previewid_'+param.queueid).removeClass('uploading');
						$('#previewid_'+param.queueid).addClass('uploaded');
					}
					else {
						var rawtext = $('div.box h1', d).text();
						if (rawtext == '') {
							rawtext = 'Formular Fehler';
						}
						$('#previewid_'+param.queueid).removeClass('uploading');
						$('#previewid_'+param.queueid).addClass('aborted');
						$('#previewid_'+param.queueid).append('<div class="error-message">'+rawtext+'</div>');

						showFailedFiles(param.quereid, param.name);
					}

					// Update the progress bar
					uploadedFilesCtr++;
					updateProgressBar(uploadedFilesCtr, dropedFilesCtr);

					//next();
					// I found no other way to make it asynchron working than with a timeout
					setTimeout(next, 5000);
				},
				error : function(d) {
					$('#previewid_'+param.queueid).removeClass('uploading');
					$('#previewid_'+param.queueid).addClass('aborted');
					$('#previewid_'+param.queueid).append('<div class="error-message">HTTP Error</div>');

					// Update the progress bar
					uploadedFilesCtr++;
					updateProgressBar(uploadedFilesCtr, dropedFilesCtr);

					showFailedFiles(param.quereid, param.name);

					//next();
					// I found no other way to make it asynchron working than with a timeout
					setTimeout(next, 500);
				}
			});

		});

	};

	// Show the failed files
	function showFailedFiles(id, name) {
		// Show the malfunction by setting the progress bar red
		$('#drop-progress div').css({'background-color' : '#c14e39'});
	}


	function updateProgressBar(loaded, total) {
		var percent = (loaded / total) * 100;

		if (percent > (100 * $('#drop-progress div').width() / $('#drop-progress div').offsetParent().width())) {
			// Animate the rising progress
			if (loaded == total) {
				$('#drop-progress div').animate({'width' : (percent)+'%'}, 500, function() {
					$(this).removeClass('slider-bar-animated');
				});
			}
			else {
				$('#drop-progress div').animate({'width' : (percent)+'%'}, 500);
			}
		}
		else {
			// New files are added
			$('#drop-progress div').width((percent)+'%');
			$('#drop-progress div').addClass('slider-bar-animated');
		}

		// Show the new text
		if (total == 1) {
			$('#drop-progress p').text(loaded+' / '+total+' Datei hochgeladen');
		}
		else {
			$('#drop-progress p').text(loaded+' / '+total+' Dateien hochgeladen');
		}
	}

	// Just some styling for the drop file container.
	$('#drop-files').bind('dragenter', function() {
		$(this).addClass('dropping');
		return false;
	});

	$('#drop-files').bind('drop', function() {
		$(this).removeClass('dropping');
		return false;
	});

	$('#drop-files').bind('dragleave', function() {
		$(this).removeClass('dropping');
		return false;
	});

	function BinaryMultiples(size) {
		var norm = ['B', 'kB', 'MB', 'GB'];
		for (var i=0; size >= 1024 && i < norm.length-1; i++) {
			size /= 1024;
		}

		// Number format with 3 significant numerics
		if (size >= 100) {
			size = Math.round(size);
		}
		else if (size >= 10) {
			size = Math.round(size * 10) / 10;
		}
		else {
			size = Math.round(size * 100) / 100;
		}

		if (i == 0) {
			return Math.round(size) + " " + norm[i];
		}
		else {
			return size + " " + norm[i];
		}
	}

});