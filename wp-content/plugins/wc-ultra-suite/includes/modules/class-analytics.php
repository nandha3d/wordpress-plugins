<?php
/**
 * Analytics Module
 * Handles profit calculations, sales tracking, and dashboard metrics
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ultra_Suite_Analytics {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_wc_ultra_suite_get_analytics', [$this, 'ajax_get_analytics']);
        add_action('wp_ajax_wc_ultra_suite_get_chart_data', [$this, 'ajax_get_chart_data']);
    }
    
    /**
     * Get analytics data
     */
    public function get_analytics_data($date_from = null, $date_to = null) {
        global $wpdb;
        
        // Default to last 30 days
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        // Get orders in date range
        $orders = wc_get_orders([
            'limit' => -1,
            'date_created' => $date_from . '...' . $date_to,
            'status' => ['wc-completed', 'wc-processing'],
        ]);
        
        $total_revenue = 0;
        $total_cost = 0;
        $total_orders = count($orders);
        $product_sales = [];
        
        foreach ($orders as $order) {
            $total_revenue += $order->get_total();
            
            // Calculate cost from order items
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $variation_id = $item->get_variation_id();
                $quantity = $item->get_quantity();
                
                // Check for variation cost price first, then product cost price
                $cost_price = 0;
                if ($variation_id) {
                    $cost_price = get_post_meta($variation_id, '_wc_ultra_suite_variation_cost_price', true);
                }
                if (!$cost_price) {
                    $cost_price = get_post_meta($product_id, '_wc_ultra_suite_cost_price', true);
                }
                
                if ($cost_price) {
                    $total_cost += floatval($cost_price) * $quantity;
                }
                
                // Track product sales (with variation breakdown)
                $tracking_id = $variation_id ? $variation_id : $product_id;
                $parent_id = $variation_id ? $product_id : 0;
                
                if (!isset($product_sales[$tracking_id])) {
                    $product = $item->get_product();
                    $product_name = $product ? $product->get_name() : $item->get_name();
                    
                    // Get variation attributes if it's a variation
                    $variation_name = '';
                    if ($variation_id && $product) {
                        $attributes = $product->get_attributes();
                        $variation_parts = [];
                        foreach ($attributes as $key => $value) {
                            $variation_parts[] = $value;
                        }
                        $variation_name = !empty($variation_parts) ? implode(', ', $variation_parts) : '';
                    }
                    
                    $product_sales[$tracking_id] = [
                        'id' => $tracking_id,
                        'parent_id' => $parent_id,
                        'name' => $product_name,
                        'variation_name' => $variation_name,
                        'is_variation' => (bool)$variation_id,
                        'quantity' => 0,
                        'revenue' => 0,
                        'cost' => 0,
                    ];
                }
                
                $product_sales[$tracking_id]['quantity'] += $quantity;
                $product_sales[$tracking_id]['revenue'] += $item->get_total();
                $product_sales[$tracking_id]['cost'] += floatval($cost_price) * $quantity;
            }
        }
        
        // Calculate profit
        $total_profit = $total_revenue - $total_cost;
        $profit_margin = $total_revenue > 0 ? ($total_profit / $total_revenue) * 100 : 0;
        
        // Calculate AOV (Average Order Value)
        $aov = $total_orders > 0 ? $total_revenue / $total_orders : 0;
        
        // Group variations under parent products
        $grouped_products = [];
        $variations_by_parent = [];
        
        foreach ($product_sales as $item) {
            if ($item['is_variation']) {
                $parent_id = $item['parent_id'];
                if (!isset($variations_by_parent[$parent_id])) {
                    $variations_by_parent[$parent_id] = [];
                }
                $variations_by_parent[$parent_id][] = $item;
            } else {
                $grouped_products[$item['id']] = $item;
                $grouped_products[$item['id']]['variations'] = [];
            }
        }
        
        // Attach variations to their parents and create parent if needed
        foreach ($variations_by_parent as $parent_id => $variations) {
            if (!isset($grouped_products[$parent_id])) {
                // Create parent product entry from first variation
                $parent_product = wc_get_product($parent_id);
                if ($parent_product) {
                    $grouped_products[$parent_id] = [
                        'id' => $parent_id,
                        'parent_id' => 0,
                        'name' => $parent_product->get_name(),
                        'variation_name' => '',
                        'is_variation' => false,
                        'quantity' => 0,
                        'revenue' => 0,
                        'cost' => 0,
                        'variations' => [],
                    ];
                }
            }
            
            if (isset($grouped_products[$parent_id])) {
                // Aggregate variation data into parent
                foreach ($variations as $variation) {
                    $grouped_products[$parent_id]['quantity'] += $variation['quantity'];
                    $grouped_products[$parent_id]['revenue'] += $variation['revenue'];
                    $grouped_products[$parent_id]['cost'] += $variation['cost'];
                }
                
                $grouped_products[$parent_id]['variations'] = $variations;
                $grouped_products[$parent_id]['has_variations'] = true;
            }
        }
        
        // Sort products by profit
        usort($grouped_products, function($a, $b) {
            $profit_a = $a['revenue'] - $a['cost'];
            $profit_b = $b['revenue'] - $b['cost'];
            return $profit_b <=> $profit_a;
        });
        
        // Add profit to each product and variation
        foreach ($grouped_products as &$product) {
            $product['profit'] = $product['revenue'] - $product['cost'];
            if (!empty($product['variations'])) {
                foreach ($product['variations'] as &$variation) {
                    $variation['profit'] = $variation['revenue'] - $variation['cost'];
                }
            }
        }
        
        return [
            'revenue' => $total_revenue,
            'cost' => $total_cost,
            'profit' => $total_profit,
            'profit_margin' => round($profit_margin, 2),
            'orders' => $total_orders,
            'aov' => $aov,
            'top_products' => array_slice(array_values($grouped_products), 0, 10),
            'date_from' => $date_from,
            'date_to' => $date_to,
        ];
    }
    
    /**
     * Get chart data for sales/profit over time
     */
    public function get_chart_data($days = 30) {
        $data = [];
        $end_date = new DateTime();
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = clone $end_date;
            $date->modify("-{$i} days");
            $date_str = $date->format('Y-m-d');
            
            $orders = wc_get_orders([
                'limit' => -1,
                'date_created' => $date_str,
                'status' => ['wc-completed', 'wc-processing'],
            ]);
            
            $daily_revenue = 0;
            $daily_cost = 0;
            
            foreach ($orders as $order) {
                $daily_revenue += $order->get_total();
                
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    $variation_id = $item->get_variation_id();
                    $quantity = $item->get_quantity();
                    
                    // Check for variation cost price first, then product cost price
                    $cost_price = 0;
                    if ($variation_id) {
                        $cost_price = get_post_meta($variation_id, '_wc_ultra_suite_variation_cost_price', true);
                    }
                    if (!$cost_price) {
                        $cost_price = get_post_meta($product_id, '_wc_ultra_suite_cost_price', true);
                    }
                    
                    if ($cost_price) {
                        $daily_cost += floatval($cost_price) * $quantity;
                    }
                }
            }
            
            // Format date: show month name only on 1st of month
            $day = $date->format('j'); // Day without leading zeros
            $date_label = ($day == 1) ? $date->format('M j') : $day;
            
            $data[] = [
                'date' => $date_label,
                'revenue' => round($daily_revenue, 2),
                'cost' => round($daily_cost, 2),
                'profit' => round($daily_revenue - $daily_cost, 2),
            ];
        }
        
        return $data;
    }
    
    /**
     * AJAX: Get analytics
     */
    public function ajax_get_analytics() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-ultra-suite')]);
        }
        
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : null;
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : null;
        
        $data = $this->get_analytics_data($date_from, $date_to);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Get chart data
     */
    public function ajax_get_chart_data() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-ultra-suite')]);
        }
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        
        $data = $this->get_chart_data($days);
        
        wp_send_json_success($data);
    }
}
