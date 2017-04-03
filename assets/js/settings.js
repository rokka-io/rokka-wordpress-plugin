jQuery(document).ready(function ($) {
	$('#mass-upload-everything').click(function (e) {
		var image_ids_to_upload = rokkaSettings.imagesToUpload;
		image_ids_to_upload = Object.keys(image_ids_to_upload).map(function (k) {
			return image_ids_to_upload[k]
		});

		var progress_fraction = 100 / image_ids_to_upload.length;
		var progress_step = 0;
		if (image_ids_to_upload.length > 0) {
			$("#progressbar").progressbar({
				value: 0
			});
			$('#progress_info').append("<br />");
			rokka_upload_image(image_ids_to_upload);
			function rokka_upload_image(image_ids_to_upload) {
				if (image_ids_to_upload.length > 0) {
					var image_id = image_ids_to_upload.shift();
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						dataType: 'json',
						data: {
							action: 'rokka_upload_image',
							image_id: image_id,
							nonce: rokkaSettings.nonce
						}
					}).done(function() {
						$('#progress_info').append("upload of image id " + image_id + " successful<br />");
					}).fail(function( res ) {
						$('#progress_info').append("upload of image id " + image_id + " failed! The Server Returned an Error: " + res.responseJSON.data + "<br />");
					}).always(function() {
						progress_step += 1;
						$("#progressbar").progressbar({
							value: progress_step * progress_fraction
						});
						rokka_upload_image(image_ids_to_upload);
					});
				}
				else {
					$('#progress_info').append("image upload done! <br />");
				}
			}
		}
		else {
			$('#progress_info').append("Nothing to process here, all images are already uploaded to Rokka.<br />");
		}
	});

	$('#create-rokka-stacks').click(function (e) {
		$.ajax({
			type: 'GET',
			url: ajaxurl,
			data: {action: 'rokka_create_stacks'},
			success: function (response) {
				$('#progress_info_stacks').html("<div id='setting-error-settings_updated' class='updated settings-error notice is-dismissible'> <p><strong>Stack creation successful!</strong></p><button type='button' class='notice-dismiss display-none'></button></div>");

			},
			error: function (response) {
				$('#progress_info_stacks').html("<div id='setting-error-settings_updated' class='notice-error settings-error notice is-dismissible'> <p><strong>Stack creation failed!</strong></p><button type='button' class='notice-dismiss display-none'></button></div>");
			}
		});
	});
});
