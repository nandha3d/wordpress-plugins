<?php
/**
 * Module Name: Invoice Manager
 * Description: Professional invoice generation with PDF export and payment tracking
 * Version: 1.0.3
 * Author: FreelanceERP
 * Requires: clients
 * Icon: media-spreadsheet
 * Module Class: FERP_Invoices_Module
 */

if (!defined('ABSPATH')) exit;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class FERP_Invoices_Module {
    
    private static $instance = null;
    private $table_invoices;
    private $table_invoice_items;
    
    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_invoices = $wpdb->prefix . 'ferp_invoices';
        $this->table_invoice_items = $wpdb->prefix . 'ferp_invoice_items';
        
        // Admin menu
        add_action('admin_menu', [$this, 'add_menu'], 15);
        
        // AJAX handlers
        add_action('wp_ajax_ferp_get_invoices', [$this, 'ajax_get_invoices']);
        add_action('wp_ajax_ferp_get_invoice', [$this, 'ajax_get_invoice']);
        add_action('wp_ajax_ferp_save_invoice', [$this, 'ajax_save_invoice']);
        add_action('wp_ajax_ferp_delete_invoice', [$this, 'ajax_delete_invoice']);
        add_action('wp_ajax_ferp_get_next_invoice_number', [$this, 'ajax_get_next_invoice_number']);
        add_action('wp_ajax_ferp_get_clients', [$this, 'ajax_get_clients']);
        add_action('wp_ajax_ferp_get_projects', [$this, 'ajax_get_projects']);
        add_action('wp_ajax_ferp_download_invoice_pdf', [$this, 'ajax_download_pdf']);
        add_action('wp_ajax_ferp_send_invoice_email', [$this, 'ajax_send_email']);
        add_action('wp_ajax_ferp_get_invoice_stats', [$this, 'ajax_get_stats']);
        
        // Public invoice view with clean URLs
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_public_invoice_view']);
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Add rewrite rules for clean invoice URLs
     * Format: yourdomain.com/invoice/INV-1001/TOKEN
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^invoice/([^/]+)/([^/]+)/?$',
            'index.php?ferp_invoice_slug=$matches[1]&ferp_invoice_token=$matches[2]',
            'top'
        );
        
        // Check if rules need flushing
        $rules = get_option('rewrite_rules');
        if (!isset($rules['^invoice/([^/]+)/([^/]+)/?$'])) {
            flush_rewrite_rules();
        }
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'ferp_invoice_slug';
        $vars[] = 'ferp_invoice_token';
        return $vars;
    }
    
    /**
     * Handle public invoice view
     */
    public function handle_public_invoice_view() {
        $invoice_slug = get_query_var('ferp_invoice_slug');
        $token = get_query_var('ferp_invoice_token');
        
        if (!$invoice_slug || !$token) {
            return; // Not an invoice request
        }
        
        global $wpdb;
        
        // Get invoice by invoice_number and verify token
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_invoices} WHERE invoice_number = %s",
            sanitize_text_field($invoice_slug)
        ));
        
        if (!$invoice) {
            wp_die(__('Invoice not found.', 'ferp-modular'), 'Not Found', ['response' => 404]);
        }
        
        // Verify token (timing-safe comparison)
        if (empty($invoice->access_token) || !hash_equals($invoice->access_token, $token)) {
            wp_die(__('Invalid access token. This invoice link may be expired or incorrect.', 'ferp-modular'), 
                   'Access Denied', 
                   ['response' => 403]);
        }
        
        // Token is valid, display the invoice
        $this->display_public_invoice($invoice->id);
        exit;
    }
    
    /**
     * Display the public invoice page
     */
