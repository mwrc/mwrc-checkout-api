<?php
require_once("includes/api_functions.inc.php");
require_once("includes/config.inc.php");
require_once("includes/functions.inc.php");
require_once("includes/product_configs.inc.php");


$retailer_info=[];

if(count($_POST))
{
    if(strlen($_POST['retailer_url']))
    {
        $retailer_info = array("retailer_id"=>$_POST["retailer_id"], "retailer_name"=>$_POST['retailer_name'], "retailer_url"=>$_POST['retailer_url']);
  		setcookie("mwrc_retailer_selection", serialize($retailer_info));
    }
}

if(have_mwrc_session())
{

    if(isset($_COOKIE['mwrc_retailer_selection']) && empty($retailer_info)) $retailer_info = unserialize($_COOKIE['mwrc_retailer_selection']);
    
    $product_id = (int)$_GET['id'];
    $request_params = array();
    
    $request_params["product_id"] = $product_id;
    $request_params["show_empty_objects"] = "yes";
    $request_params["include_offline_products"] = "no";
    $request_params["include_product_description_info"] = "yes";
    $request_params["include_cross_sell_products"] = "yes";
    $request_params["include_category_info"] = "yes";
    $request_params["recurse_parent_categories_downward"] = "yes";
    $request_params["include_image_info"] = "yes";
    $request_params["include_link_info"] = "no";
    $request_params["include_retailer_info"] = "yes";
    $request_params["include_licensee_info"] = "yes";
    $request_params["include_tabs_info"] = "yes";
    if(!empty($_GET['zip'])) $request_params["zip"] = $_GET['zip'];
    
    // Build the possible params
    $params = array();
    foreach($request_params as $param=>$val) $params[] = $param."=".$val;
    //$cookie_string_for_api ="";
    // Submit request to MWRC
    $brandRespObj = curl_get($mwrc_domain, $mwrc_lang_abbrev, "product.xml.php", implode("&", $params));
    // print $xml;
    // exit;
    // For example purposes, set the header content type to XML and print the returned XML
    //header("content-type: text/xml");
    //print $xml;
    //exit;
//     $brandRespObj = simplexml_load_string($xml);

    if( ! ($brandRespObj instanceof SimpleXMLElement)) {
        print $brandRespObj;
        exit;
    }
    $brandProduct =& $brandRespObj->product;
    
    /* print_r($brandProduct); */
    
    /*
    if(empty($brandProduct))
    {
        print "no product";
        exit;
    }
    */
    
    $parent_cats = array(0=>array("cat_id"=>0, "name"=>"Product Categories"));
    getParentCats($brandProduct->category_ancestry, $parent_cats);
    
    
    /**
     * Check product retailers
     * 
     * If product has only one retailer and that retailer is the default distributor center, 
     * then this product doesn't have any associated retailers. 
     * Do not display the zip code lookup...display product configs/options
     */
    $has_retailers = (count($brandProduct->retailers->retailer) === 1 && ($brandProduct->retailers->retailer->is_default == "yes"))?false:true;
    
    
    $current_retailer_selection = ($has_retailers)?(!empty($retailer_info["retailer_name"])?$retailer_info["retailer_name"]:""):$brandProduct->retailers->retailer->name;
    
    /**
     * this product is ready to query for configs and display add to cart button
     * 
     * - The product doesn't have any associated retailers
     * OR
     * - We already have a reatiler selected from a previous query
     * 
     */
    
    if(!$has_retailers || !empty($retailer_info["retailer_url"])) 
    {
        //check to make sure the current retailer selected is also available for the current product
        $select_new_retailer=false;
        $retailer_ids=array();
        foreach($brandProduct->retailers->retailer as $retailer)
        {
            $retailer_ids[] = (int)$retailer["retailer_id"];
        }
    
        //if the current selected retailer is not available for this product - customer must select another retailer
        if(!empty($retailer_info) && !in_array($retailer_info['retailer_id'], $retailer_ids) && $has_retailers) $select_new_retailer=true;
        
        $retailer_url = (isset($retailer_info["retailer_url"]) && !$select_new_retailer)?(($has_retailers)?$retailer_info["retailer_url"]:(string)$brandProduct->retailers->retailer->home_url):(string)$brandProduct->retailers->retailer->home_url;
                
    //print $retailer_url;
    //print (int)$select_new_retailer;
    
        $retailerRespObj = curl_get($retailer_url, $mwrc_lang_abbrev, "product.xml.php", implode("&", $params));
    
//         $retailerRespObj = simplexml_load_string($retailer_xml);
    
        $retailerProduct =& $retailerRespObj->product;
    /* print_r($retailerProduct); */
        $cross_sell_products =& $retailerProduct->cross_sell_products;
        
        /**
         * Construct product config data structures
         * 
         */
        $config_cat_names = array();
        $config_cat_ids = array();
        $config_names = array();
        $config_ids = array();
        $swatches = array();
        $min_product_price = array();
        $max_orderable_qty = array();
        $config_array = array();
        $map_array=array();
        
            
        $max_orderable_qty[(int)$retailerProduct["product_id"]] = (int)$retailerProduct->max_orderable_qty;
        $min_product_price[(int)$retailerProduct["product_id"]] = (string)$retailerProduct->discounted_retail_min_price;
        $swatches[(int)$retailerProduct["product_id"]][0] = array("image"=>(string)$retailerProduct->product_image->url, "caption"=>(string)(strlen((string)$retailerProduct->product_image->caption)?$retailerProduct->product_image->caption:$retailerProduct->name));
    
        //build main product map_array
        foreach($retailerProduct->exact_products->exact_product as $exact_product)
        {
          map_array_build($map_array, (int)$retailerProduct["product_id"], $exact_product, $exact_product->product_config);	
        
          $final_array=array();
          build_xref($final_array, $retailerProduct);
          
          $config_array[(int)$retailerProduct["product_id"]] = $final_array; 
        }
        
    /*
           print_r($config_cat_ids);
           print_r($config_ids);
           print_r($map_array);
           print_r($config_array);
           exit;
    */
    
        //build ensemble product map_array
        foreach($cross_sell_products->cross_sell_products_group as $cross_sell_products_group)
        {
            foreach($cross_sell_products_group->products->product as $cross_sell_product)
            {
                $map_array=array();
                foreach($cross_sell_product->exact_products->exact_product as $cross_sell_exact_product)
                {
                    map_array_build($map_array, (int)$cross_sell_product["product_id"], $cross_sell_exact_product, $cross_sell_exact_product->product_config);
                }
        
                $final_array=array();
                build_xref($final_array, $cross_sell_product);
                
                $max_orderable_qty[(int)$cross_sell_product["product_id"]] = (int)$cross_sell_product->max_orderable_qty;
                $min_product_price[(int)$cross_sell_product["product_id"]] = (string)$cross_sell_product->discounted_retail_min_price;
                $swatch_array = array();
                if(strlen((string)$cross_sell_product->thumbnail_image->url))
                {
                    $swatch_array["image"] = (string)$cross_sell_product->thumbnail_image->url;
                    $swatch_array["caption"] = (string)(strlen((string)$cross_sell_product->thumbnail_image->caption)?$cross_sell_product->thumbnail_image->caption:$cross_sell_product->name);
                    
                }
                $swatches[(int)$cross_sell_product["product_id"]][0] = $swatch_array;
                
                $config_array[(int)$cross_sell_product["product_id"]] = $final_array;
                
            }
        }
        
    }
}
//print "--retailers: ".(int)$has_retailers;

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php require_once("includes/functions.inc.php"); ?>    
        
    <title>LEKI - <?php echo (!empty($brandProduct->name) ? " - ".$brandProduct->name : ""); ?></title>
    
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script type="text/javascript" src="/js/mwrc.productConfigs.js"></script>
    
    <script type="text/javascript">
  	  var mwrc_widget_config = {
                                "container": ".cart_container", //Define shopping cart widget container
                                "template": { // create 'template' object to bypass default output.
                                              "items":"#mwrc_cart_qty", // CSS ID
                                              "subtotal":"#mwrc_cart_subtotal", //CSS ID
                                              "checkout_link":"#mwrc_checkout_link", //Your checkout link
                                              "account_link":"#mwrc_account_link" //Your account link
                                            }
                                };
    </script>
    
    <script type="text/javascript" src="http://leki.mwrc.net/js/cart-widget.js"></script>
    
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    
    <!-- Optional theme -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">

    <link rel="stylesheet" type="text/css" href="/css/main.css" media="all" charset="utf-8" />
    
    
    <script type="text/javascript">
    var config_cat_names = <?php echo json_encode($config_cat_names) ?>;
    var config_cat_ids = <?php echo json_encode($config_cat_ids) ?>;
    var config_names = <?php echo json_encode($config_names) ?>;
    var config_ids = <?php echo json_encode($config_ids) ?>;
    var swatches = <?php echo json_encode($swatches) ?>;
    var config_cat_to_config_map = <?php echo json_encode($config_array) ?>;
    var max_orderable_qty = <?php echo json_encode($max_orderable_qty) ?>;
    var min_product_price = <?php echo json_encode($min_product_price) ?>;
    </script>
    
    <script type="text/javascript">
    
    $(document).ready(function($) {
    
    	$.fn.productConfigs().load();
    
    	$('#addtocart').submit(function() {
    		$('#addtocart-spinner').show();
    		
    		var product_id = $('#product_id', this).val();
    		var exact_product_id = $('#exact_product_id_'+product_id, this).val();
    		var quantity = $('#qty_'+product_id, this).val();
    		var url = $(this).attr('action');
    
    		var post_data = {'action':'add_to_cart', 'product_id': product_id, 'exact_product_id': exact_product_id, 'quantity':quantity, 'url': url };
    		
    		$.post("proxy.php", post_data, function(data, stat, jqxhr) {
    			if(jqxhr.status == 200 && data.success && stat == "success")
    			{

    				$('#addtocart-spinner').hide();

                    window.location="cart.php";

                    /*
$.post("proxy.php?action=view_cart", {}, function(data, stat, jqxhr) {
                        
                        if(data.success==1) {

                            $('.cart_container #mwrc_cart_qty').html(data.cart_summary.num_items);
                            $('.cart_container #mwrc_cart_subtotal').html("$"+(data.cart_summary.subtotal/100).toFixed(2));
                        }
                        
                    }, "json");
*/

    			}
    			else
    			{
    				$('#addtocart-spinner').hide();	
    				console.log(data);			
    				alert('error:\n\n'+data.error);
    			}
    		}, "json");
    		
    		return false;
    	});
    	
    });
    
    </script>

