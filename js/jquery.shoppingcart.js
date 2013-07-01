/**
* JQuery plugin that acts as a shopping cart 
* Author: Marten Sytema (marten@sytematic.nl)
* Plugin dependencies: 
* - JQuery-JSON: http://code.google.com/p/jquery-json/
* Version: 0.6
*/
;(function( $, window, document, undefined ) {
	var deliveryCosts = {"price": 0}; //object with details about the delivery costs. based on address user filled out in checkout form.
	var distance = -1;
	var methods = {
		init : function( options ) { 
			return this.each(function(){
				var $this = this;
				methods.initSettingsData(this, options);
				methods.bindButtons(this);
				methods.load(this, function(jsonObj){
					methods.render($this);
					methods.updatePrices($this);
				});
			});
		},
		initSettingsData : function(elt, options){
				var $this = $(elt);
				var data = $this.data('shoppingCart');
				
				var defaults =  {
				      'detail' : false, //is this a detail page?
				      'checkout_page' : '/checkout',
				      'checkout_link' : 'Afrekenen',
				      'cart_text' : 'Mijn bestelling',
				      'address' : '',
				      'session_url' : '/wordpress/wp-content/plugins/webshop-plugin/models/CartStore.php',
				      'cartDataStore' : [],
				      'deliveryMethods' : [],
				      'deliveryCostsTable' : []
				};
				
				//if not yet initialized 
				if(!data)  {
				    settings = $.extend(defaults, options);
					//store it using the jQuery recommended data function.
					$this.data('shoppingCart', {
						target : $this,
						settings : settings
					});
				}							
		},
		load : function(elt, callback){
		  var $this = $(elt);
		  var settings = $this.data('shoppingCart').settings;
		  $.ajax({
				url: settings.session_url,
				type: 'GET',
				data: {"action" : "load"},
				success: function (jsonObj, textStatus, jqXHR){
					methods.logger("Loaded: ");
					methods.logger(jsonObj);
					settings.cartDataStore=jsonObj;
					callback.call(this, jsonObj);
				},
				dataType: 'json'
			});
	    },
		logger : function(msg){
			if (window.console) {
				console.log(msg);	
			} 
		},
		persist : function(elt){
			var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
			var cartDataStore = settings.cartDataStore;
			
			if(cartDataStore.length == 0){
				cartDataStore="EMPTY";
			}
			
			$.ajax({
				url : settings.session_url,
				type: 'POST',
				data: {"shoppingCart" : cartDataStore},
				success: function (jsonObj, textStatus, jqXHR){
					methods.logger("Persisted: ")
					methods.logger(jsonObj);
				},
				dataType: 'json'
			});			
	    },	
	    bindButtons : function(elt){
			var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
	    
			$('body').on('change.shoppingCart', "#deliveryMethods", function(event){
				methods.updatePrices(elt);
			});
	    	$('body').on("change.shoppingCart",".deliveryType", function(event){
	    	    methods.updatePrices(elt);
	    	});
	    	$("body").on("click.shoppingCart","a.removefromcart", function(event){
		    	event.preventDefault();
		    	methods.removeProduct(elt,event);
		    	//methods.updateCartTotalPrice(elt);
    			methods.updatePrices(elt);
				event.stopPropagation();
		    });
		    
			$('.address-line, .address-line-elsewhere').bind('change.shoppingCart', function(){
		    	var compareToAddress = '';
		    	var compareToAddress2="";
		    	
		    	$('.address-line').each(function(){
		    		compareToAddress += " "+$(this).val();
		    	});
		    	$('.address-line-elsewhere').each(function(){
		    		if($(this).val() != ""){
				    	compareToAddress2 += " "+$(this).val();	
			    	}
		    	})

		    	if(compareToAddress2.length > 0 && $('#deliveryElsewhere').is(':checked')){
			    	compareToAddress = compareToAddress2;
			    	methods.logger("Distance calc: Using address 'elsewhere'");
			    }
			    else {
				    methods.logger("Distance calc: Using normal delivery address");
			    }
			    if(methods.allAddressFieldsFilledOut()){
			    	methods.calculateDistance(compareToAddress, function(){
			   			methods.updatePrices(elt);		    	
			    	});
		    	}
		    	else {
					distance = -1;
					methods.updatePrices(elt);							    
				}
		    });	   		    
		    
		    
		    $("body").on('click.shoppingCart', 'a.removefromcart-checkout', function(event){
		    	methods.removeProduct(elt,event);
		    	methods.removeProductFromCheckoutPage(elt,event);
    			methods.updatePrices(elt);
		    });
		    $('.addtocart').on('click.shoppingCart', function(event){
		    	event.preventDefault();

			    var b = methods.addProduct(elt,event);
	    		if(b){
				   // methods.addExtraProducts(elt,event); //aanvullingen
				    methods.updatePrices(elt);
				    $('.product-added').removeClass('hidden');
			    }
		   });
	    },
	    lookupProduct: function(productId){
	    	if(webshopProducts instanceof Array) {
	    		for(var i = 0; i < webshopProducts.length; i++){
	    			var prod = webshopProducts[i];
	    			
		    		if(prod.Product_id == productId){
			    		return prod;
		    		}
	    		}
	    		
	    		return null;
	    	}
	    	else { //detailpage
		    	return webshopProducts;
	    	}

	   	},
	    addProduct : function (elt, event, productData) {
			var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
			var cartDataStore = settings.cartDataStore;
				    	
	    	var quant=1;
	    	var product = null;
	
	    	if(productData == null || productData == undefined){
		       	var clicked = $(event.currentTarget);
		       	var prodId = clicked.attr('product-id');

				quant = parseInt($('#product-amount-'+prodId).val());
	  			
		    	productRef = methods.lookupProduct(prodId);

		    	if(productRef == null){
			    	methods.logger("FATAL: product data is not embedded in page!");
		    	}

		    	//deepcopy it
		    	product = jQuery.extend(true, {}, productRef);

	    		product = methods.addSelectedOptionsToProduct(product, productRef);

				product.quantity = quant;
			
			}
			else { //used productData as input, ignore the event parameter, assume quantity is set in there
				product = productData;
			}
			
			//returns null if non-existent, and the obj from the cart it's equal to otherwise
			var existingProduct = methods.productExists(elt, product); 
			
			//check if product exists in store
			if(existingProduct != null){ //get the current quantity 
				methods.logger("product exists");

				existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(product.quantity);				
			}
			else {
			   methods.logger("product does not exist");
			   if(cartDataStore == "EMPTY")
			   		cartDataStore = [];
			   
			   cartDataStore.push(product);
			}
			methods.logger("cart: ");
			methods.logger(cartDataStore);
			
			methods.persist(elt);
			methods.logger("calling render");
			methods.render(elt);						

			return true;

	    },	
	    render : function(elt){
	    	methods.logger("Rendering");
			var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
			var cartDataStore = settings.cartDataStore;
	    	var str;

	    	if(cartDataStore.length == 0){
   		    	methods.logger("Empty cart");
	    		str=methods.getTemplate(elt, "<li><p style='margin-left: 10px;'>Het winkelwagentje is leeg.<p></li>");
	    	
	    	}
	    	else {
	    	   	methods.logger("Non-empty cart");
	    	   	var productsHtml= '';
		    	//loop over cartDataStore, and fill up all lists
		    	for(var i = 0; i < cartDataStore.length ; i++){
		    		var obj = cartDataStore[i];
					var removeclass = 'productid="'+obj.Product_id+'"';
										
					var title = obj.title;					
					var selected_options_attr =''; 

					if(obj.ProductOption != null && obj.ProductOption != undefined && obj.ProductOption.length > 0){
						title += " (";

						selected_options_attr += 'selected_options="';

						for(var j = 0; j < obj.ProductOption.length; j++){
							title += obj.ProductOption[j].optionName+" "+obj.ProductOption[j].optionValueName;
							selected_options_attr += obj.ProductOption[j].option_id;
							if(j < obj.ProductOption.length-1) {
								title+= ', ';
								selected_options_attr+=',';
							}
						}

						selected_options_attr += '"';
						title += ")";
					}
					
		    		productsHtml += 
		    				'<li class="product-row">'+
								 '<span class="quantity">'+obj.quantity+'x</span>'+
								 '<span class="product-name">'+title+'</span>'+
								 '<span class="product-remove"><a href="#" '+removeclass+' '+selected_options_attr+' class="removefromcart">&times;</a></span>'+
								 '<div style="clear: both"></div>'
							'</li>';
		    	} 
		    	str = methods.getTemplate(elt, productsHtml);
	    	}
	    	
	    	$this.html(str);
	    },
	    removeProduct : function (elt, event) {
	    	methods.logger("Removing product");
	    	var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
			var cartDataStore = settings.cartDataStore;	    	
	    	
	    	var clicked = $(event.currentTarget);
		    
	       	var id;
	       	
	       	if(clicked.attr("productid") != null)
	    		id = clicked.attr("productid");

	    	for(var i = 0; i < cartDataStore.length; i++){
				var obj = cartDataStore[i];

	    		if(id == obj.Product_id) {
			       	methods.logger("TODO: implement options @ removeProduct");
			       	var equal = true;  
			       	if(equal) {
				    	cartDataStore.splice(i,1);
				    	break;						       	
				     } 
			    }
	    	}
	    		    	
	    	methods.persist(elt);
	    	
	    	//click from the checkout page or from the X in the cart?
	    	if(clicked.attr('class') == 'removefromcart-checkout'){
			    $('li.product-row span.product-remove a.removefromcart').each(function(){
				   if($(this).attr('productid') == id){
					   $(this).parents('.product-row').fadeOut();
				   } 
			    });
		    }	
		    else {
		    	var parentRow = clicked.parents('.product-row');			    
		    	parentRow.fadeOut();		    	
		    }        
	    },
	    productExists : function(elt, product){
			var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
			var cartDataStore = settings.cartDataStore;
	    	
	    	for(var i = 0; i < cartDataStore.length ; i++){
	    	   var obj = cartDataStore[i];
	    	   if (product.Product_id == obj.Product_id){
			    	 //if it is a product, first check if there is an extra option configuration, AND one with this option config does not exist.
			    	 methods.logger("TODO implement options on productExists");
			    	if(methods.checkProductOptionsAreEqual(product, obj))
			    		return obj;
			    	//else continue to loop the cart
			    	
		    	}
	    	}
	    	return null;
	    },	    
	    checkProductOptionsAreEqual : function(product1, product2){
		    var options1 = product1.ProductOption;
		    var options2 = product2.ProductOption;
		    
		    if(options1 == null && options2 == null)
		    	return true;
		    	
		    if((options1 != null && options2 == null) || (options1 == null && options2 != null))
		    	return false;
		    
		    if(options1.length != options2.length) return false;
	
		    //both options1 and 2 are not null and have same length;
		    for(var i = 0; i < options1.length; i++){
		    	var checkingVal = options1[i].ProductOptionValue_id;
		    	var checkingOption = options1[i].ProductOption_id;
		    	
		    	var found = false;
			    for(var j = 0; j < options2.length; j++){
				   if(checkingVal    === options2[j].ProductOptionValue_id &&
				   	  checkingOption === options2[j].ProductOption_id) {
				   		found = true;
				   		break;
				   }
			    }
			    
			    if(!found) return false;
		    }
		    return true;
	    },	        
	    addSelectedOptionsToProduct : function(product, productInSite){
	    	product.ProductOption = [];
    		for(var i = 0; i < productInSite.ProductOption.length ; i++){
    			var cur = productInSite.ProductOption[i];
    			var optionSelectObj = {};
    			optionSelectObj.ProductOption_id = cur.ProductOption_id;
    			optionSelectObj.ProductOptionValue_id = $('#ProductOption_'+cur.ProductOption_id).val();
    			optionSelectObj.optionName = $('#ProductOptionName_'+cur.ProductOption_id).html();

    			optionSelectObj.extraPrice = $('#ProductOptionValueName_'+optionSelectObj.ProductOptionValue_id).attr('extraPrice');
    			var valName = $('#ProductOptionValueName_'+optionSelectObj.ProductOptionValue_id).attr('valueName');
    			optionSelectObj.optionValueName = valName;
    			methods.logger("adding product option: ");
    			methods.logger(optionSelectObj);
    			product.ProductOption.push(optionSelectObj);
    		}
	    	return product;
	    },	
	    	        
	    updatePrices : function(elt){
			var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
			var cartDataStore = settings.cartDataStore;
			var price = 0;
			var vatMap = {};
	    	//loop over cartDataStore
	    	totalInclVat=0;
	    	for(var i = 0; i < cartDataStore.length ; i++){
	    		var obj = cartDataStore[i];
	    		var currentPrice = 0;



	    		var p = parseFloat(obj.price);
	    		currentPrice = p * parseInt(obj.quantity);
	    		var optionsPrice = methods.addOptionPrices(obj);
	    			    		
	    		totalInclVat += currentPrice;
	    		totalInclVat += optionsPrice;
	    		
	    		//update vatMap
    			if(vatMap["x"+obj.VAT] == null || vatMap["x"+obj.VAT] == undefined){
	    			vatMap["x"+obj.VAT] = 0;
	    		}

	    		methods.logger("updating vatMap with: "+ parseFloat(obj.price+optionsPrice));
	    		vatMap["x"+obj.VAT] += parseFloat(currentPrice+optionsPrice);
	    		
	    		
	    	}
	    	
	    	methods.renderDeliveryMethodAndCostsOnCheckout(vatMap, totalInclVat);
			
			vatMap["x0.21"] += parseFloat(deliveryCosts.price);
	    	totalInclVat    += parseFloat(deliveryCosts.price);

			
	    	var totalExclVat = methods.renderVatRowsOnCheckout(vatMap, totalInclVat);
	    	methods.renderExclPriceOnCheckout(totalExclVat);
    	

	    	settings.vatMap = vatMap;

	    	$('.total-price').html(methods.formatEuro(totalInclVat));
	    	$('.total-field').html("<strong>&euro; "+methods.formatEuro(totalInclVat)+"</strong>");

	    },
		allAddressFieldsFilledOut : function() {
 	        var ret = true;
 	        var deliveryElseWhere = $('#deliveryElsewhere').is(':checked');
		    $('.address-line').each(function(){
		    	var x = $(this).val();
		    	if(deliveryElseWhere){
			    	if($(this).attr('id').indexOf("delivery") !== -1){
				    	if(x === undefined || x === null || x == "") {
				    		methods.logger("Not all address fields set");
					    	ret = false;
				    	}
			    	}
		    	}
		    	else {
		    		if($(this).attr('id').indexOf("delivery") === -1){
				    	if(x === undefined || x === null || x == "") {
				    		methods.logger("Not all address fields set");
					    	ret = false;
				    	}
			    	}
		    	}
			});
			
			if(ret){
	    		methods.logger("All address fields set");			 
			}
			return ret;
	    },	    
	    renderDeliveryMethodAndCostsOnCheckout : function(vatMap, totalInclVat) {
	    	//fetch #delivery
	    	var deliveryMethodElt = $('#deliveryMethods option').filter(':selected');
			$('#not-enough-ordered').addClass('hidden').removeClass('alert-error');
			$('.submit-controls').removeClass('disabled');
			$('.deliverycosts-field').html("<strong>€ "+methods.formatEuro(0.0)+"</strong>");

	    	if(deliveryMethodElt.attr('value') == 0){ //use settings.deliveryCostTable
		    	var doNotDeliver = true;
		    	var notEnoughOrdered = false;
		    	$('#not-enough-ordered').addClass('hidden').html('');
				if(distance > settings.deliveryCostsTable[settings.deliveryCostsTable.length -1].maxKm){
					$('#not-enough-ordered').removeClass('hidden').addClass('alert-error').html('Wij bezorgen helaas niet op deze afstand. Kies een andere verzendmethode.');
					deliveryCosts.price = 0.0;
					$('.deliverycosts-field').html("<strong>€ "+methods.formatEuro(deliveryCosts.price)+"</strong>");

					return;
				}
				for(var i = 0; i < settings.deliveryCostsTable.length; i++){
					var min = parseInt(settings.deliveryCostsTable[i].minKm);
					var max = parseInt(settings.deliveryCostsTable[i].maxKm);	
					methods.logger(min+" "+max+" "+distance);
					if(min <= distance && distance < max) { //if distance is within this range
						if(totalInclVat < parseFloat(settings.deliveryCostsTable[i].minimumOrderPrice)){
							if(distance > 0){
								$('#not-enough-ordered').removeClass('hidden').addClass('alert-error').html(
								'We bezorgen op deze afstand ('+ 
												methods.formatEuro(distance)+' km) vanaf een bedrag van €'+
												methods.formatEuro(parseFloat(settings.deliveryCostsTable[i].minimumOrderPrice)));
								doNotDeliver=true;
								notEnoughOrdered = true;
							    //hide submit buttons
								$('.submit-controls').addClass('disabled');
							}
							break;
						}
						else {
							//update the table of the checkout 
							deliveryCosts.price = parseFloat(settings.deliveryCostsTable[i].price);
							$('.submit-controls').removeClass('disabled');
							$('#not-enough-ordered').addClass('hidden');
							$('.deliverycosts-field').html("<strong>€ "+methods.formatEuro(deliveryCosts.price)+"</strong>");
							doNotDeliver = false;						
						}
					}
				}
				
				/*
				if(doNotDeliver && !notEnoughOrdered){
					methods.logger("DO NOT DELIVER, OUT OF RANGE");
					$('#not-enough-ordered').removeClass('hidden').html(
						'We bezorgen helaas niet op deze afstand.');
					doNotDeliver=true;
				    //hide submit buttons
					$('.submit-controls').addClass('disabled');
				}				
				*/
						    	
	    	}
	    	else { //use settings.deliveryMethodPrice
		    	deliveryCosts.price = parseFloat(deliveryMethodElt.attr('methodprice'));
		    	$('.deliverycosts-field').html("<strong>€ "+methods.formatEuro(deliveryCosts.price)+"</strong>");
	    	}
	    	

	    },
		calculateDistance : function(cptaddr, callback) {
	    	//calc distance between store and cptaddr (compare to address)
	    	var queryData = {
			  origin: settings.address,
			  destination: cptaddr,
			  travelMode: google.maps.TravelMode.DRIVING,
			  unitSystem: google.maps.UnitSystem.METRIC,
			  region: settings.region
			}
					
			var directionsService = new google.maps.DirectionsService();
			distance = -1;
	        directionsService.route(queryData, function(response, status) {
	            if (status == google.maps.DirectionsStatus.OK) {
	            	distance = parseInt(response.routes[0].legs[0].distance.value) / 1000;
	            	methods.logger("Distance found: "+distance+" km");
	            }
				else {
					methods.logger("Something went wrong, or address not found: "+status)
					methods.logger(response);
				}            
   	           	if(callback != null && callback != undefined)
	            	callback.call(distance);
			});			
	    },	    
	    renderExclPriceOnCheckout : function(totalExclVat){
		   $('.subtotal-field').html('&euro; '+methods.formatEuro(totalExclVat));
	    },
	    renderVatRowsOnCheckout : function(vatMap, totalInclVat) {
	    	var totalExclVat = totalInclVat;
		    methods.logger("renderVatRowsOnCheckout");
		    methods.logger(vatMap);
		  	for(var vatKey in vatMap){
			  	var perc = parseFloat(vatKey.substring(1));

			  	var x = vatKey.replace(".", "_");
			  	var val = vatMap[vatKey];

			  	$('.vat-value-'+x).html(methods.formatEuro(perc*parseFloat(val)));
			  	totalExclVat -= (perc * parseFloat(val));
		  	}  
		  	return totalExclVat;
	    },	    
	    addOptionPrices : function(obj){
	    	methods.logger("addOptionPrices: ", obj);
	    	methods.logger(obj);
	    	if(obj.ProductOption == null){
		    	methods.logger("addOptionPrices: No option, returning 0");
		    	return 0;
	    	}
	    	var ret = 0;
	    	for(var i = 0; i < obj.ProductOption.length; i++){
	    		var option = obj.ProductOption[i];

	    		if(option.extraPrice != null && option.extraPrice != undefined && option.extraPrice != "") {    			
				    methods.logger("Adding option price: "+option.extraPrice);
			    	ret += parseFloat(option.extraPrice);
			    }
			    else {
				    methods.logger("No option price, continueing...");
			    }
	    	}
	    	methods.logger("total option price: "+ret);
	    	return ret * parseInt(obj.quantity);
	    },
	    formatEuro : function(price){
			Number.prototype.formatMoney = function(c, d, t){
			var n = this, c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : 
					d, t = t == undefined ? "." : 
					t, s = n < 0 ? "-" : 
					"", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
					j = (j = i.length) > 3 ? j % 3 : 0;
					
			   return s + (j ? i.substr(0, j) + t : "") 
					    + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t)
					    + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
			};
		
			return price.formatMoney(2,',','.');
		},	    
	    getTemplate : function(elt, content){
			var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
	    
			var str='<ul class="nav pull-right">'+
						'<li class="divider-vertical"></li>'+
						'<li class="dropdown">'+
							'<a class="dropdown-toggle" data-toggle="dropdown" href="#" >'+
							'<i class="icon-shopping-cart icon-white"></i> '+settings.cart_text+': € <span class="total-price"></span><b class="caret"></b></a>'+
							'<ul class="dropdown-menu" id="shopping-cart-dropdown">'+
								'<li><a href="'+settings.checkout_page+'">'+settings.checkout_link+' &rarr;</a></li>'+
								'<li class="divider"></li>'+
								content+
								'<li class="divider"></li>'+
								'<li><a href="#">Totaal: € <span class="total-price"></span></a></li>'+
							
							'</ul>'+
						'</li>'+
					'</ul>';	
		    return str;
	    },		
	    showValidationError : function(){
		    $('#validation-error').removeClass('hidden');
	    },	    
	    hideValidationError : function(){
			$('#validation-error').addClass('hidden');	    
	    },	    
	    createAutoClosingAlert : function(selector, delay) {
	    	$(selector).html('<div class="the-alert alert alert-info fade in">' +
	    						 '<button data-dismiss="alert" class="close" type="button">×</button>' +
	    						 '<p>Product toegevoegd aan de bestelling.</p>' +
	    					 '</div>');
		    var alert = $('.the-alert').alert();
		    window.setTimeout(function() { alert.alert('close') }, delay);
   		},
	    checkCoupon : function(callback){
			$('#discount-text').html('Controleren couponcode…').addClass('hidden');
			if($('#coupon').val() == null || $('#coupon').val() == "" || $('#coupon').val() == undefined)
				return;
	
			$('#discount-text').html('Controleren couponcode…').removeClass('hidden');
			
			$.ajax({
				url: couponUrl,
				data: { "hostname" : hostname , "couponCode" : $('#coupon').val()},
				success: function (jsonObj, textStatus, jqXHR){

					discount = jsonObj.discount;
					couponType = jsonObj.couponType;
					if(discount == 0){
						$('#discount-text')
							.removeClass('hidden')
							.html('Dit is geen geldige couponcode.')
							.addClass('alert-error');
					}
					else {
						$('#discount-text').html('Couponcode geldig, u krijgt '+discount+'% korting.');
						
						$('#discount-text').removeClass('alert-error')
											.addClass('alert-success')
											.removeClass('hidden');
					}
					
					if(callback!=null && callback!=undefined)
						callback(discount, couponType);
				},
				dataType: 'jsonp'
			});		
		},   		
   		
   		
   		clearCart : function(elt){
	   		var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
			cartDataStore = settings.cartDataStore;
	    	methods.logger("Clearing cart!");
	    	cartDataStore = [];
	    	methods.persist();
	    	methods.render();
	    	methods.updatePrices();
	    },    
	    removeProductFromCheckoutPage : function(elt,event){
	    	methods.logger("removeProductFromCheckoutPage");
	  	    	
			event.preventDefault();
 	    	var clicked = $(event.currentTarget);
			var parentRow = clicked.parent().parent();

			parentRow.addClass('hidden');			
	    },   		
		/**
		* Want to call a plugin function later than on initialization (as a public method)?
		* $('#someElement').shoppingCart();
		* and then later:
		* $('#someElement').shoppingCart('test');
		*/
		test : function (){
			return this.each(function(){
				var $this = $(this);
				var data = $this.data('shoppingCart'); //gets the data out of the element. 
				//this way we can have multiple shopping carts if we wished to, but it is just good practice
				//alert(data.settings.checkout_page);

				//alert(settings.checkout_page+"XXXXXX");
			});
		}
	}

	$.fn.shoppingCart = function( method ) {
		
		//the 'this' keyword is a jQuery object
	    // Method calling logic
	    if ( methods[method] ) {
	      return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	    } else if ( typeof method === 'object' || ! method ) {
	      return methods.init.apply( this, arguments );
	    } else {
	      $.error( 'Method ' +  method + ' does not exist on jQuery.shoppingCart' );
	    }    
	};
})( jQuery, window, document  );