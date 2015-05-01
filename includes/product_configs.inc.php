<?php

function map_array_build(&$map_array, $product_id, &$exact_product, &$product_configs)
{
    global $config_cat_names, $config_names, $config_cat_ids;
    
    if(!($product_configs instanceof SimpleXMLElement)) return;
    
    $tmp=array();
    
    foreach($product_configs as $product_config)
    {
        $config_cat_names[(int)$product_config["cat_id"]] = (string)$product_config->product_config_cat_name;
        $config_names[(int)$product_config["id"]] = (string)$product_config->product_config_name;
        $tmp[(int)$product_config["cat_id"]] = (int)$product_config["id"];
    }
    
    ksort($tmp, SORT_NUMERIC);
    $serial_arr = serialize($tmp);
    
    $map_array[$serial_arr]["exact_product"] = (int)$exact_product["id"];
    $map_array[$serial_arr]["full_sku"] = (string)$exact_product->full_sku;
    $map_array[$serial_arr]["quantity_in_stock"] = (int)$exact_product->quantity_in_stock;
    $map_array[$serial_arr]["price"] = (string)$exact_product->price;
    $map_array[$serial_arr]["full_retail_price"] = (string)$exact_product->full_retail_price;
    $map_array[$serial_arr]["discount_retail_price"] = (string)$exact_product->full_retail_price;
    $map_array[$serial_arr]["swatch_image"] = (string)$exact_product->swatch->image;
    	
    return;
}


//recursively loop through the cats and configs in proper sequence to build the final_array
function build_xref (&$final_array_node, &$product)
{
	global $map_array, $config_cat_ids, $config_ids, $swatches;
	static $tmp=array();

  $product_config_cats = (array)$product->product_config_cats_in_seq;

	if (isset($product_config_cats["product_config_cat"][count((array) $tmp)]))
	{
		$rtmp=&$product_config_cats["product_config_cat"][count((array) $tmp)];
		
		$cat_id = (int)$rtmp["id"];    		

        if(!in_array($cat_id, @(array)$config_cat_ids[(int)$product["product_id"]])) $config_cat_ids[(int)$product["product_id"]][] = $cat_id;    		

    
		foreach($rtmp->product_configs->product_config as $product_config)
		{
		    $config_id = (int)$product_config["id"];

 		    if(!in_array($config_id, @(array)$config_ids[(int)$product["product_id"]][$cat_id])) $config_ids[(int)$product["product_id"]][$cat_id][] = $config_id;
		    
 		    $swatch_array=array();
 		    if(strlen((string)$product_config->swatch))
 		    {
 		        $swatch_array["image"] = (string)$product_config->swatch;
 		        $swatch_array["caption"] = (string)$product_config->swatch_label;
 		    }
 		    
        if(count($swatch_array)) $swatches[(int)$product["product_id"]][$cat_id][$config_id] = $swatch_array;
 		    
  			$tmp[$cat_id]=$config_id;
				build_xref($final_array_node[$config_id], $product);
		    array_pop($tmp);
		}
		return;
	}
	$tmp2=$tmp;
	ksort($tmp2, SORT_NUMERIC);

  if(isset($map_array[serialize($tmp2)])) $final_array_node=$map_array[serialize($tmp2)];

}