</head>

<body>
    
    <div class="container">        
    
        <h1>Channel Islands</h1>
        
        <div class="cart_container">
            <a href="<?php echo $mwrc_retailer_domain ?>/en/shopping-cart.php">
              <span id="mwrc_cart_qty"></span> items <!-- Example output: 2 -->
              <span id="mwrc_cart_subtotal"></span> <!-- Example output: $59.99 --> | 
              <a id="mwrc_checkout_link">Checkout</a> | 
              <a id="mwrc_account_link">Account</a>
            </a>
        </div>

<?php if(!empty($brandProduct)): ?>

<?php if(!empty($parent_cats)): ?>
<ul class="bread_crumbs">
<?php foreach((array)$parent_cats as $cat): ?>
<li>
<a href="index.php?cat_id=<?php echo $cat["cat_id"] ?>"><?php echo $cat['name'] ?></a>
</li>
<?php endforeach; ?>
<li><?php echo $brandProduct->name ?></li>
</ul>
<div class="clear"></div>
<?php endif; ?>

<hr />

<h2><?php echo $brandProduct->name ?></h2>

<h3><?php echo $brandProduct->short_description_html ?></h3>

<div id="product_image_<?php echo (int)$brandProduct["product_id"] ?>">
<img class="product_detail" src="<?php echo $brandProduct->product_image->url ?>" />
<em class="caption"><?php echo $brandProduct->product_image->caption ?></em>
</div>

