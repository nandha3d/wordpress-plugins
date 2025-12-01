<?php
/**
 * Custom Shop Page Template
 * 
 * @package WC_Ultra_Suite
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header('shop');

// Get settings
$customizer = WC_Ultra_Suite_Shop_Page_Customizer::get_instance();
$settings = $customizer->get_settings();

// Container Class
$container_class = 'wc-ultra-custom-shop-container';
if ($settings['filter_style'] === 'sidebar-cards') {
    $container_class .= ' has-sidebar';
}
?>

<!-- Hero Section -->
<div class="custom-shop-hero">
    <div class="shop-hero-content">
        <h1><?php woocommerce_page_title(); ?></h1>
        <p>Discover our amazing collection of premium products</p>
    </div>
</div>

<div class="<?php echo esc_attr($container_class); ?>">
    
    <?php if ($settings['enable_filters'] && $settings['filter_style'] === 'sidebar-cards'): ?>
        <aside class="wc-ultra-sidebar">
            
            <!-- Price Filter -->
            <?php if ($settings['enable_price_filter']): ?>
                <div class="wc-ultra-filter-card">
                    <h4>💰 Price Range</h4>
                    <div class="price-filter">
                        <input type="number" id="min_price" placeholder="Min" min="0" style="width: 70px; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                        <span style="margin: 0 5px;">to</span>
                        <input type="number" id="max_price" placeholder="Max" min="0" style="width: 70px; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                        <button class="filter-apply-btn" style="padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; margin-top: 10px; width: 100%;">Apply</button>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Category Filter -->
            <?php if ($settings['enable_category_filter']): 
                $categories = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => true,
                ));
                if ($categories && !is_wp_error($categories)):
            ?>
                <div class="wc-ultra-filter-card">
                    <h4>📂 Categories</h4>
                    <ul class="filter-list" style="list-style: none; padding: 0; margin: 0;">
                        <?php foreach ($categories as $category): ?>
                            <li style="padding: 8px 0; border-bottom: 1px solid #f1f3f5;">
                                <a href="<?php echo esc_url(get_term_link($category)); ?>" style="text-decoration: none; color: #4a5568; display: flex; justify-content: space-between; align-items: center;">
                                    <span><?php echo esc_html($category->name); ?></span>
                                    <span style="color: #a0aec0; font-size: 12px;">(<?php echo $category->count; ?>)</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; endif; ?>
            
            <!-- Attribute Filters -->
            <?php if ($settings['enable_attribute_filter'] && !empty($settings['enabled_filters'])):
                foreach ($settings['enabled_filters'] as $attribute_name):
                    $terms = get_terms(array(
                        'taxonomy' => 'pa_' . $attribute_name,
                        'hide_empty' => true,
                    ));
                    if ($terms && !is_wp_error($terms)):
                        $attribute = wc_get_attribute(wc_attribute_taxonomy_id_by_name($attribute_name));
            ?>
                <div class="wc-ultra-filter-card">
                    <h4>🎨 <?php echo esc_html($attribute ? $attribute->name : ucfirst($attribute_name)); ?></h4>
                    <ul class="filter-list" style="list-style: none; padding: 0; margin: 0;">
                        <?php foreach ($terms as $term): ?>
                            <li style="padding: 8px 0; border-bottom: 1px solid #f1f3f5;">
                                <a href="<?php echo esc_url(get_term_link($term)); ?>" style="text-decoration: none; color: #4a5568; display: flex; justify-content: space-between; align-items: center;">
                                    <span><?php echo esc_html($term->name); ?></span>
                                    <span style="color: #a0aec0; font-size: 12px;">(<?php echo $term->count; ?>)</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; endforeach; endif; ?>
            
        </aside>
    <?php endif; ?>

    <main class="wc-ultra-main-content">
        
        <?php if (woocommerce_product_loop()): ?>

            <header class="wc-ultra-shop-header">
                <div class="wc-ultra-result-count">
                    <?php woocommerce_result_count(); ?>
                </div>
                <div class="wc-ultra-ordering">
                    <?php woocommerce_catalog_ordering(); ?>
                </div>
            </header>

            <?php
            woocommerce_product_loop_start();

            if (wc_get_loop_prop('total')) {
                while (have_posts()) {
                    the_post();

                    /**
                     * Hook: woocommerce_shop_loop.
                     */
                    do_action('woocommerce_shop_loop');

                    wc_get_template_part('content', 'product');
                }
            }

            woocommerce_product_loop_end();

            /**
             * Hook: woocommerce_after_shop_loop.
             *
             * @hooked woocommerce_pagination - 10
             */
            do_action('woocommerce_after_shop_loop');
            ?>

        <?php else: ?>
            <?php do_action('woocommerce_no_products_found'); ?>
        <?php endif; ?>

    </main>
</div>

<?php get_footer('shop'); ?>
