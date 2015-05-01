<?php

require_once("includes/api_functions.inc.php");
require_once("includes/config.inc.php");
require_once("includes/functions.inc.php");

$action = $_REQUEST["action"];

$ch=curl_init();

if($action == "add_to_cart")
{

    $url = $_POST['url'];    	
    $url .= "?ajax_req=1&api_action=add&product_id={$_POST['product_id']}&exact_product_id_{$_POST['product_id']}={$_POST['exact_product_id']}&quantity_{$_POST['product_id']}=".$_POST['quantity'];
    
    //	print $host."xml/$mwrc_lang_abbrev/$mwrc_script_name?$mwrc_script_args";
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X_MWRC: add_to_cart'));
    
}
else if($action == "view")
{
    $url = $mwrc_retailer_domain."/services/cart.php?action=cart_summary"; //only quantity and subtotal 
/*     $url = $mwrc_retailer_domain."/services/cart.php?action=view"; //entire cart */
}

curl_setopt($ch, CURLOPT_URL, $url);

if (isset($_COOKIE['mwrc_session_code_1_1'])) {
    curl_setopt($ch, CURLOPT_COOKIE, "mwrc_session_code_1_1=".$_COOKIE['mwrc_session_code_1_1'].";");
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
$res=curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
curl_close($ch);
	
//	print "==".$http_status."==";
print $res;

exit;