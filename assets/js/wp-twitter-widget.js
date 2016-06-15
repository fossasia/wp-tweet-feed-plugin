function update_twitter_auth(arg) {
	if (arg == true)
    	jQuery("#twitter-widget-pro-general-settings").addClass("closed");
    else
    	jQuery("#twitter-widget-pro-general-settings").removeClass("closed");
    
    jQuery("#twitter-widget-pro-general-settings .handlediv").attr('aria-expanded', arg);
}


jQuery(function() {
	console.log("init");
	if(jQuery("#twp_loklak_api").prop('checked')){
		update_twitter_auth(true);
	}

    jQuery("#twp_loklak_api").live('change', function() {
    	if(jQuery(this).is(':checked')){
	    	update_twitter_auth(true);
	    }
	    else {
	    	update_twitter_auth(false);
	    }
	});
	
});