private function display_public_invoice($invoice_id) {
    global $wpdb;
    
    // Check if projects table exists
    $projects_table = $wpdb->prefix . 'ferp_projects';
    $has_projects = $wpdb->get_var("SHOW TABLES LIKE '{$projects_table}'");
    
    // Load invoice with or without project data - INCLUDE access_token
    if ($has_projects) {
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, c.name as client_name, c.email, c.company, 
                    c.address, c.phone, c.gst_number as client_gst,
                    p.name as project_name
             FROM {$this->table_invoices} i
             LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
             LEFT JOIN {$projects_table} p ON i.project_id = p.id
             WHERE i.id = %d",
            $invoice_id
        ));
    } else {
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, c.name as client_name, c.email, c.company, 
                    c.address, c.phone, c.gst_number as client_gst
             FROM {$this->table_invoices} i
             LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
             WHERE i.id = %d",
            $invoice_id
        ));
        if ($invoice) {
            $invoice->project_name = null;
        }
    }
    
    if (!$invoice) {
        wp_die(__('Invoice not found', 'ferp-modular'), 'Not Found', ['response' => 404]);
    }
    
    // CRITICAL: Verify access_token is present
    if (empty($invoice->access_token)) {
        error_log('Invoice ' . $invoice->id . ' has no access_token!');
        wp_die(__('This invoice link is invalid. Please request a new link.', 'ferp-modular'), 
               'Invalid Link', 
               ['response' => 403]);
    }
    
    // Load invoice items
    $items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$this->table_invoice_items} 
         WHERE invoice_id = %d 
         ORDER BY sort_order",
        $invoice_id
    ));
    
    // Get all company settings
    $company_name = get_option('ferp_company_name', get_bloginfo('name'));
    $company_logo_url = get_option('ferp_company_logo_url', '');
    $company_email = get_option('ferp_company_email', get_option('admin_email'));
    $company_phone = get_option('ferp_company_phone', '');
    $company_address = get_option('ferp_company_address', '');
    $company_website = get_option('ferp_company_website', '');
    $gst_number = get_option('ferp_gst_number', '');
    $currency = get_option('ferp_currency_symbol', '₹');
    $payment_terms = get_option('ferp_payment_terms', 'Payment is due within 30 days');
    
    // Payment details
    $payment_qrcode_url = get_option('ferp_payment_qrcode_url', '');
    $bank_account_name = get_option('ferp_bank_account_name', '');
    $bank_account_number = get_option('ferp_bank_account_number', '');
    $bank_name = get_option('ferp_bank_name', '');
    $ifsc_code = get_option('ferp_ifsc_code', '');
    
    // Get client GST if available
    $client_gst = isset($invoice->client_gst) && !empty($invoice->client_gst) ? $invoice->client_gst : '';
    
    // Render the public template
    include __DIR__ . '/views/invoice-public.php';
}
    
    /**
     * Get public invoice URL with secure token
     * Format: yourdomain.com/invoice/INV-1001/TOKEN
     */
    public function get_invoice_url($invoice_number_or_id, $token = null) {
        global $wpdb;
        
        // If invoice_number_or_id is numeric, it's an ID - get the invoice number
        if (is_numeric($invoice_number_or_id)) {
            $invoice = $wpdb->get_row($wpdb->prepare(
                "SELECT invoice_number, access_token FROM {$this->table_invoices} WHERE id = %d",
                $invoice_number_or_id
            ));
            
            if (!$invoice) {
                return '';
            }
            
            $invoice_number = $invoice->invoice_number;
            $token = $invoice->access_token;
            
            // Generate token if it doesn't exist
            if (!$token) {
                $token = wp_generate_password(32, false);
                $wpdb->update(
                    $this->table_invoices,
                    ['access_token' => $token],
                    ['id' => $invoice_number_or_id]
                );
            }
        } else {
            // It's an invoice number
            $invoice_number = $invoice_number_or_id;
            
            // If token not provided, get it from database
            if (!$token) {
                $token = $wpdb->get_var($wpdb->prepare(
                    "SELECT access_token FROM {$this->table_invoices} WHERE invoice_number = %s",
                    $invoice_number
                ));
                
                if (!$token) {
                    // Generate and save token
                    $token = wp_generate_password(32, false);
                    $wpdb->update(
                        $this->table_invoices,
                        ['access_token' => $token],
                        ['invoice_number' => $invoice_number]
                    );
                }
            }
        }
        
        // Return clean URL
        return home_url('invoice/' . urlencode($invoice_number) . '/' . $token);
    }
    
    public function add_menu() {
        add_submenu_page(
            'ferp-dashboard',
            __('Invoices', 'ferp-modular'),
            __('📄 Invoices', 'ferp-modular'),
            'manage_options',
            'ferp-invoices',
            [$this, 'render_page']
        );
    }
    
    public function render_page() {
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            $this->render_single_invoice($_GET['id']);
            return;
        }
        
        $this->render_list_view();
    }
    
    /**
     * Renders the main invoice list view template.
     */
    private function render_list_view() {
        $view_file = __DIR__ . '/views/invoices-view.php';
        if (file_exists($view_file)) {
            include $view_file;
        }
    }
    
    private function render_single_invoice($invoice_id) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'ferp_projects';
        $has_projects = $wpdb->get_var("SHOW TABLES LIKE '{$projects_table}'");
        
        if ($has_projects) {
            $invoice = $wpdb->get_row($wpdb->prepare(
                "SELECT i.*, c.name as client_name, c.email as client_email, 
                        c.company, c.address, c.phone, c.gst_number as client_gst, 
                        p.name as project_name
                 FROM {$this->table_invoices} i
                 LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
                 LEFT JOIN {$projects_table} p ON i.project_id = p.id
                 WHERE i.id = %d",
                $invoice_id
            ));
        } else {
            $invoice = $wpdb->get_row($wpdb->prepare(
                "SELECT i.*, c.name as client_name, c.email as client_email, 
                        c.company, c.address, c.phone, c.gst_number as client_gst
                 FROM {$this->table_invoices} i
                 LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
                 WHERE i.id = %d",
                $invoice_id
            ));
            if ($invoice) {
                $invoice->project_name = null;
            }
        }
        
        if (!$invoice) {
            echo '<div class="wrap">';
            echo '<h1 class="wp-heading-inline">' . __('Invoice Not Found', 'ferp-modular') . '</h1>';
            echo '<div class="notice notice-error is-dismissible"><p><strong>' . __('Error:', 'ferp-modular') . '</strong> ' . __('The requested invoice could not be found or the ID is invalid. Please check your data and try again.', 'ferp-modular') . '</p></div>';
            $this->render_list_view();
            echo '</div>'; 
            return;
        }
        
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_invoice_items} 
             WHERE invoice_id = %d 
             ORDER BY sort_order",
            $invoice_id
        ));
        
        $single_view = __DIR__ . '/views/invoice-single.php';
        if (file_exists($single_view)) {
            include $single_view;
        }
    }
    
