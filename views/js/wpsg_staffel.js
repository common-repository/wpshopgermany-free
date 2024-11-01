
	(function(jQuery) {
		
		jQuery.fn.wpsgInputStaffel = function(options) {
		
			if (options == 'close')
			{
				
				jQuery("#wpsg_staffel_dialog").dialog('close');
				return;
				
			}
			
			var settings = jQuery.extend({}, jQuery.fn.wpsgInputStaffel.defaults, options);
			var field_id = this.attr("id");
			
			var methods = {
				renderTable: function() {
					
					var strHTML = '<table style="width:100%;" borderspacing="0" cellpadding="0">';
					
					arStaffelung = Array();
					if (arStaffelung.length > 0)
					{
						 
						for (var i = 0; i < arStaffelung.length; i ++)
						{
					 
							var arData = arStaffelung[i];
							
							strHTML += '<tr class="row' + i + '">';
							strHTML += '<td style="width:50px;"></td>';
	
							if (arData[0] == 0 || arStaffelung.length <= 1)
								strHTML += '<td style="width:150px;">ab <input style="background:transparent; margin-right:5px; width:50px;" class="key" type="text" value="0" readonly="readonly" />' + settings.currency + '</td>';
							else
								strHTML += '<td style="width:150px;">ab <input onblur="arStaffelung[' + i + '][0] = jQuery(this).val(); wpsg_updateStaffelung();" style="margin-right:5px; width:50px;" class="key" type="text" value="' + arData[0] + '" />' + settings.currency + '</td>';
							
							strHTML += '<td style="width:150px;"><input onblur="arStaffelung[' + i + '][1] = jQuery(this).val(); wpsg_updateStaffelung();" style="width:50px; margin-right:5px;" class="value" type="text" value="' + arData[1] + '" />' + settings.currency + '</td>';
								
							strHTML += '<td style="text-align:right;"><input style="width:100px;" class="button" type="button" value="Entfernen" onclick="wpsg_delStaffelung(\'\', ' + i + ');" /></td>';
							strHTML += '</tr>';
							
						}
						
					} 		
					
					strHTML += '<tr><td colspan="4">&nbsp;</td></tr>';
					
					strHTML += '<tr class="row_neu">';
					strHTML += '<td>Neu:</td>';
					
					if (arStaffelung.length == 0)
						strHTML += '<td>ab <input style="background:transparent; margin-right:5px; width:50px;" class="key" type="text" value="0" readonly="readonly" />' + settings.currency + '</td>';
					else
						strHTML += '<td>ab <input style="width:50px; margin-right:5px;" class="key" type="text" value="" />' + settings.currency + '</td>';
					
					strHTML += '<td><input style="width:50px; margin-right:5px;" class="value" type="text" value="" />' + settings.currency + '</td>';		
					strHTML += '<td style="text-align:right;"><input style="width:100px;" class="button" type="button" value="Hinzufügen" onclick="return wpsg_addStaffelung(\'\');" /></td>';
					strHTML += '</tr>';
					
					strHTML += '<tr><td colspan="4">&nbsp;</td></tr>';
					strHTML += '<tr><td colspan="4" id="wpsg_staffelung_debug">&nbsp;</td></tr>';
					
					strHTML += '<tr>';
					strHTML += '<td colspan="3">&nbsp;</td>';
					strHTML += '<td style="text-align:right;"><input style="width:100px;" class="button" type="button" onclick="return jQuery(\'#' + field_id + '\').wpsgInputStaffel(\'close\');" value="Schließen" /></td>';
					strHTML += '</tr>';
					
					strHTML += '</table>';
					
					return strHTML;
				
				},
				showDialog: function() { 
				
					var strHTML  = '<div id="wpsg_staffel_dialog" style="display:none;">';
				
					strHTML += '<div style="padding:5px;" id="wpsg_staffel_table">' + methods.renderTable() + '</div>';
					strHTML += '</div>';
					
					jQuery('body').append(strHTML);
				
					jQuery("#wpsg_staffel_dialog").dialog( {    
						'dialogClass': 'wp-dialog', 
				        'modal': true,
				        'width': settings.width,
				        'height': settings.height,
				        'title': settings.title
					} );
									
		    	}
			};
 
			if (this.text().trim() == '')
			{
				this.text(wpsg_staffel.empty);
			}
						
			this.bind('click', function() {
					
				var staffelKey = jQuery(this).text().trim();				
				if (staffelKey == wpsg_staffel.empty) staffelKey = '';					
				methods.showDialog.apply();
				
			} ); 
								 			
		};
		
		jQuery.fn.wpsgInputStaffel.defaults = {
			currency: '€',
			title: wpsg_staffel.title,
			width: 350,
			height: 400
		};
		
	})(jQuery);