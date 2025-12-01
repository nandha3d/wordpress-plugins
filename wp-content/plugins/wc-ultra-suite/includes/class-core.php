<?php
/**
 * Core functionality for WC Ultra Suite
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ultra_Suite_Core {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_wc_ultra_suite_get_settings', [$this, 'ajax_get_settings']);
        add_action('wp_ajax_wc_ultra_suite_save_settings', [$this, 'ajax_save_settings']);
    }
    
    /**
     * Get plugin settings
     */
    public function get_settings() {
        return get_option('wc_ultra_suite_settings', [
            'white_label_enabled' => false,
            'white_label_name' => 'Ultra Suite',
            'hide_wc_menus' => false,
            'enable_dark_mode' => true,
            'theme_colors' => [
                'primary' => '#6366f1',
                'secondary' => '#8b5cf6',
                'accent' => '#10b981'
            ],
            'modules_enabled' => [
                'analytics' => true,
                'products' => true,
                'orders' => true,
                'crm' => true,
            ]
        ]);
    }
    
    /**
     * Save plugin settings
     */
    public function save_settings($settings) {
        return update_option('wc_ultra_suite_settings', $settings);
    }
    
    /**
     * AJAX: Get settings
     */
    public function ajax_get_settings() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-ultra-suite')]);
        }
        
        wp_send_json_success($this->get_settings());
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-ultra-suite')]);
        }
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
        
        if ($this->save_settings($settings)) {
            wp_send_json_success(['message' => __('Settings saved successfully', 'wc-ultra-suite')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save settings', 'wc-ultra-suite')]);
        }
    }
    
    /**
     * Format currency
     */
    public static function format_currency($amount) {
        return wc_price($amount);
    }
    
    /**
     * Format date
     */
    public static function format_date($date) {
        return date_i18n(get_option('date_format'), strtotime($date));
    }
    
    /**
     * Get theme colors CSS
     */
    public function get_theme_colors_css() {
        $settings = $this->get_settings();
        $colors = isset($settings['theme_colors']) ? $settings['theme_colors'] : [
            'primary' => '#6366f1',
            'secondary' => '#8b5cf6',
            'accent' => '#10b981'
        ];
        
        return "
            :root {
                --wc-ultra-primary: {$colors['primary']};
                --wc-ultra-secondary: {$colors['secondary']};
                --wc-ultra-success: {$colors['accent']};
                --wc-ultra-accent: {$colors['accent']};
            }
        ";
    }
}
