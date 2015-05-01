(function($){  
	
	$.fn.productConfigs = function(settings) {
		
		var objSelf = this;
		
		this.settings = $.extend({
			onSuccess: function () {},
			handle: '.product_configs',
			product_img_container: '#product_image_',
			product_price_container: '#product_price_',
			product_detail_img: 'img.product_detail',
			cross_sell_product: '.cross_sell_product',
		}, settings);

		this.update = function()
		{

			stored_ids = new Array;
			
			processed_changed = false;
			var auto_trigger = false;
			var config_div = $(this).parent(objSelf.settings.handle);
			var current_obj = this;
			product_id = $(config_div).attr('product-id');
			current_cat_id = $(current_obj).attr('config-cat-id');
			current_config_id = $(current_obj).val();
			var all_options = $('select.options', config_div);

			var sale_price = $(objSelf.settings.product_price_container + product_id +' .sale');
			
			objSelf.setDetailImg();

			$.each(all_options, function(cat_idx, optionObj) {
				
				var cat_id = $(optionObj).attr('config-cat-id');
				var selected_config_id = $(optionObj).val();
				
				if(processed_changed)
				{
					$('option:gt(0)', optionObj).remove();
				}
				else
				{
					if (optionObj.options.length == 0 || selected_config_id == 0 || (cat_idx == ($.inArray(parseInt(current_cat_id), config_cat_ids[product_id]) + 1)))
					{

						if(!auto_trigger)
						{
							$('option:gt(0)', optionObj).remove();
							objSelf.resetQty();
							sale_price.text(min_product_price[product_id]);
						}

						
						for (cfg_count=0; cfg_count<config_ids[product_id][cat_id].length; cfg_count++)
						{
							config_id = config_ids[product_id][cat_id][cfg_count];
							
							map = objSelf.getMap(product_id);
							
							if(config_names[config_id])
							{
								var config_option = $('<option>');	
								config_option.attr('value', config_id);
								config_option.text(config_names[config_id]);
								
								if(map[config_id]) $(optionObj).append(config_option);
							}
						}
						
						var opts = $('option', optionObj);
						if (opts.size() == 2 && 
								opts.index($("option:selected", optionObj)) == 0 &&
								$(config_div).parent(objSelf.settings.cross_sell_product).size() == 0)
						{
							auto_trigger = true;
							$("option:eq(1)", optionObj).attr("selected", "selected");
							$(optionObj).trigger('change');
							return false;
						}
						else
						{
							processed_changed = true;
						}
						
					}
					else
					{

						stored_ids[stored_ids.length] = selected_config_id;

						if (cat_idx == (all_options.length-1))
						{
							exact_product_data = objSelf.getMap(product_id);
							//console.log(exact_product_data);
							var qtyObj = objSelf.resetQty();
							var qty_in_stock = exact_product_data.quantity_in_stock;
							var max_order_qty = max_orderable_qty[product_id];							
							var qty_avail = Math.min(qty_in_stock, max_order_qty);
							
							
							for(i=1; i<=qty_avail; i++)
							{
								var qty_opt = $("<option>");
								$(qty_opt).attr('value', i);
								$(qty_opt).text(i);
								$(qtyObj).append(qty_opt);
							}
							
							if(qty_avail == 0)
							{
								var qty_opt = $("<option>");
								$(qty_opt).attr('value', '');
								$(qty_opt).text("-- OUT OF STOCK -");
								$(qtyObj).append(qty_opt);
							}
							else
							{
								$("option:eq(1)", qtyObj).attr("selected", "selected");
							}
							
							sale_price.text(exact_product_data.price);

							$('#exact_product_id_'+product_id).attr('value', exact_product_data.exact_product);
							
							return false;

						}

					}
				}
			});

		}
		
		this.load = function()
		{
			$.each($(this.settings.handle), function(idx, config_div) {
				$('select.options', config_div).bind('change keyup', objSelf.update);
				$('select:eq(0)', config_div).trigger('change');
			});
			return this;
		}
		
		this.setDetailImg = function()
		{
			if(swatches[product_id][current_cat_id])
			{
				var swatch = null;
				if(!swatches[product_id][current_cat_id][current_config_id]) swatch = swatches[product_id][0];
				else swatch = (current_config_id > 0)?swatches[product_id][current_cat_id][current_config_id]:swatches[product_id][current_config_id];
				
				if(swatch)
				{
					$(objSelf.settings.product_img_container + product_id + " " + objSelf.settings.product_detail_img).attr('src', swatch.image);
					$(objSelf.settings.product_img_container + product_id + " em.caption").text(swatch.caption);
				}
			}
		}
		
		this.getMap = function(product_id)
		{
			var map = config_cat_to_config_map[product_id];
			$.each(stored_ids, function(idx, e){
				map = map[e];
			});
			return map;
		}
		
		this.resetQty = function()
		{
			var q = $('#qty_'+product_id);
			$('option:gt(0)', q).remove();
			return q;
		}
		
		return this;
	};
	
	
})(jQuery);  //end of main document ready function
 