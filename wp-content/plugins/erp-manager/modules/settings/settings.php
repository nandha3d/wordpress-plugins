<?php
/**
 * Module Name: Settings Manager
 * Description: Company settings, invoice templates, and payment configuration
 * Version: 1.0.0
 * Author: FreelanceERP
 * Icon: admin-settings
 * Module Class: FERP_Settings_Module
 */

if (!defined('ABSPATH')) exit;

class FERP_Settings_Module {
    
    public static function init() {
        // Add menu
        add_action('admin_menu', [__CLASS__, 'add_menu'], 100);
        
        // Register AJAX handlers
        add_action('wp_ajax_ferp_save_settings', [__CLASS__, 'ajax_save_settings']);
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }
    
    public static function add_menu() {
        add_submenu_page(
            'ferp-dashboard',
            __('Settings', 'freelance-erp-manager'),
            __('⚙️ Settings', 'freelance-erp-manager'),
            'manage_options',
            'ferp-settings',
            [__CLASS__, 'render_page']
        );
    }
    
    public static function render_page() {
        // Enqueue media uploader
        wp_enqueue_media();
        
        // Correct path to view file
        $view = __DIR__ . '/views/settings-view.php';
        
        if (file_exists($view)) {
            include $view;
        } else {
            // Show error if file not found
            echo '<div class="wrap">';
            echo '<h1>Settings</h1>';
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Error:</strong> Settings view file not found at: <code>' . $view . '</code>';
            echo '</p><p>Please ensure the file exists at <code>modules/settings/views/settings-view.php</code></p></div>';
            echo '</div>';
        }
    }
    
    public static function enqueue_scripts($hook) {
        // Only load on settings page
        if ($hook !== 'freelance-erp_page_ferp-settings') {
            return;
        }
        
        // Enqueue media uploader
        wp_enqueue_media();
        
        // Enqueue CSS
        wp_enqueue_style(
            'ferp-settings-css',
            plugin_dir_url(__FILE__) . 'assets/settings.css',
            [],
            '1.0.1'
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'ferp-settings-js',
            plugin_dir_url(__FILE__) . 'assets/settings.js',
            ['jquery', 'wp-util'],
            '1.0.1',
            true
        );
        
        // Localize script
        wp_localize_script('ferp-settings-js', 'FERP', [
            'ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ferp_nonce'),
        ]);
    }
    
    // AJAX Handler
    public static function ajax_save_settings() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        // Get current logo URL
        $logo_url = get_option('ferp_company_logo_url', '');
        
        // Check if logo URL is provided in POST
        if (!empty($_POST['company_logo_url'])) {
            $logo_url = sanitize_text_field($_POST['company_logo_url']);
        }
        
        // Handle file upload if present (not commonly used with media uploader)
        if (!empty($_FILES['company_logo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            
            $upload = wp_handle_upload($_FILES['company_logo'], ['test_form' => false]);
            
            if (isset($upload['url']) && !isset($upload['error'])) {
                $logo_url = $upload['url'];
            }
        }
        
        // Save all settings
        update_option('ferp_company_name', sanitize_text_field($_POST['company_name']));
        update_option('ferp_company_logo_url', $logo_url);
        update_option('ferp_company_address', sanitize_textarea_field($_POST['company_address']));
        update_option('ferp_company_phone', sanitize_text_field($_POST['company_phone']));
        update_option('ferp_company_email', sanitize_email($_POST['company_email']));
        update_option('ferp_company_website', esc_url_raw($_POST['company_website']));
        update_option('ferp_gst_number', sanitize_text_field($_POST['gst_number']));
        
        update_option('ferp_invoice_prefix', sanitize_text_field($_POST['invoice_prefix']));
        update_option('ferp_currency_symbol', sanitize_text_field($_POST['currency_symbol']));
        update_option('ferp_currency', sanitize_text_field($_POST['currency']));
        update_option('ferp_tax_rate', floatval($_POST['tax_rate']));
        update_option('ferp_payment_terms', sanitize_textarea_field($_POST['payment_terms']));
        update_option('ferp_show_invoice_qr', isset($_POST['show_invoice_qr']) ? '1' : '0');
        
        update_option('ferp_payment_qrcode_url', sanitize_text_field($_POST['payment_qrcode_url']));
        update_option('ferp_bank_account_name', sanitize_text_field($_POST['bank_account_name']));
        update_option('ferp_bank_account_number', sanitize_text_field($_POST['bank_account_number']));
        update_option('ferp_bank_name', sanitize_text_field($_POST['bank_name']));
        update_option('ferp_ifsc_code', sanitize_text_field($_POST['ifsc_code']));
        
        wp_send_json_success([
            'message' => __('Settings saved successfully!', 'freelance-erp-manager'),
            'logo_url' => $logo_url
        ]);
    }
}

// Initialize module
FERP_Settings_Module::init();