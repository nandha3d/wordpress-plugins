<?php
/**
 * Module Name: Reports & Analytics
 * Description: Revenue, client, and project reports with analytics
 * Version: 1.0.0
 * Author: FreelanceERP
 * Icon: chart-bar
 * Module Class: FERP_Reports_Module
 * Requires: invoices, projects
 */

if (!defined('ABSPATH')) exit;

class FERP_Reports_Module {
    
    public static function init() {
        // Add menu
        add_action('admin_menu', [__CLASS__, 'add_menu'], 20);
        
        // Register AJAX handlers
        add_action('wp_ajax_ferp_get_revenue_report', [__CLASS__, 'ajax_get_revenue_report']);
        add_action('wp_ajax_ferp_get_client_revenue', [__CLASS__, 'ajax_get_client_revenue']);
        add_action('wp_ajax_ferp_get_project_time_report', [__CLASS__, 'ajax_get_project_time_report']);
        add_action('wp_ajax_ferp_get_invoice_status_report', [__CLASS__, 'ajax_get_invoice_status_report']);
    }
    
    public static function add_menu() {
        add_submenu_page(
            'ferp-dashboard',
            __('Reports', 'freelance-erp-manager'),
            __('📈 Reports', 'freelance-erp-manager'),
            'manage_options',
            'ferp-reports',
            [__CLASS__, 'render_page']
        );
    }
    
    public static function render_page() {
        $view = FERP_MODULAR_PATH . 'modules/reports/views/reports.php';
        if (file_exists($view)) {
            include $view;
        }
    }
    
    // AJAX Handlers
    public static function ajax_get_revenue_report() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        $data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE_FORMAT(issue_date, '%%Y-%%m') as month,
                SUM(total) as revenue,
                COUNT(*) as invoice_count
            FROM {$wpdb->prefix}ferp_invoices
            WHERE issue_date BETWEEN %s AND %s
            AND status = 'paid'
            GROUP BY DATE_FORMAT(issue_date, '%%Y-%%m')
            ORDER BY month
        ", $start_date, $end_date));
        
        wp_send_json_success($data);
    }
    
    public static function ajax_get_client_revenue() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        $data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                c.name as client_name,
                c.id as client_id,
                SUM(i.total) as total_revenue,
                COUNT(i.id) as invoice_count
            FROM {$wpdb->prefix}ferp_invoices i
            LEFT JOIN {$wpdb->prefix}ferp_clients c ON i.client_id = c.id
            WHERE i.issue_date BETWEEN %s AND %s
            AND i.status = 'paid'
            GROUP BY c.id
            ORDER BY total_revenue DESC
        ", $start_date, $end_date));
        
        wp_send_json_success($data);
    }
    
    public static function ajax_get_project_time_report() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        $data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.name as project_name,
                p.id as project_id,
                c.name as client_name,
                SUM(t.hours) as total_hours,
                COUNT(DISTINCT t.user_id) as team_members
            FROM {$wpdb->prefix}ferp_time_entries t
            LEFT JOIN {$wpdb->prefix}ferp_projects p ON t.project_id = p.id
            LEFT JOIN {$wpdb->prefix}ferp_clients c ON p.client_id = c.id
            WHERE t.date BETWEEN %s AND %s
            GROUP BY p.id
            ORDER BY total_hours DESC
        ", $start_date, $end_date));
        
        wp_send_json_success($data);
    }
    
    public static function ajax_get_invoice_status_report() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        
        $data = $wpdb->get_results("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(total) as total_amount
            FROM {$wpdb->prefix}ferp_invoices
            GROUP BY status
        ");
        
        wp_send_json_success($data);
    }
}