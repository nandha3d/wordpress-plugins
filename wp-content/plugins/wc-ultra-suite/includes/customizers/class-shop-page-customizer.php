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
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_styles']);
        add_action('wp_head', [$this, 'output_custom_css'], 999);
        
        // WooCommerce hooks
        add_filter('loop_shop_columns', [$this, 'set_columns']);
        add_filter('loop_shop_per_page', [$this, 'set_products_per_page']);
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return [
            'columns' => 3,
            'products_per_page' => 12,
            'grid_spacing' => 20,
            'card_style' => 'default',
            'show_rating' => true,
            'show_add_to_cart' => true,
            'show_sale_badge' => true,
            'show_sorting' => true,
            'show_result_count' => true,
            'enable_filters' => false,
            'pagination_style' => 'numbers',
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
            }
            " : "") . "
            
            " . ($settings['card_style'] === 'modern' ? "
            .woocommerce ul.products li.product {
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                transition: transform 0.3s;
            }
            .woocommerce ul.products li.product:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            }
            " : "") . "
            
            /* Hide elements based on settings */
            " . (!$settings['show_rating'] ? "
            .woocommerce ul.products li.product .star-rating {
                display: none !important;
            }
            " : "") . "
            
            " . (!$settings['show_add_to_cart'] ? "
            .woocommerce ul.products li.product .button {
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