public function enqueue_scripts($hook) {
    if ($hook !== 'freelance-erp_page_ferp-invoices') {
        return;
    }
    
    wp_enqueue_style('ferp-invoices-css', plugin_dir_url(__FILE__) . 'assets/invoices.css', [], '1.0.0');
    wp_enqueue_script('ferp-invoices-js', plugin_dir_url(__FILE__) . 'assets/invoices.js', ['jquery'], '1.0.0', true);
    
    wp_localize_script('ferp-invoices-js', 'FERP', [
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ferp_nonce'),
        'home_url' => home_url(),
        'currency_symbol' => get_option('ferp_currency_symbol', '$'),
        'i18n' => [
            'confirm_delete' => __('Are you sure you want to delete this invoice?', 'ferp-modular'),
            'saving' => __('Saving...', 'ferp-modular'),
            'loading' => __('Loading...', 'ferp-modular'),
        ]
    ]);
}
    // AJAX: Get all invoices
    public function ajax_get_invoices() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
            return;
        }
        
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_invoices}'");
        if (!$table_exists) {
            wp_send_json_success([]);
            return;
        }
        
        $projects_table = $wpdb->prefix . 'ferp_projects';
        $has_projects = $wpdb->get_var("SHOW TABLES LIKE '{$projects_table}'");
        
        if ($has_projects) {
            $invoices = $wpdb->get_results(
                "SELECT i.*, c.name as client_name, p.name as project_name
                 FROM {$this->table_invoices} i
                 LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
                 LEFT JOIN {$projects_table} p ON i.project_id = p.id
                 ORDER BY i.created_at DESC"
            );
        } else {
            $invoices = $wpdb->get_results(
                "SELECT i.*, c.name as client_name
                 FROM {$this->table_invoices} i
                 LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
                 ORDER BY i.created_at DESC"
            );
            
            if ($invoices) {
                foreach ($invoices as $invoice) {
                    $invoice->project_name = null;
                }
            }
        }
        
        wp_send_json_success($invoices ?: []);
    }
    
    // AJAX: Get clients
    public function ajax_get_clients() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
            return;
        }
        
        global $wpdb;
        
        $clients_table = $wpdb->prefix . 'ferp_clients';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$clients_table}'");
        
        if (!$table_exists) {
            wp_send_json_error(['message' => __('Clients module is not active or tables not created', 'ferp-modular')]);
            return;
        }
        
        $clients = $wpdb->get_results("SELECT * FROM {$clients_table} ORDER BY name");
        
        wp_send_json_success($clients ?: []);
    }
    
    // AJAX: Get projects
    public function ajax_get_projects() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
            return;
        }
        
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'ferp_projects';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$projects_table}'");
        
        if (!$table_exists) {
            wp_send_json_success([]);
            return;
        }
        
        $projects = $wpdb->get_results(
            "SELECT p.*, c.name as client_name
             FROM {$projects_table} p
             LEFT JOIN {$wpdb->prefix}ferp_clients c ON p.client_id = c.id
             ORDER BY p.name"
        );
        
        wp_send_json_success($projects ?: []);
    }
    
    // AJAX: Get single invoice
    public function ajax_get_invoice() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
            return;
        }
        
        $invoice_id = intval($_POST['invoice_id']);
        
        global $wpdb;
        
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, c.name as client_name
             FROM {$this->table_invoices} i
             LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
             WHERE i.id = %d",
            $invoice_id
        ));
        
        if (!$invoice) {
            wp_send_json_error(['message' => __('Invoice not found', 'ferp-modular')]);
            return;
        }
        
        // Ensure access_token exists - generate if missing
        if (empty($invoice->access_token)) {
            $token = wp_generate_password(32, false);
            $wpdb->update(
                $this->table_invoices,
                ['access_token' => $token],
                ['id' => $invoice_id]
            );
            $invoice->access_token = $token;
        }
        
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_invoice_items} 
             WHERE invoice_id = %d 
             ORDER BY sort_order",
            $invoice_id
        ));
        
        $invoice->items = $items;
        
        wp_send_json_success($invoice);
    }
    
    // AJAX: Save invoice
    public function ajax_save_invoice() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
            return;
        }
        
        $invoice_data = $_POST['invoice'];
        
        // Validate required fields
        if (empty($invoice_data['client_id']) || empty($invoice_data['invoice_number'])) {
            wp_send_json_error(['message' => __('Client and invoice number are required', 'ferp-modular')]);
            return;
        }
        
        if (empty($invoice_data['items']) || !is_array($invoice_data['items'])) {
            wp_send_json_error(['message' => __('At least one invoice item is required', 'ferp-modular')]);
            return;
        }
        
        global $wpdb;
        
        $invoice_id = !empty($invoice_data['id']) ? intval($invoice_data['id']) : 0;
        
        $data = [
            'client_id' => intval($invoice_data['client_id']),
            'project_id' => !empty($invoice_data['project_id']) ? intval($invoice_data['project_id']) : null,
            'invoice_number' => sanitize_text_field($invoice_data['invoice_number']),
            'issue_date' => sanitize_text_field($invoice_data['issue_date']),
            'due_date' => sanitize_text_field($invoice_data['due_date']),
            'status' => sanitize_text_field($invoice_data['status']),
            'subtotal' => floatval($invoice_data['subtotal']),
            'tax' => floatval($invoice_data['tax']),
            'total' => floatval($invoice_data['total']),
            'notes' => wp_kses_post($invoice_data['notes']),
        ];
        
        if ($invoice_id > 0) {
            // Update existing invoice
            $wpdb->update($this->table_invoices, $data, ['id' => $invoice_id]);
            
            // Delete existing items
            $wpdb->delete($this->table_invoice_items, ['invoice_id' => $invoice_id]);
            
            $message = __('Invoice updated successfully', 'ferp-modular');
        } else {
            // Generate token for new invoice
            $data['access_token'] = wp_generate_password(32, false);
            
            // Create new invoice
            $wpdb->insert($this->table_invoices, $data);
            $invoice_id = $wpdb->insert_id;
            
            $message = __('Invoice created successfully', 'ferp-modular');
        }
        
        // Ensure token exists (for old invoices or if update didn't have it)
        $existing_token = $wpdb->get_var($wpdb->prepare(
            "SELECT access_token FROM {$this->table_invoices} WHERE id = %d",
            $invoice_id
        ));
        
        if (empty($existing_token)) {
            $wpdb->update(
                $this->table_invoices,
                ['access_token' => wp_generate_password(32, false)],
                ['id' => $invoice_id]
            );
        }
        
        // Insert items
        $sort_order = 0;
        foreach ($invoice_data['items'] as $item) {
            $wpdb->insert($this->table_invoice_items, [
                'invoice_id' => $invoice_id,
                'description' => sanitize_text_field($item['description']),
                'quantity' => floatval($item['quantity']),
                'rate' => floatval($item['rate']),
                'amount' => floatval($item['amount']),
                'sort_order' => $sort_order++,
            ]);
        }
        
        wp_send_json_success([
            'message' => $message,
            'invoice_id' => $invoice_id
        ]);
    }
    
    // AJAX: Delete invoice
    public function ajax_delete_invoice() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
            return;
        }
        
        $invoice_id = intval($_POST['id']);
        
        global $wpdb;
        
        // Delete items first
        $wpdb->delete($this->table_invoice_items, ['invoice_id' => $invoice_id]);
        
        // Delete invoice
        $result = $wpdb->delete($this->table_invoices, ['id' => $invoice_id]);
        
        if ($result) {
            wp_send_json_success(['message' => __('Invoice deleted successfully', 'ferp-modular')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete invoice', 'ferp-modular')]);
        }
    }
    
    // AJAX: Get next invoice number
    public function ajax_get_next_invoice_number() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
            return;
        }
        
        global $wpdb;
        
        $prefix = get_option('ferp_invoice_prefix', 'INV-');
        $start_number = get_option('ferp_invoice_start_number', 1001);
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_invoices}'");
        
        if ($table_exists) {
            $last_invoice = $wpdb->get_var(
                "SELECT invoice_number FROM {$this->table_invoices} 
                 WHERE invoice_number LIKE '{$prefix}%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($last_invoice) {
                $number = intval(str_replace($prefix, '', $last_invoice));
                $next_number = $number + 1;
            } else {
                $next_number = $start_number;
            }
        } else {
            $next_number = $start_number;
        }
        
        wp_send_json_success($prefix . $next_number);
    }
    
    // AJAX: Get stats
    public function ajax_get_stats() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
            return;
        }
        
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_invoices}'");
        
        if (!$table_exists) {
            wp_send_json_success([
                'total_invoices' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'pending_amount' => 0,
                'overdue_count' => 0,
            ]);
            return;
        }
        
        $stats = [
            'total_invoices' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_invoices}") ?: 0,
            'total_amount' => $wpdb->get_var("SELECT SUM(total) FROM {$this->table_invoices}") ?: 0,
            'paid_amount' => $wpdb->get_var("SELECT SUM(total) FROM {$this->table_invoices} WHERE status = 'paid'") ?: 0,
            'pending_amount' => $wpdb->get_var("SELECT SUM(total) FROM {$this->table_invoices} WHERE status IN ('draft', 'sent')") ?: 0,
            'overdue_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_invoices} WHERE status = 'overdue'") ?: 0,
        ];
        
        wp_send_json_success($stats);
    }
    