<?php 
if(count($brandProduct->alternate_images->url)):
foreach($brandProduct->alternate_images->url as $image_url): ?>
<img src="<?php echo $image_url ?>" width="100" height="100" />
<?php 
endforeach;
endif;
?>

<?php if(isset($brandProduct->full_images->full_image)): ?>
<?php foreach($brandProduct->full_images->full_image as $image): ?>
<br /><a href="<?php echo $image->url ?>">Enlargement</a>
<?php endforeach; ?>
<?php endif; ?>

<p><?php echo (string)$brandProduct->long_description_html ?></p>

<?php 
if(!isset($retailerRespObj->product)) {
$product =& $brandProduct;
include("includes/pricing.inc.php");
}
?>

<?php if((int)$brandProduct->quantity_in_stock === 0): ?>
<p class="not_in_stock">NOT IN STOCK</p>
<?php endif; ?>


<form id="searchFeature" name="searchFeature" method="get" action="product.php" class="zip_search">
<span class="zip">ENTER ZIP / POSTAL CODE</span>
<input type="hidden" name="id" value="<?php echo $product_id ?>">
<input type="text" id="zipBox" value="<?php echo (string)$brandRespObj["zip"] ?>" name="zip">
<button type="submit">Go</button>
</form>


<?php 
/**
 * Display retailers if we have a zip code 
 */
if(($has_retailers && !isset($retailerRespObj->product)) || $select_new_retailer): ?>



<?php if(isset($brandProduct->retailers) && (string)$brandRespObj["zip"]): ?>

<?php foreach($brandProduct->retailers->retailer as $retailer): ?>

