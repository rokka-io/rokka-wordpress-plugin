jQuery(document).ready(function ($) {
	$('#mass-upload-everything').click(function (e) {
		var imageIdsToUpload = rokkaSettings.imagesToUpload;
		imageIdsToUpload = Object.keys(imageIdsToUpload).map(function (k) {
			return imageIdsToUpload[k]
		});

		var progressFraction = 100 / imageIdsToUpload.length;
		var progressStep = 0;
		if (imageIdsToUpload.length > 0) {
			$('#progressbar').progressbar({
				value: 0
			});
			$('#progress-info').append('<br />');
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
						$('#progress-info').append('upload of image id ' + imageId + ' successful<br />');
					}).fail(function( res ) {
						$('#progress-info').append('upload of image id ' + imageId + ' failed! The Server Returned an Error: ' + res.responseJSON.data + '<br />');
					}).always(function() {
						progressStep += 1;
						$('#progressbar').progressbar({
							value: progressStep * progressFraction
						});
						rokkaUploadImage(imageIdsToUpload);
					});
				} else {
					$('#progress-info').append('image upload done! <br />');
				}
			}
		} else {
			$('#progress-info').append('Nothing to process here, all images are already uploaded to Rokka.<br />');
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
			$('#progress-info-stacks').html('<div class="notice notice-error"><p>' + rokkaSettings.labels.createStacksFail + '. Error: ' + res.responseJSON.data + '</p></div>');
		});
	});
});
