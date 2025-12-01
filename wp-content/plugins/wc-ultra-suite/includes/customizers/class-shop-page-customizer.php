<?php
/**
 * Shop Page Customizer Module
 * Handles customization of shop/archive pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ultra_Suite_Shop_Page_Customizer {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    

    
    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_wc_ultra_suite_get_shop_page_settings', [$this, 'ajax_get_settings']);
        add_action('wp_ajax_wc_ultra_suite_save_shop_page_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_wc_ultra_suite_get_attributes', [$this, 'ajax_get_attributes']);
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_styles']);
        add_action('wp_head', [$this, 'output_custom_css'], 999);
        
        // WooCommerce hooks
        add_filter('loop_shop_columns', [$this, 'set_columns']);
        add_filter('loop_shop_per_page', [$this, 'set_products_per_page']);
        
        // Initialize filters
        add_action('init', [$this, 'init_filter_hooks']);
        
        // Product Loop Modifications
        add_action('wp', [$this, 'setup_product_loop_hooks']);

        // Template Override
        add_filter('template_include', [$this, 'override_shop_template'], 99);
        
        // Dummy Generator
        add_action('wp_ajax_wc_ultra_suite_generate_dummy_products', [$this, 'ajax_generate_dummy_products']);
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return [
            'columns' => 3,
            'products_per_page' => 12,
            'grid_spacing' => 20,
            'card_style' => 'modern', // Default to modern as requested
            'show_rating' => true,
            'show_add_to_cart' => true,
            'show_sale_badge' => true,
            'show_sorting' => true,
            'show_result_count' => true,
            'enable_filters' => false,
            'pagination_style' => 'numbers',
            'enable_price_filter' => true,
            'enable_category_filter' => true,
            'enable_attribute_filter' => true,
            'enable_image_carousel' => true,
            'enable_wishlist' => true,
            'enable_buy_now' => true,
            'filter_style' => 'sidebar-cards',
            'enabled_filters' => [],
        ];
    }
    
    /**
     * Get settings
     */
    public function get_settings() {
        $defaults = $this->get_default_settings();
        $saved = get_option('wc_ultra_suite_shop_page_settings', []);
        return wp_parse_args($saved, $defaults);
    }
    
    /**
     * Setup Product Loop Hooks
     */
    public function setup_product_loop_hooks() {
        $settings = $this->get_settings();
        
        if ($settings['enable_image_carousel']) {
            remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
            add_action('woocommerce_before_shop_loop_item_title', [$this, 'render_image_carousel'], 10);
        }
        
        if ($settings['enable_buy_now']) {
            add_action('woocommerce_after_shop_loop_item', [$this, 'render_action_buttons'], 15);
        }
    }
    
    /**
     * Render Image Carousel
     */
    public function render_image_carousel() {
        global $product;
        $attachment_ids = $product->get_gallery_image_ids();
        
        echo '<div class="wc-ultra-product-image-wrapper">';
        
        if ($attachment_ids) {
            echo '<div class="wc-ultra-carousel">';
            echo '<div class="wc-ultra-carousel-track">';
            echo $product->get_image('woocommerce_thumbnail', ['class' => 'wc-ultra-carousel-img']); 
            foreach (array_slice($attachment_ids, 0, 3) as $attachment_id) { // Limit to 3 extra images
                echo wp_get_attachment_image($attachment_id, 'woocommerce_thumbnail', false, ['class' => 'wc-ultra-carousel-img']);
            }
            echo '</div>';
            // Navigation arrows could be added here if needed, but we'll use hover scroll
            echo '</div>';
        } else {
            echo $product->get_image('woocommerce_thumbnail', ['class' => 'wc-ultra-main-img']);
        }
        
        // Wishlist Button (Absolute positioned on image)
        $settings = $this->get_settings();
        if ($settings['enable_wishlist']) {
            echo '<button class="wc-ultra-wishlist-btn" data-product-id="' . $product->get_id() . '" aria-label="Add to Wishlist">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                  </button>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render Action Buttons (Buy Now)
     */
    public function render_action_buttons() {
        $settings = $this->get_settings();
        
        if ($settings['enable_buy_now']) {
            global $product;
            echo '<a href="' . esc_url($product->add_to_cart_url()) . '&checkout=true" class="wc-ultra-buy-now-btn button alt">Buy Now</a>';
        }
    }

    /**
     * AJAX: Get attributes
     */
    public function ajax_get_attributes() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        $attributes = [];
        
        foreach ($attribute_taxonomies as $tax) {
            $attributes[] = [
                'name' => $tax->attribute_name,
                'label' => $tax->attribute_label
            ];
        }
        
        wp_send_json_success($attributes);
    }

    /**
     * AJAX: Generate Dummy Products
     */
    public function ajax_generate_dummy_products() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        if (!class_exists('WC_Product_Simple')) {
            wp_send_json_error(['message' => 'WooCommerce not loaded']);
        }

        try {
            for ($i = 1; $i <= 10; $i++) {
                $product = new WC_Product_Simple();
                $product->set_name('Dummy Product ' . $i . ' - ' . wp_generate_password(4, false));
                $product->set_regular_price(rand(10, 100));
                $product->set_description('This is a dummy product generated by WC Ultra Suite.');
                $product->set_short_description('Premium quality dummy product.');
                $product->set_status('publish');
                
                // Set random image if available (placeholder)
                // $product->set_image_id(...); 

                $product->save();
            }
            wp_send_json_success(['message' => '10 Dummy products generated successfully!']);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Override Shop Template
     */
    public function override_shop_template($template) {
        $settings = $this->get_settings();
        
        if (isset($settings['enable_custom_template']) && $settings['enable_custom_template']) {
            if (is_shop() || is_product_taxonomy()) {
                $new_template = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/shop-custom.php';
                if (file_exists($new_template)) {
                    return $new_template;
                }
            }
        }
        
        return $template;
    }

    /**
     * Initialize hooks for filters
     */
    public function init_filter_hooks() {
        $settings = $this->get_settings();
        
        if ($settings['enable_filters']) {
            add_action('woocommerce_before_shop_loop', [$this, 'render_shop_filters'], 20);
        }
    }

    /**
     * Render Shop Filters
     */
    public function render_shop_filters() {
        $settings = $this->get_settings();
        
        echo '<div class="wc-ultra-filters ' . esc_attr($settings['filter_style']) . '">';
        
        // Price Filter
        if ($settings['enable_price_filter']) {
            echo '<div class="wc-ultra-filter-card">';
            echo '<div class="wc-ultra-filter-header">
                    <span class="wc-ultra-filter-icon">💰</span>
                    <span class="wc-ultra-filter-title">Price Range</span>
                  </div>';
            echo '<div class="wc-ultra-price-slider">
                    <div class="wc-ultra-slider-range" style="left: 0%; width: 100%;"></div>
                    <div class="wc-ultra-slider-handle" style="left: 0%;"></div>
                    <div class="wc-ultra-slider-handle" style="left: 100%;"></div>
                  </div>';
            echo '<div class="wc-ultra-price-labels">
                    <span class="price-min">$0</span>
                    <span class="price-max">$1000+</span>
                  </div>';
            echo '</div>';
        }
        
        // Category Filter
        if ($settings['enable_category_filter']) {
            $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
            if (!empty($categories) && !is_wp_error($categories)) {
                echo '<div class="wc-ultra-filter-card">';
                echo '<div class="wc-ultra-filter-header">
                        <span class="wc-ultra-filter-icon">📁</span>
                        <span class="wc-ultra-filter-title">Categories</span>
                      </div>';
                echo '<div class="wc-ultra-filter-content">';
                foreach ($categories as $cat) {
                    echo '<label class="wc-ultra-checkbox">';
                    echo '<input type="checkbox" name="product_cat" value="' . esc_attr($cat->slug) . '">';
                    echo '<span class="wc-ultra-checkmark"></span>';
                    echo '<span class="wc-ultra-label-text">' . esc_html($cat->name) . '</span>';
                    echo '<span class="wc-ultra-count">' . $cat->count . '</span>';
                    echo '</label>';
                }
                echo '</div>';
                echo '</div>';
            }
        }
        
        // Attribute Filter
        if ($settings['enable_attribute_filter']) {
            $attribute_taxonomies = wc_get_attribute_taxonomies();
            $enabled_filters = isset($settings['enabled_filters']) ? $settings['enabled_filters'] : [];
            
            if ($attribute_taxonomies) {
                foreach ($attribute_taxonomies as $tax) {
                    // Only show enabled attributes
                    if (!in_array($tax->attribute_name, $enabled_filters)) {
                        continue;
                    }
                    
                    $taxonomy = wc_attribute_taxonomy_name($tax->attribute_name);
                    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
                    
                    if (!empty($terms) && !is_wp_error($terms)) {
                        echo '<div class="wc-ultra-filter-card">';
                        echo '<div class="wc-ultra-filter-header">
                                <span class="wc-ultra-filter-icon">🏷️</span>
                                <span class="wc-ultra-filter-title">' . esc_html($tax->attribute_label) . '</span>
                              </div>';
                        echo '<div class="wc-ultra-filter-content">';
                        foreach ($terms as $term) {
                            echo '<label class="wc-ultra-checkbox">';
                            echo '<input type="checkbox" name="filter_' . esc_attr($tax->attribute_name) . '" value="' . esc_attr($term->slug) . '">';
                            echo '<span class="wc-ultra-checkmark"></span>';
                            echo '<span class="wc-ultra-label-text">' . esc_html($term->name) . '</span>';
                            echo '<span class="wc-ultra-count">' . $term->count . '</span>';
                            echo '</label>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                }
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Save settings
     */
    public function save_settings($settings) {
        return update_option('wc_ultra_suite_shop_page_settings', $settings);
    }
    
    /**
     * AJAX: Get settings
     */
    public function ajax_get_settings() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        wp_send_json_success($this->get_settings());
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
        
        if ($this->save_settings($settings)) {
            wp_send_json_success(['message' => 'Settings saved successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to save settings']);
        }
    }
    
    /**
     * Set shop columns
     */
    public function set_columns($columns) {
        $settings = $this->get_settings();
        return intval($settings['columns']);
    }
    
    /**
     * Set products per page
     */
    public function set_products_per_page($per_page) {
        $settings = $this->get_settings();
        return intval($settings['products_per_page']);
    }
    
    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }
        
        wp_enqueue_style(
            'wc-ultra-suite-shop-page',
            WC_ULTRA_SUITE_PLUGIN_URL . 'assets/css/frontend-shop-page.css',
            [],
            WC_ULTRA_SUITE_VERSION
        );
        
        // Enqueue clean fix CSS with higher priority
        wp_enqueue_style(
            'wc-ultra-suite-shop-page-fix',
            WC_ULTRA_SUITE_PLUGIN_URL . 'assets/css/frontend-shop-page-fix.css',
            ['wc-ultra-suite-shop-page'],
            WC_ULTRA_SUITE_VERSION . '-fix'
        );
        
        // Enqueue Google Fonts
        wp_enqueue_style(
            'wc-ultra-suite-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;700&display=swap',
            [],
            null
        );
        
        // Enqueue Frontend JS
        wp_enqueue_script(
            'wc-ultra-suite-shop-page',
            WC_ULTRA_SUITE_PLUGIN_URL . 'assets/js/frontend-shop-page.js',
            ['jquery'],
            WC_ULTRA_SUITE_VERSION,
            true
        );
        
        $price_range = $this->get_price_range();
        
        wp_localize_script('wc-ultra-suite-shop-page', 'wcUltraSuiteShop', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'currency' => get_woocommerce_currency_symbol(),
            'minPrice' => $price_range['min'],
            'maxPrice' => $price_range['max']
        ]);
    }
    
    /**
     * Get Price Range
     */
    public function get_price_range() {
        global $wpdb;
        
        // Check if table exists (WooCommerce lookup table)
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->wc_product_meta_lookup}'") !== $wpdb->wc_product_meta_lookup) {
            return ['min' => 0, 'max' => 1000];
        }
        
        $sql = "
            SELECT min(min_price) as min_price, max(max_price) as max_price 
            FROM {$wpdb->wc_product_meta_lookup}
            WHERE min_price > 0
        ";
        
        $prices = $wpdb->get_row($sql);
        
        return [
            'min' => floor($prices->min_price ?? 0),
            'max' => ceil($prices->max_price ?? 1000)
        ];
    }
    
    /**
     * Output custom CSS based on settings
     */
    public function output_custom_css() {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }
        
        $settings = $this->get_settings();
        
        $css = "
        <style id='wc-ultra-suite-shop-page-custom'>
            /* Shop Page Customizations */
            
            /* Grid Spacing */
            .woocommerce ul.products li.product {
                margin-bottom: {$settings['grid_spacing']}px !important;
            }
            
            /* Card Style */
            " . ($settings['card_style'] === 'minimal' ? "
            .woocommerce ul.products li.product {
                border: none;
                box-shadow: none;
                background: transparent;
            }
            " : "") . "
            
            " . ($settings['card_style'] === 'modern' ? "
            .woocommerce ul.products li.product {
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0,0,0,0.05);
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                background: #fff;
                border: 1px solid rgba(0,0,0,0.03);
            }
            .woocommerce ul.products li.product:hover {
                transform: translateY(-10px);
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            }
            " : "") . "
            
            /* Hide elements based on settings */
            " . (!$settings['show_rating'] ? "
            .woocommerce ul.products li.product .star-rating {
                display: none !important;
            }
            " : "") . "
            
            " . (!$settings['show_add_to_cart'] ? "
            .woocommerce ul.products li.product .button.add_to_cart_button {
                display: none !important;
            }
            " : "") . "
            
            " . (!$settings['show_sale_badge'] ? "
            .woocommerce ul.products li.product .onsale {
                display: none !important;
            }
            " : "") . "
            
            " . (!$settings['show_sorting'] ? "
            .woocommerce-ordering {
                display: none !important;
            }
            " : "") . "
            
            " . (!$settings['show_result_count'] ? "
            .woocommerce-result-count {
                display: none !important;
            }
            " : "") . "
        </style>
        ";
        
        echo $css;
    }
}
