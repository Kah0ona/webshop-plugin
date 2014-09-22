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
		'items_per_page' : 25
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
			self.bindButtons();
			self.renderFilterDefinition();
			this.populateSelect(this.settings.db,$('#rim_filter_form select[name="car"]'));
			var models = this.settings.db[$('#rim_filter_form select[name="car"]').val()]; //models of currently selected car
			this.populateSelect(models,$('#rim_filter_form select[name="model"]'));
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
			str +=	   "<input type='button' id='rim_filter_button' class='filter_button' value='Zoeken' />";
			str +=	   "</form>";
		    $(this.element).html(str);

			this.populateSelect(this.settings.db , $('#rim_filter_form select[name="car"]'));


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

			$.cookie("rim_filter_form_settings_", JSON.stringify(obj));
	    },	
		restoreForm : function() {
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
				this.searchAndRenderResults();
			}
		},
	    bindButtons : function(){
			var self = this;
			$('body').on('click.'+pluginName, "#rim_filter_button", function(event){
				self.searchAndRenderResults(null, 1);
			});
			$('body').on('change.'+pluginName, '#rim_filter_form select', function(event){
				self.persistForm();
			});
			$('body').on('change.'+pluginName, '#rim_filter_form select[name="car"]', function(event){
				self.populateModelsBasedOnBrand($('#rim_filter_form select[name="car"]').val());
			});

			$('body').on('click.'+pluginName, 'th[data-sortable="true"]', function(event){
				var tgt = $(event.currentTarget);

				self.updateOrderingInSortHeaders(tgt);
				var ordering = {
					"field" : tgt.attr("data-sortkey"),
					"direction" : tgt.attr("data-sortdirection")
				};
				self.searchAndRenderResults(ordering, 1);
			});
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
			var self = this;
			var start = 0;
			var limit = this.settings.items_per_page;

			if(page != undefined){
				start = this.settings.items_per_page * page;
			} else {
				start = 0;
			}

			var baseData = {
				"hostname" : this.settings.hostname, 
				"start"    : start,
				"limit"    : limit
			};

			var params = this.getParametersFromForm();
			var theData = $.extend({}, baseData, params);

			if(ordering != undefined && ordering.direction != "none") {
				var o = "";
				if(ordering.direction == "descending"){
					o = "-";
				}
				o += ordering.field;
				theData['order']= ordering;
			}

			$.ajax({
				url: this.settings.base_url + this.settings.filterresults_url,
				jsonp: 'callback',
				dataType : 'jsonp',
				data: theData,
				success : function(jsonObj){
					self.logger('search results: ', jsonObj);
					self.renderSearchResults(jsonObj);
				}
			});
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
			var currentHeader = $('.filter_search_results tr').first();
			var str = "";
			str += "<h3>Zoekresultaten</h3>";
			str += "<table class='filter_search_results'>";

			if(currentHeader != null && currentHeader.children('th').size() != 0){
				str += "<tr>"+currentHeader.html()+"</tr>";
			} else {
				str += "<tr><th></th><th data-sortkey='productName' data-title='Naam'  data-sortable='true'>Naam</th><th>Inch</th>";
				str += "<th data-sortkey='productPrice' data-title='Prijs' data-sortable='true'>Prijs</th><th>#</th><th></th></tr>";
			}
			for(var i = 0; i < jsonObj.length; i++){
				var item = jsonObj[i];
				str += "<tr>";
				str += " <td><a class='lightbox' href='"+this.settings.base_url+"/uploads/Product/"+item.imageDish+"'>"+
					" <img src='"+this.settings.base_url+"/uploads/Product/"+item.imageDish+
					"' data-large-src='"+this.settings.base_url+"/uploads/Product/"+item.imageDish+"' "+
					" /></a></td>";
				str += " <td><a href='/products/"+item.Product_id+"'>"+item.productName+"</a></td>";
				str += " <td><a href='/products/"+item.Product_id+"'>"+item.ProductProperty[0]['propertyValue']+"</a></td>";
				str += " <td>&euro; "+this.formatEuro(item.productPrice)+"</td>";
				str += " <td><input id='filter_quantity_"+item.Product_id+"' type='text' class='filter_quantity' value='1'/></td>";
				str += " <td><a href='#' data-productid='"+item.Product_id+"' class='filter_addtocart'>Voeg toe</a></td>";
				str += "</tr>";
			}
			str += "</table>";
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

			var ret = this.lookupSizeByCar(car,model);
			ret.inch = inch;
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
