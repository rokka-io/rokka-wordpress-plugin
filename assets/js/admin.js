jQuery(document).ready( function () {

	jQuery('#nav-tab-select-01').click(function() {
		jQuery('#tab-01').removeClass('display-none');
		jQuery('#tab-02').addClass('display-none');
		jQuery('#tab-03').addClass('display-none');
		jQuery('#nav-tab-select-01').addClass('current');
		jQuery('#nav-tab-select-02').removeClass('current');
		jQuery('#nav-tab-select-03').removeClass('current');
	});

	jQuery('#nav-tab-select-02').click(function() {
		jQuery('#tab-02').removeClass('display-none');
		jQuery('#tab-01').addClass('display-none');
		jQuery('#tab-03').addClass('display-none');
		jQuery('#nav-tab-select-02').addClass('current');
		jQuery('#nav-tab-select-01').removeClass('current');
		jQuery('#nav-tab-select-03').removeClass('current');
	});

	jQuery('#nav-tab-select-03').click(function() {
		jQuery('#tab-03').removeClass('display-none');
		jQuery('#tab-01').addClass('display-none');
		jQuery('#tab-02').addClass('display-none');
		jQuery('#nav-tab-select-03').addClass('current');
		jQuery('#nav-tab-select-01').removeClass('current');
		jQuery('#nav-tab-select-02').removeClass('current');
	});

});
