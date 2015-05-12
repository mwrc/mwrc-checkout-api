# mwrc-checkout-api
This is a fully functional prototype illustrating the functionality and capabilities of the MWRC checkout API from start to finish.

To launch this prototype using PHP's built-in server run the following command:

`$ php -S localhost:8888`

### index.php

This page is the main catalog page. Your categories are displayed starting at the very top level. Click categories will take you further into the various categories until you are displayed with a list of products for the selected category. Selecting a product will direct you to `product.php`

### product.php

This page will display the selected products details. Name, description, part#, product options (configurations - if applicable), quantity, add to cart button

If you are participating in the retailer revenue share program then the customers zip code must be entered before adding a product. The customer must select a retailer to purchase from and this retailer's sub-domain will be used in the add to cart request.

### cart.php

This page will display a list of products in your shopping cart (associated with your current session)

There are also some input fields listed below to mass add products to your session (shopping cart). This form utilizes the [Create Shopping Cart](/mwrc/mwrc-checkout-api/wiki/Create-Shopping-Cart) API endpoint and expects to send the customers email, phone, shipping address and products all in one request, to instantiate a new order. Upon a successful response, the session and secure session codes are returned and must be persisted for future API calls, such as, [Place Order](/mwrc/mwrc-checkout-api/wiki/Place-Order)


## checkout.php

This page is the final page/step in the ordering process. This page is displayed to the customer after the mass add to cart (Create Shopping Cart) feature was successful.

Summary of the shopping cart is displayed, billing information (address, credit card) input fields are required. Credit card details must be passed encrypted and follow the instructions from the [MWRCEncrypt integration](/) page.

Upon a successful place order request an order id is returned and the customer can be redirected to a confirmation page.

