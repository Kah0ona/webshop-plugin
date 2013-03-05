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
				      'session_url' : '/wordpress/wp-content/plugins/webshop-plugin/models/CartStore.php',
				      'cartDataStore' : []
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
		    	methods.logger("TODO product options adding");
	    		//product = methods.addSelectedOptionsToProduct(product);
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

					if(obj.options != null && obj.options != undefined){
						title += " (";
						if(obj.options.length > 0){
							selected_options_attr += 'selected_options="';
						}
						for(var j = 0; j < obj.options.length; j++){
							title += obj.options[j].optionName+": "+obj.options[i].optionValue;
							selected_options_attr += obj.options[j].option_id;
							if(j < obj.options.length-1) {
								title+= ', ';
								selected_options_attr+=',';
							}
						}
						if(obj.options.length > 0){
							selected_options_attr += '"';
						}
						
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
			    	//if(methods.checkProductOptionsAreEqual(product, obj))
			    		return obj;
			    	//else continue to loop the cart
			    	
		    	}
	    	}
	    	return null;
	    },	    
	    checkProductOptionsAreEqual : function(product1, product2){
		    var options1 = product1.options;
		    var options2 = product2.options;
		    
		    if(options1 == null && options2 == null)
		    	return true;
		    	
		    if((options1 != null && options2 == null) || (options1 == null && options2 != null))
		    	return false;
		    
		    if(options1.length != options2.length) return false;
		    	
		    //both options1 and 2 are not null and have same length;
		    for(var i = 0; i < options1.length; i++){
		    	var checking = options1[i].ingredients_id;
		    	found = false;
			    for(var j = 0; j < options2.length; j++){
				   if(checking == options2[i].ingredients_id) {
				   		found = true;
				   		break;
				   }
			    }
			    if(!found) return false;
		    }
			//if we reach this, they are equal		    
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
	    		
	    		totalInclVat += currentPrice;
	    		
	    		//update vatMap
    			if(vatMap["x"+obj.VAT] == null || vatMap["x"+obj.VAT] == undefined){
	    			vatMap["x"+obj.VAT] = 0;
	    		}

	    		methods.logger("updating vatMap with: "+ parseFloat(obj.price));
	    		vatMap["x"+obj.VAT] += parseFloat(currentPrice);
	    	}
	    	
	    	settings.vatMap = vatMap;

	    	$('.total-price').html(methods.formatEuro(totalInclVat));


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