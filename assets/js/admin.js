jQuery(document).ready( function () {

	jQuery('#nav-tab-select-01').click(function() {
		jQuery('#tab-01, #nav-tab-select-01').addClass('active');
		jQuery('#tab-02, #nav-tab-select-02, #tab-03, #nav-tab-select-03').removeClass('active');
	});

	jQuery('#nav-tab-select-02').click(function() {
		jQuery('#tab-02, #nav-tab-select-02').addClass('active');
		jQuery('#tab-01, #nav-tab-select-01, #tab-03, #nav-tab-select-03').removeClass('active');
	});

	jQuery('#nav-tab-select-03').click(function() {
		jQuery('#tab-03, #nav-tab-select-03').addClass('active');
		jQuery('#tab-01, #nav-tab-select-01, #tab-02, #nav-tab-select-02').removeClass('active');
	});

});
