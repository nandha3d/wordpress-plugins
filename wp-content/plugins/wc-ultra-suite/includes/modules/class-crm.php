<?php
/**
 * CRM Module
 * Handles customer relationship management
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ultra_Suite_CRM {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_wc_ultra_suite_get_customers', [$this, 'ajax_get_customers']);
        add_action('wp_ajax_wc_ultra_suite_get_customer_details', [$this, 'ajax_get_customer_details']);
    }
    
    /**
     * Get customers with stats
     */
    public function get_customers($limit = 50) {
        // Get all orders to find customers
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending'],
        ]);
        
        $customer_stats = [];
        
        // Aggregate data by customer
        foreach ($orders as $order) {
            $customer_id = $order->get_customer_id();
            
            // Skip if no customer ID (guest without ID)
            if (!$customer_id) {
                $email = $order->get_billing_email();
                $customer_id = 'guest_' . md5($email);
                $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                $customer_email = $email;
            } else {
                $customer = get_user_by('id', $customer_id);
                if ($customer) {
                    $customer_name = $customer->display_name;
                    $customer_email = $customer->user_email;
                } else {
                    continue;
                }
            }
            
            if (!isset($customer_stats[$customer_id])) {
                $customer_stats[$customer_id] = [
                    'id' => $customer_id,
                    'name' => $customer_name,
                    'email' => $customer_email,
                    'orders' => 0,
                    'total_spent' => 0,
                ];
            }
            
            $customer_stats[$customer_id]['orders']++;
            $customer_stats[$customer_id]['total_spent'] += $order->get_total();
        }
        
        // Calculate LTV and AOV
        $customer_data = [];
        foreach ($customer_stats as $customer) {
            $customer['ltv'] = $customer['total_spent'];
            $customer['aov'] = $customer['orders'] > 0 ? $customer['total_spent'] / $customer['orders'] : 0;
            $customer_data[] = $customer;
        }
        
        // Sort by LTV
        usort($customer_data, function($a, $b) {
            return $b['ltv'] <=> $a['ltv'];
        });
        
        // Limit results
        return array_slice($customer_data, 0, $limit);
    }
    
    /**
     * Get customer details
     */
    public function get_customer_details($customer_id) {
        $customer = get_user_by('id', $customer_id);
        
        if (!$customer) {
            return null;
        }
        
        $orders = wc_get_orders([
            'customer_id' => $customer_id,
            'limit' => -1,
        ]);
        
        $order_data = [];
        $total_spent = 0;
        
        foreach ($orders as $order) {
            $total_spent += $order->get_total();
            $order_data[] = [
                'id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'date' => $order->get_date_created()->date('Y-m-d H:i:s'),
            ];
        }
        
        return [
            'id' => $customer->ID,
            'name' => $customer->display_name,
            'email' => $customer->user_email,
            'registered' => $customer->user_registered,
            'orders' => $order_data,
            'total_spent' => $total_spent,
            'order_count' => count($orders),
        ];
    }
    
    /**
     * AJAX: Get customers
     */
    public function ajax_get_customers() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-ultra-suite')]);
        }
        
        $data = $this->get_customers();
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Get customer details
     */
    public function ajax_get_customer_details() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-ultra-suite')]);
        }
        
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        
        $data = $this->get_customer_details($customer_id);
        
        if (!$data) {
            wp_send_json_error(['message' => __('Customer not found', 'wc-ultra-suite')]);
        }
        
        wp_send_json_success($data);
    }
}
