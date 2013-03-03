/**
* JQuery plugin that acts as a shopping cart 
* Author: Marten Sytema (marten@sytematic.nl)
* Plugin dependencies: 
* - JQuery-JSON: http://code.google.com/p/jquery-json/
* Version: 0.6
*/
;(function( $, window, document, undefined ) {

	var methods = {
		init : function( options ) { 
			return this.each(function(){
				methods.initSettingsData(this, options);
				methods.bindButtons(this);
				methods.load(this, function(jsonObj){
					methods.logger(this, jsonObj);
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
				      'session_url' : '/wp-content/plugins/webshop-plugin/models/CartStore.php'
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
	    
	    	$('body').on("change.shoppingCart",".deliveryType", function(event){
	    	     methods.updatePrices(elt);
	    	});
	    	$("body").on("click.shoppingCart","a.removefromcart", function(event){
		    	event.preventDefault();
		    	methods.removeProduct(elt,event);
		    	methods.updateCartTotalPrice(elt);
    			methods.updatePrices(elt);
				event.stopPropagation();
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
				    methods.addExtraProducts(elt,event); //aanvullingen
				    methods.updatePrices(elt);
				    $('.product-added').removeClass('hidden');
			    }
		   });
	    },
	    /* This method assumes that on a page there is a variable holding the product data. 
		 *    We currently only support adding a product to the cart from a detail page
	     */
	    lookupProduct: function(){
	   		return webshopProduct;
	   	},
	    addProduct : function (elt, event, productData) {
			var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
			var cartDataStore = settings.cartDataStore;
				    	
	    	var quant=1;
	    	var product = null;
	    	
	    	if(productData == null || productData == undefined){
		       	var clicked = $(event.currentTarget);

				quant = parseInt($('#product-amount').val());

		    	productRef = methods.lookupProduct();
		    	//deepcopy it
		    	product = jQuery.extend(true, {}, productRef);
	    		product = methods.addSelectedOptionsToProduct(product);
				product.quantity = quant;
			
			}
			else { //used productData as input, ignore the event parameter, assume quantity is set in there
				product = productData;
			}
			
			//returns null if non-existent, and the obj from the cart it's equal to otherwise
			var existingProduct = methods.productExists(product); 
			
			//check if product exists in store
			if(existingProduct != null){ //get the current quantity 
				methods.logger("product exists");

				if(existingProduct.type == 'package'){
					methods.updateQuantityInPackage(product,existingProduct);
				}

				existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(product.quantity);				
/*				else { //material or product, just update the quantity
					existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(product.quantity);				
				}*/
			}
			else {
			   methods.logger("product does not exist");
			   if(cartDataStore == "EMPTY")
			   		cartDataStore = [];
			   
			   product = methods.multiplyProductsInPackageByX(quant, product);
			   cartDataStore.push(product);
			}
			methods.logger("cart: ");
			methods.logger(cartDataStore);
			
			methods.persist();
			
			//add the item to each cart visually
			cartPluginInstance.each(function(){
				methods.logger("calling render");
				methods.render($(this));			
			});
			
			return true;

	    },	    
	    addSelectedOptionsToProduct : function(product){
    		for(var i = 0; i < productOptions.options.length ; i++){
	    		if(methods.optionIsSelected(parseInt(productOptions.options[i].option_id))){
	    			if(product.options == null || product.options == undefined){
		    			product.options = [];
	    			}
	    			
			    	product.options.push(productOptions.options[i]);			    		
	    		}
    		}

	    	return product;
	    },	
	    optionIsSelected : function(id){
	    	methods.logger("TODO: IMPLEMENT ME (optionIsSelected)");
	    	return true; 
	    },	        
	    updatePrices : function(elt){
			var $this = $(elt);
			var settings = $this.data('shoppingCart').settings;
		    
	    },
	    getTemplate : function(elt){
			var $this = $(elt);
			var settings = $this.data('shoppingCart');
	    
			var str='<ul class="nav pull-right">'+
						'<li class="divider-vertical"></li>'+
						'<li class="dropdown">'+
							'<a class="dropdown-toggle" data-toggle="dropdown" href="#" >'+
							'<i class="icon-shopping-cart icon-white"></i> '+settings.cart_text+': € <span class="total-price"></span><b class="caret"></b></a>'+
							'<ul class="dropdown-menu">'+
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