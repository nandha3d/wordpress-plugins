<?php
/**
 * Custom Product Card Template for Nike Design
 * 
 * @package WC_Ultra_Suite
 */

if (!defined('ABSPATH')) {
    exit;
}

global $product;

// Ensure visibility.
if (empty($product) || !$product->is_visible()) {
    return;
}
?>
<li <?php wc_product_class('', $product); ?>>
    
    <!-- Brand Label -->
    <span class="wc-ultra-brand-label">NIKE</span>
    
    <!-- Product Image -->
    <a href="<?php echo esc_url($product->get_permalink()); ?>">
        <?php echo $product->get_image('woocommerce_thumbnail'); ?>
    </a>
    
    <!-- Product Title -->
    <h2 class="woocommerce-loop-product__title">
        <a href="<?php echo esc_url($product->get_permalink()); ?>">
            <?php echo $product->get_name(); ?>
        </a>
    </h2>
    
    <!-- Subtitle -->
    <span class="wc-ultra-product-subtitle">New product 2022</span>
    
    <!-- Dark Footer -->
    <div class="wc-ultra-product-footer">
        <!-- Star Rating -->
        <?php if ($rating_html = wc_get_rating_html($product->get_average_rating())): ?>
            <?php echo $rating_html; ?>
        <?php endif; ?>
        
        <!-- Price -->
        <span class="wc-ultra-product-price">
            <?php echo $product->get_price_html(); ?>
        </span>
    </div>
    
    <!-- Add to Cart Button -->
    <?php woocommerce_template_loop_add_to_cart(); ?>
    
</li>
