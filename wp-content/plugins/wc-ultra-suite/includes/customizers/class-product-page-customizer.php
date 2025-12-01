<?php
/**
 * Product Page Customizer Module
 * Handles customization of single product pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ultra_Suite_Product_Page_Customizer {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_wc_ultra_suite_get_product_page_settings', [$this, 'ajax_get_settings']);
        add_action('wp_ajax_wc_ultra_suite_save_product_page_settings', [$this, 'ajax_save_settings']);
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_styles']);
        add_action('wp_head', [$this, 'output_custom_css'], 999);
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return [
            'image_position' => 'left',
            'gallery_style' => 'thumbnails',
            'button_text' => 'Add to Cart',
            'button_style' => 'default',
            'show_quantity' => true,
            'show_sku' => true,
            'show_categories' => true,
            'show_tags' => false,
            'show_related' => true,
            'related_count' => 4,
        ];
    }
    
    /**
     * Get settings
     */
    public function get_settings() {
        $defaults = $this->get_default_settings();
        $saved = get_option('wc_ultra_suite_product_page_settings', []);
        return wp_parse_args($saved, $defaults);
    }
    
    /**
     * Save settings
     */
    public function save_settings($settings) {
        return update_option('wc_ultra_suite_product_page_settings', $settings);
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
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        if (!is_product()) {
            return;
        }
        
        wp_enqueue_style(
            'wc-ultra-suite-product-page',
            WC_ULTRA_SUITE_PLUGIN_URL . 'assets/css/frontend-product-page.css',
            [],
            WC_ULTRA_SUITE_VERSION
        );
    }
    
    /**
     * Output custom CSS based on settings
     */
    public function output_custom_css() {
        if (!is_product()) {
            return;
        }
        
        $settings = $this->get_settings();
        
        $css = "
        <style id='wc-ultra-suite-product-page-custom'>
            /* Product Page Customizations */
            
            /* Image Position */
            " . ($settings['image_position'] === 'right' ? "
            .woocommerce div.product div.images {
                float: right;
            }
            .woocommerce div.product div.summary {
                float: left;
            }
            " : "") . "
            
            /* Gallery Style */
            " . ($settings['gallery_style'] === 'slider' ? "
            .woocommerce div.product div.images .flex-control-thumbs {
                display: none;
            }
            " : "") . "
            
            /* Button Style */
            .woocommerce div.product form.cart .button {
                " . ($settings['button_style'] === 'rounded' ? "border-radius: 50px;" : "") . "
                " . ($settings['button_style'] === 'square' ? "border-radius: 0;" : "") . "
            }
            
            /* Hide elements based on settings */
            " . (!$settings['show_quantity'] ? "
            .woocommerce div.product form.cart .quantity {
                display: none !important;
            }
            " : "") . "
            
            " . (!$settings['show_sku'] ? "
            .woocommerce div.product .product_meta .sku_wrapper {
                display: none !important;
            }
            " : "") . "
            
            " . (!$settings['show_categories'] ? "
            .woocommerce div.product .product_meta .posted_in {
                display: none !important;
            }
            " : "") . "
            
            " . (!$settings['show_tags'] ? "
            .woocommerce div.product .product_meta .tagged_as {
                display: none !important;
            }
            " : "") . "
            
            " . (!$settings['show_related'] ? "
            .woocommerce .related.products {
                display: none !important;
            }
            " : "") . "
        </style>
        ";
        
        echo $css;
    }
}
