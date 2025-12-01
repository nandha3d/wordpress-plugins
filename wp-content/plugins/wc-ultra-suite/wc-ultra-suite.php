<?php
/**
 * Plugin Name: WC Ultra Suite
 * Plugin URI: https://wcultrasuite.com
 * Description: The complete WooCommerce management solution - Professional product addons, advanced analytics, profit tracking, and premium admin dashboard
 * Version: 1.0.0
 * Author: Your Agency
 * Author URI: https://youragency.com
 * Text Domain: wc-ultra-suite
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WC_ULTRA_SUITE_VERSION', '1.0.0');
define('WC_ULTRA_SUITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_ULTRA_SUITE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_ULTRA_SUITE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main WC Ultra Suite Class
 */
class WC_Ultra_Suite {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Declare WooCommerce HPOS compatibility
        add_action('before_woocommerce_init', [$this, 'declare_compatibility']);
        
        // Check if WooCommerce is active
        add_action('plugins_loaded', [$this, 'init']);
    }
    
    /**
     * Declare compatibility with WooCommerce features
     */
    public function declare_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check for WooCommerce
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }
        
        // Load plugin files
        $this->load_dependencies();
        
        // Initialize modules
        $this->init_modules();
        
        // Hooks
        add_action('admin_menu', [$this, 'add_admin_menu'], 25);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once WC_ULTRA_SUITE_PLUGIN_DIR . 'includes/class-core.php';
        require_once WC_ULTRA_SUITE_PLUGIN_DIR . 'includes/modules/class-analytics.php';
        require_once WC_ULTRA_SUITE_PLUGIN_DIR . 'includes/modules/class-products.php';
        require_once WC_ULTRA_SUITE_PLUGIN_DIR . 'includes/modules/class-orders.php';
        require_once WC_ULTRA_SUITE_PLUGIN_DIR . 'includes/modules/class-crm.php';
        require_once WC_ULTRA_SUITE_PLUGIN_DIR . 'includes/customizers/class-product-page-customizer.php';
        require_once WC_ULTRA_SUITE_PLUGIN_DIR . 'includes/customizers/class-shop-page-customizer.php';
    }
    
    /**
     * Initialize modules
     */
    private function init_modules() {
        WC_Ultra_Suite_Core::get_instance();
        WC_Ultra_Suite_Analytics::get_instance();
        WC_Ultra_Suite_Products::get_instance();
        WC_Ultra_Suite_Orders::get_instance();
        WC_Ultra_Suite_CRM::get_instance();
        WC_Ultra_Suite_Product_Page_Customizer::get_instance();
        WC_Ultra_Suite_Shop_Page_Customizer::get_instance();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Ultra Suite', 'wc-ultra-suite'),
            __('Ultra Suite', 'wc-ultra-suite'),
            'manage_woocommerce',
            'wc-ultra-suite',
            [$this, 'render_sales_dashboard'],
            'dashicons-chart-area',
            56
        );
        
        // Sales submenu (Analytics Dashboard)
        add_submenu_page(
            'wc-ultra-suite',
            __('Sales Analytics', 'wc-ultra-suite'),
            __('📊 Sales', 'wc-ultra-suite'),
            'manage_woocommerce',
            'wc-ultra-suite',
            [$this, 'render_sales_dashboard']
        );
        
        // Product Page Customizer
        add_submenu_page(
            'wc-ultra-suite',
            __('Product Page Customizer', 'wc-ultra-suite'),
            __('🛍️ Product Page', 'wc-ultra-suite'),
            'manage_woocommerce',
            'wc-ultra-suite-product-page',
            [$this, 'render_product_page_customizer']
        );
        
        // Shop Page Customizer
        add_submenu_page(
            'wc-ultra-suite',
            __('Shop Page Customizer', 'wc-ultra-suite'),
            __('🏪 Shop Page', 'wc-ultra-suite'),
            'manage_woocommerce',
            'wc-ultra-suite-shop-page',
            [$this, 'render_shop_page_customizer']
        );
        
        // Settings
        add_submenu_page(
            'wc-ultra-suite',
            __('Settings', 'wc-ultra-suite'),
            __('⚙️ Settings', 'wc-ultra-suite'),
            'manage_woocommerce',
            'wc-ultra-suite-settings',
            [$this, 'render_settings']
        );
    }
    
    
    /**
     * Render the Sales dashboard (Analytics)
     */
    public function render_sales_dashboard() {
        include WC_ULTRA_SUITE_PLUGIN_DIR . 'templates/dashboard.php';
    }
    
    /**
     * Render Product Page Customizer
     */
    public function render_product_page_customizer() {
        include WC_ULTRA_SUITE_PLUGIN_DIR . 'templates/dashboard.php';
    }
    
    /**
     * Render Shop Page Customizer
     */
    public function render_shop_page_customizer() {
        include WC_ULTRA_SUITE_PLUGIN_DIR . 'templates/dashboard.php';
    }
    
    /**
     * Render Settings page
     */
    public function render_settings() {
        include WC_ULTRA_SUITE_PLUGIN_DIR . 'templates/dashboard.php';
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'wc-ultra-suite') === false) {
            return;
        }
        
        // Styles
        wp_enqueue_style(
            'wc-ultra-suite-admin',
            WC_ULTRA_SUITE_PLUGIN_URL . 'assets/css/admin-style.css',
            [],
            WC_ULTRA_SUITE_VERSION
        );
        
        // Theme customizer styles
        wp_enqueue_style(
            'wc-ultra-suite-theme-customizer',
            WC_ULTRA_SUITE_PLUGIN_URL . 'assets/css/theme-customizer.css',
            ['wc-ultra-suite-admin'],
            WC_ULTRA_SUITE_VERSION
        );
        
        // Customizer interface styles
        wp_enqueue_style(
            'wc-ultra-suite-customizer-interface',
            WC_ULTRA_SUITE_PLUGIN_URL . 'assets/css/customizer-interface.css',
            ['wc-ultra-suite-admin'],
            WC_ULTRA_SUITE_VERSION
        );
        
        // Add theme colors as inline CSS
        $core = WC_Ultra_Suite_Core::get_instance();
        wp_add_inline_style('wc-ultra-suite-admin', $core->get_theme_colors_css());
        
        // Scripts
        wp_enqueue_script(
            'wc-ultra-suite-admin',
            WC_ULTRA_SUITE_PLUGIN_URL . 'assets/js/admin-app.js',
            ['jquery'],
            WC_ULTRA_SUITE_VERSION,
            true
        );
        
        // Determine initial view based on page slug
        $current_screen = get_current_screen();
        $initial_view = 'dashboard';
        
        if ($current_screen) {
            if (strpos($current_screen->id, 'wc-ultra-suite-product-page') !== false) {
                $initial_view = 'product-page';
            } elseif (strpos($current_screen->id, 'wc-ultra-suite-shop-page') !== false) {
                $initial_view = 'shop-page';
            } elseif (strpos($current_screen->id, 'wc-ultra-suite-settings') !== false) {
                $initial_view = 'settings';
            }
        }
        
        // Localize script
        wp_localize_script('wc-ultra-suite-admin', 'wcUltraSuite', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_ultra_suite_nonce'),
            'pluginUrl' => WC_ULTRA_SUITE_PLUGIN_URL,
            'shopUrl' => get_permalink(wc_get_page_id('shop')),
            'productUrl' => ($latest_product = wc_get_products(['limit' => 1])) ? get_permalink($latest_product[0]->get_id()) : '',
            'currencySymbol' => get_woocommerce_currency_symbol(),
            'currencyPosition' => get_option('woocommerce_currency_pos'),
            'initialView' => $initial_view,
            'i18n' => [
                'loading' => __('Loading...', 'wc-ultra-suite'),
                'error' => __('An error occurred', 'wc-ultra-suite'),
                'success' => __('Success!', 'wc-ultra-suite'),
                'confirmDelete' => __('Are you sure you want to delete this?', 'wc-ultra-suite'),
            ]
        ]);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load on product pages
        if (!is_product()) {
            return;
        }
        
        wp_enqueue_style(
            'wc-ultra-suite-frontend',
            WC_ULTRA_SUITE_PLUGIN_URL . 'assets/css/frontend-style.css',
            [],
            WC_ULTRA_SUITE_VERSION
        );
        
        wp_enqueue_script(
            'wc-ultra-suite-frontend',
            WC_ULTRA_SUITE_PLUGIN_URL . 'assets/js/frontend-addons.js',
            ['jquery'],
            WC_ULTRA_SUITE_VERSION,
            true
        );
        
        wp_localize_script('wc-ultra-suite-frontend', 'wcUltraSuiteFrontend', [
            'currencySymbol' => get_woocommerce_currency_symbol(),
            'currencyPosition' => get_option('woocommerce_currency_pos'),
        ]);
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('WC Ultra Suite requires WooCommerce to be installed and active.', 'wc-ultra-suite'); ?></p>
        </div>
        <?php
    }
}

// Initialize the plugin
function wc_ultra_suite() {
    return WC_Ultra_Suite::get_instance();
}

// Start the plugin
wc_ultra_suite();