<form action="product.php?id=<?php echo $brandProduct['product_id'] ?>" method="post" id="retailer_<?php echo (int)$retailer["retailer_id"] ?>" class="retailer">

<input type="hidden" name="retailer_id" value="<?php echo $retailer["retailer_id"] ?>" />
<input type="hidden" name="retailer_name" value="<?php echo htmlentities($retailer->name) ?>" />
<input type="hidden" name="retailer_url" value="<?php echo $retailer->home_url ?>" />
<p><?php echo (string)$retailer->name ?><br /><?php echo (string)$retailer->address1 ?><br /><?php echo (string)$retailer->city ?>, <?php echo $retailer->state ?> <?php echo $retailer->postal_code ?><br />Distance: <?php echo $retailer->distance ?></p>

<?php if((int)$brandProduct->quantity_in_stock > 0): ?>
<p><button type="submit">Buy from <?php echo htmlentities($retailer->name) ?></button></p>
<?php else: ?>
<p>Call: <?php echo $retailer->phone ?></p>
<?php endif; ?>

</form>

<?php endforeach; ?>

<?php endif; ?>


<?php elseif(isset($retailerRespObj->product)): 

/**
 * Display possible product configs/options
 */
?>

<?php if((int)$retailerProduct->quantity_in_stock > 0): ?>

<h3>Retailer Selection: <?php echo $current_retailer_selection ?> [<?php echo $brandRespObj["zip"] ?>]</h3>

<form id="addtocart" name="product" method="get" action="<?php echo $retailer_url ?><?php echo $retailerRespObj["language_abbrev"] ?>/product.php">

<input type="hidden" id="action" name="action" value="add" />
<input type="hidden" id="product_id" name="product_id" value="<?php echo (int)$retailerProduct["product_id"] ?>" />
<input type="hidden" value="<?php echo (string)$retailerProduct->discounted_retail_price ?>" name="price_<?php echo (int)$retailerProduct["product_id"] ?>" id="price_<?php echo (int)$retailerProduct["product_id"] ?>" />
<input type="hidden" value="<?php echo (count($retailerProduct->exact_products->exact_product)==1)?(int)$retailerProduct->exact_products->exact_product["id"]:""?>" name="exact_product_id_<?php echo (int)$retailerProduct["product_id"] ?>" id="exact_product_id_<?php echo (int)$retailerProduct["product_id"] ?>" />




<div class="product_configs" id="product_options_<?php echo (int)$retailerProduct["product_id"] ?>" product-id="<?php echo (int)$retailerProduct["product_id"] ?>">

<?php 
/**
 * Output product config cats
 */
$has_configs=false;
if(isset($retailerProduct->product_config_cats_in_seq->product_config_cat)): //configs 
$has_configs=true;
?>

<?php foreach($retailerProduct->product_config_cats_in_seq->product_config_cat as $retailerProduct_config_cat): ?>

<select id="config_cat_<?php echo (int)$retailerProduct["product_id"] ?>_<?php echo (int)$retailerProduct_config_cat["id"] ?>" name="dd_<?php echo (int)$retailerProduct["product_id"] ?>_<?php echo (int)$retailerProduct_config_cat["id"] ?>" config-cat-id="<?php echo (int)$retailerProduct_config_cat["id"] ?>" class="options">
<option value="0"><?php echo strtoupper((string)$retailerProduct_config_cat->name) ?></option>
<?php foreach($retailerProduct_config_cat->product_configs->product_config as $retailerProduct_config): ?>
<option value="<?php echo (int)$retailerProduct_config["id"] ?>"><?php echo (string)$retailerProduct_config->name ?></option>
<?php endforeach; ?>
</select>

<?php endforeach; ?>

<?php endif; ?>



<select id="qty_<?php echo (int)$retailerProduct["product_id"] ?>" name="quantity_<?php echo (int)$retailerProduct["product_id"] ?>" class="quantity">
<option value="0">QUANTITY</option>
<?php for($i=1; $i<=min((int)$retailerProduct->max_orderable_qty, (int)$retailerProduct->quantity_in_stock); $i++): ?>
<option value="<?php echo $i; ?>"<?php echo ((!$has_configs && $i==1)?" selected=\"selected\"":"") ?>><?php echo $i; ?></option>
<?php endfor; ?>
</select>

</div>




<?php else: ?>

<p>Out of Stock</p>

<h3>Retailers you can buy from: </h3>

<?php if(isset($brandProduct->retailers) && (string)$brandRespObj["zip"]): ?>

<?php foreach($brandProduct->retailers->retailer as $retailer): ?>

