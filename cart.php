<?php
require_once("includes/api_functions.inc.php");
require_once("includes/config.inc.php");
require_once("includes/functions.inc.php");

$api_endpoint = "leki-store.devel2.mwrc.net/services/cart.php"; 

if(count($_POST))
{
/*
print "<pre>";
print_r($_POST);
print "</pre>";
exit;
*/
        $data = array();
        
        $data["email"]["email"] = "dan@formula23.com";
        $data["email"]["optin"] = "yes";
        
        $data["phone"]["country_code"] = "1";
        $data["phone"]["area_code"] = "310";
        $data["phone"]["number"] = "6004938";    
        $data["phone"]["extension"] = "";
        $data["phone"]["description"] = "Mobile";
        $data["phone"]["optin"] = "yes";
    
        $data["shipping"]["first_name"] = "Dan";
        $data["shipping"]["last_name"] = "Schultz";
        $data["shipping"]["company_name"] = "API TEST";
        $data["shipping"]["address1"] = "101 Test Ave";
        $data["shipping"]["address2"] = "#807";
        $data["shipping"]["city"] = "Culver City";
        $data["shipping"]["state"] = "CA";
        $data["shipping"]["postal_code"] = "90230";
        $data["shipping"]["country"] = "US";

        foreach($_POST["items"] as $item) {
            if( ! empty($item["full_sku"]) && (int)$item["quantity"] > 0) {
                $data["items"][] = $item;
            }
        }
            
        $data_string = json_encode( $data );

        $ch=curl_init();
    	curl_setopt($ch, CURLOPT_URL, "https://".$api_endpoint."?action=create");
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    	
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    
        $cookie="";
    	if( ! empty($_COOKIE['mwrc_session_code_1_1'])) $cookie .= "mwrc_session_code_1_1=".$_COOKIE['mwrc_session_code_1_1'].";";
      	if( ! empty($_COOKIE["mwrc_secure_session_code"])) $cookie .= "mwrc_secure_session_code=".$_COOKIE["mwrc_secure_session_code"].";";
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);  	    
    
      	$create_response=curl_exec($ch);	
    	$error = curl_error($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);  	
    	$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    	curl_close($ch);
    	        
        if($error) {
            print "CURL Error: $error";
            exit;
        }

        print "\nhttp code:\n";
        print_r($http_status);
        
        print "\ncreate response:\n";
        print_r($create_response);
        
        print "\nerror:\n";
        print_r($error);
//         exit;
        
        $create_resp_obj = json_decode($create_response);
/*
        print "===";
print_r($create_resp_obj);
*/

        /**
        * These session values do not necessarily need to be stored in cookies.
        * Preferably, these values would be stored internally using any storage engine of your choice.
        */
        if( ! empty($create_resp_obj->session_code)) {
            setcookie("mwrc_session_code_1_1", $create_resp_obj->session_code, 0, "/");
        	$_COOKIE["mwrc_session_code_1_1"] = $create_resp_obj->session_code;            
        }

        if( ! empty($create_resp_obj->secure_session_code)) {
        	setcookie("mwrc_secure_session_code", $create_resp_obj->secure_session_code, 0, "/");
        	$_COOKIE["mwrc_secure_session_code"] = $create_resp_obj->secure_session_code;            
        }
print_r($_COOKIE);
exit;
    	if($http_status==200) {

            if($create_resp_obj->success==1) {
                header("Location: /checkout.php");
                exit;

            }
        
    	}
        else {            	            
/*
            print "HTTP Code: ".$http_status."<br />";
            print "local/cart.php - ".__LINE__;
            print_r($create_resp_obj);
            exit;
*/
        }
	
// 	exit;
}
// else {

/*
*
* Get shopping cart
*
*/

    
$ch=curl_init();
curl_setopt($ch, CURLOPT_URL, "http://".$api_endpoint."?action=view");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

if (isset($_COOKIE['mwrc_session_code_1_1'])) {
	$cookie = "mwrc_session_code_1_1=".$_COOKIE['mwrc_session_code_1_1'].";";
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
}

$view_response=curl_exec($ch);	
$error = curl_error($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);  	
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

$view_resp_obj = json_decode($view_response, true);

    
// }

