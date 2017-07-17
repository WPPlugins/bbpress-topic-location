jQuery(document).ready(function($){
    
        if (!navigator.geolocation) return;
        
        
	/*be careful, this should work for both frontend & backend*/
	$('#bbp_topic_location_field').each(function() {
	
		var input = $(this).find('input:text');
		var link_container = $(this).find('small');
        var link = link_container.children('a');
                
		//DISPLAY LINK
		link_container.show();

		link.click(function(){
			input.addClass('loading');
			input.attr('disabled', 'disabled');
			navigator.geolocation.getCurrentPosition( 

				function (position) {  
						input.removeClass('loading');
						input.removeAttr('disabled');
						//input.val(oqp.geo_home_text);

						input.val(position.coords.latitude+','+position.coords.longitude);

				}, 
				// next function is the error callback
				function (error)
				{
					var error_msg;
					input.removeClass('loading');
					input.removeAttr('disabled');

					switch(error.code) 
					{
							case error.TIMEOUT:
									error_msg=bbptl.geo_error_timeout;
									break;
							case error.POSITION_UNAVAILABLE:
									error_msg=bbptl.geo_error_unavailable;
									break;
							case error.PERMISSION_DENIED:
									error_msg=bbptl.geo_error_capability;
									break;
							case error.UNKNOWN_ERROR:
									error_msg=bbptl.geo_error;
									break;
					}
					alert(error_msg);

				}
			);
			
		});
	
		
	});
});
