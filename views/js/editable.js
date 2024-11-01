
	/**
	 * Wrapper, um die Editierfunktion austauschbarer zu machen
	 */
	jQuery.fn.wpsg_editable = function(url, options) {
	
		return this.each(function() {
			
			var opt = { 
				'type': 'text',
				'url': url,
				'pk': 1,
				'ajaxOptions': {
					'type': 'post'
				},
				'params': options['submitdata'],
				'placeholder': wpsg_ajax.ie_placeholder,
				'emptytext': wpsg_ajax.ie_emptytext,
				'validate': function(value) {
					
				    //if(jQuery.trim(value) == '') { return wpsg_ajax.ie_validate_empty; }
				    
				}				
			};

            if (typeof options.type == "string") opt['type'] = options.type;

			if (options.type == "select" && Array.isArray(options.data))
			{
			 	 
				var value = "0";
				var strValue = jQuery(this).html();
				
				for (var i in options.data)
				{
				
					if (options.data[i].text == strValue) value = options.data[i].value;
					
				}
				
				opt.source = options.data;
                opt.type = 'select';
                opt.value = value;
				
			}			 
            else if (options.type == 'multiarray')
            {

                var ar = [];
                var arData = jQuery.parseJSON(options.data);
				var strValue = jQuery(this).html();
 
                for (var i in arData)
                {

                    objData = { 'text': arData[i].name, 'children': [] };

                    for (var j in arData[i]['fields'])
                    {

                        objData['children'].push( { 'value': j, 'text': arData[i]['fields'][j] } );

						if (arData[i]['fields'][j] == strValue) value = j;
						
                    }

                    ar.push(objData);

                }

                opt.source = ar;
                opt.type = 'select';
				opt.value = value; //j;

			}
			else if (options.type == 'checklist')
			{
				
				opt.type = 'checklist';
				opt.source = [];
				opt.value = options.value;
				
				for (var i in options.data)
				{
				
					opt.source.push( { value: i, text: options.data[i] } );
					
				}
				
			}
			else
			{

				opt.display = function(value, sourceData) {
		        
					jQuery(this).html(sourceData);
				
				};
				
			}			

            if (typeof options.callback == "function") opt.success = options.callback;
			
			jQuery(this).editable(opt);
			
		} );
	
	}