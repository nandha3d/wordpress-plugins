<?php
/**
 * Module Name: Project Estimates Manager
 * Description: Complete estimates management with templates, detailed breakdowns, and project conversion
 * Version: 1.0.0
 * Author: FreelanceERP
 * Icon: chart-line
 * Module Class: FERP_Estimates_Module
 */

if (!defined('ABSPATH')) exit;

class FERP_Estimates_Module {
    
    public static function init() {
        // Add menu
        add_action('admin_menu', [__CLASS__, 'add_menu'], 20);
        
        // Register AJAX handlers
        add_action('wp_ajax_ferp_get_estimates', [__CLASS__, 'ajax_get_estimates']);
        add_action('wp_ajax_ferp_get_estimate', [__CLASS__, 'ajax_get_estimate']);
        add_action('wp_ajax_ferp_save_estimate', [__CLASS__, 'ajax_save_estimate']);
        add_action('wp_ajax_ferp_delete_estimate', [__CLASS__, 'ajax_delete_estimate']);
        add_action('wp_ajax_ferp_get_next_estimate_number', [__CLASS__, 'ajax_get_next_estimate_number']);
        add_action('wp_ajax_ferp_convert_estimate_to_project', [__CLASS__, 'ajax_convert_estimate_to_project']);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function add_menu() {
        add_submenu_page(
            'ferp-dashboard',
            __('Estimates', 'freelance-erp-manager'),
            __('📊 Estimates', 'freelance-erp-manager'),
            'manage_options',
            'ferp-estimates',
            [__CLASS__, 'render_page']
        );
    }
    
    public static function render_page() {
        $estimates_view = FERP_MODULAR_PATH . 'modules/estimates/views/estimates.php';
        if (file_exists($estimates_view)) {
            include $estimates_view;
        } else {
            echo '<div class="wrap"><h1>Estimates Module</h1><p>View file not found.</p></div>';
        }
    }
    
    public static function enqueue_assets($hook) {
        if ($hook !== 'freelance-erp_page_ferp-estimates') {
            return;
        }
        
        wp_enqueue_style(
            'ferp-estimates',
            FERP_MODULAR_URL . 'modules/estimates/assets/estimates.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'ferp-estimates',
            FERP_MODULAR_URL . 'modules/estimates/assets/estimates.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }
    
    // AJAX Handlers
    public static function ajax_get_estimates() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        global $wpdb;
        $estimates = $wpdb->get_results("
            SELECT e.*, c.name as clientname,
            (SELECT SUM(hours * quantity) FROM {$wpdb->prefix}ferp_estimate_items WHERE estimate_id = e.id) as total_hours
            FROM {$wpdb->prefix}ferp_estimates e
            LEFT JOIN {$wpdb->prefix}ferp_clients c ON e.client_id = c.id
            ORDER BY e.created_at DESC
        ");
        
        wp_send_json_success($estimates);
    }
    
    public static function ajax_get_estimate() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        global $wpdb;
        $estimate_id = intval($_POST['id']);
        
        $estimate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ferp_estimates WHERE id = %d",
            $estimate_id
        ));
        
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ferp_estimate_items WHERE estimate_id = %d ORDER BY id",
            $estimate_id
        ));
        
        wp_send_json_success([
            'estimate' => $estimate,
            'items' => $items
        ]);
    }
    
    public static function ajax_save_estimate() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $estimate = $_POST['estimate'];
        
        $data = [
            'client_id' => !empty($estimate['clientid']) ? intval($estimate['clientid']) : null,
            'estimate_number' => sanitize_text_field($estimate['estimatenumber']),
            'project_name' => sanitize_text_field($estimate['projectname']),
            'description' => sanitize_textarea_field($estimate['description']),
            'valid_until' => !empty($estimate['validuntil']) ? sanitize_text_field($estimate['validuntil']) : null,
            'subtotal' => floatval($estimate['subtotal']),
            'tax' => floatval($estimate['tax']),
            'total' => floatval($estimate['total']),
            'notes' => sanitize_textarea_field($estimate['notes']),
            'status' => !empty($estimate['status']) ? sanitize_text_field($estimate['status']) : 'draft'
        ];
        
        if (!empty($estimate['id'])) {
            $estimate_id = intval($estimate['id']);
            $wpdb->update($wpdb->prefix . 'ferp_estimates', $data, ['id' => $estimate_id]);
            $wpdb->delete($wpdb->prefix . 'ferp_estimate_items', ['estimate_id' => $estimate_id]);
            $message = __('Estimate updated successfully!', 'freelance-erp-manager');
        } else {
            $wpdb->insert($wpdb->prefix . 'ferp_estimates', $data);
            $estimate_id = $wpdb->insert_id;
            $current = intval(get_option('ferp_next_estimate_number', 1001));
            update_option('ferp_next_estimate_number', $current + 1);
            $message = __('Estimate created successfully!', 'freelance-erp-manager');
        }
        
        foreach ($estimate['items'] as $item) {
            $wpdb->insert($wpdb->prefix . 'ferp_estimate_items', [
                'estimate_id' => $estimate_id,
                'category' => sanitize_text_field($item['category']),
                'description' => sanitize_text_field($item['description']),
                'quantity' => floatval($item['quantity']),
                'hours' => floatval($item['hours']),
                'rate' => floatval($item['rate']),
                'amount' => floatval($item['amount'])
            ]);
        }
        
        wp_send_json_success(['message' => $message, 'id' => $estimate_id]);
    }
    
    public static function ajax_delete_estimate() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        global $wpdb;
        $estimate_id = intval($_POST['id']);
        
        $wpdb->delete($wpdb->prefix . 'ferp_estimate_items', ['estimate_id' => $estimate_id]);
        $wpdb->delete($wpdb->prefix . 'ferp_estimates', ['id' => $estimate_id]);
        
        wp_send_json_success(['message' => __('Estimate deleted successfully', 'freelance-erp-manager')]);
    }
    
    public static function ajax_get_next_estimate_number() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        $prefix = get_option('ferp_estimate_prefix', 'EST-');
        $next_num = get_option('ferp_next_estimate_number', 1001);
        
        wp_send_json_success($prefix . $next_num);
    }
    
    public static function ajax_convert_estimate_to_project() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        global $wpdb;
        $estimate_id = intval($_POST['id']);
        
        $estimate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ferp_estimates WHERE id = %d",
            $estimate_id
        ));
        
        if (!$estimate) {
            wp_send_json_error(['message' => __('Estimate not found', 'freelance-erp-manager')]);
        }
        
        $project_data = [
            'client_id' => $estimate->client_id,
            'name' => $estimate->project_name,
            'description' => $estimate->description,
            'status' => 'active',
            'budget' => $estimate->total,
            'start_date' => date('Y-m-d')
        ];
        
        $wpdb->insert($wpdb->prefix . 'ferp_projects', $project_data);
        $project_id = $wpdb->insert_id;
        
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ferp_estimate_items WHERE estimate_id = %d",
            $estimate_id
        ));
        
        $position = 0;
        foreach ($items as $item) {
            $wpdb->insert($wpdb->prefix . 'ferp_tasks', [
                'project_id' => $project_id,
                'title' => $item->description,
                'description' => $item->category,
                'estimated_hours' => floatval($item->hours) * floatval($item->quantity),
                'status' => 'todo',
                'priority' => 'medium',
                'position' => $position++
            ]);
        }
        
        $wpdb->update(
            $wpdb->prefix . 'ferp_estimates',
            ['status' => 'converted'],
            ['id' => $estimate_id]
        );
        
        wp_send_json_success([
            'message' => __('Estimate converted to project successfully!', 'freelance-erp-manager'),
            'project_id' => $project_id
        ]);
    }
}