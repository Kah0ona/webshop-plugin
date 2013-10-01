var SELECT_FUTURE  = -1;
var NOT_AVAILABLE  = -2;
var AVAILABLE      = -3;

var reqMsg = "Dit veld is verplicht.";
var emailMsg = "Vul een geldig e-mailadres in.";
var discount = 0;

var submitType = "invoice";
jQuery(document).ready(function($){

		
	var submitOptions = {
		beforeSubmit : function(arr, form, options){
			if($('.submit-controls').hasClass('disabled')){
				return false;
			}
										
		  	//hide the form to prevent another click
		  	var f = $('#order-form');
		
			showSendingMessage(f);
			return true;	
			
		},
		data : { 
			"hostname" : hostname,
			"action" : "place_order",
			"cart_nonce" : SubmitFormUrl.cart_nonce /*,  
			orderType : submitType*/
		},
		success : function(data, textStatus, jqXHR) {
			var tmp = data;
			if(data.error != null){
				$('#order-form').replaceWith('<div class="alert alert-error span12"><strong>Fout:</strong> Er ging iets mis met het versturen van de bestelling: '+data.error+'</div>');
			}
			else {
				window.location.href = data.redirectUrl;
			}
		}
	};
	
	var validationOptions = {
			rules : {
				firstname : {
					required: true
				},
				surname : {
					required: true
				},
				street : {
					required: true
				},
				number : {
					required: true
				},
				postcode : {
					required: true
				},
				city : {
					required: true
				},
				email : {
					required: true,
					email: true
				},
				phone : {
					required: true
				},
				orderDate: {
					required: true
				},
				orderDateTime : {
					required: true
				}
			},
		
			messages : {
				firstname: {
				   required: reqMsg
			    },
				surname: {
				   required: reqMsg
			    },
				street: {
				   required: reqMsg
			    },
				number: {
				   required: reqMsg
			    },
				postcode: {
				   required: reqMsg
			    },
				city: {
				   required: reqMsg
			    },
				email: {
				   required: reqMsg,
				   email: emailMsg
			    },
				phone: {
				   required: reqMsg
			    },
			    persons : {
			    	required: reqMsg
			    },
			    orderDate : {
			    	required: reqMsg
			    }  
			},
		    errorPlacement: function(error, element) {
			   error.insertAfter(element);
			}
			,
			submitHandler : function(){
				$('#order-form').ajaxSubmit(submitOptions);	//does some extra validation.		
			},
			invalidHandler: function(form, validator) {
				//alert('invalid');
			}
	}; 

	
    $('#order-form').validate(validationOptions);
	
});

function formatEuro(price){
	Number.prototype.formatMoney = function(c, d, t){
	var n = this, c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : d, t = t == undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
	   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	};

	return price.formatMoney(2,',','.');
}

function showSendingMessage(f){
	f.replaceWith('<div class="alert alert-info span12"><strong>Even geduld:</strong> bezig met versturen van de gegevens...</div>');
}

function showSuccesMessage(ret){
	//just redirect to the success page
	window.location.href = baseUrl+"/success/";
}