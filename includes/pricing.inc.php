
<div id="product_price_<?php echo (int)$product["product_id"] ?>">


<?php
$display_retail_range = false;
if((float)$product->full_retail_min_price_raw != (float)$product->full_retail_max_price_raw): 
$display_retail_range = true;
?>
<p>MSRP: <span class="price_range suggested_range"><?php echo $product->full_retail_min_price ?> - <?php echo $product->full_retail_max_price ?></span></p>
<?php endif; ?>


<?php
$display_discount_retail_range = false;
if((float)$product->discounted_retail_min_price_raw != (float)$product->discounted_retail_max_price_raw): 
$display_discount_retail_range = true;
?>
<p>From: <span class="price sale_range"><?php echo $product->discounted_retail_min_price ?> - <?php echo $product->discounted_retail_max_price ?></span></p>

<p>sale w/ range:<span class="price sale"><?php echo $product->discounted_retail_min_price ?></span></p>

<?php endif; ?>



<?php if(isset($product->discounted_retail_price)): ?>

<?php if(!$display_retail_range): ?><p>full retail+sale: <span class="price suggested"><?php echo $product->full_retail_price ?></span></p><?php endif; ?>
<?php if(!$display_discount_retail_range): ?><p>sale: <span class="price sale"><?php echo $product->discounted_retail_price ?></span></p><?php endif; ?>

<?php else: ?>

<?php if(!$display_discount_retail_range): ?><p>full retail no sale price: <span class="price sale"><?php echo $product->full_retail_price ?></span></p><?php endif;?>

<?php endif; ?>

</div>