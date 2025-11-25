<?php
/**
 * Module Name: Project Manager
 * Description: Project management with tasks, progress tracking, and invoice generation
 * Version: 1.0.0
 * Author: FreelanceERP
 * Icon: portfolio
 * Module Class: FERP_Projects_Module
 * Requires: clients
 */

if (!defined('ABSPATH')) exit;

class FERP_Projects_Module {
    
    public static function init() {
        // Add menu
        add_action('admin_menu', [__CLASS__, 'add_menu'], 20);
        
        // Register AJAX handlers
        add_action('wp_ajax_ferp_get_projects', [__CLASS__, 'ajax_get_projects']);
        add_action('wp_ajax_ferp_save_project', [__CLASS__, 'ajax_save_project']);
        add_action('wp_ajax_ferp_delete_project', [__CLASS__, 'ajax_delete_project']);
    }
    
    public static function add_menu() {
        add_submenu_page(
            'ferp-dashboard',
            __('Projects', 'freelance-erp-manager'),
            __('📊 Projects', 'freelance-erp-manager'),
            'manage_options',
            'ferp-projects',
            [__CLASS__, 'render_page']
        );
    }
    
    public static function render_page() {
        $view = FERP_MODULAR_PATH . 'modules/projects/views/projects.php';
        if (file_exists($view)) {
            include $view;
        }
    }
    
    // AJAX Handlers
    public static function ajax_get_projects() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $projects = $wpdb->get_results("
            SELECT p.*, c.name as client_name
            FROM {$wpdb->prefix}ferp_projects p
            LEFT JOIN {$wpdb->prefix}ferp_clients c ON p.client_id = c.id
            ORDER BY p.created_at DESC
        ");
        wp_send_json_success($projects);
    }
    
    public static function ajax_save_project() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $project = $_POST['project'];
        
        $data = [
            'client_id' => intval($project['client_id']),
            'name' => sanitize_text_field($project['name']),
            'description' => sanitize_textarea_field($project['description']),
            'status' => sanitize_text_field($project['status']),
            'budget' => !empty($project['budget']) ? floatval($project['budget']) : null,
            'start_date' => !empty($project['start_date']) ? sanitize_text_field($project['start_date']) : null,
            'end_date' => !empty($project['end_date']) ? sanitize_text_field($project['end_date']) : null
        ];
        
        if (!empty($project['id'])) {
            $wpdb->update(
                $wpdb->prefix . 'ferp_projects',
                $data,
                ['id' => intval($project['id'])]
            );
            $id = $project['id'];
            $message = __('Project updated', 'freelance-erp-manager');
        } else {
            $wpdb->insert($wpdb->prefix . 'ferp_projects', $data);
            $id = $wpdb->insert_id;
            $message = __('Project saved', 'freelance-erp-manager');
        }
        
        wp_send_json_success(['id' => $id, 'message' => $message]);
    }
    
    public static function ajax_delete_project() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $id = intval($_POST['id']);
        $wpdb->delete($wpdb->prefix . 'ferp_projects', ['id' => $id]);
        wp_send_json_success(['message' => __('Project deleted', 'freelance-erp-manager')]);
    }
}