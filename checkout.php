<?php
require_once("includes/api_functions.inc.php");
require_once("includes/config.inc.php");
require_once("includes/functions.inc.php");

$api_endpoint = "http://leki-store.devel2.mwrc.net/services/cart.php"; 

if(count($_POST)) {
    
    $data = array();
        
    $data["billing_shipping_same"] = "no";
    
    $data["billing"]["first_name"] = "Dan - API";
    $data["billing"]["last_name"] = "Schultz";
    $data["billing"]["company_name"] = "API TEST2 - Billing Diff";
    $data["billing"]["address1"] = "101 Test Ave";
    $data["billing"]["address2"] = "#807";
    $data["billing"]["city"] = "Culver City";
    $data["billing"]["state"] = "CA";
    $data["billing"]["postal_code"] = "90230";
    $data["billing"]["country"] = "US";

    $data["card"]["card_encrypt"] = $_POST["enc_data"];
            
    $data_string = json_encode( $data );

    $ch=curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_endpoint."?action=checkout");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    
	if (isset($_COOKIE['mwrc_session_code_1_1'])) {
    	$cookie = "mwrc_session_code_1_1=".$_COOKIE['mwrc_session_code_1_1'].";";
    	$cookie .= "mwrc_secure_session_code=".$_COOKIE["mwrc_secure_session_code"].";";
	    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }

  	$checkout_response=curl_exec($ch);	
	$error = curl_error($ch);
	  	
    $info = curl_getinfo($ch);
	  	
//         print_r($info);
    print_r($checkout_response);
    exit;
    
	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);  	
	$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);
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

$create_resp_obj = json_decode($view_response);

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
    
    <script language="Javascript" src="https://leki-store.mwrc.net/js/keys/create.js" type="text/javascript"></script>
    <script language="Javascript" src="http://leki-store.devel2.mwrc.net/js/library/MWRCEncrypt.min.js" type="text/javascript"></script>
    
    <script language="Javascript" type="text/javascript">
        
    $(document).ready(function (){

        $('#place_order').click(function(e) {
        
            var cc_num = $('#card_number').val();
            var cc_cvv = $('#card_cvv').val();
            var cc_exp_m = $('#card_exp_m').val();
            var cc_exp_y = $('#card_exp_y').val();
        
            if(MWRCEncrypt.encrypt(cc_num, cc_cvv, cc_exp_m, cc_exp_y)) {
               MWRCEncrypt.updateForm('enc_data');
               $('#final_checkout').submit();
            } else {
                throw "Unable to encrypt credit card data.";
            }
            
            return false;
            
        });
        
    });

    </script>

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
        
    
    
    <?php if(!empty($_COOKIE["checkout_step2"]) && !empty($create_resp_obj)): ?>
    
    <h2>CHECKOUT</h2>
    <h3>Order id: <?php echo (string)$create_resp_obj->session_order_id ?></h3>
    
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
    <?php foreach($create_resp_obj->cart_summary->items as $item): ?>
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
        Subtotal: <?php echo $create_resp_obj->cart_summary->totals->subtotal->{0}->formatted ?><br />
        Shipping: <?php echo $create_resp_obj->cart_summary->totals->shipping ?><br />
        Tax: <?php echo $create_resp_obj->cart_summary->totals->tax ?><br />
        Total: <?php echo $create_resp_obj->cart_summary->totals->grand_total ?>
    </p>
    
    <form action="./cart.php" method="post" id="final_checkout" name="final_checkout">
        
        <h3>Enter Billing Address</h3>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" class="form-control" id="company_name" placeholder="Company Name">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" class="form-control" id="first_name" placeholder="First Name" value="John">
                </div>


                <div class="form-group">
                    <label for="address1">Address 1</label>
                    <input type="text" class="form-control" id="address1" placeholder="Address 1" value="1234 Test Ave.">
                </div>

                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" class="form-control" id="city" placeholder="city" value="Culver City">
                </div>

                <div class="form-group">
                    <label for="postal_code">Zip</label>
                    <input type="text" class="form-control" id="postal_code" placeholder="postal_code" value="90230">
                </div>
                
            </div>
            <div class="col-md-6">
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" class="form-control" id="last_name" placeholder="Last Name" value="Doe">
                </div>
                
                <div class="form-group">
                    <label for="address1">Address 2</label>
                    <input type="text" class="form-control" id="address2" placeholder="Address 2">
                </div>

                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" class="form-control" id="state" placeholder="state" value="CA">
                </div>

                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" class="form-control" id="country" placeholder="country" value="US">
                </div>


                
            </div>
        </div>
        
        
        
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


