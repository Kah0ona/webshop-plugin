/**
* JQuery plugin that acts as a filter module, based on incoming filter definition
* Author: Marten Sytema (marten@sytematic.nl)
* Plugin dependencies: 
* - JQuery-JSON: http://code.google.com/p/jquery-json/
* Version: 0.6
*/
;(function( $, window, document, undefined ) {
	var pluginName = "filtersystem";
	var atLeastOneSelected = false;
	var defaults =  {
		'base_url' :          'http://webshop.lokaalgevonden.nl',
		'definition_url' :    '/public/filterdefinitions',
		'filterresults_url' : '/public/filterproducts',
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

			this.load(function(jsonObj){
				var count = 0;
				for (k in jsonObj) {
					if (jsonObj.hasOwnProperty(k)) {
					   	count++;
					}
				}
				
				if(count > 0) { //there is something
					$(this.element).parent().show();
					self.renderFilterDefinition(jsonObj);
					self.bindButtons();
					self.restoreForm();
					if(this.settings.searchOnPageLoad && atLeastOneSelected){
						this.searchAndRenderResults(this.current_ordering, 0);
					}
				} else {
					$(this.element).parent().hide();
				}
			}); 
			
		},
		load : function(callback){
			var self = this;
			var theData = {
				"hostname"			  : this.settings.hostname, 
//				"FilterDefinition_id" : this.settings.FilterDefinition_id
			};

			if(this.settings.use_category != null && this.settings.use_category && this.settings.Category_id != null){
				theData['Category_id'] = this.settings.Category_id;
			} else {
				if(this.settings.FilterDefinition_id == null){
					self.logger("WARNING, no FilterDefinition_id is set, nor a Category_id is set!");
				}
				theData["FilterDefinition_id"] = this.settings.FilterDefinition_id;
			}


			if(this.settings.extra_param_string != null){
				var s = this.settings.extra_param_string;
				var vars = s.split('&');
				var p = vars[0].split('=');
				theData['customKey'] = p[0];
				theData['customVal'] = p[1];
			}

			$.ajax({
				url: this.settings.base_url + this.settings.definition_url,
				jsonp: 'callback',
				dataType : 'jsonp',
				data: theData,
				success : function(jsonobj){
					self.logger('loaded: ', jsonobj);
					self.filterdefinition = jsonobj;
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
			var str = "<form id='filter_form_"+this.id+"' class='filter_form'>";
			for (var prop in jsonObj) {
				var items = jsonObj[prop];
				if(
					(prop == 'Kleur' && !this.settings.show_color) ||   
					(prop == 'Seizoen' && !this.settings.show_season) ||
					(prop == 'Merk' && !this.settings.show_brand)   
				) {

				} else {
					str += "<div class='filter_controlgroup'>";
					str += "<label for='"+prop+"'>"+prop+": </label>";
					str += "<select name='"+prop+"' class='filter_select'>";
					str += " <option>-- Alles --</option>";
//					items.sort();

					//**************** TODO move somewhere else *******************
					Array.prototype.alphanumSort = function(caseInsensitive) {
						for (var z = 0, t; t = this[z]; z++) {
							this[z] = [];
							var x = 0, y = -1, n = 0, i, j;

							while (i = (j = t.charAt(x++)).charCodeAt(0)) {
								var m = (i == 46 || (i >=48 && i <= 57));
								if (m !== n) {
									this[z][++y] = "";
									n = m;
								}
								this[z][y] += j;
							}
						}

						this.sort(function(a, b) {
							for (var x = 0, aa, bb; (aa = a[x]) && (bb = b[x]); x++) {
								if (caseInsensitive) {
									aa = aa.toLowerCase();
									bb = bb.toLowerCase();
								}
								if (aa !== bb) {
									var c = Number(aa), d = Number(bb);
									if (c == aa && d == bb) {
										return c - d;
									} else return (aa > bb) ? 1 : -1;
								}
							}
							return a.length - b.length;
						});

						for (var z = 0; z < this.length; z++) {
							if(this[z].constructor === Array){
								this[z] = this[z].join("");
							}
						}
					}
					//****************
					items.alphanumSort();

					for(var i = 0; i < items.length; i++){
						var item = items[i];
						str += "<option>"+item+"</option>";
					}
					str += "</select>";
					str += "</div>";
				}
			}
			str += "<input type='button' id='filter_button_"+this.id+"' class='filter_button' value='Zoeken' />";
			str += "</form>";
		    $(this.element).html(str);

		},

		renderPagination : function(total, currentPage){ 
			if(currentPage == null){
				currentPage = 0;
			}
			var itemsPerPage = this.settings.items_per_page;
			var numPages = Math.ceil(parseInt(total) / parseInt(itemsPerPage));
			var html = "";
			html += "<div class='filter_search_results_"+this.id+"_pagination'>";
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
		scrollToDiv : function(className){
			$('html,body').animate({ scrollTop: $(className).offset().top}, 'slow');

		},


		persistForm : function(){
			var obj = {};
			$('#filter_form_'+this.id+' select').each(function(){
				obj[$(this).attr('name')] = $(this).val();
			});

			$.cookie("filter_form_settings_"+this.id, JSON.stringify(obj));
	    },	
		restoreForm : function() {
			atLeastOneSelected = false;
			var cookie = $.cookie("filter_form_settings_"+this.id);
			if(cookie == null) {
				return;
			}
			var obj = JSON.parse(cookie);
			if(obj != null){
				$('#filter_form_'+this.id+' select option').each(function(){
					var select = $(this).parent('select');
					var selectName = select.attr('name');
					var val = $(this).html();

					if(obj[selectName] != null && obj[selectName] == val) {
						$(this).attr('selected','selected');
						if(val != '-- Alles --') {
							atLeastOneSelected=true;
						}
					}
				});


			}
		},
	    bindButtons : function(){
			var self = this;
			$('body').on('click.'+pluginName, "#filter_button_"+this.id, function(event){
				self.searchAndRenderResults(this.current_ordering, 0);
			});
			$('body').on('change.'+pluginName, '#filter_form_'+this.id+' select', function(event){
				self.persistForm();
			});

			$('body').on('click.'+pluginName, '.filter_search_results_'+this.id+' th[data-sortable="true"]', function(event){
				var tgt = $(event.currentTarget);

				self.updateOrderingInSortHeaders(tgt);
				var ordering = {
					"field" : tgt.attr("data-sortkey"),
					"direction" : tgt.attr("data-sortdirection")
				};
				self.searchAndRenderResults(ordering, self.current_page);
			});
			//pagination
			$('body').on('click.'+pluginName, '.filter_search_results_'+this.id+'_pagination a', function(event){
				event.preventDefault();
				var tgt = $(event.currentTarget);
				var page = parseInt(tgt.attr("data-page"));
				self.searchAndRenderResults(self.current_ordering, page);
			});
	   	},
		updateOrderingInSortHeaders : function(tgt){
				//unsort all other columns
				$('.filter_search_results_'+this.id+' th[data-sortable="true"]')
					.not(tgt).attr("data-sortdirection", "none").each(function(){

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
			this.current_ordering = ordering;
			this.current_page = page;
			var start = this.settings.items_per_page * this.current_page;
			var limit = this.settings.items_per_page;

			var baseData = {
				"hostname" : this.settings.hostname, 
				"start" : start,
				"limit" : limit
					
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

			if(this.settings.extra_param_string != null){
				var s = this.settings.extra_param_string;
				var vars = s.split('&');
				for(var i = 0; i < vars.length; i++){
					var p = vars[i].split('=');
					theData[p[0]] = p[1];
				}
			}

			if(this.settings.Category_id != null){
				theData['Category_id'] = this.settings.Category_id;
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

			if(this.settings.customResultsRenderer != undefined){
				str = this.settings.customResultsRenderer.call(this, jsonObj);
			} else {
				str = this.defaultSearchRenderer(jsonObj)
			}

			target.html(str);
		},
		defaultSearchRenderer : function(jsonObj){
			var str = "";

			var currentHeader = $('.filter_search_results_'+this.id+' tr').first();

			var str = "";
			str += "<h3>Zoekresultaten</h3>";
			str += "<table class='filter_search_results filter_search_results_"+this.id+"'>";

			if(currentHeader != null && currentHeader.children('th').size() != 0){
				str += "<tr>"+currentHeader.html()+"</tr>";
			} else {
				str += "<tr><th></th><th data-sortkey='productName' data-title='Naam'  data-sortable='true'>Naam</th><th>Merk</th>";
				
				for(var k in this.filterdefinition){
					if(
						(k == 'Kleur'  && this.settings.show_color) ||
						(k == 'Merk'  && this.settings.show_brand) ||
						(k == 'Seizoen'  && this.settings.show_season) ||
						(k != 'Kleur' && k != 'Merk' && k != 'Seizoen')
					) {
						str += "<th>"+k+"</th>";
					} 
				}
				
				str += "<th data-sortkey='productPrice' data-title='Prijs' data-sortable='true'>Prijs</th><th>#</th><th></th></tr>";
			}
			
			for(var i = 0; i < jsonObj.length; i++){
				var item = jsonObj[i];
				str += "<tr>";
				str += " <td><img src='"+this.settings.base_url+"/uploads/Product/"+item.imageDish+"' /></td>";
				str += " <td>"+this.getEmptyStringIfNull(item.brand)+"</td>";
				str += " <td><a href='/products/"+item.Product_id+"'>"+item.productName+"</a></td>";
				var j = 0;
			//	this.logger(this.settings);
				for(var k in this.filterdefinition){
					if(k == 'Kleur' && this.settings.show_color)  {
						str += "<td>"+product.productColor+"</td>";
					} else if(k == 'Merk' && this.settings.show_brand ) {
						str += "<td>"+product.brand+"</td>";
					} else if(k == 'Seizoen' && this.settings.show_season){
						str += "<td>"+product.productSeason+"</td>"; 
					} else if(k != 'Kleur' && k != 'Merk' && k != 'Seizoen') {
						var val = item.ProductProperty[j]['propertyValue'];
						if(val == undefined){
							val = "";
						} 
						str += "<td>"+val+"</td>";
						j++;
					}	
				}


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
			var ret = {}; 
			$('#filter_form_'+this.id+' .filter_select').each(function(){
				var name = $(this).attr("name");
				var val = $(this).val();
				if(val != "" && val != '-- Alles --'){
					if(name == 'Merk'){
						ret['brand'] = val;
					} else if(name == 'Kleur') {
						ret['productColor'] = val;
					} else if(name == 'Seizoen') {
						ret['productSeason'] = val;
					} else {
						ret[name] = val;
					}
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
