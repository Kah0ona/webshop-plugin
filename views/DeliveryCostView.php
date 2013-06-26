<?php 
/**
* Overview, showing a list of categories
*/
class DeliveryCostView extends GenericView {
	public function render($args = null, $deliveryCosts=null) { 
		$deliveryText = $args['delivery_text'];
		$deliveryText = str_replace('_total_', "&euro;'+formatEuro(minOrderPrice)+'", $deliveryText);
		$deliveryText = str_replace('_deliverycosts_', "&euro;'+formatEuro(delCosts)+'", $deliveryText);
		$deliveryText = str_replace('_freedelivery_', "&euro;'+formatEuro(freeDelivery)+'", $deliveryText);	
		$theAddress = $this->model->getOptions()->getOption('address');
		$region = $this->model->getOptions()->getOption('region');
		
	?>
<script type='text/javascript' src='http://maps.google.com/maps/api/js?sensor=false&#038;key=AIzaSyCPR76T3otWlBnPh1fK0Pe2bNgIJOBjVwc&#038;ver=3.3.2'></script>
<script type="text/javascript">
	var homeAddr = '<?php echo $theAddress; ?>';	
	var deliveryCosts =	 <?php echo $this->model->fetchDeliveryCostsDefault(); ?>;
	var region_ = '<?php echo $region; ?>';
	var useFormula= false;
	
	directionsDisplay = new google.maps.DirectionsRenderer();
	
	function calculateDistance (cptaddr, callback) {
	    	//calc distance between store and cptaddr (compare to address)
	    	var queryData = {
			  origin: homeAddr,
			  destination: cptaddr,
			  travelMode: google.maps.TravelMode.DRIVING,
			  unitSystem: google.maps.UnitSystem.METRIC,
			  region: region_
			}
					
			var directionsService = new google.maps.DirectionsService();
			distance = -1;
	        directionsService.route(queryData, function(response, status) {
	            if (status == google.maps.DirectionsStatus.OK) {

	            	distance = parseInt(response.routes[0].legs[0].distance.value) / 1000;
	            	
	            	jQuery('#city-result').addClass('hidden');
	            }
	            else if(status == google.maps.DirectionsStatus.NOT_FOUND){
		            jQuery('#city-result').removeClass('hidden')
		            				 .addClass('alert alert-warning')
		            				 .html('Het ingevulde adres is niet gevonden');
	            }
         
   	           	if(callback != null && callback != undefined)
	            	callback.call(this,distance, response, status);

			});			
	    	
	    	
  			
	}
	
	function formatEuro(price){
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
	}
	
	function updateDistanceResult(distance){
		var delPrice = 0;
		    	//check in the settings.deliveryCosts what is the delivery price
		var max = 0;
		
		if(!useFormula) {
			
			for(var i = 0; i < deliveryCosts.length; i++){
				var min = deliveryCosts[i].minKm;
				max = deliveryCosts[i].maxKm;	
				if(min <= distance && distance <= max) { //if distance is within this range
					if(distance > 0){
						jQuery('#city-result').removeClass('hidden alert alert-error')
										 .addClass('alert alert-success')
										 .html('We bezorgen op deze afstand ('+ 
		formatEuro(distance)+' km) vanaf een bestelbedrag van €'+
		formatEuro(deliveryCosts[i].minimumOrderPrice)+
		'. De bezorgkosten bedragen €'+formatEuro(deliveryCosts[i].price)+'.');
										 
					    //hide submit buttons
					}
					break;
				}
			}
			
			if (distance > max){
				jQuery('#city-result')
					.removeClass('hidden alert alert-success')
					.addClass('alert alert-error')
					.html('Helaas bezorgen we niet op deze afstand ('+formatEuro(distance)+' km)');			
				
			}
		}
		else { //use formula
			var delivery = deliveryCosts[0];
			//console.log("!!!!!!");
			//console.log(delivery);
			var delCosts = 0;
		
			var minOrderPrice = 0;
			if(parseFloat(distance) * parseFloat(delivery.minOrderPricePerKm) < parseFloat(delivery.absoluteMinOrderPrice)) 
				minOrderPrice = parseFloat(delivery.absoluteMinOrderPrice);
			else
				minOrderPrice = parseFloat(distance) * parseFloat(delivery.minOrderPricePerKm); 
		
			
			var freeDelivery = 0; //euro amount to be ordered for free delivery
			
			delCosts = parseFloat(distance) * parseFloat(delivery.pricePerKm);
			
			
			if(delivery.useMultiplierFreeDelivery){
				freeDelivery = parseFloat(delivery.deliveryFreeMultiplier) * parseFloat(minOrderPrice);
			}
			else {
				freeDelivery = parseFloat(delivery.deliveryFreeAmount);
			}
			
			if(delivery.absoluteMaxDistance == null)
				delivery.absoluteMaxDistance=500;

			if(distance > delivery.absoluteMaxDistance){
				jQuery('.submit-controls').addClass('disabled');
				jQuery('#city-result').removeClass('hidden').html('We bezorgen helaas niet op deze afstand.');
			}
			else { //within reach
				jQuery('#city-result')
					.removeClass('hidden alert alert-error')
					.addClass('alert alert-success')
					.html('<?php echo $deliveryText; ?>');

			}

			
		}
	}
	
	function renderMap(response, status){
		var myOptions = {
		    mapTypeId: google.maps.MapTypeId.ROADMAP,
		    zoom: 7,
            center: new google.maps.LatLng(52.397, 4.644)

		};
		
		map = new google.maps.Map(document.getElementById("map-placeholder"), myOptions);

		directionsDisplay.setDirections(response);
		directionsDisplay.setMap(map);
	}
	
	function startDistanceCalculator(){
		jQuery('#city-result').addClass('hidden');
		calculateDistance(jQuery('#city-check').val(), function(distance, response, status){
			updateDistanceResult(distance);
			renderMap(response,status);
		});
	}
	
	jQuery(document).ready(function($){
		jQuery('#check-button').click(function(){
			startDistanceCalculator();
		});
		
		jQuery('#city-check').blur(function(){
			startDistanceCalculator();

		});
		
		jQuery('#delivery-form').submit(function(event){
			event.preventDefault();
			startDistanceCalculator();
		});
		
		
		
		var matchIE = /MSIE\s([\d]+)/;
		if(matchIE.test(navigator.userAgent)){
		  jQuery('.modal').removeClass('fade');
		}

	});
	

</script>

<p><a data-toggle="modal" data-target="#city-modal" class="btn btn-inverse info-button">Controleren</a></p>

<div class="modal hide fade" id="city-modal">
	 <div class="modal-header">
	 	<button class="close" data-dismiss="modal">×</button>
	 	<h3>Waar bezorgen we?</h3>
	 </div>
	 <div class="modal-body">
	 	 <p>Vul uw straatnaam en woonplaats in, en bekijk of, 
		 	en vanaf welk bedrag we bij u bezorgen.
		 </p>
		 	 	
		 <p>
			 <form class="form-horizontal" id="delivery-form">
				 <label for="city-check">Uw adres: *
				 	<input type="text" id="city-check" />
			 	 </label>
		 	</form>
		 </p>
		 <p id="city-result">
		 
		 </p>
		 <p>
			 <div id="map-placeholder" ></div>
		 </p>
	 </div>
	 <div class="modal-footer">
	 	<a href="#" class="btn btn-primary" id="check-button">Check</a>
	 </div>
</div>	
	
	<?php
	}
}
?>