/*
print_r($view_resp_obj);
exit;
*/

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <?php //require_once("includes/sessions.inc.php"); ?>        
    
    <title>LEKI - Cart</title>
    
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    
    <!-- Optional theme -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
    
    <link rel="stylesheet" type="text/css" href="css/main.css" media="all" charset="utf-8" />
    
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    
<!--
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
    
    <script type="text/javascript" src="http://kotalongboards.mwrc.net/js/cart-widget.js"></script>
-->
    
</head>

<body>

<div class="container">

    <h1>LEKI Shopping Cart</h1>
    
        <div class="cart_container">
            <a href="<?php echo $mwrc_retailer_domain ?>/en/shopping-cart.php">
              <span id="mwrc_cart_qty"></span> items <!-- Example output: 2 -->
              <span id="mwrc_cart_subtotal"></span> <!-- Example output: $59.99 -->
            </a> | 
            <a href="index.php">Continue Shopping</a>
        </div>
        <hr />
        
        <?php if( ! empty($create_resp_obj->message->errors)): ?>
        
        <div class="alert alert-danger" role="alert">
            <?php foreach($create_resp_obj->message->errors as $error): ?>
            <p><?php echo $error->error_msg; ?></p>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
        
        <h3>Current Cart</h3>
        
        <br />
        <p>These are the current items in your shopping cart.</p>
        <br />
        
        <?php if( ! empty($view_resp_obj["cart_summary"]["items"])): ?>
        
        <table class="table">
            <tr>
                <th></th>
                <th>Name</th>
                <th>Part#</th>
                <th>Config</th>
                <th>Retailer</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Subtotal</th>
            </tr>
            
            <?php foreach((array)$view_resp_obj["cart_summary"]["items"] as $order_detail_id => $line_item): ?>
            
            <tr>
                <td><img src="<?php echo $line_item["image_uri"] ?>" class="img-responsive" style="max-width:100px" /></td>
                <td><?php echo $line_item["name"] ?></td>
                <td><?php echo $line_item["part_number"] ?></td>
                <td>
                    <?php if(!empty($line_item['configs'])): ?>
                    <ul>
                    <?php foreach($line_item["configs"] as $label=>$val): ?>
                        <li><?php echo $label ?>: <?php echo $val ?></li>
                    <?php endforeach; ?>
                    </ul>                    
                    <?php endif; ?>
                </td>
                <td><?php echo (!empty($line_item["retailer"])?$line_item["retailer"]:"") ?></td>
                <td><?php echo $line_item["unit_price_formatted"] ?></td>
                <td><?php echo $line_item["quantity"] ?></td>
                <td><?php echo $line_item["quantity_price_formatted"] ?></td>
            </tr>
            
            <?php endforeach; ?>
            
        </table>

        <dl class="dl-horizontal pull-right">
          <dt>Order Sub-total</dt>
          <dd><?php echo $view_resp_obj["cart_summary"]["totals"]["subtotal"][0]["formatted"] ?></dd>
        </dl>
        
        <div class="clear"></div>

        <a href="checkout.php" class="btn btn-primary pull-right">Checkout</a>
        
        <div class="clear"></div>

        <?php else: ?>

        <p class="text-danger">Your session does not have any products</p>

        <?php endif; ?>        
        
    <hr />
    
    <h2>Add items to your cart</h2>
    <p>Fill out the form below and submit to add products to create a new shopping cart/order</p>
    <p>If zip code is not provided, retailer will default to distribution center</p>
    <form action="./cart.php" method="post">

        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    <th>SKU/Part#</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach(range(0, 10) as $line): ?>
            <tr>
                <td><?php echo $line ?></td>
                <td>
                    <div class="form-group">
                        <input type="text" class="form-control" id="sku_<?php echo $line ?>" placeholder="SKU" name="items[<?php echo $line ?>][full_sku]">
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <input type="text" class="form-control" id="qty_<?php echo $line ?>" placeholder="Qty" name="items[<?php echo $line ?>][quantity]">
                    </div>                    
                </td>
            </tr>
            <?php endforeach; ?>
            
            </tbody>
        </table>
        
        <button type="submit" class="btn btn-primary" name="submit_order" value="create">Create Order</button>
    </form>
    
</div>

</body>

</html>


