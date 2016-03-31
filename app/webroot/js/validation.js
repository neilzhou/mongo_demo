/**
 *  @brief The validation is used for check some pre-defined type input|checkbox...
 *  
 *  @author Neil.zhou created at 20140725
 */
var Validation = {
  isEmail: function (email){
    return /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test( email );
  },
  isPhoneUS: function (phone_number){
    /**
     * 1(212)-999-2345 or
     * 212 999 2344 or
     * 212-999-0983 or
     * 2129992345
     */
    return phone_number.length > 9 &&
		phone_number.match(/^(\+?1-?)?(\([2-9]([02-9]\d|1[02-9])\)|[2-9]([02-9]\d|1[02-9]))-?[2-9]([02-9]\d|1[02-9])-?\d{4}$/);
  },
  errors: {
    phone: 'Please specify a valid phone number',
    required: "This field is required.",
		remote: "Please fix this field.",
		email: "Please enter a valid email address.",
		url: "Please enter a valid URL.",
		date: "Please enter a valid date.",
		dateISO: "Please enter a valid date ( ISO ).",
		number: "Please enter a valid number.",
		digits: "Please enter only digits.",
		creditcard: "Please enter a valid credit card number.",
		equalTo: "Please enter the same value again.",
		maxlength: "Please enter no more than {0} characters.",
		minlength: "Please enter at least {0} characters.",
		rangelength: "Please enter a value between {0} and {1} characters long." ,
		range: "Please enter a value between {0} and {1}.",
		max: "Please enter a value less than or equal to {0}." ,
		min: "Please enter a value greater than or equal to {0}." 
  },
  format: function(source, params){
    if ( params.constructor !== Array ) {
      params = [ params ];
    }
    $.each( params, function( i, n ) {
      source = source.replace( new RegExp( "\\{" + i + "\\}", "g" ), function() {
        return n;
      });
    });
    return source;
  }
};