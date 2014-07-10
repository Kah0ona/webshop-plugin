/**
* JQuery plugin that acts as a filter module, based on incoming filter definition
* Author: Marten Sytema (marten@sytematic.nl)
* Plugin dependencies: 
* - JQuery-JSON: http://code.google.com/p/jquery-json/
* Version: 0.6
*/
;(function( $, window, document, undefined ) {
	var pluginName = "filtersystem";
	var defaults =  {
		'base_url' :          'http://webshop.lokaalgevonden.nl',
		'definition_url' :    '/public/filterdefinitions',
		'filterresults_url' : '/public/filterproducts',
		'target_elt' : '#filter_search_results'
	
	};	
	function FiltersystemPlugin(element, options){
		this.element = element;
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}
	
	FiltersystemPlugin.prototype = {
		init : function( options ) {

			var self = this;
			this.load(function(jsonObj){
				self.renderFilterDefinition(jsonObj);
				self.bindButtons();
			});
		},
		load : function(callback){
			var self = this;
			$.ajax({
				url: this.settings.base_url + this.settings.definition_url,
				jsonp: 'callback',
				dataType : 'jsonp',
				data: {
					"hostname"			  : this.settings.hostname, 
					"FilterDefinition_id" : this.settings.FilterDefinition_id
				},
				success : function(jsonobj){
					self.logger('loaded: ', jsonobj);
					callback.call(self,jsonobj);
				}
			});
	    },
		logger : function(msg){
			if(window.console) {
				console.log(msg);	
			} 
		},
		renderFilterDefinition : function(jsonObj) {
			this.logger('rendering filter definition');
			var str = "<form id='filter_form' class='filter_form'>";
			for (var prop in jsonObj) {
				var items = jsonObj[prop];
				str += "<label for='"+prop+"'>"+prop+": </label>";
				str += "<select name='"+prop+"' class='filter_select'>";
				str += " <option>-- Alles --</option>";
				for(var i = 0; i < items.length; i++){
					var item = items[i];
					str += "<option>"+item+"</option>";
				}
				str += "</select>";
			}
			str += "<input type='button' id='filter_button' class='filter_button' value='Zoeken' />";
			str += "</form>";
		    $(this.element).html(str);

		},
		persist : function(){
			//persist the selected values in a cookie/localstorage
	    },	
	    bindButtons : function(){
			var self = this;
			$('body').on('click.'+pluginName, "#filter_button", function(event){
				self.searchAndRenderResults();
			});
	   	},
		searchAndRenderResults : function(){
			var self = this;
			var start = 0;
			var limit = 20;
			var baseData = {
				"hostname" : this.settings.hostname, 
				"start" : start,
				"limit" : limit
					
			};
			var params = this.getParametersFromForm();
			var theData = $.extend({}, baseData, params);
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
			var str = "";
			str += "<h3>Zoekresultaten</h3>";
			str += "<table class='filter_search_results'>";
			for(var i = 0; i < jsonObj.length; i++){
				var item = jsonObj[i];
				str += "<tr>";
				str += " <td><img src='"+this.settings.base_url+"/uploads/Product/"+item.imageDish+"' /></td>";
				str += " <td><a href='/products/"+item.Product_id+"'>"+item.productName+"</a></td>";
				str += " <td>&euro; "+this.formatEuro(item.productPrice)+"</td>";
				str += "</tr>";
			}
			str += "</table>";

			target.html(str);
		},
		getParametersFromForm : function() {
			var ret = {};
			$('.filter_form .filter_select').each(function(){
				var name = $(this).attr("name");
				var val = $(this).val();
				if(val != "" && val != '-- Alles --'){
					ret[name] = val
				}
			});

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
