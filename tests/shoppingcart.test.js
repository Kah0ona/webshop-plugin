function endsWith(str, suffix) {
	return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

describe("Testsuite for the shoppingcart jquery plugin.", function() {
	var initAddressLinesInDom = function(){
			$('<div id="wrap"></div>').appendTo('body');	
			$('<input type="text" name="firstname" class="input-large" id="firstname">').appendTo('#wrap');
			$('<input type="text" name="surname" class="input-large" id="surname">').appendTo('#wrap');
			$('<input type="text" value="hugo de grootstraat" name="street" class="input-large address-line" id="street">').appendTo('#wrap');
			$('<input type="text" name="city" class="input-large address-line" id="city">').appendTo('#wrap');
			$('<input type="text" name="postcode" maxlength="7" class="input-large span3 address-line" id="postcode">').appendTo('#wrap');
			$('<input type="text" name="number" maxlength="7" class="input-large span3 address-line" id="number">').appendTo('#wrap');
			$('<input type="text" name="country" class="input-large address-line" id="country">').appendTo('#wrap');			
			$('<input type="text" name="email" class="input-large" id="email">').appendTo('#wrap');			
			$('<input type="text" name="phone" class="input-large" id="phone">').appendTo('#wrap');
												
			$('<input type="submit" name="invoice" class="submit-controls btn btn-primary " id="invoice" value="Plaats bestelling" style="width: 130px;" />').appendTo('#wrap');		
			$('<select id="deliveryMethods"><option selected="selected" value="0"></option></select>').appendTo('#wrap');				


			var dom = window.location.pathname.split('ShoppingcartRunner.html')[0];

			$('#wrap').wrap('<form name="order-form_" id="order-form" class="form-horizontal" action="'+dom+'mock-form-handler.php" method="post" novalidate="novalidate"></form>');
	};
	
	
	var initCheckoutProductAmountInputField = function(){
		$('<div id="wrap"></div>').appendTo('body');	
		$('<input type="text" class="checkout-amount check-amount-163 span2" data-productid="163" value="2" />').appendTo('#wrap');
	}
	
	afterEach(function(){33
		$('#shoppingcart').remove();
		$('#detailform').remove();
		$('#wrap').remove();
		$('#order-form').remove();
	});
	
	beforeEach(function () {
		
	    $('<div id="shoppingcart"></div>').appendTo('body');
   		var detailForm = 
   			'<div id="detailform"><input class="input-small" name="product-amount" id="product-amount-163" value="8" type="text"> '+
			'<div class="span12 standard-products product-data">'+
			'<div class="span12 product-data ">'+
			'<span product-type="package" product-index="0" class="addtocart" product-id="163">'+
			'  <a href="#" class="btn" >Voeg toe</a>'+
			'</span></div>';

	    $(detailForm).appendTo('body');
	    
	});

	describe('Loading and initializing the shoppingcart plugin.', function(){
		it("Should load data from the server upon initialization, via an AJAX call.", function() {
		    spyOn($, 'ajax');

			$('#shoppingcart').shoppingCart({});
			expect($.ajax).toHaveBeenCalled();
		});
	});
	
	describe('Adding products test suite.', function(){
		it('Should persist a quantity of 8 added products when the add product is clicked on a detail page.', function(){
		    spyOn($, 'ajax');
			
			webshopProducts = {};
			webshopProducts = [{"Product_id":163, "title": "Product 1", "quantity" : 8, "ProductOption": [], "price" : 12, "VAT" : 0.21}];	
			$('#shoppingcart').shoppingCart({ detail : true });
			$('.addtocart').click(); //simulate click
			expect($.ajax).toHaveBeenCalled();
			expect($.ajax.mostRecentCall.args[0].data.shoppingCart[0].quantity).toBe(8);
		});
		
		it('When adding a product twice, the cart only has 1 entry for the product, but the quantity has to be 2.', function(){
		    spyOn($, 'ajax');
			webshopProducts = {};
			webshopProducts = [{"Product_id":163, "title": "Product 1", "quantity" : 8, "ProductOption": [], "price" : 12, "VAT" : 0.21}];	
			$('#shoppingcart').shoppingCart({ detail : true });
			$('.addtocart').click(); //simulate click 
			
			expect($.ajax.calls[1].args[0].data.shoppingCart[0].quantity).toBe(8);
			
			$('.addtocart').click(); //simulate click #2

			expect($.ajax.calls[2].args[0].data.shoppingCart[0].quantity).toBe(16);			
			expect($.ajax.calls[2].args[0].data.shoppingCart.length).toBe(1);		
			
			expect($.ajax.calls.length).toBe(3); //load, save, save of adding product twice.
			
		});

	});
	
	describe('Removing products testsuite.', function(){
		it('Should yield an empty cart, when the X is clicked in a cart with a single product.', function(){
		    spyOn($, 'ajax');

			$('#shoppingcart').shoppingCart({cartDisplayMode: 'block', detail: false});
			
			webshopProducts = {};
			webshopProducts = [{"Product_id":163, "title": "Product 1", "quantity" : 1, "ProductOption": [], "price" : 12, "VAT" : 0.21}];	
			
			
			$('.addtocart').click(); //simulate click, and adding a product


			expect($.ajax.calls[1].args[0].data.shoppingCart[0].quantity).toBe(8); //not using detailForm here, we 're on a public page
			expect($.ajax.calls[1].args[0].data.shoppingCart.length).toBe(1);		

			$('a.removefromcart').trigger('click');
			

			expect($.ajax.calls[1].args[0].data.shoppingCart.length).toBe(0);
		});
	});
	
	
	describe('SKU related testcases', function(){
		it('should say "product niet op voorraad", when out of stock, on the detail page', function(){
			webshopProducts = [{"Product_id":1,"title":"Malene Birger jurk","desc":"*test*\n\n- markdown\n- list\n- bla\n- bla\n\n__underscore__\n*asterisk*\n","thumb":"http:\/\/webshopdev.sytematic.nl\/uploads\/Product\/Product_imageDish_1_1379072509780.jpg","quantity":1,"price":99.95,"VAT":0.21,"ProductOption":[{"ProductOption_id":1,"optionName":"Schoenmaat","influencesSKU":true,"ProductOptionValue":[{"ProductOptionValue_id":1,"optionValue":"40","ProductOption_id":1},{"ProductOptionValue_id":2,"optionValue":"41","ProductOption_id":1},{"ProductOptionValue_id":3,"optionValue":"42","ProductOption_id":1},{"ProductOptionValue_id":4,"optionValue":"43","ProductOption_id":1},{"ProductOptionValue_id":5,"optionValue":"44","ProductOption_id":1}]}],"SKU":[{"SKU_id":14,"skuNumber":"123456-40","skuQuantity":1,"ProductOptionValue":[{"ProductOptionValue_id":1,"optionValue":"40","ProductOption_id":1}],"Product_id":1},{"SKU_id":15,"skuNumber":"123456-41","skuQuantity":1,"ProductOptionValue":[{"ProductOptionValue_id":2,"optionValue":"41","ProductOption_id":1}],"Product_id":1},{"SKU_id":16,"skuNumber":"123456-42","skuQuantity":2,"ProductOptionValue":[{"ProductOptionValue_id":3,"optionValue":"42","ProductOption_id":1}],"Product_id":1},{"SKU_id":17,"skuNumber":"123456-43","skuQuantity":4,"ProductOptionValue":[{"ProductOptionValue_id":4,"optionValue":"43","ProductOption_id":1}],"Product_id":1},{"SKU_id":18,"skuNumber":"123456-44","skuQuantity":0,"ProductOptionValue":[{"ProductOptionValue_id":5,"optionValue":"44","ProductOption_id":1}],"Product_id":1}]}];

			var detailForm2 = '<div class="single-product product-1" data-productid="1" itemscope itemtype="http://schema.org/Product">'+
				'	<div class="skuInfo skuInfo-1"></div>'+
				'	<form class="form ">'+
				'		<input class="input-large product-amount-1  " name="product-amount" id="product-amount-1" value="1" type="text" /> '+
				'		<select name="size" class="input-large ProductOptionSelector" id="ProductOption_1" option_id="1">'+
				'			<option extraPrice="" value="1" valueName="40" selected 	 id="ProductOptionValueName_1"> 40</option> '+
				'			<option extraPrice="" value="2" valueName="41" id="ProductOptionValueName_2"> 41	</option> '+
				'			<option extraPrice="" value="3" valueName="42" id="ProductOptionValueName_3"> 42</option>'+
				'			<option extraPrice="" value="4" valueName="43" id="ProductOptionValueName_4">43</option> '+
				'			<option extraPrice="" value="5" valueName="44" id="ProductOptionValueName_5"> 44</option>'+
				'		</select>'+
				'	</form>	'+
				'	<span product-type="product" product-index="0" product-id="1" class="addtocart ">'+
				'	<a href="#" class="btn" ><i class="icon-shopping-cart icon-white"></i> Toevoegen</a>'+
				'	</span>'+
				'</div>   	';
			$(detailForm2).appendTo('body');
			
			$('#shoppingcart').shoppingCart({cartDisplayMode: 'block', detail: false});

			
			//size 44 is out of stock (optionvalue selection is ProductOptionValue_id=5;
			$('.product-1 .ProductOptionSelector').val('5');
			$('.product-1 .ProductOptionSelector').change(); //triggers the calculation
			
			//expected sku number is 123456-44
			expect($('.product-1 .skuInfo-1').attr('inStock')).toBe('false');
			expect($('.product-1 .skuInfo-1').attr('skuNumber')).toBe('123456-44');
			expect($('.product-1 .skuInfo-1').html()).toBe('Dit product is tijdelijk niet meer op voorraad.');
			
			$('.product-1').remove();
		});
		
		it('should have an attr instock=true, when there are enough products, on the detail page', function(){
			webshopProducts = [{"Product_id":1,"title":"Malene Birger jurk","desc":"*test*\n\n- markdown\n- list\n- bla\n- bla\n\n__underscore__\n*asterisk*\n","thumb":"http:\/\/webshopdev.sytematic.nl\/uploads\/Product\/Product_imageDish_1_1379072509780.jpg","quantity":1,"price":99.95,"VAT":0.21,"ProductOption":[{"ProductOption_id":1,"optionName":"Schoenmaat","influencesSKU":true,"ProductOptionValue":[{"ProductOptionValue_id":1,"optionValue":"40","ProductOption_id":1},{"ProductOptionValue_id":2,"optionValue":"41","ProductOption_id":1},{"ProductOptionValue_id":3,"optionValue":"42","ProductOption_id":1},{"ProductOptionValue_id":4,"optionValue":"43","ProductOption_id":1},{"ProductOptionValue_id":5,"optionValue":"44","ProductOption_id":1}]}],"SKU":[{"SKU_id":14,"skuNumber":"123456-40","skuQuantity":1,"ProductOptionValue":[{"ProductOptionValue_id":1,"optionValue":"40","ProductOption_id":1}],"Product_id":1},{"SKU_id":15,"skuNumber":"123456-41","skuQuantity":1,"ProductOptionValue":[{"ProductOptionValue_id":2,"optionValue":"41","ProductOption_id":1}],"Product_id":1},{"SKU_id":16,"skuNumber":"123456-42","skuQuantity":2,"ProductOptionValue":[{"ProductOptionValue_id":3,"optionValue":"42","ProductOption_id":1}],"Product_id":1},{"SKU_id":17,"skuNumber":"123456-43","skuQuantity":4,"ProductOptionValue":[{"ProductOptionValue_id":4,"optionValue":"43","ProductOption_id":1}],"Product_id":1},{"SKU_id":18,"skuNumber":"123456-44","skuQuantity":3,"ProductOptionValue":[{"ProductOptionValue_id":5,"optionValue":"44","ProductOption_id":1}],"Product_id":1}]}];

			var detailForm2 = '<div class="single-product product-1" data-productid="1"  itemscope itemtype="http://schema.org/Product">'+
				'	<div class="skuInfo skuInfo-1"></div>'+
				'	<form class="form ">'+
				'		<input class="input-large product-amount-1  " name="product-amount" id="product-amount-1" value="1" type="text" /> '+
				'		<select name="size" class="input-large ProductOptionSelector" id="ProductOption_1" option_id="1">'+
				'			<option extraPrice="" value="1" valueName="40" selected 	 id="ProductOptionValueName_1"> 40</option> '+
				'			<option extraPrice="" value="2" valueName="41" id="ProductOptionValueName_2"> 41	</option> '+
				'			<option extraPrice="" value="3" valueName="42" id="ProductOptionValueName_3"> 42</option>'+
				'			<option extraPrice="" value="4" valueName="43" id="ProductOptionValueName_4">43</option> '+
				'			<option extraPrice="" value="5" valueName="44" id="ProductOptionValueName_5"> 44</option>'+
				'		</select>'+
				'	</form>	'+
				'	<span product-type="product" product-index="0" product-id="1" class="addtocart ">'+
				'	<a href="#" class="btn" ><i class="icon-shopping-cart icon-white"></i> Toevoegen</a>'+
				'	</span>'+
				'</div>   	';
			$(detailForm2).appendTo('body');
			
			$('#shoppingcart').shoppingCart({cartDisplayMode: 'block', detail: false});

			
			//size 44 is out of stock (optionvalue selection is ProductOptionValue_id=5;
			$('.product-1 .ProductOptionSelector').val('5');
			$('.product-1 .ProductOptionSelector').change(); //triggers the calculation
			
			//expected sku number is 123456-44
			expect($('.product-1 .skuInfo-1').attr('inStock')).toBe('true');
			expect($('.product-1 .skuInfo-1').attr('skuNumber')).toBe('123456-44');
			expect($('.product-1 .skuInfo-1').html()).toBe('');
			
			$('.product-1').remove();
		});
		
		
		it('Option-less product: should say "product niet op voorraad", when out of stock, on the detail page', function(){
			webshopProducts = [{"Product_id":1,"title":"Malene Birger jurk","desc":"*test*\n\n- markdown\n- list\n- bla\n- bla\n\n__underscore__\n*asterisk*\n","thumb":"http:\/\/webshopdev.sytematic.nl\/uploads\/Product\/Product_imageDish_1_1379072509780.jpg","quantity":1,"price":99.95,"VAT":0.21,"ProductOption":[],"SKU":[{"SKU_id":14,"skuNumber":"123456-40","skuQuantity":0,"ProductOptionValue":[],"Product_id":1},{"SKU_id":15,"skuNumber":"123456-41","skuQuantity":1,"ProductOptionValue":[{"ProductOptionValue_id":2,"optionValue":"41","ProductOption_id":1}],"Product_id":1},{"SKU_id":16,"skuNumber":"123456-42","skuQuantity":2,"ProductOptionValue":[{"ProductOptionValue_id":3,"optionValue":"42","ProductOption_id":1}],"Product_id":1},{"SKU_id":17,"skuNumber":"123456-43","skuQuantity":4,"ProductOptionValue":[{"ProductOptionValue_id":4,"optionValue":"43","ProductOption_id":1}],"Product_id":1},{"SKU_id":18,"skuNumber":"123456-44","skuQuantity":3,"ProductOptionValue":[{"ProductOptionValue_id":5,"optionValue":"44","ProductOption_id":1}],"Product_id":1}]}];

			var detailForm2 = '<div class="single-product product-1" data-productid="1"  itemscope itemtype="http://schema.org/Product">'+
				'	<div class="skuInfo skuInfo-1"></div>'+
				'	<form class="form ">'+
				'		<input class="input-large product-amount-1  " name="product-amount" id="product-amount-1" value="1" type="text" /> '+
				'	</form>	'+
				'	<span product-type="product" product-index="0" product-id="1" class="addtocart ">'+
				'	<a href="#" class="btn" ><i class="icon-shopping-cart icon-white"></i> Toevoegen</a>'+
				'	</span>'+
				'</div>   	';
			$(detailForm2).appendTo('body');
			
			$('#shoppingcart').shoppingCart({cartDisplayMode: 'block', detail: false});

			
			expect($('.product-1 .skuInfo-1').attr('inStock')).toBe('false');
			expect($('.product-1 .skuInfo-1').attr('skuNumber')).toBe('123456-40');
			expect($('.product-1 .skuInfo-1').html()).toBe('Dit product is tijdelijk niet meer op voorraad.');
			
			$('.product-1').remove();
		});


		
	});
	
	describe('Calculate distance.', function(){
		it('should calculate the distance when all address fields in the checkout form are filled out.', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route');
			
			initAddressLinesInDom();

			$('#shoppingcart').shoppingCart({cartDisplayMode : 'block', deliveryCostsTable : [{"DeliveryCost_id":46,"price":12.5,"minKm":0,"maxKm":1000,"minimumOrderPrice":0}]});

			expect(google.maps.DirectionsService.prototype.route).not.toHaveBeenCalled();
			$('#city').val('Den Haag');
			$('#city').val('Den Haag');
			$('#postcode').val('2518EE');
			$('#number').val('62b');

			$('.address-line').change();
			expect(google.maps.DirectionsService.prototype.route).not.toHaveBeenCalled();
			$('#country').val('Nederland');
			$('.address-line').change();
	
			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();						
			$('#wrap').remove();
			
		});
		
				
		var fakeGoogleMapsReply = function(x,y){
				//console.log(y);
				y.call(this, { routes : [{ legs: [{distance : {value: 10000000}}]}]}, google.maps.DirectionsStatus.OK);
		};
		var fakeGoogleMapsReplyWithinReach = function(x,y){
				//console.log(y);
				y.call(this, { routes : [{ legs: [{distance : {value: 10000}}]}]}, google.maps.DirectionsStatus.OK);
		};
		
		var buildDom = function(delCosts){
			$('<div id="wrap"></div>').appendTo('body');	
			$('<input type="hidden" name="deliveryType"  class="deliveryType input-large" value="bezorgen" />').appendTo('#wrap');
			$('<input type="text"  name="street" class="input-large address-line" id="street">').appendTo('#wrap');
			$('<input type="text" name="city" class="input-large address-line" id="city">').appendTo('#wrap');
			$('<input type="text" name="postcode" maxlength="7" class="input-large span3 address-line" id="postcode">').appendTo('#wrap');
			$('<input type="text" name="number" maxlength="7" class="input-large span3 address-line" id="number">').appendTo('#wrap');
			$('<input type="text" name="country" class="input-large address-line" id="country">').appendTo('#wrap');			
			$('<p id="not-enough-ordered" class="hidden alert alert-error"></p>').appendTo('#wrap');
			$('<select id="deliveryMethods"><option selected="selected" value="0"></option></select>').appendTo('#wrap');						

			$('#shoppingcart').shoppingCart({cartDisplayMode : 'block',
				"deliveryCostsTable" : delCosts
			});

			$('#street').val('balistraat');
			$('#city').val('Den Helder');
			$('#postcode').val(' ');
			$('#number').val('1');
			$('#country').val('Nederland');
			$('.address-line').change();
		}
		
		it('should show an error message when the distance is out of reach of the delivery table', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route').andCallFake(fakeGoogleMapsReply);
			
			buildDom( [
									   {"DeliveryCost_id":3,"price":0,"minKm":0,"maxKm":35,"minimumOrderPrice":109.5},
									   {"DeliveryCost_id":5,"price":10,"minKm":35,"maxKm":50,"minimumOrderPrice":109.5},
									   {"DeliveryCost_id":6,"price":15,"minKm":50,"maxKm":70,"minimumOrderPrice":250},
									   {"DeliveryCost_id":46,"price":22.5,"minKm":70,"maxKm":350,"minimumOrderPrice":350}
								  ]);

			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();						
			
			var result = $('#not-enough-ordered').html();
			var isHidden = $('#not-enough-ordered').hasClass('hidden');
			expect(result).toBe('Wij bezorgen helaas niet op deze afstand. Kies een andere verzendmethode.');
			expect(isHidden).toBe(false);
			
			$('#wrap').remove();

		});
		
		it('should show an error message when the distance is within reach of the delivery table, but there is not enough ordered', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route').andCallFake(fakeGoogleMapsReplyWithinReach);
			buildDom( [
									   {"DeliveryCost_id":3,"price":0,"minKm":0,"maxKm":35,"minimumOrderPrice":109.5},
									   {"DeliveryCost_id":5,"price":10,"minKm":35,"maxKm":50,"minimumOrderPrice":109.5},
									   {"DeliveryCost_id":6,"price":15,"minKm":50,"maxKm":70,"minimumOrderPrice":250},
									   {"DeliveryCost_id":46,"price":22.5,"minKm":70,"maxKm":350,"minimumOrderPrice":350}
			]);
			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();						

			var isHidden = $('#not-enough-ordered').hasClass('hidden');
			expect(isHidden).toBe(false);
			expect($('#not-enough-ordered').html()).toMatch('vanaf een bedrag van');
			expect($('.submit-controls').hasClass('disabled')).toBe(false);
			
			$('#wrap').remove();

		});
		
		it('should show NO error message when the distance is within reach of the delivery table, AND there is enough ordered', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route').andCallFake(fakeGoogleMapsReplyWithinReach);
			buildDom([
									   {"DeliveryCost_id":3,"price":0,"minKm":0,"maxKm":35,"minimumOrderPrice":0},
									   {"DeliveryCost_id":5,"price":10,"minKm":35,"maxKm":50,"minimumOrderPrice":0},
									   {"DeliveryCost_id":6,"price":15,"minKm":50,"maxKm":70,"minimumOrderPrice":0},
									   {"DeliveryCost_id":46,"price":22.5,"minKm":70,"maxKm":350,"minimumOrderPrice":0}
			]);
			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();						

			var isHidden = $('#not-enough-ordered').hasClass('hidden');
			expect(isHidden).toBe(true);
			expect($('.submit-controls').hasClass('disabled')).toBe(false);
			
			$('#wrap').remove();

		});
	});
	
	describe('Checkout page testsuite.', function(){
		it('should update the cart, and recalculate prices when the amount field on the checkout page is changed', function(){
			webshopProducts = {};
			webshopProducts = [{"Product_id":163, "title": "Product 1", "quantity" : 1, "ProductOption": [], "price" : 12, "VAT" : 0.21}];	

			initCheckoutProductAmountInputField();
			initAddressLinesInDom();

			$('#shoppingcart').shoppingCart({
				cartDisplayMode : 'block', 
				address : 'Wassenaar',
				deliveryCostsTable : [{"DeliveryCost_id":46,"price":12.5,"minKm":0,"maxKm":1000,"minimumOrderPrice":0}]
			});
			
			$('.addtocart').click(); //simulate click 

			spyOn($, 'ajax');

			
			//change the field
			$('.check-amount-163').val('2'); //increase amount by 1
			expect($.ajax).not.toHaveBeenCalled();
			
			$('.check-amount-163').change();

			expect($.ajax).toHaveBeenCalled();

			expect($.ajax.mostRecentCall.args[0].data.shoppingCart[0].quantity).toBe(2);


			$('#wrap').remove();
		});
		

		it('Should calculate delivery costs (when the \'deliver ourselves\' option is selected, and charge according to distance), and show it in the total of the checkout page.', function(){
			//response.routes[0].legs[0].distance.value
			spyOn(google.maps.DirectionsService.prototype, 'route').andCallFake(function(x,y){
				//console.log(y);
				y.call(this, { routes : [{ legs: [{distance : {value: 10000}}]}]}, google.maps.DirectionsStatus.OK);
			});
			
			initAddressLinesInDom();
			$('<div class="deliverycosts-field"></div>').appendTo('#wrap');
		/*
	webshopProducts = {};
			webshopProducts = [{"Product_id":163, "title": "Product 1", "quantity" : 1, "ProductOption": [], "price" : 12, "VAT" : 0.21}];	
*/

			$('#shoppingcart').shoppingCart({
				cartDisplayMode : 'block', 
				address : 'Wassenaar',
				deliveryCostsTable : [{"DeliveryCost_id":46,"price":12.5,"minKm":0,"maxKm":1000,"minimumOrderPrice":0}]
			});

			expect(google.maps.DirectionsService.prototype.route).not.toHaveBeenCalled();
			$('#city').val('Den Haag');
			$('#city').val('Den Haag');
			$('#postcode').val('2518EE');
			$('#number').val('62b');

			$('.address-line').change();
			expect(google.maps.DirectionsService.prototype.route).not.toHaveBeenCalled();
			$('#country').val('Nederland');
			$('.address-line').change();
		
			
			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();			
			var resultRaw = $('.deliverycosts-field').html();
			//console.log('Result raw: '+resultRaw);			
			
			expect(endsWith(resultRaw, '12,50</strong>')).toBe(true);
			$('#wrap').remove();

		});
		
		it('should calculate the VAT correctly on the checkout table.', function(){
		    spyOn($, 'ajax');

			$('<div id="wrap"></div>').appendTo('body');	

			$('<td class="text-right subtotal-field"></td>').appendTo('#wrap');
			$('<td class="text-right vat-field-x0_21"><strong>€ <span class="vat-value-x0_21"></span></strong></td>').appendTo('#wrap');

			webshopProducts = {};
			webshopProducts = [{"Product_id":163, "title": "Product 1", "quantity" : 1, "ProductOption": [], "price" : 12, "VAT" : 0.21}];	
			$('#shoppingcart').shoppingCart({ detail : true });
			$('.addtocart').click(); //simulate click 
			
			expect($.ajax).toHaveBeenCalled();
			expect($.ajax.mostRecentCall.args[0].data.shoppingCart[0].quantity).toBe(8);
			expect($.ajax.mostRecentCall.args[0].data.shoppingCart[0].price).toBe(12);

			var result = $('.vat-value-x0_21').html();
			var resultTotalExclVat = $('.subtotal-field').html();
			//vat should be:
			// 8 * 12 = 96; 96/1.21 = 79.34; 96-79.34 = 16.66
			expect(result).toBe('16,66');
			expect(resultTotalExclVat.substring(2)).toBe('79,34');
			$('#wrap').remove();

		});
		
		it('Should check if a coupon is valid, and render 13% discount on the checkout page',function(){


			$('<div id="wrap"></div>').appendTo('body');	
			$('<input id="coupon" type="text" />').appendTo('#wrap');
			$('<div id="discount-text"></div>').appendTo('#wrap');
			$('<td class="discount-field"></td>').appendTo('#wrap');
			$('<td id="discount-row" class="hidden"></td>').appendTo('#wrap');
			$('#shoppingcart').shoppingCart({ detail : true });
			expect($('#discount-row').hasClass('hidden')).toBe(true);
			spyOn($, 'ajax').andCallFake(function(opts){
								
				opts.success.call(this, {'discount':13}, 'success',null);

			});
						
			$('#coupon').val('123');

			$('#coupon').change();

			var ret = $('.discount-field').html();
			expect(ret).toBe('13%');
			expect($('#discount-row').hasClass('hidden')).toBe(false);
			$('#wrap').remove();
			
		});
		
		it('Should give a discount of 10 percent, and it should be reflected in the total field on the checkout page', function(){
			//add two products to cart, with total price of 10e
			$('<div id="wrap"></div>').appendTo('body');	
			$('<input id="coupon" type="text" />').appendTo('#wrap');
			$('<div id="discount-text"></div>').appendTo('#wrap');
			$('<td class="discount-field"></td>').appendTo('#wrap');
			$('<td class="total-field" class="hidden"></td>').appendTo('#wrap');

			webshopProducts = {};
			webshopProducts = [{"Product_id":163, "title": "Product 1", "quantity" : 2, "ProductOption": [], "price" : 10, "VAT" : 0.21}];	
			
			$('#shoppingcart').shoppingCart({ detail : true });
			
			$('.addtocart').click(); //simulate click			
						
						
			//add coupon giving 10% discount
			spyOn($, 'ajax').andCallFake(function(opts){
				opts.success.call(this, {'discount':10}, 'success',null);
			});

			$('#coupon').val('123');
			$('#coupon').change();

			//verify the total is 72euro (10% discount on the total)
			var res = $('.total-field').html()
			expect(res).toMatch(/72,00/g);

			$('#wrap').remove();

		});
		
		it('test basic functioning of order-form.js', function(){
			initAddressLinesInDom();
			$('#firstname').val('Firstname');
			$('#surname').val('surname');
			$('#street').val('balistraat');
			$('#city').val('Den Helder');
			$('#postcode').val(' ');
			$('#number').val('1');
			$('#country').val('nederland');
			
			
			var submitOptions = {
				beforeSubmit : function(arr, form, options){
					return true;	
				},
				data : { 
					"hostname" : 'test'
				},
				success : function(data, textStatus, jqXHR) {
					alert(data);
					expect(data.redirectUrl).toBe('https://www.sisow.nl/Sisow/iDeal/Simulator.aspx?merchantid=2537507457&txid=TEST080489493323&sha1=c754690982273dc5a0b7ff3f65f733a33a4d142b');
				}
			};
			
			var validationOptions = {
					rules : {},
					messages : {},
					submitHandler : function(){
						$('#order-form').ajaxSubmit(submitOptions);	//does some extra validation.		
					},
					invalidHandler: function(form, validator) {
						//alert('invalid');
					}
			}; 
			
			$('#order-form').validate(validationOptions);
			
			$('#invoice').click();
		});
	});
});