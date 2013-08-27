function endsWith(str, suffix) {
	return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

describe("Testsuite for the shoppingcart jquery plugin.", function() {
	var initAddressLinesInDom = function(){
			$('<div id="wrap"></div>').appendTo('body');	
			$('<input type="text" value="hugo de grootstraat" name="street" class="input-large address-line" id="street">').appendTo('#wrap');
			$('<input type="text" name="city" class="input-large address-line" id="city">').appendTo('#wrap');
			$('<input type="text" name="postcode" maxlength="7" class="input-large span3 address-line" id="postcode">').appendTo('#wrap');
			$('<input type="text" name="number" maxlength="7" class="input-large span3 address-line" id="number">').appendTo('#wrap');
			$('<input type="text" name="country" class="input-large address-line" id="country">').appendTo('#wrap');			
			$('<select id="deliveryMethods"><option selected="selected" value="0"></option></select>').appendTo('#wrap');						
	};
	
	afterEach(function(){
		$('#shoppingcart').remove();
		$('#detailform').remove();
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
	    
	    spyOn($, 'ajax');
	    
	});

	describe('Loading and initializing the shoppingcart plugin.', function(){
		it("Should load data from the server upon initialization, via an AJAX call.", function() {
			$('#shoppingcart').shoppingCart({});
			expect($.ajax).toHaveBeenCalled();
		});
	});
	
	describe('Adding products test suite.', function(){
		it('Should persist a quantity of 8 added products when the add product is clicked on a detail page.', function(){
			webshopProducts = {};
			webshopProducts = [{"Product_id":163, "title": "Product 1", "quantity" : 8, "ProductOption": [], "price" : 12, "VAT" : 0.21}];	
			$('#shoppingcart').shoppingCart({ detail : true });
			$('.addtocart').click(); //simulate click
			expect($.ajax).toHaveBeenCalled();
			expect($.ajax.mostRecentCall.args[0].data.shoppingCart[0].quantity).toBe(8);
		});
		
		it('When adding a product twice, the cart only has 1 entry for the product, but the quantity has to be 2.', function(){
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
	});
	
	describe('Checkout page testsuite.', function(){

		it('Should calculate delivery costs (when the \'deliver ourselves\' option is selected, and charge according to distance), and show it in the total of the checkout page.', function(){
			//response.routes[0].legs[0].distance.value
			spyOn(google.maps.DirectionsService.prototype, 'route').andCallFake(function(x,y){
				//console.log(y);
				y.call(this, { routes : [{ legs: [{distance : {value: 10000}}]}]}, google.maps.DirectionsStatus.OK);
			});
			
			initAddressLinesInDom();
			$('<div class="deliverycosts-field"></div>').appendTo('#wrap');

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
			console.log('Result raw: '+resultRaw);			
			
			expect(endsWith(resultRaw, '12,50</strong>')).toBe(true);
			$('#wrap').remove();

		});
	});
});