<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/includes/api_functions.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/includes/config.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/includes/functions.inc.php");

$cat_id = ! empty($_GET['cat_id']) ? $_GET['cat_id'] : 0;

$request_params = array();
$request_params["category_id"] = $cat_id;
$request_params["include_cross_sell_products"] = "yes";
$request_params["include_link_info"] = "yes";
$request_params["include_meta_info"] = "yes";
$request_params["include_category_products"] = "yes";
$request_params["recurse"] = "yes";
$request_params["recurse_parent_categories_downward"] = "yes";
$request_params["show_empty_objects"] = "yes";
$request_params["include_offline_products"] = "no";
$request_params["include_offline_retailers"] = "no";

// Build the possible params
$params = array();
foreach($request_params as $param=>$val) $params[] = $param."=".$val;

//print $cookie_string_for_api;

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
     <?php require_once($_SERVER["DOCUMENT_ROOT"]."/includes/sessions.inc.php"); ?>
    
    <title>Channel Islands - <?php echo (strlen($category->name))?" - ".$category->name:""; ?></title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    
    <!-- Optional theme -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
    
    <link rel="stylesheet" type="text/css" href="/css/main.css" media="all" charset="utf-8" />
    
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    
</head>

<body>

<div class="container">
<?php

// Submit request to MWRC
$xmlobj = curl_get($mwrc_domain, $mwrc_lang_abbrev, "category.xml.php", implode("&", $params));

if(!($xmlobj instanceof SimpleXMLElement)) {
    print $xmlobj;
    exit;
}

$category =& $xmlobj->category;
//print_r($category->products->product);
//exit;

$parent_cats = array(0=>array("cat_id"=>0, "name"=>"Product Categories"));

getParentCats($category->category_ancestry, $parent_cats);

if((int)$category["category_id"]>0) array_push($parent_cats, array("cat_id"=>(int)$category["category_id"], "name"=>(string)$category->name));

?>

<h1>Channel Islands Catalog</h1>

<?php if(count($parent_cats)): ?>
<ul class="bread_crumbs">
<?php foreach((array)$parent_cats as $cat): ?>
<li>
<?php if((int)$category["category_id"]!=$cat["cat_id"]): ?>
<a href="index.php?cat_id=<?php echo $cat["cat_id"] ?>"><?php echo $cat['name'] ?></a>
<?php else: echo $cat["name"]; endif; ?>
</li>
<?php endforeach; ?>
</ul>
<div class="clear"></div>
<?php endif; ?>
<hr />

<?php if(count($category->child_categories->category)): ?>

<ul id="cats" class="list">
<?php foreach($category->child_categories->category as $sub_category): 
?>
<li class="item">
<a href="index.php?cat_id=<?php echo (int)$sub_category["category_id"] ?>"><?php echo htmlentities((string)$sub_category->name) ?></a>
</li>
<?php endforeach; ?>
</ul>

<?php endif; ?>


<?php if(count($category->products->product)): ?>

<h1>Products</h1>

<ul id="products" class="list">
<?php foreach($category->products->product as $product): 
// print_r($product);
?>

<li class="item">
<img src="<?php echo $product->thumbnail_image_url ?>" style="max-width:100px; max-height:100px;" /><br />
<a href="product.php?id=<?php echo (int)$product["product_id"] ?>"><?php echo htmlentities((string)$product->name) ?></a><br />

<?php if(isset($product->discounted_retail_price)): ?>
<span class="price msrp"><?php echo $product->price ?></span>
<span class="price sale"><?php echo $product->discounted_retail_price ?></span>
<?php else: ?>
<span class="price sale"><?php echo $product->price ?></span>
<?php endif; ?>

</li>


<?php endforeach; ?>
</ul>

<?php endif; ?>
</div>
</body>

</html>


