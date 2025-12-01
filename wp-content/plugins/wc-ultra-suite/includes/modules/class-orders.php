<?php
/**
 * Orders Module
 * Handles order management and kanban view
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ultra_Suite_Orders {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_wc_ultra_suite_get_orders', [$this, 'ajax_get_orders']);
        add_action('wp_ajax_wc_ultra_suite_update_order_status', [$this, 'ajax_update_order_status']);
    }
    
    /**
     * Get orders
     */
    public function get_orders($status = 'any', $limit = 50) {
        $args = [
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        
        if ($status !== 'any') {
            $args['status'] = $status;
        }
        
        $orders = wc_get_orders($args);
        $order_data = [];
        
        foreach ($orders as $order) {
            $order_data[] = [
                'id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'customer_email' => $order->get_billing_email(),
                'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'items_count' => $order->get_item_count(),
            ];
        }
        
        return $order_data;
    }
    
    /**
     * Get orders grouped by status (for Kanban)
     */
    public function get_orders_by_status() {
        $statuses = wc_get_order_statuses();
        $grouped_orders = [];
        
        foreach ($statuses as $status_key => $status_label) {
            $status = str_replace('wc-', '', $status_key);
            $grouped_orders[$status] = [
                'label' => $status_label,
                'orders' => $this->get_orders($status, 20),
            ];
        }
        
        return $grouped_orders;
    }
    
    /**
     * AJAX: Get orders
     */
    public function ajax_get_orders() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-ultra-suite')]);
        }
        
        $view = isset($_POST['view']) ? sanitize_text_field($_POST['view']) : 'list';
        
        if ($view === 'kanban') {
            $data = $this->get_orders_by_status();
        } else {
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'any';
            $data = $this->get_orders($status);
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Update order status
     */
    public function ajax_update_order_status() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-ultra-suite')]);
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(['message' => __('Order not found', 'wc-ultra-suite')]);
        }
        
        $order->update_status($new_status);
        
        wp_send_json_success(['message' => __('Order status updated', 'wc-ultra-suite')]);
    }
}
