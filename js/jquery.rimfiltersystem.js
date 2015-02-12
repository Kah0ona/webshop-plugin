/**
* JQuery plugin that searches through rims based on steekmaat/naafgat/ET-waarde/Auto merk
* Author: Marten Sytema (marten@sytematic.nl)
* Plugin dependencies: 
* - JQuery-JSON: http://code.google.com/p/jquery-json/
* Version: 0.6
*/
;(function( $, window, document, undefined ) {
	var pluginName = "rimfiltersystem";
	var defaults =  {
		'base_url' :          'http://webshop.lokaalgevonden.nl',
		'filterresults_url' : '/public/filterrims',
		'target_elt' : '#filter_search_results',
		'items_per_page' : 50
	};	
	function FiltersystemPlugin(element, options){
		this.element = element;
		this.id = $(element).attr('id');
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}
	
	FiltersystemPlugin.prototype = {
		init : function( options ) {
			var self = this;

			this.current_ordering = {
				"field" : "productPrice",
				"direction" : "ascending"
			};

			self.bindButtons();
			self.renderFilterDefinition();

//			this.populateSelect(this.settings.db,$('#rim_filter_form select[name="car"]'));
//			var models = this.settings.db[$('#rim_filter_form select[name="car"]').val()]; //models of currently selected car
//			this.populateSelect(models,$('#rim_filter_form select[name="model"]'));
			self.restoreForm();

		},
		logger : function(msg){
			if(window.console) {
				console.log(msg);	
			} 
		},
		renderFilterDefinition : function() {
			this.logger('rendering rim filter definition');
			var str = "<form id='rim_filter_form' class='filter_form'>";
			str +=	   "<div class='filter_controlgroup'>";
			str +=		"<label for='car'>Merk</label>";
			str +=		"<select name='car' class='filter_select'></select>";
			str +=	   "</div>";
			str +=	   "<div class='filter_controlgroup'>";
			str +=		"<label for='model'>Type/bouwjaar</label>";
			str +=		"<select name='model' class='filter_select'></select>";
			str +=	   "</div>";
			str +=	   "<div class='filter_controlgroup'>";
			str +=		"<label for='inch'>Inch</label>";
			str +=		"<select name='inch' class='filter_select'>";
			for(var i = 10; i <=24 ; i++){
				str +=			"<option>"+i+"</option>";
			}
			str +=	    "</select>";
			str +=	   "</div>";
			str +=	   "<div class='filter_controlgroup'>";
			str +=		"<label for='material'>Materiaal</label>";
			str +=		"<select name='material' class='filter_select'><option>Lichtmetaal/aluminium</option><option>Staal</option></select>";
			str +=	   "</div>";
			str +=	   "<input type='button' id='rim_filter_button' class='filter_button' value='Zoeken' />";
			str +=	   "</form><small class='steekmaat_credits'>Steekmaten via <a href='http://steekmaat.nl' target='_blank'>steekmaat.nl</small>";
		    $(this.element).html(str);

			//this.populateSelect(this.settings.db , $('#rim_filter_form select[name="car"]'));


		},
		populateSelect : function(json, tgt){
			var items = "";
			var keys = [];
			for (k in json) {
				if (json.hasOwnProperty(k)) {
					keys.push(k);
				}
			}
			keys.sort()
			for(var i = 0; i < keys.length; i++){
				var k = keys[i];
				items += "<option>"+k+"</option>";
			}
			tgt.html(items);
		},
		populateModelsBasedOnBrand : function(brand){
			var models = this.settings.db[brand];
			if(models != null){
				this.populateSelect(models,$('#rim_filter_form select[name="model"]'));
			}
		},
		persistForm : function(){
			var obj = {};
			$('#rim_filter_form select').each(function(){
				obj[$(this).attr('name')] = $(this).val();
			});

			$.cookie("rim_filter_form_settings", JSON.stringify(obj));
	    },	
/*		restoreForm : function() {
			var cookie = $.cookie("rim_filter_form_settings");
			if(cookie == null) {
				return;
			}
			var obj = JSON.parse(cookie);
			if(obj != null){
				$('#rim_filter_form select option').each(function(){
					var select = $(this).parent('select');
					var selectName = select.attr('name');
					var val = $(this).html();

					if(obj[selectName] != null && obj[selectName] == val) {
						$(this).attr('selected','selected');
					}
				});
				this.searchAndRenderResults(this.current_ordering, 0);
			}
		},
		*/

		restoreForm : function() {
			var cookie = $.cookie("rim_filter_form_settings");
			if(cookie == null) {
				this.populateSelect(this.settings.db,$('#rim_filter_form select[name="car"]'));
				var models = this.settings.db[$('#rim_filter_form select[name="car"]').val()]; //models of currently selected car
				this.populateSelect(models,$('#rim_filter_form select[name="model"]'));
				return;
			}

			var obj = JSON.parse(cookie);
			var carElt = $('#rim_filter_form select[name="car"]');
			
			//restore the car brand field first.
			this.populateSelect(this.settings.db, carElt);

			carElt.children('option').each(function(){
				var selectName = carElt.attr('name'); // 'car'
				var val = $(this).html();

				if(obj[selectName] != null && obj[selectName] == val) {
					$(this).attr('selected','selected');
				}
			});

			var models = this.settings.db[$('#rim_filter_form select[name="car"]').val()]; //models of currently selected car

			var modelsElt = $('#rim_filter_form select[name="model"]');

			this.populateSelect(models,modelsElt);
			

			modelsElt.children('option').each(function(){
				var selectName = modelsElt.attr('name'); // 'model'
				var val = $(this).html();

				if(obj[selectName] != null && obj[selectName] == val) {
					$(this).attr('selected','selected');
				}
			});

			var inchElt = $('#rim_filter_form select[name="inch"]');

			inchElt.children('option').each(function(){
				var selectName = inchElt.attr('name'); // 'inch'
				var val = $(this).html();

				if(obj[selectName] != null && obj[selectName] == val) {
					$(this).attr('selected','selected');
				}
			});

			var materialElt = $('#rim_filter_form select[name="material"]');
			
			materialElt.children('option').each(function(){
				var selectName = materialElt.attr('name'); // 'material'
				var val = $(this).html();

				if(obj[selectName] != null && obj[selectName] == val) {
					$(this).attr('selected','selected');
				}
			});

			this.searchAndRenderResults(self.current_ordering, 0);
		},
	    bindButtons : function(){
			var self = this;
			$('body').on('click.'+pluginName, "#rim_filter_button", function(event){
				self.searchAndRenderResults(self.current_ordering, 0);
			});
			$('body').on('change.'+pluginName, '#rim_filter_form select', function(event){
				self.persistForm();
			});
			$('body').on('change.'+pluginName, '#rim_filter_form select[name="car"]', function(event){
				self.populateModelsBasedOnBrand($('#rim_filter_form select[name="car"]').val());
			});

			$('body').on('click.'+pluginName, '.filter_search_results_rims th[data-sortable="true"]', function(event){
				var tgt = $(event.currentTarget);

				self.updateOrderingInSortHeaders(tgt);
				var ordering = {
					"field" : tgt.attr("data-sortkey"),
					"direction" : tgt.attr("data-sortdirection")
				};
				self.searchAndRenderResults(ordering, self.current_page);
			});
			//pagination
			$('body').on('click.'+pluginName, '.filter_rims_search_results_pagination a', function(event){
				event.preventDefault();
				var tgt = $(event.currentTarget);
				var page = parseInt(tgt.attr("data-page"));
				self.searchAndRenderResults(self.current_ordering, page);
			});
	   	},
		renderPagination : function(total, currentPage){ 
			if(currentPage == null){
				currentPage = 0;
			}
			var itemsPerPage = this.settings.items_per_page;
			var numPages = Math.ceil(parseInt(total) / parseInt(itemsPerPage));
			var html = "";
			html += "<div class='filter_rims_search_results_pagination'>";
			html += " <small>Gevonden resultaten: "+total+".</small>";
			if(numPages > 1){
				html += " <ul class='filter_search_results_pagination'>";
				for(var i = 1; i <= numPages; i++){
					var active = "";
					if(i == (currentPage+1)){
						active = "class='active'";
					}
					html += "  <li><a href='#' data-page='"+(i-1)+"' "+active+">"+i+"</a></li>";
				}	
				html += " </ul>";
			}
			html += "</div>";

			return html;
		},
		updateOrderingInSortHeaders : function(tgt){
				//unsort all other columns
				$('th[data-sortable="true"]').not(tgt).attr("data-sortdirection", "none").each(function(){
					$(this).html($(this).attr('data-title'));
				});

				var dir = tgt.attr("data-sortdirection");
				//update our target with 
				if(dir == null || dir==undefined || dir == "none"){
					tgt.attr("data-sortdirection", "ascending");
				} else if(dir == "ascending") {
					tgt.attr("data-sortdirection", "descending");
				} else {
					tgt.attr("data-sortdirection", "none");
				}
			
				this.updateOrderingCaret(tgt);
		},
		updateOrderingCaret : function(tgt){
			var dir = tgt.attr("data-sortdirection");
	
			var h = "";
			if(dir == "ascending") {
				h =  " &#9650;"
			} else if(dir == "descending") {
				h =  " &#9660;"
			}
			tgt.html(tgt.attr("data-title")+h);
		},
		searchAndRenderResults : function(ordering, page){
			this.current_ordering = ordering;
			this.current_page = page;

			var self = this;
			var start = this.settings.items_per_page * this.current_page;
			var limit = this.settings.items_per_page;

			var baseData = {
				"hostname" : this.settings.hostname, 
				"start"    : start,
				"limit"    : limit
			};

			var params = this.getParametersFromForm();
			var theData = $.extend({}, baseData, params);

			if(this.current_ordering != undefined && this.current_ordering.direction != "none") {
				var o = "";
				if(this.current_ordering.direction == "descending"){
					o = "-";
				}
				o += this.current_ordering.field;
				theData['order']= o;
			}

			$.ajax({
				url: this.settings.base_url + this.settings.filterresults_url,
				jsonp: 'callback',
				dataType : 'jsonp',
				data: theData,
				success : function(jsonObj){
					self.logger('search results: ', jsonObj);
					self.renderSearchResults(jsonObj, page);
					self.scrollToDiv('.Search-results');
				}
			});
		},
		scrollToDiv : function(className){
			$('html,body').animate({ scrollTop: $(className).offset().top}, 'slow');

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
		renderSearchResults : function(jsonObj){
			var target = $(this.settings.target_elt);
			var str = this.defaultSearchRenderer(jsonObj)
			target.html(str);
		},
		defaultSearchRenderer : function(jsonObj){
			var currentHeader = $('.filter_search_results_rims tr').first();
			var str = "";
			str += "<h3>Zoekresultaten</h3>";
			str += "<table class='filter_search_results filter_search_results_rims'>";

			if(currentHeader != null && currentHeader.children('th').size() != 0){
				str += "<tr>"+currentHeader.html()+"</tr>";
			} else {
				str += "<tr><th></th><th data-sortkey='productName' data-title='Naam'  data-sortable='true'>Naam</th><th>Inch</th>";
				str += "<th data-sortkey='productPrice' data-title='Prijs' data-sortable='true'>Prijs</th><th>#</th><th></th></tr>";
			}
			for(var i = 0; i < jsonObj.length; i++){
				var item = jsonObj[i];

				var inch = item.ProductProperty[0]['propertyValue'];
				if(isNaN(inch)){
					inch = item.ProductProperty[1]['propertyValue'];
				}
				str += "<tr>";
				str += " <td><a class='lightbox' href='"+this.settings.base_url+"/uploads/Product/"+item.imageDish+"'>"+
					" <img src='"+this.settings.base_url+"/uploads/Product/"+item.imageDish+
					"' data-large-src='"+this.settings.base_url+"/uploads/Product/"+item.imageDish+"' "+
					" /></a></td>";
				str += " <td><a href='/products/"+item.Product_id+"'>"+item.productName+"</a></td>";
				str += " <td><a href='/products/"+item.Product_id+"'>"+inch+"</a></td>";
				str += " <td>&euro; "+this.formatEuro(item.productPrice)+"</td>";
				str += " <td><input id='filter_quantity_"+item.Product_id+"' type='text' class='filter_quantity' value='1'/></td>";
				str += " <td><a href='#' data-productid='"+item.Product_id+"' class='filter_addtocart'>Voeg toe</a></td>";
				str += "</tr>";
			}
			str += "</table>";

			var total = 0;
			if(jsonObj.length > 0){
				total = parseInt(jsonObj[0].productOrder);
			}

			str += this.renderPagination(total, this.current_page);
			return str;
		},
		getEmptyStringIfNull : function(str){
			if(str == null){
				return "";
			} else {
				return str;
			}
		},
		getParametersFromForm : function() {
			var car = $('#rim_filter_form select[name="car"]').val();
			var model = $('#rim_filter_form select[name="model"]').val();
			var inch = $('#rim_filter_form select[name="inch"]').val();
			var material = $('#rim_filter_form select[name="material"]').val();

			var ret = this.lookupSizeByCar(car,model);
			ret.inch = inch;
			ret.Materiaal = material;
			return ret;
		},
		lookupSizeByCar : function(car,model){
			if( this.settings.db[car] == undefined ||
				this.settings.db[car][model] == undefined){
				this.logger("Unknown car or model! "+car+", "+model);
				return null;
			}
			var ret = this.settings.db[car][model];
			//ret is in format:
			//  {
			//		"steekmaat": "4x98",
			//		"naafgat": "58.1",
			//		"etwaarde": "40"
			//	}
			return ret;
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
				var data = $this.data('filtersystem'); //gets the data out of the element. 
			});
		}
	}

	$.fn[ pluginName ] = function ( options ) {
		return this.each(function() {
			if ( !$.data( this, "plugin_" + pluginName ) ) {
					$.data( this, "plugin_" + pluginName, new FiltersystemPlugin( this, options ) );
			}
		});
	};
})( jQuery, window, document  );