// AJAX: Send email
    public function ajax_send_email() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'ferp-modular')]);
            return;
        }
        
        $invoice_id = intval($_POST['invoice_id']);
        
        $result = $this->send_invoice_email($invoice_id);
        
        if ($result) {
            global $wpdb;
            $wpdb->update(
                $this->table_invoices,
                ['status' => 'sent'],
                ['id' => $invoice_id]
            );
            
            wp_send_json_success(['message' => __('Invoice sent successfully', 'ferp-modular')]);
        } else {
            wp_send_json_error(['message' => __('Failed to send invoice', 'ferp-modular')]);
        }
    }
    
    // AJAX: Download PDF
    public function ajax_download_pdf() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'ferp-modular'));
            return;
        }
        
        $invoice_id = intval($_GET['invoice_id']);
        
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'ferp_projects';
        $has_projects = $wpdb->get_var("SHOW TABLES LIKE '{$projects_table}'");
        
        if ($has_projects) {
            $invoice = $wpdb->get_row($wpdb->prepare(
                "SELECT i.*, c.name as client_name, c.email, c.company, 
                        c.address, c.phone, c.gst_number as client_gst,
                        p.name as project_name
                 FROM {$this->table_invoices} i
                 LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
                 LEFT JOIN {$projects_table} p ON i.project_id = p.id
                 WHERE i.id = %d",
                $invoice_id
            ));
        } else {
            $invoice = $wpdb->get_row($wpdb->prepare(
                "SELECT i.*, c.name as client_name, c.email, c.company, 
                        c.address, c.phone, c.gst_number as client_gst
                 FROM {$this->table_invoices} i
                 LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
                 WHERE i.id = %d",
                $invoice_id
            ));
            if ($invoice) {
                $invoice->project_name = null;
            }
        }
        
        if (!$invoice) {
            wp_die(__('Invoice not found', 'ferp-modular'));
        }
        
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_invoice_items} 
             WHERE invoice_id = %d 
             ORDER BY sort_order",
            $invoice_id
        ));
        
        $settings = [
            'company_name' => get_option('ferp_company_name', get_bloginfo('name')),
            'company_logo_url' => get_option('ferp_company_logo_url', ''),
            'company_email' => get_option('ferp_company_email', get_option('admin_email')),
            'company_phone' => get_option('ferp_company_phone', ''),
            'company_address' => get_option('ferp_company_address', ''),
            'company_website' => get_option('ferp_company_website', ''),
            'gst_number' => get_option('ferp_gst_number', ''),
            'currency' => get_option('ferp_currency_symbol', '₹'),
            'payment_terms' => get_option('ferp_payment_terms', 'Payment is due within 30 days'),
            'payment_qrcode_url' => get_option('ferp_payment_qrcode_url', ''),
            'bank_account_name' => get_option('ferp_bank_account_name', ''),
            'bank_account_number' => get_option('ferp_bank_account_number', ''),
            'bank_name' => get_option('ferp_bank_name', ''),
            'ifsc_code' => get_option('ferp_ifsc_code', ''),
        ];
        
        $html = $this->get_pdf_html($invoice, $items, $settings);
        
        if (class_exists('Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf([
                'enable_remote' => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $filename = 'Invoice-' . $invoice->invoice_number . '.pdf';
            $dompdf->stream($filename, ['Attachment' => 1]);
            exit;
        } else {
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename="Invoice-' . $invoice->invoice_number . '.html"');
            echo $html;
            exit;
        }
    }
    
    // Send invoice email
    private function send_invoice_email($invoice_id) {
        global $wpdb;
        
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, c.name as client_name, c.email as client_email
             FROM {$this->table_invoices} i
             LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
             WHERE i.id = %d",
            $invoice_id
        ));
        
        if (!$invoice || !$invoice->client_email) {
            return false;
        }
        
        $subject = sprintf(
            __('Invoice %s from %s', 'ferp-modular'),
            $invoice->invoice_number,
            get_option('ferp_company_name', get_bloginfo('name'))
        );
        
        $message = $this->get_email_template($invoice);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($invoice->client_email, $subject, $message, $headers);
    }
    
    // Get email template
    private function get_email_template($invoice) {
        $company_name = get_option('ferp_company_name', get_bloginfo('name'));
        $company_logo_url = get_option('ferp_company_logo_url', '');
        $company_email = get_option('ferp_company_email', get_option('admin_email'));
        $company_phone = get_option('ferp_company_phone', '');
        $company_address = get_option('ferp_company_address', '');
        $company_website = get_option('ferp_company_website', '');
        $gst_number = get_option('ferp_gst_number', '');
        $currency = get_option('ferp_currency_symbol', '₹');
        $payment_terms = get_option('ferp_payment_terms', 'Payment is due within 30 days');
        
        $payment_qrcode_url = get_option('ferp_payment_qrcode_url', '');
        $bank_account_name = get_option('ferp_bank_account_name', '');
        $bank_account_number = get_option('ferp_bank_account_number', '');
        $bank_name = get_option('ferp_bank_name', '');
        $ifsc_code = get_option('ferp_ifsc_code', '');
        
        $invoice_url = $this->get_invoice_url($invoice->invoice_number, $invoice->access_token);
        
        ob_start();
        ?>
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
                <?php if ($company_logo_url): ?>
                    <img src="<?php echo esc_url($company_logo_url); ?>" alt="<?php echo esc_attr($company_name); ?>" style="max-width: 180px; max-height: 60px; margin-bottom: 15px;">
                <?php endif; ?>
                <h1 style="margin: 0 0 10px 0; font-size: 28px;"><?php echo esc_html($company_name); ?></h1>
                <p style="margin: 0; opacity: 0.9; font-size: 16px;">Invoice Notification</p>
            </div>
            
            <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px;">
                <h2 style="color: #1f2937; margin-top: 0;">Dear <?php echo esc_html($invoice->client_name); ?>,</h2>
                <p style="color: #4b5563; line-height: 1.6;">
                    Thank you for your business! Please find your invoice details below:
                </p>
                
                <table style="width: 100%; border-collapse: collapse; margin: 25px 0; background: #f8f9fa; border-radius: 8px; overflow: hidden;">
                    <tr style="background: #f0f0f0;">
                        <td style="padding: 15px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #4b5563;">Invoice Number:</td>
                        <td style="padding: 15px; border-bottom: 1px solid #e5e7eb; text-align: right; color: #1f2937; font-weight: 600;">
                            <?php echo esc_html($invoice->invoice_number); ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 15px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #4b5563;">Issue Date:</td>
                        <td style="padding: 15px; border-bottom: 1px solid #e5e7eb; text-align: right; color: #1f2937;">
                            <?php echo date('M d, Y', strtotime($invoice->issue_date)); ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 15px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #4b5563;">Due Date:</td>
                        <td style="padding: 15px; border-bottom: 1px solid #e5e7eb; text-align: right; color: #1f2937;">
                            <?php echo date('M d, Y', strtotime($invoice->due_date)); ?>
                        </td>
                    </tr>
                    <tr style="background: #667eea; color: white;">
                        <td style="padding: 20px; font-weight: 600; font-size: 16px;">Total Amount:</td>
                        <td style="padding: 20px; text-align: right; font-weight: bold; font-size: 20px;">
                            <?php echo $currency . number_format($invoice->total, 2); ?>
                        </td>
                    </tr>
                </table>
                
                <?php if ($payment_qrcode_url || $bank_account_number): ?>
                <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 25px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #1e40af; font-size: 16px;">💳 Payment Information</h3>
                    
                    <?php if ($payment_qrcode_url): ?>
                    <div style="text-align: center; margin-bottom: 15px;">
                        <img src="<?php echo esc_url($payment_qrcode_url); ?>" alt="Payment QR Code" style="max-width: 150px; border: 2px solid #ddd; border-radius: 8px; padding: 10px; background: white;">
                        <p style="margin: 10px 0 0 0; color: #1e40af; font-weight: 600;">Scan to pay with UPI</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($bank_account_number): ?>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <?php if ($bank_account_name): ?>
                        <tr>
                            <td style="padding: 8px 0; color: #1e40af; font-weight: 600;">Account Name:</td>
                            <td style="padding: 8px 0; text-align: right; color: #1f2937;"><?php echo esc_html($bank_account_name); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td style="padding: 8px 0; color: #1e40af; font-weight: 600;">Account Number:</td>
                            <td style="padding: 8px 0; text-align: right; color: #1f2937;"><?php echo esc_html($bank_account_number); ?></td>
                        </tr>
                        <?php if ($bank_name): ?>
                        <tr>
                            <td style="padding: 8px 0; color: #1e40af; font-weight: 600;">Bank Name:</td>
                            <td style="padding: 8px 0; text-align: right; color: #1f2937;"><?php echo esc_html($bank_name); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($ifsc_code): ?>
                        <tr>
                            <td style="padding: 8px 0; color: #1e40af; font-weight: 600;">IFSC Code:</td>
                            <td style="padding: 8px 0; text-align: right; color: #1f2937;"><?php echo esc_html($ifsc_code); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($payment_terms): ?>
                <div style="background: #fffbeb; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 25px 0;">
                    <p style="margin: 0; color: #78350f; line-height: 1.6;">
                        <strong style="color: #92400e;">Payment Terms:</strong><br>
                        <?php echo nl2br(esc_html($payment_terms)); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <p style="text-align: center; margin: 30px 0;">
                    <a href="<?php echo esc_url($invoice_url); ?>" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                        View Invoice Online
                    </a>
                </p>
                
                <p style="color: #4b5563; line-height: 1.6; margin-top: 30px;">
                    If you have any questions about this invoice, please contact us.
                </p>
                
                <p style="color: #4b5563; line-height: 1.6;">
                    Thank you for your business!
                </p>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; text-align: center; color: #6b7280; font-size: 13px; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0 0 5px 0;"><strong style="color: #1f2937;"><?php echo esc_html($company_name); ?></strong></p>
                <?php if ($company_address): ?>
                    <p style="margin: 5px 0;"><?php echo nl2br(esc_html($company_address)); ?></p>
                <?php endif; ?>
                <p style="margin: 5px 0;">
                    <?php echo esc_html($company_email); ?>
                    <?php if ($company_phone): ?>
                        | <?php echo esc_html($company_phone); ?>
                    <?php endif; ?>
                </p>
                <?php if ($company_website): ?>
                    <p style="margin: 5px 0;"><?php echo esc_html($company_website); ?></p>
                <?php endif; ?>
                <?php if ($gst_number): ?>
                    <p style="margin: 5px 0;">GST: <?php echo esc_html($gst_number); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Get PDF HTML
    private function get_pdf_html($invoice, $items, $settings) {
        extract($settings);
        
        $status_colors = [
            'paid' => ['bg' => '#d1fae5', 'text' => '#065f46'],
            'sent' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
            'draft' => ['bg' => '#f3f4f6', 'text' => '#6b7280'],
            'overdue' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        ];
        
        $status_style = $status_colors[$invoice->status] ?? $status_colors['draft'];
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #333; line-height: 1.5; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; }
        .header-flex { display: table; width: 100%; }
        .header-left { display: table-cell; width: 50%; vertical-align: top; }
        .header-right { display: table-cell; width: 50%; text-align: right; vertical-align: top; }
        .invoice-number { font-size: 36pt; font-weight: bold; margin-bottom: 10px; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-size: 9pt; font-weight: bold; text-transform: uppercase; background: <?php echo $status_style['bg']; ?>; color: <?php echo $status_style['text']; ?>; }
        .logo { max-width: 150px; max-height: 60px; margin-bottom: 10px; }
        .company-name { font-size: 18pt; font-weight: bold; margin-bottom: 5px; }
        .company-info { font-size: 9pt; line-height: 1.6; opacity: 0.95; }
        .body { background: white; padding: 30px; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; }
        .info-grid { display: table; width: 100%; margin-bottom: 30px; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; }
        .info-col-left { padding-right: 15px; }
        .info-col-right { padding-left: 15px; }
        .section-title { font-size: 11pt; font-weight: bold; color: #667eea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 2px solid #667eea; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 5px; }
        .info-row { margin-bottom: 8px; }
        .info-label { font-weight: bold; color: #4b5563; display: inline-block; width: 45%; }
        .info-value { display: inline-block; width: 54%; text-align: right; color: #1f2937; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table thead { background: #667eea; color: white; }
        .items-table th { padding: 12px 10px; text-align: left; font-weight: bold; }
        .items-table td { padding: 12px 10px; border-bottom: 1px solid #e5e7eb; }
        .items-table tbody tr:last-child td { border-bottom: 2px solid #667eea; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { float: right; width: 50%; margin-top: 20px; }
        .summary-box { background: #f8f9fa; border-radius: 8px; overflow: hidden; }
        .summary-row { padding: 12px 15px; border-bottom: 1px solid #e5e7eb; }
        .summary-row:after { content: ""; display: table; clear: both; }
        .summary-label { float: left; font-weight: bold; color: #4b5563; }
        .summary-value { float: right; font-weight: bold; color: #1f2937; }
        .summary-total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; font-size: 14pt; }
        .summary-total .summary-label, .summary-total .summary-value { color: white; }
        .payment-section { clear: both; background: #f0f9ff; padding: 20px; border-radius: 8px; border: 2px solid #3b82f6; margin-top: 30px; }
        .payment-title { font-size: 12pt; font-weight: bold; color: #1e40af; margin-bottom: 15px; }
        .payment-grid { display: table; width: 100%; }
        .payment-col-left { display: table-cell; width: 35%; text-align: center; vertical-align: top; padding-right: 15px; }
        .payment-col-right { display: table-cell; width: 65%; vertical-align: top; }
        .qr-code { width: 150px; height: 150px; border: 2px solid #ddd; border-radius: 8px; padding: 10px; background: white; }
        .qr-label { margin-top: 8px; font-weight: bold; color: #1e40af; font-size: 9pt; }
        .bank-table { width: 100%; border-collapse: collapse; }
        .bank-table td { padding: 8px 0; border-bottom: 1px solid #bfdbfe; }
        .bank-table td:first-child { font-weight: bold; color: #1e40af; width: 40%; }
        .bank-table tr:last-child td { border-bottom: none; }
        .notes { clear: both; background: #fffbeb; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b; margin-top: 20px; }
        .notes-title { font-weight: bold; color: #92400e; margin-bottom: 8px; }
        .notes-content { color: #78350f; line-height: 1.6; }
        .footer { clear: both; background: white; padding: 20px 30px; border: 1px solid #e5e7eb; border-top: 2px solid #667eea; border-radius: 0 0 10px 10px; margin-top: 20px; text-align: center; color: #6b7280; font-size: 9pt; }
        .footer-company { font-weight: bold; color: #1f2937; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-flex">
                <div class="header-left">
                    <div class="invoice-number"><?php echo esc_html($invoice->invoice_number); ?></div>
                    <span class="status-badge"><?php echo strtoupper($invoice->status); ?></span>
                </div>
                <div class="header-right">
                    <?php if ($company_logo_url): ?>
                        <img src="<?php echo esc_url($company_logo_url); ?>" class="logo" alt="Logo">
                    <?php endif; ?>
                    <div class="company-name"><?php echo esc_html($company_name); ?></div>
                    <div class="company-info">
                        <?php if ($company_email): ?>&#128231; <?php echo esc_html($company_email); ?><br><?php endif; ?>
                        <?php if ($company_phone): ?>&#128241; <?php echo esc_html($company_phone); ?><br><?php endif; ?>
                        <?php if ($company_website): ?>&#127760; <?php echo esc_html(str_replace(['http://', 'https://'], '', $company_website)); ?><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="body">
            <div class="info-grid">
                <div class="info-col info-col-left">
                    <div class="section-title">BILL TO</div>
                    <div class="info-box">
                        <strong style="font-size: 11pt;"><?php echo esc_html($invoice->client_name); ?></strong><br>
                        <?php if (!empty($invoice->company)): ?><?php echo esc_html($invoice->company); ?><br><?php endif; ?>
                        <?php if (!empty($invoice->address)): ?><?php echo nl2br(esc_html($invoice->address)); ?><br><?php endif; ?>
                        <?php if (!empty($invoice->phone)): ?>&#128241; <?php echo esc_html($invoice->phone); ?><br><?php endif; ?>
                        <?php if (!empty($invoice->client_gst)): ?>GST: <?php echo esc_html($invoice->client_gst); ?><?php endif; ?>
                    </div>
                </div>
                
                <div class="info-col info-col-right">
                    <div class="section-title">INVOICE DETAILS</div>
                    <div class="info-box">
                        <div class="info-row">
                            <span class="info-label">Invoice Number:</span>
                            <span class="info-value"><strong><?php echo esc_html($invoice->invoice_number); ?></strong></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Issue Date:</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($invoice->issue_date)); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Due Date:</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($invoice->due_date)); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value"><span class="status-badge"><?php echo strtoupper($invoice->status); ?></span></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 15%;" class="text-center">Quantity</th>
                        <th style="width: 17%;" class="text-right">Rate</th>
                        <th style="width: 18%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo nl2br(esc_html($item->description)); ?></td>
                        <td class="text-center"><?php echo number_format($item->quantity, 2); ?></td>
                        <td class="text-right"><?php echo $currency . number_format($item->rate, 2); ?></td>
                        <td class="text-right"><strong><?php echo $currency . number_format($item->amount, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="summary">
                <div class="summary-box">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal:</span>
                        <span class="summary-value"><?php echo $currency . number_format($invoice->subtotal, 2); ?></span>
                    </div>
                    <?php if ($invoice->tax > 0): ?>
                    <div class="summary-row">
                        <span class="summary-label">Tax:</span>
                        <span class="summary-value"><?php echo $currency . number_format($invoice->tax, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-total">
                        <span class="summary-label">TOTAL:</span>
                        <span class="summary-value"><?php echo $currency . number_format($invoice->total, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($payment_qrcode_url || $bank_account_number): ?>
            <div class="payment-section">
                <div class="payment-title">&#128179; Bank Details</div>
                <div class="payment-grid">
                    <?php if ($payment_qrcode_url): ?>
                    <div class="payment-col-left">
                        <div style="font-weight: bold; color: #1e40af; margin-bottom: 8px;">Scan to Pay</div>
                        <img src="<?php echo esc_url($payment_qrcode_url); ?>" class="qr-code" alt="QR">
                        <div class="qr-label">Scan to pay with any UPI app</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($bank_account_number): ?>
                    <div class="payment-col-right">
                        <table class="bank-table">
                            <?php if ($bank_account_name): ?>
                            <tr>
                                <td>Account Name:</td>
                                <td><?php echo esc_html($bank_account_name); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td>Account Number:</td>
                                <td><?php echo esc_html($bank_account_number); ?></td>
                            </tr>
                            <?php if ($bank_name): ?>
                            <tr>
                                <td>Bank Name:</td>
                                <td><?php echo esc_html($bank_name); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($ifsc_code): ?>
                            <tr>
                                <td>IFSC Code:</td>
                                <td><?php echo esc_html($ifsc_code); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($invoice->notes) || $payment_terms): ?>
            <div class="notes">
                <div class="notes-title"><?php echo !empty($invoice->notes) ? 'Notes / Terms' : 'Payment Terms'; ?></div>
                <div class="notes-content">
                    <?php echo nl2br(esc_html(!empty($invoice->notes) ? $invoice->notes : $payment_terms)); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <div class="footer-company"><?php echo esc_html($company_name); ?></div>
            <?php if ($company_address): ?><?php echo nl2br(esc_html($company_address)); ?><br><?php endif; ?>
            <?php echo esc_html($company_email); ?>
            <?php if ($company_phone): ?> | <?php echo esc_html($company_phone); ?><?php endif; ?>
            <?php if ($company_website): ?><br><?php echo esc_html($company_website); ?><?php endif; ?>
            <?php if ($gst_number): ?><br>GST: <?php echo esc_html($gst_number); ?><?php endif; ?>
            <div style="margin-top: 10px; font-size: 8pt;">Thank you for your business!</div>
        </div>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}

// Initialize module
FERP_Invoices_Module::init();