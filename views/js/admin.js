function wpsg_loading(selector) {

	var jqSel = jQuery(selector);
	
	jqSel.css( {
		'position': 'relative',
		'display': 'block',
		'width': jqSel.width(),
		'height': jqSel.height(true)
	} );

	jqSel.append('<div class="ajax_loading"><img class="loading" src="' + wpsg_ajax.img_ajaxloading + '" alt="Bitte warten" /></div>');
	
}

function wpsg_loading_done(selector) {

	var jqSel = jQuery(selector);
	
	jqSel.find('.ajax_loading').remove();
	
}


wpsg_number_format = function (number, decimals, dec_point, thousands_sep) {

	var n = !isFinite(+number) ? 0 : + number, prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }    return s.join(dec);
  };

	var wpsg_Tablefix = function(e, ui) {
		
		ui.children().each(function() {
			
			jQuery(this).find('td').each(function() {
				
				jQuery(this).width(jQuery(this).width());
				
			} );
			
			jQuery(this).width(jQuery(this).width());
			
		} );
		
		return ui;
		
	};
    
jQuery.fn.wpsg_adminbox = function(options) {
		
	return this.each(function() {
				
		var adminbox_id = jQuery(this).attr("id");
		
		jQuery(this).find('.title').bind('click', function() {
		 
			var content = jQuery(this).next();
						
			if (content.is(':visible'))
			{
			
				content.hide();
				jQuery(this).css('border-bottom', '1px solid #AAAAAA');
				jQuery.cookie(adminbox_id, '0', { expires: 14000 } );
				
			}
			else
			{
				
				content.show();
				jQuery(this).css('border-bottom', '0px');
				jQuery.cookie(adminbox_id, '1', { expires: 14000 } );
								
			}
			
		} );
		
		if (jQuery.cookie(adminbox_id) == null || jQuery.cookie(adminbox_id) == 0)
		{
			
			jQuery(this).find('.title').click();
			
		}
		
	} );
	
}

jQuery.fn.wpsg_tab = function(options) {
		
	return this.each(function() {
		
		var tab_obj = jQuery(this);
	
		// Init
		tab_obj.find('.tabcontent').hide();
		tab_obj.find('.tab').removeClass('akttab');
		
		var aktTab = 1;
		
		if (jQuery.cookie(options['cookiename']) != null && jQuery.cookie(options['cookiename']) > 0)
		{
			aktTab = jQuery.cookie(options['cookiename']);
		}
		
		jQuery('#tab' + aktTab).addClass('akttab');
		jQuery('#tabcontent' + aktTab).show();
		
		if (typeof options['tab' + aktTab] == 'function')
		{
			options['tab' + aktTab]();
		}
		
		tab_obj.find('.tab').bind('click', function() {
			
			tab_obj.find('.tab').removeClass('akttab');
			tab_obj.find('.tabcontent').hide();
			
			jQuery(this).addClass('akttab');
			
			var strID = jQuery(this).attr("id").replace(/tab/, '');
			jQuery.cookie(options['cookiename'], strID, { expires: 14000 } );
			
			jQuery('#tabcontent' + strID).show();
			
			if (typeof options['tab' + strID] == 'function')
			{
				options['tab' + strID]();
			}
			
		} );
		
	} );

};

/**
 * jPlot - Custom Formatter functions
 */
wpsg_statistics_number_format = function (formatString, value) {
	return wpsg_number_format(value, 2, ',', '.');
} 

wpsg_statistics_integer_format = function (formatString, value) {
	return wpsg_number_format(value, 0, ',', '.');
} 

function wpsg_in_array(needle, haystack) 
{
	
    var length = haystack.length;
    
    for(var i = 0; i < length; i++) {
    	
        if (haystack[i] == needle) return true;
        
    }
    
    return false;
    
}

	var po; 

	function wpsg_ajaxBind() {

		jQuery('.wpsg-is-dismissible').on('click', '.notice-dismiss', function(event, el) {

			var dismiss_url = jQuery(this).parent('.notice.is-dismissible').attr('data-dismiss-url');
			if (dismiss_url) { jQuery.get(dismiss_url); }

		});

		// Hilfe Tooltips
		jQuery('*[data-wpsg-tip]').on('click', function() { 

			//jQuery(this).off('click').on('click', function() { return false; } );
 
			if (typeof po === "object")
			{
				
				if (po != this) jQuery(po).popover('hide');
				
			}
			
			po = this;
			
			if (jQuery(this).hasClass('activated'))
			{
								
				jQuery(this).popover('show');
				
				return false;
				
			}
			
			jQuery(this).popover( {
				'html': true,
				'content': '<div id="wpsg-popover-content"><img src="' + wpsg_ajax.img_ajaxloading + '" alt="' + wpsg_ajax.label_pleasewait + '" /></div>',
				'trigger': 'focus',
				'container': '#wpsg-bs',
				'placement': 'right'
			} ).popover('show');

			jQuery.ajax( {
				url: '?page=wpsg-Admin&subaction=loadHelp&noheader=1',
				data: {
					field: jQuery(this).attr('data-wpsg-tip')
				},
				success: function(data) {
					
					var popover = jQuery(po).attr('data-content', data).data('popover');
					jQuery(po).data('bs.popover').options.content = data;
					
					jQuery(po).popover('show');
										
				}
			} );
			 
			jQuery(this).addClass('activated');
			
			return false;
			
		} ).css('pointer-events', 'auto');
				
	}

	jQuery(document).ready(function() {

        // Sortierung
        jQuery('th.wpsg_order').bind('click', function() {

            var direction = "ASC";
            if (jQuery(this).hasClass('wpsg_order_asc') && jQuery('#wpsg_order').val() == jQuery(this).attr("data-order")) direction = "DESC";

            jQuery('#wpsg_ascdesc').val(direction);
            jQuery('#wpsg_order').val(jQuery(this).attr("data-order"));

            jQuery('#filter_form').submit();

		    return false;

        } );
		
		jQuery("html,body").on("click touchstart", function() {
	
			if (typeof po === "object") jQuery(po).popover('hide');
						
		});		

		jQuery('.wpsg_showhide_filter').bind('click', function() { 
			
			if (jQuery(this).hasClass('active'))
			{
				
				jQuery('.wpsg-filter').slideUp(150);
				jQuery(this).removeClass('active');
				
			}
			else
			{
			
				jQuery('.wpsg-filter').slideDown(150, function() { jQuery('.wpsg-filter input[type="text"]').first().focus(); } );
				jQuery(this).addClass('active');
				
			}
			
		} );
				
		wpsg_ajaxBind();
		
	} );
