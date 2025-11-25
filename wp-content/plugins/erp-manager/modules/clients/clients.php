<?php
/**
 * Module Name: Client Manager
 * Description: Complete client management with country codes and contact information
 * Version: 1.0.0
 * Author: FreelanceERP
 * Icon: groups
 * Module Class: FERP_Clients_Module
 */

if (!defined('ABSPATH')) exit;

class FERP_Clients_Module {
    
    public static function init() {
        // Add menu
        add_action('admin_menu', [__CLASS__, 'add_menu'], 20);
        
        // Register AJAX handlers
        add_action('wp_ajax_ferp_get_clients', [__CLASS__, 'ajax_get_clients']);
        add_action('wp_ajax_ferp_save_client', [__CLASS__, 'ajax_save_client']);
        add_action('wp_ajax_ferp_delete_client', [__CLASS__, 'ajax_delete_client']);
        add_action('wp_ajax_ferp_get_client_stats', [__CLASS__, 'ajax_get_client_stats']);
        add_action('wp_ajax_ferp_import_clients', [__CLASS__, 'ajax_import_clients']);
        add_action('wp_ajax_ferp_export_clients', [__CLASS__, 'ajax_export_clients']);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function add_menu() {
        add_submenu_page(
            'ferp-dashboard',
            __('Clients', 'freelance-erp-manager'),
            __('👥 Clients', 'freelance-erp-manager'),
            'manage_options',
            'ferp-clients',
            [__CLASS__, 'render_page']
        );
    }
    
    public static function enqueue_assets($hook) {
        if ($hook !== 'freelance-erp_page_ferp-clients') {
            return;
        }
        
        // Enqueue DataTables for advanced table features
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css');
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', ['jquery'], null, true);
        
        wp_localize_script('jquery', 'FERP', [
            'ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ferp_nonce')
        ]);
    }
    
    public static function render_page() {
        $view = FERP_MODULAR_PATH . 'modules/clients/views/clients.php';
        if (file_exists($view)) {
            include $view;
        } else {
            echo '<div class="wrap"><h1>Clients Module</h1><p>View file not found at: ' . $view . '</p></div>';
        }
    }
    
    // AJAX Handlers
public static function ajax_get_clients() {
    check_ajax_referer('ferp_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
    }
    
    global $wpdb;
    
    // Check if related tables exist
    $projects_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ferp_projects'") ? true : false;
    $invoices_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ferp_invoices'") ? true : false;
    
    // Build query based on existing tables
    if ($projects_table_exists && $invoices_table_exists) {
        // Full query with all JOINs
        $clients = $wpdb->get_results("
            SELECT 
                c.*,
                COUNT(DISTINCT p.id) as total_projects,
                COUNT(DISTINCT i.id) as total_invoices,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN i.status IN ('sent', 'overdue') THEN i.total ELSE 0 END), 0) as pending_amount
            FROM {$wpdb->prefix}ferp_clients c
            LEFT JOIN {$wpdb->prefix}ferp_projects p ON c.id = p.client_id
            LEFT JOIN {$wpdb->prefix}ferp_invoices i ON c.id = i.client_id
            GROUP BY c.id
            ORDER BY c.name
        ");
    } else if ($projects_table_exists) {
        // Query with only projects
        $clients = $wpdb->get_results("
            SELECT 
                c.*,
                COUNT(DISTINCT p.id) as total_projects,
                0 as total_invoices,
                0 as total_revenue,
                0 as pending_amount
            FROM {$wpdb->prefix}ferp_clients c
            LEFT JOIN {$wpdb->prefix}ferp_projects p ON c.id = p.client_id
            GROUP BY c.id
            ORDER BY c.name
        ");
    } else if ($invoices_table_exists) {
        // Query with only invoices
        $clients = $wpdb->get_results("
            SELECT 
                c.*,
                0 as total_projects,
                COUNT(DISTINCT i.id) as total_invoices,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN i.status IN ('sent', 'overdue') THEN i.total ELSE 0 END), 0) as pending_amount
            FROM {$wpdb->prefix}ferp_clients c
            LEFT JOIN {$wpdb->prefix}ferp_invoices i ON c.id = i.client_id
            GROUP BY c.id
            ORDER BY c.name
        ");
    } else {
        // Simple query without any JOINs
        $clients = $wpdb->get_results("
            SELECT 
                c.*,
                0 as total_projects,
                0 as total_invoices,
                0 as total_revenue,
                0 as pending_amount
            FROM {$wpdb->prefix}ferp_clients c
            ORDER BY c.name
        ");
    }
    
    // Log for debugging
    error_log('FERP Clients Query Result: ' . print_r($clients, true));
    error_log('FERP Projects table exists: ' . ($projects_table_exists ? 'yes' : 'no'));
    error_log('FERP Invoices table exists: ' . ($invoices_table_exists ? 'yes' : 'no'));
    
    // Check for SQL errors
    if ($wpdb->last_error) {
        error_log('FERP SQL Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
        return;
    }
    
    wp_send_json_success($clients);
}
    
    public static function ajax_delete_client() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $id = intval($_POST['id']);
        
        // Check if client has projects or invoices
        $has_projects = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ferp_projects WHERE client_id = %d",
            $id
        ));
        
        $has_invoices = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ferp_invoices WHERE client_id = %d",
            $id
        ));
        
        if ($has_projects > 0 || $has_invoices > 0) {
            wp_send_json_error(['message' => __('Cannot delete client with existing projects or invoices', 'freelance-erp-manager')]);
            return;
        }
        
        $wpdb->delete($wpdb->prefix . 'ferp_clients', ['id' => $id]);
        wp_send_json_success(['message' => __('Client deleted successfully', 'freelance-erp-manager')]);
    }
    
    public static function ajax_get_client_stats() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $client_id = intval($_POST['client_id']);
        
        $stats = [
            'projects' => $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ferp_projects WHERE client_id = %d ORDER BY created_at DESC LIMIT 5",
                $client_id
            )),
            'invoices' => $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ferp_invoices WHERE client_id = %d ORDER BY created_at DESC LIMIT 5",
                $client_id
            )),
            'total_revenue' => $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total), 0) FROM {$wpdb->prefix}ferp_invoices WHERE client_id = %d AND status = 'paid'",
                $client_id
            )),
            'pending_amount' => $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total), 0) FROM {$wpdb->prefix}ferp_invoices WHERE client_id = %d AND status IN ('sent', 'overdue')",
                $client_id
            ))
        ];
        
        wp_send_json_success($stats);
    }
    
    public static function ajax_export_clients() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $clients = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ferp_clients ORDER BY name", ARRAY_A);
        
        // Generate CSV
        $csv_output = "Name,Email,Phone,Company,Address,Notes\n";
        foreach ($clients as $client) {
            $csv_output .= sprintf(
                '"%s","%s","%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $client['name']),
                str_replace('"', '""', $client['email']),
                str_replace('"', '""', $client['phone']),
                str_replace('"', '""', $client['company']),
                str_replace('"', '""', $client['address']),
                str_replace('"', '""', $client['notes'])
            );
        }
        
        wp_send_json_success(['csv' => $csv_output, 'filename' => 'clients-export-' . date('Y-m-d') . '.csv']);
    }
}