jQuery(document).ready(function () {
	jQuery('.rokka-delete-image').click( function(e) {
		if ( confirm( rokkaAdmin.labels.deleteImageConfirm ) !== true ) {
			e.preventDefault();
		}
	});
});
