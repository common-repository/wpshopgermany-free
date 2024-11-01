 
    function wpsg_mod_productvariants_reload(event)
    {
        
        var template_index = jQuery(event.target).closest('.wpsg_mod_productvariants_product_wrap').attr('wpsg-productindex');
 
        wpsg_blockProductTemplate(template_index);
  
		jQuery.ajax( {
			'url': wpsg_ajax.ajaxurl,
			'method': 'get',
			'data': {
				'action': 'wpsg_productvariants_switch',
				'wpsg_post_id': jQuery('#wpsg_produktform_' + template_index + ' input[name="wpsg_post_id"]').val(),
				'form_data': jQuery('#wpsg_produktform_' + template_index).serialize(),				
				'product_index': template_index
			},
			'success': function(data) {
		 
				jQuery('#wpsg_produktform_' + template_index).replaceWith(data);
				
			}
		} );
        
    } // function wpsg_mod_productvariants_reload(event)