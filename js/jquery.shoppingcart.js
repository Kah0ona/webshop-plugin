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
	var discount = undefined;
	var pluginName = "shoppingCart";
	var defaults =  {
				      'detail' : false, //is this a detail page?
				      'checkout_page' : '/checkout',
				      'checkout_link' : 'Afrekenen',
					  'is_checkout' : false,
				      'cart_text' : 'Mijn bestelling',
				      'address' : '',
				      'session_url' : '/wordpress/wp-content/plugins/webshop-plugin/models/CartStore.php',
				      'personaldetails_url' : '/wordpress/wp-content/plugins/webshop-plugin/models/PersonalDetailsStore.php',
					  'use_scheduler' : false,
					  'max_future_delivery_date' : null,
					  'pricesInclVat' : true,
				      'cartDataStore' : [],
				      'deliveryMethods' : [],
				      'deliveryCostsTable' : []
	};	
	
	function ShoppingcartPlugin(element, options){
		this.element = element;
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.couponUrl = options.couponUrl;
		this.hostname = options.hostname;
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

			if(this.settings.is_checkout){
				this.loadPersonalDetails();
			}
			
			if($.datepicker !== undefined){
				$.datepicker.setDefaults( $.datepicker.regional[ "nl" ] )		
				$(".deliveryDatePicker").datepicker({
					onSelect: function(dateText, inst){
						self.logger('Selected date: '+dateText);
						if(self.settings.use_scheduler){
							self.handleDateSelect(dateText);
						}

					},
					altField: "#deliveryDate"
				});			
			}

			this.loadSchedulingData();
		
		},

		populateDatePickerInit : function(){
			var inTwoDays = new Date();
			inTwoDays.setDate(inTwoDays.getDate() + 1);
			inTwoDays.setHours(0);
			inTwoDays.setMinutes(0);
			
			var s = new String((inTwoDays.getDate()+1));
			if(s.length == 1){
				s = "0"+s;
			}
			var m = new String((inTwoDays.getMonth()+1));
			if(m.length==1){
				m = "0"+m;
			}
			$('#deliveryDate').val(s+'-'+m+"-"+(inTwoDays.getYear()+1900));
		},
		handleDateSelect : function(dateText) {
			var self = this;
			var valid = this.checkTooFarInFuture();
			var proceed = true;
			if(valid) {
				var tgt = $('.schedulerMessage');
				tgt.hide();
				tgt.html('');
				tgt.removeClass('alert-error').addClass('alert-success');

				var dt = self.parseDdMmYyyyString(dateText);
				var inTwoDays = new Date();
				inTwoDays.setDate(inTwoDays.getDate() + 1);
				inTwoDays.setHours(0);
				inTwoDays.setMinutes(0);
				if(dt.getTime() < inTwoDays.getTime()){
					proceed = false;
					tgt.show();
					tgt.html('Kies tenminste 2 dagen in de toekomst.');
					tgt.addClass('alert-error').removeClass('alert-success');

					var s = new String((inTwoDays.getDate()+1));
					if(s.length == 1){
						s = "0"+s;
					}
					var m = new String((inTwoDays.getMonth()+1));
					if(m.length==1){
						m = "0"+m;
					}

					$('#deliveryDate').val(s+'-'+m+"-"+(inTwoDays.getYear()+1900));
				}
				self.logger('click on datepicker');
				self.populateTimeSlotsBasedOnSelectedDate();
				if(proceed){ 
					self.renderOccupationMessage();
				}

			}
		},
		populateTimeSlotsBasedOnSelectedDate : function(){
			if(this.settings.use_scheduler){
				var d = $('#deliveryDate').val();
				var date = null;
				if(d == null || d == ""){
					date = new Date();
				} else {
					date = this.parseDdMmYyyyString(d);
				}
				var data = this.settings.scheduler_data;

				var weekDay = this.getDayOfWeekByIndex(date.getDay());
				var slotSize = this.calculateSlotSizeFromCart();

				var hours = data.openingHours[weekDay];

				//for each slot, start from start time, and create timeslots the size of time
				var html = "";
				for(var i = 0; i < hours.length; i++){
					var h = hours[i];

					var p = h.split("-");
					var p2 = p[0].split(".");
					var hS = parseInt(p2[0]);
					var mS = parseInt(p2[1]);

					var pE = p[1].split(".");
					var hE = parseInt(pE[0]);
					var mE = parseInt(pE[1]);

					var endDateHours = new Date();
					endDateHours.setMinutes(mE);
					endDateHours.setHours(hE);

					var slotDate = new Date();
					slotDate.setMinutes(mS);
					slotDate.setHours(hS);
					for( ; this.addMinutesToDate(slotDate, slotSize).getTime() <= endDateHours.getTime() && slotSize != 0; slotDate = this.addMinutesToDate(slotDate, slotSize)){
						var fromM = new String(slotDate.getMinutes());
						if(fromM.length == 1){
							fromM += "0";
						}
						var from = slotDate.getHours()+":"+fromM;
						var endDate = this.addMinutesToDate(slotDate, slotSize); //end of prev slot is start of new one
						var endM = new String(endDate.getMinutes());
						if(endM.length == 1){
							endM += "0";
						}
						var to = endDate.getHours()+":"+endM;
						html += "<option value='"+from+"-"+to+"'>"+from+"-"+to+"</option>";
					}
				}
				if(html.trim() == ""){
					html = "<option>Wij zijn vandaag gesloten</option>";
				}
				$('select[name="deliveryTime"]').html(html);
			}
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
		persist : function(callback){
			if(this.cartDataStore.length == 0){
				this.cartDataStore="EMPTY";
			}
			
			var self = this;
			$.ajax({
				url : this.settings.session_url,
				type: 'POST',
				data: {"shoppingCart" : this.cartDataStore},
				success: function (jsonObj, textStatus, jqXHR){
					self.logger("Persisted: ")
					self.logger(jsonObj);
					callback.call(self, jsonObj);
				},
				dataType: 'json'
			});			
	    },
		loadPersonalDetails : function(){
		  var self = this;
		  $.ajax({
				url: this.settings.personaldetails_url,
				type: 'GET',
				data: {"action" : "load"},
				success: function (jsonObj, textStatus, jqXHR){
					self.logger("Loaded personal details: ");
					self.logger(jsonObj);
					self.populatePersonalDetailForm(jsonObj);

				},
				dataType: 'json'
			});


		},
		populatePersonalDetailForm : function(jsonObj){
			for(var key in jsonObj){
				$('#order-form input[name="'+key+'"], #order-form textarea[name="'+key+'"]').val(jsonObj[key]);
			}
		},
		storePersonalDetailsInSession : function(event){
			var changed = $(event.currentTarget);
			var val = changed.val();
			var self = this;
			var d = {};
			d[changed.attr('name')] = val;

			$.ajax({
				url : this.settings.personaldetails_url,
				type: 'POST',
				data: d,
				success: function (jsonObj, textStatus, jqXHR){
					self.logger("Personal details persisted: ")
					self.logger(jsonObj);
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


			$('.ProductOptionSelector').bind('change.shoppingCart', function(event){
				self.updateInStockMessage(event);
			});
			
			$(document).ready(function(){
				$('.product, .single-product').each(function(){
					var elt = $(this);
					var prodId = parseInt(elt.attr('data-productid'));
					self.updateInStockMessage(null, prodId);
				});
			});


			$('body').on('change.shoppingCart', '#order-form input, #order-form textarea', function(event){
				self.storePersonalDetailsInSession(event);
		    	var compareToAddress = '';
		    	var compareToAddress = '';
		    	
		    	var compareToAddress2="";
		    	
		    	$('.address-line').each(function(){
		    		compareToAddress += " "+$(this).val();
		    	});
		    	$('.address-line-elsewhere').each(function(){
		    		if($(this).val() != "" && $(this).attr('id') != 'deliveryElsewhere'){
				    	compareToAddress2 += " "+$(this).val();	
			    	}
		    	});

		    	if(compareToAddress2.length > 0 && $('#deliveryElsewhere').is(':checked')){
			    	compareToAddress = compareToAddress2;
			    	self.logger("Distance calc: Using address 'elsewhere'");
			    }
			    else {
				    self.logger("Distance calc: Using normal delivery address");
			    }
			    if(self.allAddressFieldsFilledOut()){
			    	self.calculateDistance(compareToAddress, function(dist){
			   			self.updatePrices();		    					    		
			    	});
		    	}
		    	else {
					distance = -1;
					self.updatePrices();							    
				}
		    });	 
		    
		    $("body").on('change.shoppingCart', '.checkout-amount', function(event){
				self.updateQuantityOfProduct(event);

    			self.updatePrices();
			    if(self.settings.use_scheduler){
				   self.renderOccupationMessage();
			    }
		    });
		    
		    
		    $("body").on('click.shoppingCart', 'a.removefromcart-checkout', function(event){
   			 	self.logger("a.removefromcart-checkout clicked");

		    	self.removeProduct(event);
		    	self.removeProductFromCheckoutPage(event);
    			self.updatePrices();
		    });
		    
		    $('body').on('click.shoppingCart', '.addtocart', function(event){
		    	event.preventDefault();
			    var b = self.addProduct(event);
	    		if(b){
				   // this.addExtraProducts(,event); //aanvullingen
				    self.updatePrices();
				    $('.product-added').removeClass('hidden');
				    self.createAutoClosingAlert();
			    }
		   });

		   $('body').on('click.shoppingCart', '.filter_addtocart', function(event){
		    	event.preventDefault();
				var elt = $(event.currentTarget);
				var productId = parseInt(elt.attr("data-productid"));
				var quant = parseInt($('#filter_quantity_'+productId).val());
				if(quant == null || quant == undefined){
					self.logger("filter_addtocart: Warning quantity is null, using 1");
					quant = 1;
				}
				self.fetchProductFromServer(productId, function(jsonObj){
					jsonObj.quantity = quant;
					var b =	self.addProduct(null,jsonObj); 
					if(b){
						self.updatePrices();
						$('.product-added').removeClass('hidden');
						self.createAutoClosingAlert();
					}
				});
		   });

		   $('body').on('click.shoppingCart', '.deliveryDatePicker', function(event){
				event.preventDefault();
		   });
		   $('body').on('change.shoppingCart', '#deliveryDate, select[name="deliveryTime"]', function(event){
			   if(self.settings.use_scheduler){
			       var valid = self.checkTooFarInFuture();
				   if(valid) {
					  self.renderOccupationMessage();
				   }
			   }
		   });
		   
		   $('#coupon').bind('change.shoppingCart', function(){
		    	self.checkCoupon(function(){
		    		self.updatePrices();
		    	});
		    });
	    },
		checkTooFarInFuture : function() {
			if(this.settings.max_future_delivery_date != null && 
			   this.settings.max_future_delivery_date != undefined){
				var days = parseInt(this.settings.max_future_delivery_date);
				var date = this.parseDdMmYyyyString($('#deliveryDate').val());

				var date2 = new Date(date.getTime());
				date2.setDate(date.getDate() - days);
				var now = new Date();
				now.setHours(0);
				now.setMinutes(0);
				now.setSeconds(0);

				if(date2.getTime() > now.getTime()){
					this.logger('Too far in the future');
					var tgt = $('.schedulerMessage');
					tgt.show();
					tgt.html('De dag mag niet meer dan '+days+' in de toekomst liggen.');
					tgt.addClass('alert-error').removeClass('alert-success');
					return false;
				}  else {
					this.logger('Valid, not too far in future');
					var tgt = $('.schedulerMessage');
					tgt.hide();
					tgt.html('');
					tgt.removeClass('alert-error').addClass('alert-success');

					return true;
				}

			} 
			return false;
		},
		renderOccupationMessage : function(){
		   var self = this;
		   var tgt = $('.schedulerMessage');
		   tgt.hide();
		   tgt.html('');
		   $('.submit-controls').removeClass('disabled');
		   if(self.checkIfTimeslotIsAvailable()){
			   self.logger("Dit moment is nog beschikbaar");
			   tgt.removeClass('hidden');
			   $('.submit-controls').removeClass('disabled');
			   tgt.show();
			   tgt.html('Dit moment is nog beschikbaar.');
			   tgt.removeClass('alert-error').addClass('alert-success');
		   } else {
			   self.logger("Dit moment is NIET beschikbaar");
			   tgt.removeClass('hidden');
			   tgt.show();
			   tgt.html('Dit moment is <strong>niet meer</strong> beschikbaar, kies een ander moment.');
			   $('.submit-controls').addClass('disabled');
			   tgt.addClass('alert-error').removeClass('alert-success');
		   }
		},
		parseDdMmYyyyString : function(s){
			from = s.split("-");
			f = new Date(from[2], from[1] - 1, from[0]);
			return f;
		},
		getDayOfWeekByIndex : function(i){
			var r = null;
			switch(i){
				case 0:
					r = "sunday";
					break;
				case 1:
					r = "monday";
					break;
				case 2:
					r = "tuesday";
					break;
				case 3:
					r = "wednesday";
					break;
				case 4:
					r = "thursday";
					break;
				case 5:
					r = "friday";
					break;
				case 6:
					r = "saturday";
					break;
				default:
					r = null;

			}

			return r;
		},
		loadSchedulingData : function(){
			var self = this;
		    if(this.settings.use_scheduler && this.settings.is_checkout){
				$.ajax({
					url: this.settings.schedulerUrl,
					jsonp: 'callback',
					dataType : 'jsonp',
					data: {
						"hostname" : this.hostname
					},
					success : function(jsonobj){
						self.logger('Downloaded scheduling data using jsonp: ', jsonobj);
						self.settings.scheduler_data = jsonobj;
						self.populateTimeSlotsBasedOnSelectedDate();
						self.populateDatePickerInit();
						self.renderOccupationMessage();
					}
				});
		   }

		},
		/**
		 * Checks availability based on previous orders, and displays error if not available, is no op if scheduler is not used
		 */
		checkIfTimeslotIsAvailable : function(){
		   var data = this.settings.scheduler_data;
		   if($('select[name="deliveryTime"]').val() == 'Wij zijn vandaag gesloten'){
			   $('.schedulerMessage').addClass('hidden');
			   $('.schedulerMessage').hide();
			   return false; 
		   }

		   var requestedDate = this.parseDdMmYyyyString($('#deliveryDate').val());
		   var requestedTime = this.parseTimeString($('select[name="deliveryTime"]').val());
		   requestedDate.setHours(requestedTime.h);
		   requestedDate.setMinutes(requestedTime.m);
		   var slotSizeMinutes = this.calculateSlotSizeFromCart();

		   return this.isDuringOpeningHours(requestedDate, data.openingHours, slotSizeMinutes) &&
				  this.isCapacityLeft(requestedDate, slotSizeMinutes, data.capacityPerSlot,data.occupiedSlots);
		},
		parseTimeString : function(s){
			var p = s.split("-");
			var p2 = p[0].split(":");
			return { "h" : p2[0], "m" : p2[1] };
		},
		calculateSlotSizeFromCart : function() {
			var ret = 0;
	    	for(var i = 0; i < this.cartDataStore.length; i++){
				var p = this.cartDataStore[i];
				var q = p.quantity;
				var min = p.processingTimeMinutes;
				ret += parseInt(q)*parseInt(min);
			}
			return ret;
		},
		isDuringOpeningHours : function(date, hours, slotSizeMin) {
			var dayOfWeek = this.getDayOfWeekByIndex(date.getDay()); //used for checking with opening hours
			var hours = hours[dayOfWeek];//array like: ["10:00-12:00", "13:00-17.00"]

			for(var i = 0; i < hours.length; i++){
				var s = hours[i];
				var pieces = s.split("-");
				var start = pieces[0];
				var end = pieces[1];

				//vars below contain openinghours data
				var pS = start.split(".");
				var startHours = parseInt(pS[0]);
				var startMin   = parseInt(pS[1]);

				var pE = end.split(".");
				var endHours = parseInt(pE[0]);
				var endMin   = parseInt(pE[1]);

				//does this fit in the slot? return true, else continue
				if(this.fitsInSlot(startHours,startMin,endHours,endMin,date,slotSizeMin)){
					return true;
				}
			}

			return false;
		},
		addMinutesToDate : function(date, mins){
			return new Date(date.getTime() + mins * 60000);
		},
		/**
		 * starthour, startmin, endhour, endmin, date to be checked if it fits in.
		 */
		fitsInSlot : function(sh, sm, eh, em, date, slotSizeMins){
			var m = date.getMinutes();
			var h = date.getHours();
			var dateEnd = this.addMinutesToDate(date, slotSizeMins);
			dem = dateEnd.getMinutes();
			deh = dateEnd.getHours();
			return (sh < h   || (sh == h && sm <= m)) &&
				   (eh > deh || (eh == deh && em >= dem));
		},
		isCapacityLeft : function(date, slotLength, capacity, occupiedSlots){
			var dateEnd = this.addMinutesToDate(date, slotLength);
			return this.getNumOverlaps(date, dateEnd, occupiedSlots) < capacity;
		},
		getNumOverlaps : function(date, dateEnd, occupiedSlots) {
			var overlapCounter = 0;
			var sh = date.getHours();
			var sm = date.getMinutes();
			var eh = dateEnd.getHours();
			var em = dateEnd.getMinutes();
			for(var i = 0; i < occupiedSlots.length; i++){
				var sObj = occupiedSlots[i];

				var slotStart = new Date(sObj.time.time);
				var slotSizeMinutes = sObj.duration;

				if(this.sameDay(slotStart, date) && this.slotOverlaps(slotStart, slotSizeMinutes, sh, sm, eh, em)){
					overlapCounter++;
				}
			}
			return overlapCounter;
		},
		sameDay : function (date1, date2){
			var current  = new Date(date1.getTime()).setHours(0, 0, 0, 0);
			var previous = new Date(date2.getTime()).setHours(0, 0, 0, 0);
			return current == previous;
		},
		slotOverlaps : function(slotStart, slotSize, sh, sm, eh, em){
			var slotEnd = this.addMinutesToDate(slotStart, slotSize);
			var ret =  (slotEnd.getHours()   > sh || (slotEnd.getHours()   == sh && slotEnd.getMinutes()   > sm)) &&
			           (slotStart.getHours() < eh || (slotStart.getHours() == eh && slotStart.getMinutes() < em));
			return ret;
		},
	    lookupProduct: function(productId){
    		for(var i = 0; i < webshopProducts.length; i++){
    			var prod = webshopProducts[i];
    			
	    		if(prod.Product_id == productId){
		    		return prod;
	    		}
    		}
    		
    		return null;
	   	},
		fetchProductFromServer : function(productId, callback){
			var self = this;
			$.ajax({
				url: this.settings.productsUrl,
				jsonp: 'callback',
				dataType : 'jsonp',
				data: {
					"hostname"   : this.hostname, 
					"Product_id" : productId
				},
				success : function(jsonobj){
					self.logger('downloaded product #'+productId+' using jsonp: ', jsonobj);
					var prod = self.transformProduct(jsonobj[0]);
					callback.call(self,prod);
				}
			});

		},
		transformProduct : function(jsonObj){
			//returns the server-side representation of the product
			var ret = {};
			ret.Product_id = jsonObj.Product_id;
			ret.title = jsonObj.productName;
			ret.desc = jsonObj.productDesc;
			ret.thumb = this.settings.baseUrl+"/uploads/Product/"+jsonObj.imageDish;
			ret.price = parseFloat(jsonObj.productPrice);
			ret.VAT = jsonObj.productVAT;
			ret.brand = jsonObj.brand;
			ret.color = jsonObj.productColor;
			ret.quantity = 1;
			ret.processingTimeMinutes = jsonObj.processingTimeMinutes;
			ret.discount = jsonObj.productDiscount;
			ret.productNumber = jsonObj.productNumber;
			ret.ProductOption = jsonObj.ProductOption;
			ret.MediaLibrary = jsonObj.MediaLibrary;
			ret.ProductProperty = jsonObj.ProductProperty;
			ret.SKU = jsonObj.SKU;
			ret.productDeliveryTime = jsonObj.productDeliveryTime;
			return ret;	
		},
		renderProductPriceOnCheckout : function(product){
			if(product.customPrice != null){
				var price = parseFloat(product.customPrice);
				$('.checkout-product-price[data-productid="'+product.Product_id+'"]').html("&euro; "+this.formatEuro(price));
			}
		},

	   	updateQuantityOfProduct: function(event){
			var elt = $(event.currentTarget);
			var productId = parseInt(elt.attr('data-productid'));
			var qty = parseInt(elt.val());
			
	   	
			if(qty == null || qty == ""){
				qty = 0; 
			}
			
	    	for(var i = 0; i < this.cartDataStore.length; i++){
				var p = this.cartDataStore[i];
				if(p.Product_id == productId){ //found
					p.quantity = qty;
					if(this.settings.beforeInsertingProductToCartHook != null){
						this.settings.beforeInsertingProductToCartHook.call(this, p, true);
					}
					this.renderProductPriceOnCheckout(p);
				}
			}
			
			this.persist();
	   	},
		allSkusAreZero : function(product) {
			if(product.SKU == null) {
				//return false, since we don't use the SKU system , so it always should be in store
				return false;
			}
			for(var i = 0; product.SKU != null && i < product.SKU.length ; i++){
				var sku = product.SKU[i];
				if(sku.skuQuantity != null && sku.skuQuantity > 0){
					return false;
				}
			}
			return true;
		},
	   	updateInStockMessage : function(event, productId){
	   		this.logger('updateInStockMessage');
	   		var optionValue = null;
	   		var elt = null;
	   		
	   		this.logger('optionValue: '+optionValue);
			var prods;
	   		
	   		if(event != null) {
	   			elt = $(event.currentTarget);
	   			optionValue = parseInt(elt.val());
	   		}
	   		var prods = webshopProducts;
			for(var i = 0; i < prods.length; i++){
				var prod = prods[i];
				if(productId != null && prod.Product_id != productId) {
					continue;
				} //if productId == null, it means event is not null, and therefore we can deduct which product it is.
				
				if(this.productHasOptionValue(optionValue,prod)){ //if true, this is our product we have to check SKU's for
					// check all the other selected options for this product, and their values
					var otherSelectedOptionValues = this.getSelectedOptionsForThisProduct(prod);
					var sku = this.lookupSkuBySelectedOptionValues(prod, otherSelectedOptionValues);
					var skuElt = $('.skuInfo-'+prod.Product_id).removeClass('alert').removeClass('alert-error').hide();
					skuElt.html('');
					if(sku == null){ //user doesnt use stock info, so do nothing.
						this.logger('No sku found');
						skuElt.attr('skuNumber', '').attr('inStock', "true");
					} else { //sku row found, check quantity, and encode skuNumber in the DOM for easy retrieval after adding.
						skuElt.attr('skuNumber', sku.skuNumber).attr('inStock', "true");
						this.logger("SKU FOUND: ",sku);
						if(sku.skuQuantity < 1){
							var delTime = '';
							if(prod.productDeliveryTime != null && prod.productDeliveryTime != undefined){
								delTime = ' De levertijd is: '+prod.productDeliveryTime;
							}
							skuElt.html('Dit product is tijdelijk niet meer op voorraad.'+delTime).attr('inStock','false').addClass('alert').addClass('alert-error').show();
						}
					}
				}
			}

			
			//look up if this combination has anything in stock, by looking at the SKU numbers
			
			//if a sku row is found display instock or not
			
			//if no sku row is found, assume in stock, don't display anything, since this shop owner doesn't use the sku rows (yet)
	   	},
	   	lookupSkuBySelectedOptionValues : function(product, optionValues){
	   		if(product.SKU != null) {
				for(var i = 0; i < product.SKU.length; i++){
					var sku = product.SKU[i];
					var values = [];
					for(var j = 0; j < sku.ProductOptionValue.length; j++){
						values.push(sku.ProductOptionValue[j].ProductOptionValue_id);
					}
					if(this.skuValuesAreEqual(values, optionValues)){
						return sku;
					}
				}
			}
			return null;
	   	},
	   	skuValuesAreEqual : function(valSet1, valSet2){
		   	if(valSet1.length != valSet2.length) { 
		   		return false; 
		   	}
		   	
		   	//sort both arrays numerically
		   	var sortNumber = function(a,b){
			   	return parseInt(a) - parseInt(b);
		   	}
		   	
		   	valSet1.sort(sortNumber);
		   	valSet2.sort(sortNumber);

			for(var i = 0; i < valSet1.length; i++){
				if(valSet1[i] != valSet2[i]){
					return false;
				}
			}		   		
			return true;
		   		
	   	},
	   	productHasOptionValue : function(optionValue,prod){
	   		if(optionValue == null || prod.ProductOption == null || prod.ProductOption.length == 0){
		   		return true; //optionValue is null, and prod is an option-less product, ie. return true
	   		}
		   	if(prod.ProductOption != null){
				for(var i = 0; i < prod.ProductOption.length; i++){
					var opt = prod.ProductOption[i];
					if(opt.ProductOptionValue != null){
						for(var j = 0; j < opt.ProductOptionValue.length; j++){
							var val = opt.ProductOptionValue[j];
							if(val.ProductOptionValue_id == optionValue){
								return true;
							}
						}
					}
				}
		   	}
		   	return false;
	   	},
	   	getSelectedOptionsForThisProduct : function(product){
	   		var ret = [];
	   		$('.product-'+product.Product_id+' .ProductOptionSelector.InfluencesSKU').each(function(){
				
		   		var val = $(this).val();
		   		ret.push(parseInt(val));
	   		});
	   		return ret;
	   	},
	    addProduct : function (event, productData) {
			if(this.settings.customAddProductValidator != null){
				var res = this.settings.customAddProductValidator.call(this, event, productData);
//				this.logger("RES"+res);
				if(!res) return;
			}
			//maybe this should be done nicer
	    	var quant=1;
	    	var product = null;
	
	    	if(productData == null || productData == undefined){
		       	var clicked = $(event.currentTarget);
		       	var prodId = clicked.attr('product-id');

				quant = parseInt($('#product-amount-'+prodId).val());
	  			
		    	var productRef = this.lookupProduct(prodId);

				if($('.skuInfo-'+prodId).html() != '' && $('.skuInfo-'+prodId).html() != undefined && $('.skuInfo-'+prodId).html() != null 
					|| (this.settings.hideSoldOutProducts == 'hide' && this.allSkusAreZero(productRef))		
					){ //not in stock
					alert('U kunt dit product niet toevoegen aan het winkelmandje, want het is niet meer op voorraad');
					return;
				}

		    	if(productRef == null){
			    	this.logger("FATAL: product data is not embedded in page!");
			    	return;
		    	}

		    	//deepcopy it
		    	product = jQuery.extend(true, {}, productRef);

	    		product = this.addSelectedOptionsToProduct(product, productRef);
				product = this.addSkuInformationToProduct(product);
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

				if(this.settings.beforeInsertingProductToCartHook != null){
					this.settings.beforeInsertingProductToCartHook.call(this, existingProduct, true);
				}

			}
			else {
				this.logger("product does not exist");
				if(this.cartDataStore == "EMPTY") {
					this.cartDataStore = [];
				}


				if(this.settings.beforeInsertingProductToCartHook != null){
					product = this.settings.beforeInsertingProductToCartHook.call(this, product, false);
				}

				this.cartDataStore.push(product);
			}
			this.logger("cart: ");
			this.logger(this.cartDataStore);
			var self = this;
			this.persist(function(jsonObj){
				if(self.settings.onProductAdded != null){
					self.settings.onProductAdded.call(this, product);
				}
			});
			this.logger("calling render");
			this.render();						


			return true;

	    },
	    addSkuInformationToProduct : function(product){
	    	this.logger('Add sku info to product');
	    	var otherSelectedOptionValues = this.getSelectedOptionsForThisProduct(product);
			var sku = this.lookupSkuBySelectedOptionValues(product, otherSelectedOptionValues);
			this.logger('found sku: ');
			this.logger(sku);
			if(sku != null && sku != ""){
				product.sku = sku;
			}
			return product;
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
	    		str=this.getTemplate("<li><p style='margin-left: 10px;' class='emptycart'>Het winkelwagentje is leeg.<p></li>");
	    	
	    	}
	    	else {
	    	   	this.logger("Non-empty cart");
	    	   	var productsHtml= '';
		    	//loop over cartDataStore, and fill up all lists
		    	for(var i = 0; i < this.cartDataStore.length ; i++){
		    		var obj = this.cartDataStore[i];
					var removeclass = 'productid="'+obj.Product_id+'"';
										
					var title = obj.title;		
								
					if(obj.brand != null && obj.brand != "") {
						title = obj.brand + " - " + title;
					}
					
					
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
								 '<span class="quantity quantity-'+obj.Product_id+'">'+obj.quantity+'x</span>'+
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
	    	   if (product.Product_id == parseInt(obj.Product_id)){
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
			if(options1 == null && options2 == null){
		    	return true;
			}
			if(options1 == null){
				options1 = [];
			}
			if(options2 == null){
				options2 = [];
			}
		    
		    if(options1.length != options2.length) return false;
	
		    //both options1 and 2 are not null and have same length;
		    for(var i = 0; i < options1.length; i++){
		    	var checkingVal = options1[i].ProductOptionValue_id;
		    	var checkingOption = options1[i].ProductOption_id;
		    	
		    	var found = false;
			    for(var j = 0; j < options2.length; j++){
				   if(checkingVal    === parseInt(options2[j].ProductOptionValue_id) &&
				   	  checkingOption === parseInt(options2[j].ProductOption_id)) {
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
    			optionSelectObj.ProductOptionValue_id = $('.product-'+product.Product_id+' #ProductOption_'+cur.ProductOption_id).val();
    			optionSelectObj.optionName = $('.product-'+product.Product_id+' #ProductOptionName_'+cur.ProductOption_id).html();

    			optionSelectObj.extraPrice = $('.product-'+product.Product_id+' #ProductOptionValueName_'+optionSelectObj.ProductOptionValue_id).attr('extraPrice');
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
	    	total=0;
	    	for(var i = 0; i < this.cartDataStore.length ; i++){
	    		var obj = this.cartDataStore[i];
	    		var currentPrice = 0;

				if(obj.customPrice != null) { 
					//if there is a calculated custom price in the cart, use this price, and override normal price.
					//discounts will not be applied!
					var prodPrice = parseFloat(obj.customPrice);
					total += prodPrice;
					vatMap["x"+obj.VAT] += prodPrice;
				} else {
					var p = parseFloat(obj.price);

					var quantumDiscount = 0;

					if(obj.quantumDiscount != null && 
					   obj.quantumDiscount != "" &&
					   obj.quantumDiscountPer != null &&
					   obj.quantumDiscountPer != "" &&
					   obj.quantumDiscountPer <= parseInt(obj.quantity)){
						p -= parseFloat(obj.quantumDiscount); //deduct quantum discount from the price
					}

					currentPrice = p * parseInt(obj.quantity);
					var optionsPrice = this.addOptionPrices(obj);
					
					var productDiscountFactor = 1;
					
					if(obj.discount != null && obj.discount != ""){
						productDiscountFactor = 1-(parseFloat(obj.discount)/100);
					}

					var prodPrice = ((currentPrice + optionsPrice)) * productDiscountFactor;
				
					total += prodPrice;
					
					//update vatMap
					if(vatMap["x"+obj.VAT] == null || vatMap["x"+obj.VAT] == undefined){
						vatMap["x"+obj.VAT] = 0;
					}

					this.logger("updating vatMap with: "+ parseFloat(prodPrice));
					vatMap["x"+obj.VAT] += parseFloat(prodPrice);
				}
	    		
	    		$('.quantity-'+obj.Product_id).html(obj.quantity+'x');
	    	}
	    	
	    	this.renderDeliveryMethodAndCostsOnCheckout(vatMap, total);
			
			if(vatMap["x0.21"] == undefined || vatMap["x0.21"] == null){
				vatMap["x0.21"] = 0.0;
			}
			
			vatMap["x0.21"] += parseFloat(deliveryCosts.price);

			total += parseFloat(deliveryCosts.price);
			var totalInclVat = 0;
			var totalExclVat = 0;
			if(this.settings.pricesInclVat){
				totalExclVat = this.renderVatRowsOnCheckout(vatMap, total);
				totalInclVat = total;
			} else {//exclvat 
				//horrible.... 
				totalExclVat = total;
				totalInclVat = this.renderVatRowsOnCheckout(vatMap, total);
			}

			this.renderExclPriceOnCheckout(totalExclVat);
			this.renderDiscountOnCheckout();
			
			if(discount != null && discount != undefined)
				totalInclVat = totalInclVat * (1 - (parseInt(discount) / 100));
			
	    	$('.total-price').html(this.formatEuro(totalInclVat));
	    	$('.total-field').html("<strong>&euro; "+this.formatEuro(totalInclVat)+"</strong>");

	    },
	    renderDiscountOnCheckout : function(){
	    	$('#discount-row').addClass('hidden');
			if(discount == undefined){
				$('#discount-text').addClass('hidden');
				$('.discount-field').html('');
			}
			else if(discount == 0){
				$('#discount-text')
					.removeClass('hidden')
					.html('Dit is geen geldige couponcode.')
					.addClass('alert-error');

				$('.discount-field').html('');				
	
			}
			else {
				$('#discount-row').removeClass('hidden');
				$('#discount-text').html('Couponcode geldig, u krijgt '+discount+'% korting.');
				
				$('#discount-text').removeClass('alert-error')
									.addClass('alert-success')
									.removeClass('hidden');
									
				$('.discount-field').html(discount+'%');													
			}
	    },
	    
		allAddressFieldsFilledOut : function() {
			var ret = true;
 	        var deliveryElseWhere = $('#deliveryElsewhere').is(':checked');
 	        var self = this;
 	        var selector = '';
 	        if(deliveryElseWhere) {
	 	        selector = '.address-line-elsewhere';
 	        }
 	        else {
	 	        selector = '.address-line';	 	        
 	        }
		    $(selector).each(function(){
		    	var x = $(this).val();
		    	if(x === undefined || x === null || x == "") {
		    		self.logger("Not all address fields set");
			    	ret = false;
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

	    	if(deliveryMethod.attr('value') === "0"){ //use settings.deliveryCostTable
		    	var doNotDeliver = true;
		    	var notEnoughOrdered = false;
		    	$('#not-enough-ordered').addClass('hidden').html('');
				
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
				
				if(this.distanceIsOutOfReach(distance)){
					this.logger("Address out of reach!");
					$('.submit-controls').addClass('disabled');
					$('#not-enough-ordered').removeClass('hidden').html('Wij bezorgen helaas niet op deze afstand. Kies een andere verzendmethode.');
					doNotDeliver = true;						

				}
	    	}
	    	else { //use settings.deliveryMethodPrice
	    		var x = deliveryMethod.attr('methodprice');
	    		if(x == undefined || x == null || x == ""){
		    		x = 0;
	    		}
		    	deliveryCosts.price = parseFloat(x);
		    	
		    	//set the delviery costs to zero if the total order amount surpasses the threshold
		    	var threshold = deliveryMethod.attr("freedeliverythreshold");
		    	if(threshold != null && threshold != "" && totalInclVat >= parseFloat(threshold)){
			    	deliveryCosts.price = 0;
		    	}
		    	
		    	$('.deliverycosts-field').html("<strong>€ "+this.formatEuro(deliveryCosts.price)+"</strong>");
	    	}
	    	

	    },
	    distanceIsOutOfReach : function(dist){
			this.logger('checking if it is out of reach');
	    	var max = 0;
			for(var i = 0; i < this.settings.deliveryCostsTable.length; i++){
				var cur = this.settings.deliveryCostsTable[i].maxKm;
				if(cur > max) {
					max = cur;
				}
			}
			return dist > max;
	    },
		calculateDistance : function(cptaddr, callback) {
			this.logger("Calculating distance between: "+cptaddr+" AND "+this.settings.address);
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
			var self = this;
	        directionsService.route(queryData, function(response, status) {
	            if (status == google.maps.DirectionsStatus.OK) {
	            	distance = parseInt(response.routes[0].legs[0].distance.value) / 1000;
	            	self.logger("Distance found: "+distance+" km");
	            }
				else {
					self.logger("Something went wrong, or address not found: "+status)
					self.logger(response);
				}            
   	           	if(callback != null && callback != undefined)
	            	callback.call(distance);
			});			
	    },	    
	    renderExclPriceOnCheckout : function(totalExclVat){
		   $('.subtotal-field').html('&euro; '+this.formatEuro(totalExclVat));
	    },
		/**
		 * If this.settings.pricesInclVat is true, returns the price excl. vat, otherwise returns price INCL vat.
		 */
	    renderVatRowsOnCheckout : function(vatMap, total) {
			if(this.settings.pricesInclVat){
				var totalExclVat = total;
				this.logger("renderVatRowsOnCheckout");
				this.logger(vatMap);
				for(var vatKey in vatMap){
					var perc = parseFloat(vatKey.substring(1));

					var x = vatKey.replace(".", "_");
					var val = parseFloat(vatMap[vatKey]);

					var vat = val - (val / (1+perc));

					$('.vat-value-'+x).html(this.formatEuro(vat));
					
					totalExclVat -= vat;
				}  
			} else { //excl vat
				this.logger("renderVatRowsOnCheckout");
				this.logger(vatMap);
				var sumVats = 0;
				for(var vatKey in vatMap){
					var perc = parseFloat(vatKey.substring(1));

					var x = vatKey.replace(".", "_");
					var val = parseFloat(vatMap[vatKey]);
					var vat = val * perc;
					sumVats += vat;
					$('.vat-value-'+x).html(this.formatEuro(vat));
				}  
				return total + sumVats;
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
							'<a class="dropdown-toggle" data-toggle="dropdown" id="expand-cart" href="#" >'+
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
	    checkCoupon : function(callback){
			$('#discount-text').html('Controleren couponcode…').addClass('hidden');
			if($('#coupon').val() == null || $('#coupon').val() == "" || $('#coupon').val() == undefined)
				return;
	
			var self = this;
			$('#discount-text').html('Controleren couponcode…').removeClass('hidden');

			$.ajax({
				url: this.couponUrl,
				data: { "hostname" : this.hostname , "couponCode" : $('#coupon').val()},
				type: 'GET',

				success: function (jsonObj, textStatus, jqXHR){

					discount = jsonObj.discount;

					
					if(callback!=null && callback!=undefined)
						callback(discount);
				},
				dataType: 'jsonp'
			});		
		},   		
   		createAutoClosingAlert : function() {
   			var delay = 2000;
   			var containerDiv = $('<div class="product-added-popup"></div>');
   			containerDiv.appendTo('body');
   			
   			var popupText = 'Product toegevoegd aan de bestelling. U kunt meteen <a href="'+this.settings.checkout_page+'">afrekenen</a> of verder winkelen.';
   			
   			if(this.settings.popupText != null){
	   			popupText = this.settings.popupText;
   			}
   			
	    	containerDiv.html('<div class="the-alert alert alert-info fade in">' +
	    						 '<button data-dismiss="alert" class="close" type="button">×</button>' +
	    						 '<p>'+popupText+'</p>' +
	    					 '</div>');
		    var alert = $('.the-alert').alert();
		    window.setTimeout(function() { 
		    	alert.alert('close'); 
		    	$('.product-added-popup').remove(); 
		    }, delay);
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
