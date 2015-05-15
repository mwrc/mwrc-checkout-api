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
        extract($_POST);
        
        ///DO validation...
        
        //Populate form post
        $data = array();        
        $data["email"] = $email;
        $data["phone"] = $phone;
        $data["shipping"] = $shipping;

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
/*
      	print "create";
      	print_r($create_response);
      	exit;
*/

/*
        print "\nhttp code:\n";
        print_r($http_status);
        
        print "\ncreate response:\n";
        print_r($create_response);
        
        print "\nerror:\n";
        print_r($error);
*/
//         exit;
        
        $create_resp_obj = json_decode($create_response);
     
        /**
        * These session values do not necessarily need to be stored in cookies.
        * Preferably, these values would be stored internally using any storage engine of your choice.
        */
        if( ! empty($create_resp_obj->meta->session_code)) {
            setcookie("mwrc_session_code_1_1", $create_resp_obj->meta->session_code, 0, "/");
        	$_COOKIE["mwrc_session_code_1_1"] = $create_resp_obj->meta->session_code;            
        }

        if( ! empty($create_resp_obj->meta->secure_session_code)) {
        	setcookie("mwrc_secure_session_code", $create_resp_obj->meta->secure_session_code, 0, "/");
        	$_COOKIE["mwrc_secure_session_code"] = $create_resp_obj->meta->secure_session_code;            
        }

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
/*
print_r($view_response);
exit;
*/
$error = curl_error($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);  	
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

$view_resp_obj = json_decode($view_response, true);

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
        
        <hr/>
        
        <h3>Email Address</h3>

        <div class="row">
            <div class="col-md-6">
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email[email]" placeholder="Enter email">
              </div>
                
            </div>
            <div class="col-md-6">
              <div class="checkbox">
                <label>
                  <input type="checkbox" name="email[optin]" value="1"> Yes, use this e-mail address to send me e-mail updates on new products, promotions and events.
                </label>
              </div>
                
            </div>            
        </div>        
        
        <hr/>
        
        <h3>Phone Number</h3>

        <div class="row">
            <div class="col-md-2">
                <label for="description">Description</label>                
                <select class="form-control" id="description" name="phone[description]">
                  <option value="">Description</option>
                  <option value="Mobile" selected="selected">Mobile</option>
                  <option value="Home">Home</option>
                  <option value="Work">Work</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="country_code">Country Code</label>                
                <select class="form-control" id="country_code" name="phone[country_code]">
                  <option value="">Country Code</option>
                  <option value="1" selected="selected">US +1</option>
                </select>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="area_code">Area Code</label>
                    <input type="text" class="form-control" id="area_code" placeholder="Area Code" name="phone[area_code]">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="number">Number</label>
                    <input type="text" class="form-control" id="number" placeholder="Number" name="phone[number]">
                </div>
            </div>
            <div class="col-md-3">
                <label>
                  <input type="checkbox" name="phone[optin]" value="1"> Yes, use my number to send me text updates on new products, promotions and events.
                </label>                
            </div>
        </div>
        
        <hr/>
        
        <h3>Shipping Address</h3>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" class="form-control" id="company_name" placeholder="Company Name" name="shipping[company_name]">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" class="form-control" id="first_name" placeholder="First Name" name="shipping[first_name]" value="John">
                </div>


                <div class="form-group">
                    <label for="address1">Address 1</label>
                    <input type="text" class="form-control" id="address1" placeholder="Address 1" name="shipping[address1]" value="1234 Test Ave.">
                </div>

                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" class="form-control" id="city" placeholder="city" name="shipping[city]" value="Culver City">
                </div>

                <div class="form-group">
                    <label for="postal_code">Zip</label>
                    <input type="text" class="form-control" id="postal_code" placeholder="postal_code" name="shipping[postal_code]" value="90230">
                </div>
                
            </div>
            <div class="col-md-6">
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" class="form-control" id="last_name" placeholder="Last Name" name="shipping[last_name]" value="Doe">
                </div>
                
                <div class="form-group">
                    <label for="address1">Address 2</label>
                    <input type="text" class="form-control" id="address2" placeholder="Address 2" name="shipping[address2]">
                </div>

                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" class="form-control" id="state" placeholder="state" name="shipping[state]" value="CA">
                </div>

                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" class="form-control" id="country" placeholder="country" name="shipping[country]" value="US">
                </div>
                
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary" name="submit_order" value="create">Create Order</button>
    </form>
    
</div>    
    
</body>

</html>


