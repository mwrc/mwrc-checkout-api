<?php
require_once("includes/api_functions.inc.php");
require_once("includes/config.inc.php");
require_once("includes/functions.inc.php");

$api_endpoint = "http://leki-store.devel2.mwrc.net/services/cart.php"; 

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
    	curl_setopt($ch, CURLOPT_URL, $api_endpoint."?action=create");
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        
    	if (isset($_COOKIE['mwrc_session_code_1_1'])) {
        	$cookie = "mwrc_session_code_1_1=".$_COOKIE['mwrc_session_code_1_1'].";";
    	    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
    
      	$create_response=curl_exec($ch);	
    	$error = curl_error($ch);

        $info = curl_getinfo($ch);
    	  	
/*
        print_r($info);
        print_r($create_response);        
        exit;
*/
        
    	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);  	
    	$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    	curl_close($ch);
    	/*
            header("Content-Type: ".$contentType);
        //     echo $contentType;
        	print "http status:\n";
        	print $http_status."\n\n";
        	
        // 	print "Response: <br />";
            print $create_response;
            exit;
        */
        
        	
        //     $info = curl_getinfo ($ch);	
        // 	print_r($info);
        
        	
        /*
        	if ( ! $error) {
            	print "http status: ".$http_status."<br />";
            	
                $json_data = json_decode($response);
                print "JSON RESP:";
                print "<pre>";
                print_r($json_data);
                print $error;
        	} else {
            	print "error";
                print $error;	
        	}
        */

    	if($http_status==200) {
        
            $create_resp_obj = json_decode($create_response);
        
            setcookie("mwrc_session_code_1_1", $create_resp_obj->session_code, 0, "/");
        	$_COOKIE["mwrc_session_code_1_1"] = $create_resp_obj->session_code;
            
        	setcookie("mwrc_secure_session_code", $create_resp_obj->secure_session_code, 0, "/");
        	$_COOKIE["mwrc_secure_session_code"] = $create_resp_obj->secure_session_code;
    
            header("Location: /checkout.php");
            exit;
    	}
			
	
// 	exit;
}


/*
*
* Get shopping cart
*
*/

    
$ch=curl_init();
curl_setopt($ch, CURLOPT_URL, $api_endpoint."?action=view");
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
// print_r($view_resp_obj);
// exit;

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title>LEKI - Cart</title>
    
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    
    <!-- Optional theme -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
    
    <link rel="stylesheet" type="text/css" href="css/main.css" media="all" charset="utf-8" />
    
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    
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
                <td><?php echo $line_item["retailer"] ?></td>
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

        <button class="btn btn-primary pull-right" type="submit">Checkout</button>
        
        <div class="clear"></div>

        <?php else: ?>

        <p class="text-info">Your session does not have any products</p>

        <?php endif; ?>        
        
    <hr />
    
    <?php if(empty($create_resp_obj)): ?>

    <h2>Create new order</h2>
    <p>Fill out the form below and submit to add products to create a new shopping cart/order</p>
    <p>If zip code is not provided, retailer will default to distribution center</p>
    <form action="./cart.php" method="post">
<!--
        $data["items"][0]["product_id"] = 0;
        $data["items"][0]["exact_product_id"] = 0;
        $data["items"][0]["full_sku"] = "T6322274";
        $data["items"][0]["quantity"] = 1;
        $data["items"][0]["unit_price"] = 139.95;
        $data["items"][0]["unit_price_discount"] = 0;
        $data["items"][0]["price"] = 139.95;
-->
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
    
    <?php endif; ?>
    
    
    
    <?php if(!empty($_COOKIE["checkout_step2"]) && !empty($create_resp_obj)): ?>
    
    <h2>CHECKOUT</h2>
    <h3>Order id: <?php echo (string)$create_resp_obj->session_order_id ?></h3>
    <h3>Ship To:</h3>
    <p>
        <?php echo $create_resp_obj->customer_info->shipping_address->first_name." ".$create_resp_obj->customer_info->shipping_address->last_name  ?><br />
        <?php echo $create_resp_obj->customer_info->shipping_address->company_name ?><br />
        <?php echo $create_resp_obj->customer_info->shipping_address->address1 ?> <?php echo $create_resp_obj->customer_info->shipping_address->address2 ?><br />
        <?php echo $create_resp_obj->customer_info->shipping_address->city ?>, <?php echo $create_resp_obj->customer_info->shipping_address->state ?>         <?php echo $create_resp_obj->customer_info->shipping_address->postal_code ?>
    </p>
    <p><?php echo $create_resp_obj->customer_info->email->email ?></p>
    <p><?php echo $create_resp_obj->customer_info->phone->country_code ?>-<?php echo $create_resp_obj->customer_info->phone->area_code ?>-<?php echo $create_resp_obj->customer_info->phone->number ?></p>
    
    <h3>Order</h3>
    
    <table class="table">   
        <tr>
            <th></th>
            <th>Product</th>
            <th>Part#</th>
            <th>Retailer</th>
            <th>Unit Price</th>
            <th>Qty</th>
            <th>Price</th>
        </tr> 
    <?php foreach($create_resp_obj->order_details->items as $item): ?>
    <tr>
        <td><img src="http://leki-store.mwrc.net<?php echo $item->image ?>" style="max-width: 100px; max-height: 100px;" /></td>
        <td><?php echo $item->name ?></td>
        <td><?php echo $item->part_number ?></td>
        <td><?php echo $item->retailer ?></td>
        <td><?php echo $item->unit_price_formatted ?></td>
        <td><?php echo $item->quantity ?></td>        
        <td><?php echo $item->quantity_price_formatted ?></td>
    </tr>
    <?php endforeach; ?>
    </table>
    
    <p>
        Subtotal: <?php echo $create_resp_obj->order_details->totals->subtotal->{0}->formatted ?><br />
        Shipping: <?php echo $create_resp_obj->order_details->totals->shipping ?><br />
        Tax: <?php echo $create_resp_obj->order_details->totals->tax ?><br />
        Total: <?php echo $create_resp_obj->order_details->totals->grand_total ?>
    </p>
    
    <form action="./cart.php" method="post" id="final_checkout" name="final_checkout">
        
        <input type="hidden" name="submit_order" value="checkout" />
        
        <h3>Enter Billing Address</h3>
        
        
        <h3>Enter Credit Card</h3>
        
        <input type="hidden" id="enc_data" name="enc_data" value="" />
        
        <div class="form-group">
            <label for="card_number">Card Number</label>
            <input type="text" class="form-control" id="card_number" placeholder="Card Number">
        </div>
        
        <div class="form-group">
            <label for="card_cvv">Card CVV</label>
            <input type="text" class="form-control" id="card_cvv" placeholder="Card CVV">
        </div>
       
        <div class="form-group">
            <label for="card_exp_m">Card Exp. MM</label>
            <select id="card_exp_m" class="form-control">
                <option value="">MM</option>
                <?php for($i=1;$i<=12;$i++): ?>
                <option value="<?php echo $i ?>"><?php echo $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="card_exp_y">Card Exp. YYYY</label>
            <select id="card_exp_y" class="form-control">
                <option value="">YYYY</option>
                <?php for($i=date("Y"); $i<=date("Y")+10; $i++): ?>
                <option value="<?php echo $i ?>"><?php echo $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
    
        <button type="submit" name="place_order" id="place_order" value="checkout">Place Order</button>
        
    </form>
    
    <?php endif; ?>
    
</div>

</body>

</html>


