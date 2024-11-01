
(function($){
    $.fn.validationEngineLanguage = function(){
    };
    $.validationEngineLanguage = {
        newLang: function(){
            $.validationEngineLanguage.allRules = {
                "required": { // Add your regex rules here, you can take telephone as an example
                    "regex": "none",
                    "alertText": wpsg_trans_v1.required_alertText,
                    "alertTextCheckboxMultiple": wpsg_trans_v1.required_alertTextCheckboxMultiple,
                    "alertTextCheckboxe": wpsg_trans_v1.required_alertTextCheckboxe
                },
                "minSize": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.minSize_alertText,
                    "alertText2": wpsg_trans_v1.minSize_alertText2
                },
                "maxSize": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.maxSize_alertText,
                    "alertText2": wpsg_trans_v1.maxSize_alertText
                },
				"groupRequired": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.groupRequired_alertText
                },
                "min": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.min_alertText
                },
                "max": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.max_alertText
                },
                "past": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.past_alertText
                },
                "future": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.future_alertText
                },	
                "maxCheckbox": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.maxCheckbox_alertText
                },
                "minCheckbox": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.minCheckbox_alertText,
                    "alertText2": wpsg_trans_v1.minCheckbox_alertText2
                },
                "equals": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.equals_alertText
                },
                "creditCard": {
                    "regex": "none",
                    "alertText": wpsg_trans_v1.creditCard_alertText
                },
                "phone": {
                    // credit: jquery.h5validate.js / orefalo
                    "regex": /^([\+][0-9]{1,3}[ \.\-])?([\(]{1}[0-9]{1,6}[\)])?([0-9 \.\-\/]{3,20})((x|ext|extension)[ ]?[0-9]{1,4})?$/,
                    "alertText": wpsg_trans_v1.phone_alertText
                },
                "email": {
                    // Shamelessly lifted from Scott Gonzalez via the Bassistance Validation plugin http://projects.scottsplayground.com/email_address_validation/
                    "regex": /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i,
                    "alertText": wpsg_trans_v1.email_alertText
                },
                "integer": {
                    "regex": /^[\-\+]?\d+$/,
                    "alertText": wpsg_trans_v1.integer_alertText
                },
                "number": {
                    // Number, including positive, negative, and floating decimal. credit: orefalo
                    "regex": /^[\-\+]?(([0-9]+)([\.,]([0-9]+))?|([\.,]([0-9]+))?)$/,
                    "alertText": wpsg_trans_v1.number_alertText
                },
                "date": {
                    // Date in ISO format. Credit: bassistance
                    "regex": /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/,
                    "alertText": wpsg_trans_v1.date_alertText
                },
                "ipv4": {
                	"regex": /^((([01]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))[.]){3}(([0-1]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))$/,
                    "alertText": wpsg_trans_v1.ipv4_alertText
                },
                "url": {
                    "regex": /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i,
                    "alertText": wpsg_trans_v1.url_alertText
                },
                "onlyLetterSp": {
	                 "regex": /^[a-zA-ZäüöÄÜÖßs\ \\\']+$/,
                     "alertText": wpsg_trans_v1.onlyLetterSp_alertText
                },
				"onlyLetterNumber": {
					"regex": /^[0-9a-zA-ZäüöÄÜÖßs-]+$/,
					"alertText": wpsg_trans_v1.onlyLetterNumber_alertText
				}, 
                // --- CUSTOM RULES -- Those are specific to the demos, they can be removed or changed to your likings
                "ajaxUserCall": {
                    "url": "ajaxValidateFieldUser",
                    // you may want to pass extra data on the ajax call
                    "extraData": "name=eric",
                    "alertText": wpsg_trans_v1.ajaxUserCall_alertText,
                    "alertTextLoad": wpsg_trans_v1.ajaxUserCall_alertTextLoad
                },
                "ajaxNameCall": {
                    // remote json service location
                    "url": "ajaxValidateFieldName",
                    // error
                    "alertText": wpsg_trans_v1.ajaxNameCall_alertText,
                    // if you provide an "alertTextOk", it will show as a green prompt when the field validates
                    "alertTextOk": wpsg_trans_v1.ajaxNameCall_alertTextOk,
                    // speaks by itself
                    "alertTextLoad": wpsg_trans_v1.ajaxNameCall_alertTextLoad
                },
                "validate2fields": {
                    "alertText": wpsg_trans_v1.validate2fields_alertText
                },
                "vname": {
                		"regex": "none",
                    "alertText": wpsg_trans_v1.vname_alertText
                }
            };
            
        }
    };
    $.validationEngineLanguage.newLang();
})(jQuery);