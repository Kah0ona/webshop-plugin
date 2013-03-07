var SELECT_FUTURE  = -1;
var NOT_AVAILABLE  = -2;
var AVAILABLE      = -3;

var reqMsg = "Dit veld is verplicht.";
var emailMsg = "Vul een geldig e-mailadres in. ";
var discount = 0;

var submitType = "invoice";
var shouldCheckDate = true; //set this in like header.php to false if you want to enable orders that don't have the 2 day in advance check
$(document).ready(function(){

	$.datepicker.setDefaults( $.datepicker.regional[ "nl" ] )		
	$("#orderDate").datepicker({
		onSelect: function(dateText, inst){
			if(shouldCheckDate){
				var show = !checkDate(dateText);
				if(show){
					$('#dateError').removeClass('hidden');
				}
				else {
					$('#dateError').addClass('hidden');
				}
			}
		}
	});
	
	var submitOptions = {
		beforeSubmit : function(arr, form, options){
			if($('.submit-controls').hasClass('disabled')){
				return false;
			}
				
			if(!updating){ //only if it's a new order, updating existing one does not need this check
				if(shouldCheckDate){
				var b = checkDate($('#orderDate').val());
					if(!b) {
						return false;
					}
				}
			}
							
		  	//hide the form to prevent another click
		  	var f = $('#order-form');
		
			showSendingMessage(f);
			return true;	
		},
		data : { 
			"hostname" : hostname /*,  
			orderType : submitType*/
		},
		success : function(data, textStatus, jqXHR) {
		  	resetCookie();
			showSuccesMessage();
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
	
	if(!updating && shouldCheckDate){
	
		var show = !checkDate($('#orderDate').val());
		if(show){
			$('#dateError').removeClass('hidden');
		}
		else {
			$('#dateError').addClass('hidden');
		}
	}
	
});

function resetCookie(){
	var exDate=new Date();
	exDate.setTime(exDate.getTime()+ 1000*60*60*24); //24 hours
	//exDate.setUTCMilliSeconds(999); //todo check timezone
	
	var jsonString = "[]";
	
	var c_value=escape(jsonString) + "; expires="+exDate.toUTCString()+'; path=/';
	document.cookie="shoppingCart" + "=" + c_value;	
}

function getCartCookie(){
  var i,x,y,ARRcookies=document.cookie.split(";");
  for (i=0; i<ARRcookies.length; i++) {
	  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
	  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
	  x=x.replace(/^\s+|\s+$/g,"");
	  if(x == "shoppingCart"){
      	return unescape(y); //return the stored json object
      }

  }
  return null;
   
}

function saveFormInCookie(){
		
}

function restoreFormFromCookie(){
	
}


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

var numDaysBeforeOneCanOrder = 1;

function checkDate(dateText){

	
	
	var txt = dateText;
	var p1 = txt.split("-");

	var sel =  new Date(p1[2], p1[1]-1, p1[0], "00", "00", "00"); //this is also tested on iPhone, and now works

	//atleast 2 days from now
	var d = new Date();
	d.setHours(0);
	d.setMinutes(0);
	
	d.setSeconds(0);
	return !(sel.getTime() < parseInt(d.getTime())+parseInt(numDaysBeforeOneCanOrder*24*60*60*1000));
}
