jQuery(document).ready(function ($) {
	function getCurrentDateTime() {
		var currentdate = new Date();
		return '[' + ('0' + currentdate.getDate()).slice(-2) + "."
		+ ('0' + (currentdate.getMonth()+1)).slice(-2)  + "."
		+ currentdate.getFullYear() + " "
		+ ('0' + (currentdate.getHours()+1)).slice(-2) + ":"
		+ ('0' + (currentdate.getMinutes()+1)).slice(-2) + ":"
		+ ('0' + (currentdate.getSeconds()+1)).slice(-2) + ']';
	}

	$('#mass-upload-everything').click(function (e) {
		var uploadProgressbar = $('#upload-progressbar'),
			uploadProgressLogWrapper = $('#upload-progress-log-wrapper'),
			uploadProgressLog = $('#upload-progress-log'),
			uploadProgresInfo = $('#upload-progress-info'),
			imageIdsToUpload = rokkaSettings.imagesToUpload,
			hasError = false;

		uploadProgresInfo.html('');
		uploadProgressLog.val('');
		uploadProgressbar.hide();
		uploadProgressLogWrapper.hide();

		var progressFraction = 100 / imageIdsToUpload.length;
		var progressStep = 0;
		if (imageIdsToUpload.length > 0) {
			// initialize progressbar
			uploadProgressbar.show();
			uploadProgressbar.progressbar({
				value: 0
			});
			uploadProgressLogWrapper.show();

			// upload first image
			rokkaUploadImage(imageIdsToUpload);

			function rokkaUploadImage(imageIdsToUpload) {
				if (imageIdsToUpload.length > 0) {
					var imageId = imageIdsToUpload.shift();
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						dataType: 'json',
						data: {
							action: 'rokka_upload_image',
							image_id: imageId,
							nonce: rokkaSettings.nonce
						}
					}).done(function() {
						uploadProgressLog.val(uploadProgressLog.val() + getCurrentDateTime() + ' ' + rokkaSettings.labels.uploadSingleImageSuccess + ' ' + imageId + '\n');
					}).fail(function( res ) {
						uploadProgressLog.val(uploadProgressLog.val() + getCurrentDateTime() + ' ' + rokkaSettings.labels.uploadSingleImageFail + ' ' + imageId + ' / Error: ' + res.responseJSON.data + '\n');
						hasError = true;
					}).always(function() {
						progressStep += 1;
						uploadProgressbar.progressbar('value', progressStep * progressFraction);
						rokkaUploadImage(imageIdsToUpload);
					});
				} else {
					if ( ! hasError ) {
						uploadProgressLog.val(uploadProgressLog.val() + getCurrentDateTime() + ' ' + rokkaSettings.labels.uploadImagesSuccess);
						uploadProgresInfo.html('<div class="notice notice-success"><p>' + rokkaSettings.labels.uploadImagesSuccess + '</p></div>')
					} else {
						uploadProgressLog.val(uploadProgressLog.val() + getCurrentDateTime() + ' ' + rokkaSettings.labels.uploadImagesFail);
						uploadProgresInfo.html('<div class="notice notice-error"><p>' + rokkaSettings.labels.uploadImagesFail + '</p></div>')
					}
				}
			}
		} else {
			uploadProgresInfo.html('<div class="notice notice-success"><p>' + rokkaSettings.labels.uploadImagesAlreadyUploaded + '</p></div>');
		}
	});

	$('#mass-delete-everything').click(function (e) {
		var uploadProgressbar = $('#upload-progressbar'),
			uploadProgressLogWrapper = $('#upload-progress-log-wrapper'),
			uploadProgressLog = $('#upload-progress-log'),
			uploadProgresInfo = $('#upload-progress-info'),
			imageIdsToDelete = rokkaSettings.imagesToDelete,
			hasError = false;

		if ( confirm( rokkaSettings.labels.deleteImagesConfirm ) !== true) {
			return;
		}

		uploadProgresInfo.html('');
		uploadProgressLog.val('');
		uploadProgressbar.hide();
		uploadProgressLogWrapper.hide();

		var progressFraction = 100 / imageIdsToDelete.length;
		var progressStep = 0;
		if (imageIdsToDelete.length > 0) {
			// initialize progressbar
			uploadProgressbar.show();
			uploadProgressbar.progressbar({
				value: 0
			});
			uploadProgressLogWrapper.show();

			// delete first image
			rokkaDeleteImage(imageIdsToDelete);

			function rokkaDeleteImage(imageIdsToDelete) {
				if (imageIdsToDelete.length > 0) {
					var imageId = imageIdsToDelete.shift();
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						dataType: 'json',
						data: {
							action: 'rokka_delete_image',
							image_id: imageId,
							nonce: rokkaSettings.nonce
						}
					}).done(function() {
						uploadProgressLog.val(uploadProgressLog.val() + getCurrentDateTime() + ' ' + rokkaSettings.labels.deleteSingleImageSuccess + ' ' + imageId + '\n');
					}).fail(function( res ) {
						uploadProgressLog.val(uploadProgressLog.val() + getCurrentDateTime() + ' ' + rokkaSettings.labels.deleteSingleImageFail + ' ' + imageId + ' / Error: ' + res.responseJSON.data + '\n');
						hasError = true;
					}).always(function() {
						progressStep += 1;
						uploadProgressbar.progressbar('value', progressStep * progressFraction);
						rokkaDeleteImage(imageIdsToDelete);
					});
				} else {
					if ( ! hasError ) {
						uploadProgressLog.val(uploadProgressLog.val() + getCurrentDateTime() + ' ' + rokkaSettings.labels.deleteImagesSuccess);
						uploadProgresInfo.html('<div class="notice notice-success"><p>' + rokkaSettings.labels.deleteImagesSuccess + '</p></div>')
					} else {
						uploadProgressLog.val(uploadProgressLog.val() + getCurrentDateTime() + ' ' + rokkaSettings.labels.deleteImagesFail);
						uploadProgresInfo.html('<div class="notice notice-error"><p>' + rokkaSettings.labels.deleteImagesFail + '</p></div>')
					}
				}
			}
		} else {
			uploadProgresInfo.html('<div class="notice notice-success"><p>' + rokkaSettings.labels.deleteImagesNoImage + '</p></div>');
		}
	});

	$('#create-rokka-stacks').click(function (e) {
		$('#progress-info-stacks').html('<div class="notice notice-info"><p class="loading-indicator"><img src="' + rokkaSettings.loadingSpinnerUrl + '" alt="" width="16" height="16" /><span> ' + rokkaSettings.labels.createStacksStart + '</span></p></div>');

		$.ajax({
			type: 'GET',
			url: ajaxurl,
			data: {
				action: 'rokka_create_stacks',
				nonce: rokkaSettings.nonce
			}
		}).done(function() {
			$('#progress-info-stacks').html('<div class="notice notice-success"><p>' + rokkaSettings.labels.createStacksSuccess + '</p></div>');
		}).fail(function( res ) {
			$('#progress-info-stacks').html('<div class="notice notice-error"><p>' + rokkaSettings.labels.createStacksFail + ' ' + res.responseJSON.data + '</p></div>');
		});
	});
});
