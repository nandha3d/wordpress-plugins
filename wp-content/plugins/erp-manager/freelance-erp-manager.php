<?php
/**
 * Plugin Name: FERP Modular Manager
 * Plugin URI: https://yourwebsite.com
 * Description: Modular system for Freelance ERP Manager - Add unlimited modules with plug-and-play architecture
 * Version: 1.0.0
 * Author: FreelanceERP
 * Author URI: https://yourwebsite.com
 * Text Domain: ferp-modular
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) exit;

// Define constants
define('FERP_MODULAR_VERSION', '1.0.0');
define('FERP_MODULAR_PATH', plugin_dir_path(__FILE__));
define('FERP_MODULAR_URL', plugin_dir_url(__FILE__));
define('FERP_MODULAR_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class FERP_Modular_Manager {
    
    private static $instance = null;
    private static $modules = [];
    private static $active_modules = [];
    private static $modules_dir = '';
    
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
        self::$modules_dir = FERP_MODULAR_PATH . 'modules/';
        
        // Create modules directory if it doesn't exist
        if (!file_exists(self::$modules_dir)) {
            wp_mkdir_p(self::$modules_dir);
        }
        
        // Initialize
        add_action('plugins_loaded', [$this, 'init'], 5);
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Auto-discover modules
        $this->discover_modules();
        
        // Load active modules
        $this->load_active_modules();
        
        // Admin hooks
        add_action('admin_menu', [$this, 'add_main_menu'], 9); // PRIORITY 9 - Create parent menu first
        add_action('admin_menu', [$this, 'add_modules_menu'], 20); // PRIORITY 20 - Then add submenu
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // AJAX hooks
        add_action('wp_ajax_ferp_toggle_module', [$this, 'ajax_toggle_module']);
        add_action('wp_ajax_ferp_install_module', [$this, 'ajax_install_module']);
        add_action('wp_ajax_ferp_delete_module', [$this, 'ajax_delete_module']);
        
        // Add settings link
        add_filter('plugin_action_links_' . FERP_MODULAR_BASENAME, [$this, 'add_action_links']);
    }
    
    /**
     * Add main menu page (PARENT)
     */
    public function add_main_menu() {
        add_menu_page(
            __('Freelance ERP', 'ferp-modular'),
            __('Freelance ERP', 'ferp-modular'),
            'manage_options',
            'ferp-dashboard',
            [$this, 'render_dashboard_page'],
            'dashicons-portfolio',
            30
        );
        
        // Add Dashboard as first submenu item
        add_submenu_page(
            'ferp-dashboard',
            __('Dashboard', 'ferp-modular'),
            __('📊 Dashboard', 'ferp-modular'),
            'manage_options',
            'ferp-dashboard',
            [$this, 'render_dashboard_page']
        );
    }
    
    /**
     * Add modules menu page (SUBMENU)
     */
    public function add_modules_menu() {
        add_submenu_page(
            'ferp-dashboard',
            __('Modules', 'ferp-modular'),
            __('🧩 Modules', 'ferp-modular'),
            'manage_options',
            'ferp-modules',
            [$this, 'render_modules_page']
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Freelance ERP Dashboard', 'ferp-modular'); ?></h1>
            <div class="card">
                <h2><?php _e('Welcome to Freelance ERP Manager', 'ferp-modular'); ?></h2>
                <p><?php _e('Manage your freelance business with modular system.', 'ferp-modular'); ?></p>
                
                <h3><?php _e('Quick Stats', 'ferp-modular'); ?></h3>
                <p>
                    <strong><?php _e('Active Modules:', 'ferp-modular'); ?></strong> 
                    <?php echo count(self::$active_modules); ?><br>
                    
                    <strong><?php _e('Available Modules:', 'ferp-modular'); ?></strong> 
                    <?php echo count(self::$modules); ?>
                </p>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=ferp-modules'); ?>" class="button button-primary">
                        <?php _e('Manage Modules', 'ferp-modular'); ?>
                    </a>
                </p>
                
                <?php if (!empty(self::$active_modules)): ?>
                <h3><?php _e('Active Modules', 'ferp-modular'); ?></h3>
                <ul>
                    <?php foreach (self::$active_modules as $slug): ?>
                        <?php if (isset(self::$modules[$slug])): ?>
                        <li>
                            <strong><?php echo esc_html(self::$modules[$slug]['name']); ?></strong> - 
                            <?php echo esc_html(self::$modules[$slug]['description']); ?>
                        </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Auto-discover modules
     */
    private function discover_modules() {
        if (!file_exists(self::$modules_dir)) {
            return;
        }
        
        $module_folders = glob(self::$modules_dir . '*', GLOB_ONLYDIR);
        
        foreach ($module_folders as $folder) {
            $module_slug = basename($folder);
            $module_file = $folder . '/' . $module_slug . '.php';
            
            if (file_exists($module_file)) {
                $module_data = $this->get_module_data($module_file);
                
                if ($module_data && !empty($module_data['name'])) {
                    self::$modules[$module_slug] = [
                        'slug' => $module_slug,
                        'file' => $module_file,
                        'folder' => $folder,
                        'name' => $module_data['name'],
                        'description' => $module_data['description'],
                        'version' => $module_data['version'],
                        'author' => $module_data['author'],
                        'requires' => $module_data['requires'],
                        'icon' => $module_data['icon'],
                        'class' => $module_data['class']
                    ];
                }
            }
        }
        
        // Sort by name
        uasort(self::$modules, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
    }
    
    /**
     * Get module metadata
     */
    private function get_module_data($file) {
        $file_data = get_file_data($file, [
            'name' => 'Module Name',
            'description' => 'Description',
            'version' => 'Version',
            'author' => 'Author',
            'requires' => 'Requires',
            'icon' => 'Icon',
            'class' => 'Module Class'
        ]);
        
        return $file_data;
    }
    
    /**
     * Load active modules
     */
    private function load_active_modules() {
        self::$active_modules = get_option('ferp_modular_active_modules', []);
        
        foreach (self::$active_modules as $module_slug) {
            if (isset(self::$modules[$module_slug])) {
                $this->load_module($module_slug);
            }
        }
    }
    
    /**
     * Load a single module
     */
    private function load_module($module_slug) {
        if (!isset(self::$modules[$module_slug])) {
            return false;
        }
        
        $module = self::$modules[$module_slug];
        
        // Check requirements
        if (!$this->check_requirements($module['requires'])) {
            return false;
        }
        
        // Include the module file
        require_once $module['file'];
        
        // Initialize module class
        if (!empty($module['class']) && class_exists($module['class'])) {
            if (method_exists($module['class'], 'init')) {
                call_user_func([$module['class'], 'init']);
            }
        }
        
        do_action('ferp_modular_module_loaded', $module_slug, $module);
        
        return true;
    }
    
    /**
     * Check module requirements
     */
    private function check_requirements($requires) {
        if (empty($requires)) {
            return true;
        }
        
        $required = array_map('trim', explode(',', $requires));
        
        foreach ($required as $req) {
            if (!in_array($req, self::$active_modules)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Activate module
     */
/**
 * Activate module (DEBUG VERSION)
 */
public function activate_module($module_slug) {
    error_log('========== ACTIVATING MODULE: ' . $module_slug . ' ==========');
    
    if (!isset(self::$modules[$module_slug])) {
        error_log('ERROR: Module not found in modules array');
        return new WP_Error('not_found', __('Module not found', 'ferp-modular'));
    }
    
    if (in_array($module_slug, self::$active_modules)) {
        error_log('ERROR: Module already active');
        return new WP_Error('already_active', __('Module is already active', 'ferp-modular'));
    }
    
    $module = self::$modules[$module_slug];
    error_log('Module data: ' . print_r($module, true));
    
    // Check requirements
    if (!$this->check_requirements($module['requires'])) {
        error_log('ERROR: Requirements not met');
        return new WP_Error('requirements', __('Required modules are not active', 'ferp-modular'));
    }
    
    // Run activation hook
    $activation_file = $module['folder'] . '/activate.php';
    error_log('Looking for activation file: ' . $activation_file);
    error_log('File exists: ' . (file_exists($activation_file) ? 'YES' : 'NO'));
    
    if (file_exists($activation_file)) {
        error_log('Loading activation file...');
        require_once $activation_file;
        
        $func = $module_slug . '_activate';
        error_log('Looking for function: ' . $func);
        error_log('Function exists: ' . (function_exists($func) ? 'YES' : 'NO'));
        
        if (function_exists($func)) {
            error_log('Calling activation function...');
            call_user_func($func);
            error_log('Activation function completed');
        } else {
            error_log('ERROR: Activation function not found!');
        }
    } else {
        error_log('WARNING: No activation file found');
    }
    
    // Add to active modules
    self::$active_modules[] = $module_slug;
    update_option('ferp_modular_active_modules', self::$active_modules);
    error_log('Added to active modules list');
    
    // Load the module
    error_log('Loading module...');
    $load_result = $this->load_module($module_slug);
    error_log('Module loaded: ' . ($load_result ? 'YES' : 'NO'));
    
    do_action('ferp_modular_module_activated', $module_slug);
    
    error_log('========== MODULE ACTIVATION COMPLETE ==========');
    
    return true;
}
    
    /**
     * Deactivate module
     */
    public function deactivate_module($module_slug) {
        if (!in_array($module_slug, self::$active_modules)) {
            return new WP_Error('not_active', __('Module is not active', 'ferp-modular'));
        }
        
        // Check dependencies
        foreach (self::$modules as $slug => $mod) {
            if (!in_array($slug, self::$active_modules)) {
                continue;
            }
            
            if (!empty($mod['requires'])) {
                $requires = array_map('trim', explode(',', $mod['requires']));
                if (in_array($module_slug, $requires)) {
                    return new WP_Error('dependency', 
                        sprintf(__('Cannot deactivate: "%s" depends on this module', 'ferp-modular'), $mod['name'])
                    );
                }
            }
        }
        
        $module = self::$modules[$module_slug];
        
        // Run deactivation hook
        $deactivation_file = $module['folder'] . '/deactivate.php';
        if (file_exists($deactivation_file)) {
            require_once $deactivation_file;
            
            $func = $module_slug . '_deactivate';
            if (function_exists($func)) {
                call_user_func($func);
            }
        }
        
        // Remove from active modules
        self::$active_modules = array_diff(self::$active_modules, [$module_slug]);
        update_option('ferp_modular_active_modules', self::$active_modules);
        
        do_action('ferp_modular_module_deactivated', $module_slug);
        
        return true;
    }
    
    /**
     * Render modules page
     */
    public function render_modules_page() {
        ?>
        <div class="wrap ferp-modular-wrap">
            <style>
                <?php include FERP_MODULAR_PATH . 'assets/admin.css'; ?>
            </style>
            
            <?php include FERP_MODULAR_PATH . 'views/modules-page.php'; ?>
            
            <script>
                <?php include FERP_MODULAR_PATH . 'assets/admin.js'; ?>
            </script>
        </div>
        <?php
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'freelance-erp_page_ferp-modules') {
            return;
        }
        
        // Remove admin notices
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        
        // Check if files exist before enqueueing
        $css_file = FERP_MODULAR_PATH . 'assets/admin.css';
        $js_file = FERP_MODULAR_PATH . 'assets/admin.js';
        
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'ferp-modular-admin',
                FERP_MODULAR_URL . 'assets/admin.css',
                [],
                filemtime($css_file)
            );
        }
        
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'ferp-modular-admin',
                FERP_MODULAR_URL . 'assets/admin.js',
                ['jquery'],
                filemtime($js_file),
                true
            );
            
            wp_localize_script('ferp-modular-admin', 'ferpModular', [
                'ajax' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ferp_modular_nonce'),
                'i18n' => [
                    'confirm_delete' => __('Are you sure you want to delete this module? This cannot be undone.', 'ferp-modular'),
                    'uploading' => __('Uploading...', 'ferp-modular'),
                    'installing' => __('Installing...', 'ferp-modular'),
                ]
            ]);
        }
    }
    
    /**
     * AJAX: Toggle module
     */
    public function ajax_toggle_module() {
        check_ajax_referer('ferp_modular_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
            return;
        }
        
        $module = sanitize_text_field($_POST['module']);
        $action = sanitize_text_field($_POST['toggle_action']);
        
        // Add error logging
        error_log('FERP Module Toggle: ' . $module . ' - ' . $action);
        
        if ($action === 'activate') {
            $result = $this->activate_module($module);
        } else {
            $result = $this->deactivate_module($module);
        }
        
        if (is_wp_error($result)) {
            error_log('FERP Module Error: ' . $result->get_error_message());
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }
        
        wp_send_json_success([
            'message' => $action === 'activate' 
                ? __('Module activated successfully', 'ferp-modular')
                : __('Module deactivated successfully', 'ferp-modular')
        ]);
    }
    
    /**
     * AJAX: Install module
     */
    public function ajax_install_module() {
        check_ajax_referer('ferp_modular_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
        }
        
        if (empty($_FILES['module_zip'])) {
            wp_send_json_error(['message' => __('No file uploaded', 'ferp-modular')]);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        WP_Filesystem();
        
        $file = $_FILES['module_zip'];
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            wp_send_json_error(['message' => $upload['error']]);
        }
        
        // Extract zip
        $zip = new ZipArchive;
        if ($zip->open($upload['file']) === true) {
            $zip->extractTo(self::$modules_dir);
            $zip->close();
            unlink($upload['file']);
            
            wp_send_json_success(['message' => __('Module installed successfully', 'ferp-modular')]);
        } else {
            wp_send_json_error(['message' => __('Failed to extract module', 'ferp-modular')]);
        }
    }
    
    /**
     * AJAX: Delete module
     */
    public function ajax_delete_module() {
        check_ajax_referer('ferp_modular_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
        }
        
        $module = sanitize_text_field($_POST['module']);
        
        if (!isset(self::$modules[$module])) {
            wp_send_json_error(['message' => __('Module not found', 'ferp-modular')]);
        }
        
        // Deactivate first if active
        if (in_array($module, self::$active_modules)) {
            $this->deactivate_module($module);
        }
        
        // Delete folder
        $folder = self::$modules[$module]['folder'];
        $this->delete_directory($folder);
        
        wp_send_json_success(['message' => __('Module deleted successfully', 'ferp-modular')]);
    }
    
    /**
     * Recursively delete directory
     */
    private function delete_directory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!$this->delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Add action links
     */
    public function add_action_links($links) {
        $custom_links = [
            '<a href="' . admin_url('admin.php?page=ferp-modules') . '">' . __('Modules', 'ferp-modular') . '</a>',
        ];
        return array_merge($custom_links, $links);
    }
    
    /**
     * Get all modules
     */
    public static function get_modules() {
        return self::$modules;
    }
    
    /**
     * Get active modules
     */
    public static function get_active_modules() {
        return self::$active_modules;
    }
    
    /**
     * Check if module is active
     */
    public static function is_module_active($module_slug) {
        return in_array($module_slug, self::$active_modules);
    }
}

// Initialize the plugin
function ferp_modular_init() {
    return FERP_Modular_Manager::get_instance();
}
add_action('plugins_loaded', 'ferp_modular_init', 1);

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create modules directory
    $modules_dir = plugin_dir_path(__FILE__) . 'modules/';
    if (!file_exists($modules_dir)) {
        wp_mkdir_p($modules_dir);
    }
    
    // Create assets directory
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    
    // Create views directory
    $views_dir = plugin_dir_path(__FILE__) . 'views/';
    if (!file_exists($views_dir)) {
        wp_mkdir_p($views_dir);
    }
    
    // Create readme file
    $readme = $modules_dir . 'README.txt';
    if (!file_exists($readme)) {
        file_put_contents($readme, "Drop your module folders here!\n\nEach module should be in its own folder with a main PHP file.\nExample: modules/my-module/my-module.php");
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up if needed
});