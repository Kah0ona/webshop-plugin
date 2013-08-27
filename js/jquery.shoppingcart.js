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
	var vatMap = {};
	var pluginName = "shoppingCart";
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
	
	function ShoppingcartPlugin(element, options){
		this.element = element;
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}
	
	ShoppingcartPlugin.prototype = {
		init : function( options ) {
			var self = this;
			this.cartDataStore = [];		
			this.bindButtons();
			this.load(function(jsonObj){
				self.render();
				self.updatePrices();
			});

		},
		load : function(callback){
		  var self = this;
		  $.ajax({
				url: this.settings.session_url,
				type: 'GET',
				data: {"action" : "load"},
				success: function (jsonObj, textStatus, jqXHR){
					self.logger("Loaded: ");
					self.logger(jsonObj);
					self.cartDataStore=jsonObj;
					callback.call(self, jsonObj);
				},
				dataType: 'json'
			});
	    },
		logger : function(msg){
			if(window.console) {
				console.log(msg);	
			} 
		},
		persist : function(){
			if(this.cartDataStore.length == 0){
				this.cartDataStore="EMPTY";
			}
			
			$.ajax({
				url : this.settings.session_url,
				type: 'POST',
				data: {"shoppingCart" : this.cartDataStore},
				success: function (jsonObj, textStatus, jqXHR){
					this.logger("Persisted: ")
					this.logger(jsonObj);
				},
				dataType: 'json'
			});			
	    },	
	    bindButtons : function(){
	    	var self = this;
			$('body').on('change.shoppingCart', "#deliveryMethods", function(event){
				self.updatePrices();
			});
	    	$('body').on("change.shoppingCart",".deliveryType", function(event){
	    	    self.updatePrices();
	    	});
	    	$("body").on("click.shoppingCart","a.removefromcart", function(event){
   			 	self.logger("a.removefromcart clicked");

		    	event.preventDefault();
		    	self.removeProduct(event);
		    	//this.updateCartTotalPrice();
    			self.updatePrices();
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
			    	self.logger("Distance calc: Using address 'elsewhere'");
			    }
			    else {
				    self.logger("Distance calc: Using normal delivery address");
			    }
			    if(self.allAddressFieldsFilledOut()){
			    	self.calculateDistance(compareToAddress, function(){
			   			self.updatePrices();		    	
			    	});
		    	}
		    	else {
					distance = -1;
					self.updatePrices();							    
				}
		    });	   		    
		    
		    
		    $("body").on('click.shoppingCart', 'a.removefromcart-checkout', function(event){
   			 	self.logger("a.removefromcart-checkout clicked");

		    	self.removeProduct(event);
		    	self.removeProductFromCheckoutPage(event);
    			self.updatePrices();
		    });
		    
		    $('.addtocart').on('click.shoppingCart', function(event){
		    	event.preventDefault();
			    var b = self.addProduct(event);
	    		if(b){
				   // this.addExtraProducts(,event); //aanvullingen
				    self.updatePrices();
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
	    addProduct : function (event, productData) {
				    	
	    	var quant=1;
	    	var product = null;
	
	    	if(productData == null || productData == undefined){
		       	var clicked = $(event.currentTarget);
		       	var prodId = clicked.attr('product-id');

				quant = parseInt($('#product-amount-'+prodId).val());
	  			
		    	productRef = this.lookupProduct(prodId);

		    	if(productRef == null){
			    	this.logger("FATAL: product data is not embedded in page!");
			    	return;
		    	}

		    	//deepcopy it
		    	product = jQuery.extend(true, {}, productRef);

	    		product = this.addSelectedOptionsToProduct(product, productRef);

				product.quantity = quant;
			
			}
			else { //used productData as input, ignore the event parameter, assume quantity is set in there
				product = productData;
			}
			
			//returns null if non-existent, and the obj from the cart it's equal to otherwise
			var existingProduct = this.productExists( product); 
			
			//check if product exists in store
			if(existingProduct != null){ //get the current quantity 
				this.logger("product exists");

				existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(product.quantity);				
			}
			else {
			   this.logger("product does not exist");
			   if(this.cartDataStore == "EMPTY")
			   		this.cartDataStore = [];
			   
			   this.cartDataStore.push(product);
			}
			this.logger("cart: ");
			this.logger(this.cartDataStore);
			
			this.persist();
			this.logger("calling render");
			this.render();						

			return true;

	    },
	    removeProduct : function (event) {
	    	this.logger("Removing product");
	    	
	    	var clicked = $(event.currentTarget);
		    
	       	var id;
	       	
	       	if(clicked.attr("productid") != null)
	    		id = clicked.attr("productid");

	    	for(var i = 0; i < this.cartDataStore.length; i++){
				var obj = this.cartDataStore[i];

	    		if(id == obj.Product_id) {
			       	this.logger("TODO: implement options @ removeProduct");
			       	var equal = true;  
			       	if(equal) {
				    	this.cartDataStore.splice(i,1);
				    	break;						       	
				     } 
			    }
	    	}
	    		    	
	    	this.persist();
	    	
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
	    render : function(){
	    	this.logger("Rendering");
	    	var str;

	    	if(this.cartDataStore.length == 0){
   		    	this.logger("Empty cart");
	    		str=this.getTemplate("<li><p style='margin-left: 10px;float: right;' class='emptycart'>Het winkelwagentje is leeg.<p></li>");
	    	
	    	}
	    	else {
	    	   	this.logger("Non-empty cart");
	    	   	var productsHtml= '';
		    	//loop over cartDataStore, and fill up all lists
		    	for(var i = 0; i < this.cartDataStore.length ; i++){
		    		var obj = this.cartDataStore[i];
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
		    	str = this.getTemplate(productsHtml);
	    	}
	    	
	    	$(this.element).html(str);
	    },

	    productExists : function(product){
	    	for(var i = 0; i < this.cartDataStore.length ; i++){
	    	   var obj = this.cartDataStore[i];
	    	   if (product.Product_id == obj.Product_id){
			    	 //First check if there is an extra option configuration, AND one with this option config does not exist.
			    	if(this.checkProductOptionsAreEqual(product, obj))
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
    		for(var i = 0; i < productInSite.ProductOption.length; i++){
    			var cur = productInSite.ProductOption[i];
    			var optionSelectObj = {};
    			optionSelectObj.ProductOption_id = cur.ProductOption_id;
    			optionSelectObj.ProductOptionValue_id = $('#ProductOption_'+cur.ProductOption_id).val();
    			optionSelectObj.optionName = $('#ProductOptionName_'+cur.ProductOption_id).html();

    			optionSelectObj.extraPrice = $('#ProductOptionValueName_'+optionSelectObj.ProductOptionValue_id).attr('extraPrice');
    			var valName = $('#ProductOptionValueName_'+optionSelectObj.ProductOptionValue_id).attr('valueName');
    			optionSelectObj.optionValueName = valName;
    			this.logger("adding product option: ");
    			this.logger(optionSelectObj);
    			product.ProductOption.push(optionSelectObj);
    		}
	    	return product;
	    },	
	    	        
	    updatePrices : function(){
			var price = 0;
			var vatMap = {};
	    	//loop over cartDataStore
	    	totalInclVat=0;
	    	for(var i = 0; i < this.cartDataStore.length ; i++){
	    		var obj = this.cartDataStore[i];
	    		var currentPrice = 0;



	    		var p = parseFloat(obj.price);
	    		currentPrice = p * parseInt(obj.quantity);
	    		var optionsPrice = this.addOptionPrices(obj);
	    			    		
	    		totalInclVat += currentPrice;
	    		totalInclVat += optionsPrice;
	    		
	    		//update vatMap
    			if(vatMap["x"+obj.VAT] == null || vatMap["x"+obj.VAT] == undefined){
	    			vatMap["x"+obj.VAT] = 0;
	    		}

	    		this.logger("updating vatMap with: "+ parseFloat(obj.price+optionsPrice));
	    		vatMap["x"+obj.VAT] += parseFloat(currentPrice+optionsPrice);
	    		
	    		
	    	}
	    	
	    	this.renderDeliveryMethodAndCostsOnCheckout(vatMap, totalInclVat);
			
			vatMap["x0.21"] += parseFloat(deliveryCosts.price);
	    	totalInclVat    += parseFloat(deliveryCosts.price);

			
	    	var totalExclVat = this.renderVatRowsOnCheckout(vatMap, totalInclVat);
	    	this.renderExclPriceOnCheckout(totalExclVat);

	    	$('.total-price').html(this.formatEuro(totalInclVat));
	    	$('.total-field').html("<strong>&euro; "+this.formatEuro(totalInclVat)+"</strong>");

	    },
		allAddressFieldsFilledOut : function() {
 	        var ret = true;
 	        var deliveryElseWhere = $('#deliveryElsewhere').is(':checked');
 	        var self = this;
		    $('.address-line').each(function(){
		    	var x = $(this).val();
		    	if(deliveryElseWhere){
			    	if($(this).attr('id').indexOf("delivery") !== -1){
				    	if(x === undefined || x === null || x == "") {
				    		this.logger("Not all address fields set");
					    	ret = false;
				    	}
			    	}
		    	}
		    	else {
		    		if($(this).attr('id').indexOf("delivery") === -1){
				    	if(x === undefined || x === null || x == "") {
				    		self.logger("Not all address fields set");
					    	ret = false;
				    	}
			    	}
		    	}
			});
			
			if(ret){
	    		this.logger("All address fields set");			 
			}
			return ret;
	    },	    
	    renderDeliveryMethodAndCostsOnCheckout : function(vatMap, totalInclVat) {
	    	//fetch #delivery
	    	var deliveryMethod = $('#deliveryMethods option').filter(':selected');
			$('#not-enough-ordered').addClass('hidden').removeClass('alert-error');
			$('.submit-controls').removeClass('disabled');
			$('.deliverycosts-field').html("<strong>€ "+this.formatEuro(0.0)+"</strong>");

	    	if(deliveryMethod.attr('value') == 0){ //use settings.deliveryCostTable
		    	var doNotDeliver = true;
		    	var notEnoughOrdered = false;
		    	$('#not-enough-ordered').addClass('hidden').html('');
				if(distance > this.settings.deliveryCostsTable[this.settings.deliveryCostsTable.length -1].maxKm){
					$('#not-enough-ordered').removeClass('hidden').addClass('alert-error').html('Wij bezorgen helaas niet op deze afstand. Kies een andere verzendmethode.');
					deliveryCosts.price = 0.0;
					$('.deliverycosts-field').html("<strong>€ "+this.formatEuro(deliveryCosts.price)+"</strong>");
					$('.submit-controls').addClass('disabled');

					return;
				}
				for(var i = 0; i < this.settings.deliveryCostsTable.length; i++){
					var min = parseInt(this.settings.deliveryCostsTable[i].minKm);
					var max = parseInt(this.settings.deliveryCostsTable[i].maxKm);	
					this.logger(min+" "+max+" "+distance);
					if(min <= distance && distance < max) { //if distance is within this range
						if(totalInclVat < parseFloat(this.settings.deliveryCostsTable[i].minimumOrderPrice)){
							if(distance > 0){
								$('#not-enough-ordered').removeClass('hidden').addClass('alert-error').html(
								'We bezorgen op deze afstand ('+ 
												this.formatEuro(distance)+' km) vanaf een bedrag van €'+
												this.formatEuro(parseFloat(this.settings.deliveryCostsTable[i].minimumOrderPrice)));
								doNotDeliver=true;
								notEnoughOrdered = true;
							    //hide submit buttons
								$('.submit-controls').addClass('disabled');
							}
							break;
						}
						else {
							//update the table of the checkout 
							deliveryCosts.price = parseFloat(this.settings.deliveryCostsTable[i].price);
							$('.submit-controls').removeClass('disabled');
							$('#not-enough-ordered').addClass('hidden');
							$('.deliverycosts-field').html("<strong>€ "+this.formatEuro(deliveryCosts.price)+"</strong>");
							doNotDeliver = false;						
						}
					}
				}
				
				/*
				if(doNotDeliver && !notEnoughOrdered){
					this.logger("DO NOT DELIVER, OUT OF RANGE");
					$('#not-enough-ordered').removeClass('hidden').html(
						'We bezorgen helaas niet op deze afstand.');
					doNotDeliver=true;
				    //hide submit buttons
					$('.submit-controls').addClass('disabled');
				}				
				*/
						    	
	    	}
	    	else { //use settings.deliveryMethodPrice
	    		var x = deliveryMethod.attr('methodprice');
	    		if(x == undefined || x == null){
		    		x = 0;
	    		}
		    	deliveryCosts.price = parseFloat(x);
		    	$('.deliverycosts-field').html("<strong>€ "+this.formatEuro(deliveryCosts.price)+"</strong>");
	    	}
	    	

	    },
		calculateDistance : function(cptaddr, callback) {
	    	//calc distance between store and cptaddr (compare to address)
	    	var queryData = {
			  origin: this.settings.address,
			  destination: cptaddr,
			  travelMode: google.maps.TravelMode.DRIVING,
			  unitSystem: google.maps.UnitSystem.METRIC,
			  region: this.settings.region
			}
					
			var directionsService = new google.maps.DirectionsService();
			distance = -1;
	        directionsService.route(queryData, function(response, status) {
	            if (status == google.maps.DirectionsStatus.OK) {
	            	distance = parseInt(response.routes[0].legs[0].distance.value) / 1000;
	            	this.logger("Distance found: "+distance+" km");
	            }
				else {
					this.logger("Something went wrong, or address not found: "+status)
					this.logger(response);
				}            
   	           	if(callback != null && callback != undefined)
	            	callback.call(distance);
			});			
	    },	    
	    renderExclPriceOnCheckout : function(totalExclVat){
		   $('.subtotal-field').html('&euro; '+this.formatEuro(totalExclVat));
	    },
	    renderVatRowsOnCheckout : function(vatMap, totalInclVat) {
	    	var totalExclVat = totalInclVat;
		    this.logger("renderVatRowsOnCheckout");
		    this.logger(vatMap);
		  	for(var vatKey in vatMap){
			  	var perc = parseFloat(vatKey.substring(1));

			  	var x = vatKey.replace(".", "_");
			  	var val = vatMap[vatKey];

			  	$('.vat-value-'+x).html(this.formatEuro(perc*parseFloat(val)));
			  	totalExclVat -= (perc * parseFloat(val));
		  	}  
		  	return totalExclVat;
	    },	    
	    addOptionPrices : function(obj){
	    	this.logger("addOptionPrices: ", obj);
	    	this.logger(obj);
	    	if(obj.ProductOption == null){
		    	this.logger("addOptionPrices: No option, returning 0");
		    	return 0;
	    	}
	    	var ret = 0;
	    	for(var i = 0; i < obj.ProductOption.length; i++){
	    		var option = obj.ProductOption[i];

	    		if(option.extraPrice != null && option.extraPrice != undefined && option.extraPrice != "") {    			
				    this.logger("Adding option price: "+option.extraPrice);
			    	ret += parseFloat(option.extraPrice);
			    }
			    else {
				    this.logger("No option price, continueing...");
			    }
	    	}
	    	this.logger("total option price: "+ret);
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
	    getTemplate : function(content){
    
			var str='<ul class="nav pull-right">'+
						'<li class="divider-vertical"></li>'+
						'<li class="dropdown">'+
							'<a class="dropdown-toggle" data-toggle="dropdown" href="#" >'+
							'<i class="icon-shopping-cart icon-white"></i> '+this.settings.cart_text+': € <span class="total-price"></span><b class="caret"></b></a>'+
							'<ul class="dropdown-menu" id="shopping-cart-dropdown">'+
								'<li><a href="'+this.settings.checkout_page+'">'+this.settings.checkout_link+' &rarr;</a></li>'+
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
   		
   		
   		clearCart : function(){
	    	this.logger("Clearing cart!");
	    	cartDataStore = [];
	    	this.persist();
	    	this.render();
	    	this.updatePrices();
	    },    
	    removeProductFromCheckoutPage : function(event){
	    	this.logger("removeProductFromCheckoutPage");
	  	    	
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
			});
		}
	}

	$.fn[ pluginName ] = function ( options ) {
		return this.each(function() {
			if ( !$.data( this, "plugin_" + pluginName ) ) {
					$.data( this, "plugin_" + pluginName, new ShoppingcartPlugin( this, options ) );
			}
		});
	};
})( jQuery, window, document  );