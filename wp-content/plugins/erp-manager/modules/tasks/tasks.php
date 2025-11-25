<?php
/**
 * Module Name: Task Manager
 * Description: Task tracking and management with Kanban board
 * Version: 1.0.0
 * Author: FreelanceERP
 * Icon: list-view
 * Module Class: FERP_Tasks_Module
 * Requires: projects
 */

if (!defined('ABSPATH')) exit;

class FERP_Tasks_Module {
    
    public static function init() {
        // Add menu
        add_action('admin_menu', [__CLASS__, 'add_menu'], 20);
        
        // Register AJAX handlers
        add_action('wp_ajax_ferp_get_tasks', [__CLASS__, 'ajax_get_tasks']);
        add_action('wp_ajax_ferp_save_task', [__CLASS__, 'ajax_save_task']);
        add_action('wp_ajax_ferp_delete_task', [__CLASS__, 'ajax_delete_task']);
        add_action('wp_ajax_ferp_update_task_status', [__CLASS__, 'ajax_update_task_status']);
    }
    
    public static function add_menu() {
        add_submenu_page(
            'ferp-dashboard',
            __('Tasks', 'freelance-erp-manager'),
            __('✅ Tasks', 'freelance-erp-manager'),
            'manage_options',
            'ferp-tasks',
            [__CLASS__, 'render_page']
        );
    }
    
    public static function render_page() {
        $view = FERP_MODULAR_PATH . 'modules/tasks/views/tasks.php';
        if (file_exists($view)) {
            include $view;
        }
    }
    
    // AJAX Handlers
    public static function ajax_get_tasks() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        global $wpdb;
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $where = $project_id ? "WHERE project_id = $project_id" : "";
        $tasks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ferp_tasks $where ORDER BY position");
        wp_send_json_success($tasks);
    }
    
    public static function ajax_save_task() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $task = $_POST['task'];
        
        $data = [
            'project_id' => intval($task['project_id']),
            'title' => sanitize_text_field($task['title']),
            'description' => sanitize_textarea_field($task['description']),
            'status' => sanitize_text_field($task['status']),
            'priority' => sanitize_text_field($task['priority']),
            'due_date' => !empty($task['due_date']) ? sanitize_text_field($task['due_date']) : null,
            'assigned_to' => !empty($task['assigned_to']) ? intval($task['assigned_to']) : null,
            'estimated_hours' => !empty($task['estimated_hours']) ? floatval($task['estimated_hours']) : null,
            'position' => isset($task['position']) ? intval($task['position']) : 0
        ];
        
        if (!empty($task['id'])) {
            $wpdb->update(
                $wpdb->prefix . 'ferp_tasks',
                $data,
                ['id' => intval($task['id'])]
            );
            $id = $task['id'];
            $message = __('Task updated', 'freelance-erp-manager');
        } else {
            $wpdb->insert($wpdb->prefix . 'ferp_tasks', $data);
            $id = $wpdb->insert_id;
            $message = __('Task saved', 'freelance-erp-manager');
        }
        
        wp_send_json_success(['id' => $id, 'message' => $message]);
    }
    
    public static function ajax_delete_task() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $id = intval($_POST['id']);
        $wpdb->delete($wpdb->prefix . 'ferp_tasks', ['id' => $id]);
        wp_send_json_success(['message' => __('Task deleted', 'freelance-erp-manager')]);
    }
    
    public static function ajax_update_task_status() {
        check_ajax_referer('ferp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'freelance-erp-manager')]);
        }
        
        global $wpdb;
        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);
        $position = intval($_POST['position']);
        
        $wpdb->update(
            $wpdb->prefix . 'ferp_tasks',
            ['status' => $status, 'position' => $position],
            ['id' => $id]
        );
        
        wp_send_json_success(['message' => __('Task updated', 'freelance-erp-manager')]);
    }
}