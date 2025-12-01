<?php
/**
 * Products Module
 * Handles product management, cost tracking, and product addons
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ultra_Suite_Products {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add cost price field to simple products
        add_action('woocommerce_product_options_pricing', [$this, 'add_cost_price_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_cost_price_field']);
        
        // Add cost price field to product variations
        add_action('woocommerce_variation_options_pricing', [$this, 'add_variation_cost_price_field'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'save_variation_cost_price_field'], 10, 2);
        
        // Add product addons tab
        add_filter('woocommerce_product_data_tabs', [$this, 'add_addons_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'add_addons_panel']);
        add_action('woocommerce_process_product_meta', [$this, 'save_addons']);
        
        // Frontend addon display
        add_action('woocommerce_before_add_to_cart_button', [$this, 'display_addons']);
        
        // Cart functionality
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 2);
        add_action('woocommerce_before_calculate_totals', [$this, 'calculate_addon_prices']);
        add_filter('woocommerce_get_item_data', [$this, 'display_cart_item_data'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_order_item_data'], 10, 4);
        
        // AJAX handlers
        add_action('wp_ajax_wc_ultra_suite_get_products', [$this, 'ajax_get_products']);
        add_action('wp_ajax_wc_ultra_suite_update_product', [$this, 'ajax_update_product']);
    }
    
    /**
     * Add cost price field to product
     */
    public function add_cost_price_field() {
        woocommerce_wp_text_input([
            'id' => '_wc_ultra_suite_cost_price',
            'label' => __('Cost Price', 'wc-ultra-suite') . ' (' . get_woocommerce_currency_symbol() . ')',
            'description' => __('Enter the cost of goods for profit calculation', 'wc-ultra-suite'),
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => [
                'step' => '0.01',
                'min' => '0'
            ]
        ]);
    }
    
    /**
     * Save cost price field
     */
    public function save_cost_price_field($post_id) {
        $cost_price = isset($_POST['_wc_ultra_suite_cost_price']) ? sanitize_text_field($_POST['_wc_ultra_suite_cost_price']) : '';
        update_post_meta($post_id, '_wc_ultra_suite_cost_price', $cost_price);
    }
    
    /**
     * Add cost price field to variations
     */
    public function add_variation_cost_price_field($loop, $variation_data, $variation) {
        $cost_price = get_post_meta($variation->ID, '_wc_ultra_suite_variation_cost_price', true);
        ?>
        <p class="form-row form-row-full">
            <label><?php _e('Cost Price', 'wc-ultra-suite'); ?> (<?php echo get_woocommerce_currency_symbol(); ?>):</label>
            <input type="number" 
                   step="0.01" 
                   min="0" 
                   name="_wc_ultra_suite_variation_cost_price[<?php echo $loop; ?>]" 
                   value="<?php echo esc_attr($cost_price); ?>" 
                   placeholder="0.00"
                   style="width: 100%;" />
            <span class="description"><?php _e('Enter the cost of goods for this variation', 'wc-ultra-suite'); ?></span>
        </p>
        <?php
    }
    
    /**
     * Save variation cost price field
     */
    public function save_variation_cost_price_field($variation_id, $loop) {
        if (isset($_POST['_wc_ultra_suite_variation_cost_price'][$loop])) {
            $cost_price = sanitize_text_field($_POST['_wc_ultra_suite_variation_cost_price'][$loop]);
            update_post_meta($variation_id, '_wc_ultra_suite_variation_cost_price', $cost_price);
        }
    }
    
    /**
     * Add addons tab to product data
     */
    public function add_addons_tab($tabs) {
        $tabs['wc_ultra_suite_addons'] = [
            'label' => __('Product Addons', 'wc-ultra-suite'),
            'target' => 'wc_ultra_suite_addons_data',
            'class' => ['show_if_simple', 'show_if_variable'],
        ];
        return $tabs;
    }
    
    /**
     * Add addons panel content
     */
    public function add_addons_panel() {
        global $post;
        ?>
        <div id="wc_ultra_suite_addons_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <p class="form-field">
                    <label><?php _e('Product Addons', 'wc-ultra-suite'); ?></label>
                    <span class="description"><?php _e('Add custom fields with additional costs to this product', 'wc-ultra-suite'); ?></span>
                </p>
                
                <div id="wc-ultra-suite-addons-container">
                    <?php
                    $addons = get_post_meta($post->ID, '_wc_ultra_suite_addons', true);
                    if (!is_array($addons)) {
                        $addons = [];
                    }
                    
                    foreach ($addons as $index => $addon) {
                        $this->render_addon_row($index, $addon);
                    }
                    ?>
                </div>
                
                <p class="toolbar">
                    <button type="button" class="button button-primary" id="wc-ultra-suite-add-addon">
                        <?php _e('Add Addon', 'wc-ultra-suite'); ?>
                    </button>
                </p>
            </div>
        </div>
        
        <script type="text/template" id="wc-ultra-suite-addon-template">
            <?php $this->render_addon_row('{{INDEX}}', []); ?>
        </script>
        
        <script>
        jQuery(document).ready(function($) {
            let addonIndex = <?php echo count($addons); ?>;
            
            $('#wc-ultra-suite-add-addon').on('click', function() {
                const template = $('#wc-ultra-suite-addon-template').html();
                const html = template.replace(/{{INDEX}}/g, addonIndex);
                $('#wc-ultra-suite-addons-container').append(html);
                addonIndex++;
            });
            
            $(document).on('click', '.wc-ultra-suite-remove-addon', function() {
                $(this).closest('.wc-ultra-suite-addon-row').remove();
            });
        });
        </script>
        
        <style>
        .wc-ultra-suite-addon-row {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .wc-ultra-suite-addon-row .form-field {
            margin-bottom: 10px;
        }
        .wc-ultra-suite-addon-row label {
            display: inline-block;
            width: 150px;
            font-weight: 600;
        }
        .wc-ultra-suite-addon-row input,
        .wc-ultra-suite-addon-row select {
            width: 300px;
        }
        </style>
        <?php
    }
    
    /**
     * Render a single addon row
     */
    private function render_addon_row($index, $addon = []) {
        $label = isset($addon['label']) ? $addon['label'] : '';
        $type = isset($addon['type']) ? $addon['type'] : 'checkbox';
        $price = isset($addon['price']) ? $addon['price'] : '';
        $required = isset($addon['required']) ? $addon['required'] : false;
        $options = isset($addon['options']) ? $addon['options'] : '';
        ?>
        <div class="wc-ultra-suite-addon-row">
            <p class="form-field">
                <label><?php _e('Label', 'wc-ultra-suite'); ?></label>
                <input type="text" name="wc_ultra_suite_addons[<?php echo $index; ?>][label]" value="<?php echo esc_attr($label); ?>" placeholder="<?php _e('e.g., Gift Wrap', 'wc-ultra-suite'); ?>">
            </p>
            
            <p class="form-field">
                <label><?php _e('Type', 'wc-ultra-suite'); ?></label>
                <select name="wc_ultra_suite_addons[<?php echo $index; ?>][type]">
                    <option value="checkbox" <?php selected($type, 'checkbox'); ?>><?php _e('Checkbox', 'wc-ultra-suite'); ?></option>
                    <option value="text" <?php selected($type, 'text'); ?>><?php _e('Text Input', 'wc-ultra-suite'); ?></option>
                    <option value="textarea" <?php selected($type, 'textarea'); ?>><?php _e('Textarea', 'wc-ultra-suite'); ?></option>
                    <option value="select" <?php selected($type, 'select'); ?>><?php _e('Select Dropdown', 'wc-ultra-suite'); ?></option>
                    <option value="radio" <?php selected($type, 'radio'); ?>><?php _e('Radio Buttons', 'wc-ultra-suite'); ?></option>
                </select>
            </p>
            
            <p class="form-field">
                <label><?php _e('Price', 'wc-ultra-suite'); ?> (<?php echo get_woocommerce_currency_symbol(); ?>)</label>
                <input type="number" step="0.01" min="0" name="wc_ultra_suite_addons[<?php echo $index; ?>][price]" value="<?php echo esc_attr($price); ?>" placeholder="0.00">
            </p>
            
            <p class="form-field">
                <label><?php _e('Options', 'wc-ultra-suite'); ?></label>
                <input type="text" name="wc_ultra_suite_addons[<?php echo $index; ?>][options]" value="<?php echo esc_attr($options); ?>" placeholder="<?php _e('Option 1|Option 2|Option 3 (for select/radio)', 'wc-ultra-suite'); ?>">
                <span class="description"><?php _e('Separate options with |', 'wc-ultra-suite'); ?></span>
            </p>
            
            <p class="form-field">
                <label>
                    <input type="checkbox" name="wc_ultra_suite_addons[<?php echo $index; ?>][required]" value="1" <?php checked($required, true); ?>>
                    <?php _e('Required', 'wc-ultra-suite'); ?>
                </label>
            </p>
            
            <p class="form-field">
                <button type="button" class="button wc-ultra-suite-remove-addon"><?php _e('Remove', 'wc-ultra-suite'); ?></button>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save addons
     */
    public function save_addons($post_id) {
        $addons = isset($_POST['wc_ultra_suite_addons']) ? $_POST['wc_ultra_suite_addons'] : [];
        
        // Clean and validate addons
        $cleaned_addons = [];
        foreach ($addons as $addon) {
            if (!empty($addon['label'])) {
                $cleaned_addons[] = [
                    'label' => sanitize_text_field($addon['label']),
                    'type' => sanitize_text_field($addon['type']),
                    'price' => floatval($addon['price']),
                    'required' => isset($addon['required']) && $addon['required'] == '1',
                    'options' => sanitize_text_field($addon['options']),
                ];
            }
        }
        
        update_post_meta($post_id, '_wc_ultra_suite_addons', $cleaned_addons);
    }
    
    /**
     * Display addons on product page
     */
    public function display_addons() {
        global $product;
        
        $addons = get_post_meta($product->get_id(), '_wc_ultra_suite_addons', true);
        
        if (!is_array($addons) || empty($addons)) {
            return;
        }
        
        echo '<div class="wc-ultra-suite-addons">';
        echo '<h3 class="wc-ultra-suite-addons-title">' . __('Customize Your Product', 'wc-ultra-suite') . '</h3>';
        
        foreach ($addons as $index => $addon) {
            $field_name = 'wc_ultra_suite_addon_' . $index;
            $required = $addon['required'] ? 'required' : '';
            $price_display = $addon['price'] > 0 ? ' <span class="addon-price">(+' . wc_price($addon['price']) . ')</span>' : '';
            
            echo '<div class="wc-ultra-suite-addon-field">';
            echo '<label class="addon-label">' . esc_html($addon['label']) . $price_display . '</label>';
            
            switch ($addon['type']) {
                case 'checkbox':
                    echo '<input type="checkbox" name="' . $field_name . '" value="yes" ' . $required . ' data-price="' . $addon['price'] . '">';
                    break;
                    
                case 'text':
                    echo '<input type="text" name="' . $field_name . '" ' . $required . ' data-price="' . $addon['price'] . '" class="addon-text-input">';
                    break;
                    
                case 'textarea':
                    echo '<textarea name="' . $field_name . '" ' . $required . ' data-price="' . $addon['price'] . '" class="addon-textarea" rows="3"></textarea>';
                    break;
                    
                case 'select':
                    $options = explode('|', $addon['options']);
                    echo '<select name="' . $field_name . '" ' . $required . ' data-price="' . $addon['price'] . '" class="addon-select">';
                    echo '<option value="">-- ' . __('Select', 'wc-ultra-suite') . ' --</option>';
                    foreach ($options as $option) {
                        echo '<option value="' . esc_attr(trim($option)) . '">' . esc_html(trim($option)) . '</option>';
                    }
                    echo '</select>';
                    break;
                    
                case 'radio':
                    $options = explode('|', $addon['options']);
                    foreach ($options as $option) {
                        echo '<label class="addon-radio-label">';
                        echo '<input type="radio" name="' . $field_name . '" value="' . esc_attr(trim($option)) . '" ' . $required . ' data-price="' . $addon['price'] . '">';
                        echo ' ' . esc_html(trim($option));
                        echo '</label>';
                    }
                    break;
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Add addon data to cart item
     */
    public function add_cart_item_data($cart_item_data, $product_id) {
        $addons = get_post_meta($product_id, '_wc_ultra_suite_addons', true);
        
        if (!is_array($addons) || empty($addons)) {
            return $cart_item_data;
        }
        
        $addon_data = [];
        
        foreach ($addons as $index => $addon) {
            $field_name = 'wc_ultra_suite_addon_' . $index;
            
            if (isset($_POST[$field_name]) && !empty($_POST[$field_name])) {
                $addon_data[] = [
                    'label' => $addon['label'],
                    'value' => sanitize_text_field($_POST[$field_name]),
                    'price' => $addon['price'],
                ];
            }
        }
        
        if (!empty($addon_data)) {
            $cart_item_data['wc_ultra_suite_addons'] = $addon_data;
        }
        
        return $cart_item_data;
    }
    
    /**
     * Calculate addon prices in cart
     */
    public function calculate_addon_prices($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['wc_ultra_suite_addons'])) {
                $addon_price = 0;
                
                foreach ($cart_item['wc_ultra_suite_addons'] as $addon) {
                    $addon_price += floatval($addon['price']);
                }
                
                if ($addon_price > 0) {
                    $cart_item['data']->set_price($cart_item['data']->get_price() + $addon_price);
                }
            }
        }
    }
    
    /**
     * Display addon data in cart
     */
    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['wc_ultra_suite_addons'])) {
            foreach ($cart_item['wc_ultra_suite_addons'] as $addon) {
                $item_data[] = [
                    'name' => $addon['label'],
                    'value' => $addon['value'] . ($addon['price'] > 0 ? ' (+' . wc_price($addon['price']) . ')' : ''),
                ];
            }
        }
        
        return $item_data;
    }
    
    /**
     * Save addon data to order
     */
    public function save_order_item_data($item, $cart_item_key, $values, $order) {
        if (isset($values['wc_ultra_suite_addons'])) {
            foreach ($values['wc_ultra_suite_addons'] as $addon) {
                $item->add_meta_data($addon['label'], $addon['value'], true);
            }
        }
    }
    
    /**
     * AJAX: Get products
     */
    public function ajax_get_products() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-ultra-suite')]);
        }
        
        $args = [
            'limit' => -1,
            'status' => 'publish',
        ];
        
        $products = wc_get_products($args);
        $product_data = [];
        
        foreach ($products as $product) {
            $cost_price = get_post_meta($product->get_id(), '_wc_ultra_suite_cost_price', true);
            $addons = get_post_meta($product->get_id(), '_wc_ultra_suite_addons', true);
            
            $product_data[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'price' => $product->get_price(),
                'cost_price' => $cost_price ? floatval($cost_price) : 0,
                'stock' => $product->get_stock_quantity(),
                'stock_status' => $product->get_stock_status(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'addons_count' => is_array($addons) ? count($addons) : 0,
            ];
        }
        
        wp_send_json_success($product_data);
    }
    
    /**
     * AJAX: Update product
     */
    public function ajax_update_product() {
        check_ajax_referer('wc_ultra_suite_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wc-ultra-suite')]);
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error(['message' => __('Product not found', 'wc-ultra-suite')]);
        }
        
        // Update fields
        if (isset($_POST['price'])) {
            $product->set_regular_price(sanitize_text_field($_POST['price']));
        }
        
        if (isset($_POST['stock'])) {
            $product->set_stock_quantity(intval($_POST['stock']));
        }
        
        if (isset($_POST['cost_price'])) {
            update_post_meta($product_id, '_wc_ultra_suite_cost_price', sanitize_text_field($_POST['cost_price']));
        }
        
        $product->save();
        
        wp_send_json_success(['message' => __('Product updated successfully', 'wc-ultra-suite')]);
    }
}