<form action="product.php?id=<?php echo $brandProduct['product_id'] ?>" method="post">
<input type="hidden" name="retailer_id" value="<?php echo $retailer["retailer_id"] ?>" />
<input type="hidden" name="retailer_name" value="<?php echo htmlentities($retailer->name) ?>" />
<input type="hidden" name="retailer_url" value="<?php echo $retailer->home_url ?>" />
<p><?php echo (string)$retailer->name ?><br /><?php echo (string)$retailer->address1 ?><br /><?php echo (string)$retailer->city ?>, <?php echo $retailer->state ?> <?php echo $retailer->postal_code ?><br />Distance: <?php echo $retailer->distance ?><br />
Call: <?php echo $retailer->phone ?></p>
</form>

<?php endforeach; ?>

<?php endif; ?>

<?php endif; ?>


<?php 

$product =& $retailerProduct;
include("includes/pricing.inc.php");

?>

<?php if(count($retailerProduct->cross_sell_products->cross_sell_products_group)): ?>

<h2>Cross Sell Products:</h2>
<?php foreach($retailerProduct->cross_sell_products->cross_sell_products_group as $cross_sell_products_group):?>


<div class="cross_sell_products_group">

<h3><?php echo ((string)$cross_sell_products_group->name) ?></h3>

<?php foreach($cross_sell_products_group->products->product as $cross_sell_product): ?>

<input type="hidden" value="<?php echo (string)$cross_sell_product->price ?>" name="price_<?php echo (int)$cross_sell_product["product_id"] ?>" id="price_<?php echo (int)$cross_sell_product["product_id"] ?>" />
<input type="hidden" name="exact_product_id_<?php echo (int)$cross_sell_product["product_id"] ?>" id="exact_product_id_<?php echo (int)$cross_sell_product["product_id"] ?>" />


<div id="cross_sell_product_<?php echo (int)$cross_sell_product["product_id"] ?>" class="cross_sell_product">

<h4><?php echo ((string)$cross_sell_product->product_name) ?></h4>

<div id="product_image_<?php echo (int)$cross_sell_product["product_id"] ?>">
<img class="product_detail" src="<?php echo $cross_sell_product->thumbnail_image->url ?>" />
<em class="caption"><?php echo $cross_sell_product->thumbnail_image->caption ?></em>
</div>

<p><?php echo $cross_sell_product->short_description ?></p>

<div class="product_configs" id="product_options_<?php echo (int)$cross_sell_product["product_id"] ?>" product-id="<?php echo (int)$cross_sell_product["product_id"] ?>">

<?php if(isset($cross_sell_product->product_config_cats_in_seq->product_config_cat)): //configs ?>

<?php foreach($cross_sell_product->product_config_cats_in_seq->product_config_cat as $retailerProduct_config_cat): ?>


<select id="config_cat_<?php echo (int)$cross_sell_product["product_id"] ?>_<?php echo (int)$retailerProduct_config_cat["id"] ?>" name="dd_<?php echo (int)$cross_sell_product["product_id"] ?>_<?php echo (int)$retailerProduct_config_cat["id"] ?>" config-cat-id="<?php echo (int)$retailerProduct_config_cat["id"] ?>" class="options">
<option value="0"><?php echo strtoupper((string)$retailerProduct_config_cat->name) ?></option>
<?php foreach($retailerProduct_config_cat->product_configs->product_config as $retailerProduct_config): ?>
<option value="<?php echo (int)$retailerProduct_config["id"] ?>"><?php echo (string)$retailerProduct_config->name ?></option>
<?php endforeach; ?>
</select>

<?php endforeach; ?>

<?php endif; ?>

<select id="qty_<?php echo (int)$cross_sell_product["product_id"] ?>" name="quantity_<?php echo (int)$cross_sell_product["product_id"] ?>" class="quantity">
<option value="0">QUANTITY</option>
<?php for($i=1; $i<=min($cross_sell_product->max_orderable_qty, $cross_sell_product->quantity_in_stock); $i++): ?>
<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
<?php endfor; ?>
</select>

</div>

<?php 

$product =& $cross_sell_product;
include("includes/pricing.inc.php");

?>


</div>

<?php endforeach; ?>

</div>

<?php endforeach; ?>

<?php endif; ?>

<?php if((int)$retailerProduct->quantity_in_stock > 0): ?>

<button type="submit">Add to Cart</button><img id="addtocart-spinner" src="images/spinner.gif" style="display: none;" />

<?php endif; ?>

</form>

<?php endif; //end elseif retailerRespObj ?>

<?php else: ?>
<p>Product not available</p>
<?php endif; ?>

</div>

</body>

</html>

