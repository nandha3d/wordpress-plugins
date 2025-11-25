<?php
if (!defined('ABSPATH')) exit;

function invoices_activate() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Invoices table
    $table_invoices = $wpdb->prefix . 'ferp_invoices';
    $sql_invoices = "CREATE TABLE IF NOT EXISTS {$table_invoices} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        client_id bigint(20) NOT NULL,
        project_id bigint(20) DEFAULT NULL,
        invoice_number varchar(100) NOT NULL,
        issue_date date NOT NULL,
        due_date date NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'draft',
        subtotal decimal(15,2) NOT NULL DEFAULT 0.00,
        tax decimal(15,2) NOT NULL DEFAULT 0.00,
        total decimal(15,2) NOT NULL DEFAULT 0.00,
        notes text,
        access_token varchar(100) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY client_id (client_id),
        KEY project_id (project_id),
        KEY status (status),
        KEY invoice_number (invoice_number),
        KEY access_token (access_token)
    ) $charset_collate;";
    
    // Invoice items table (unchanged)
    $table_items = $wpdb->prefix . 'ferp_invoice_items';
    $sql_items = "CREATE TABLE IF NOT EXISTS {$table_items} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        invoice_id bigint(20) NOT NULL,
        description text NOT NULL,
        quantity decimal(10,2) NOT NULL DEFAULT 1.00,
        rate decimal(15,2) NOT NULL DEFAULT 0.00,
        amount decimal(15,2) NOT NULL DEFAULT 0.00,
        sort_order int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY invoice_id (invoice_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql_invoices);
    dbDelta($sql_items);
    
    // Check if access_token column exists, add if not
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_invoices} LIKE 'access_token'");
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE {$table_invoices} ADD COLUMN access_token varchar(100) DEFAULT NULL AFTER notes");
        $wpdb->query("ALTER TABLE {$table_invoices} ADD KEY access_token (access_token)");
    }
    
    // Set default options
    $defaults = [
        'ferp_invoice_prefix' => 'INV-',
        'ferp_invoice_start_number' => 1001,
        'ferp_currency_symbol' => '$',
        'ferp_tax_rate' => 0,
        'ferp_invoice_template' => 'modern',
        'ferp_company_name' => get_bloginfo('name'),
        'ferp_company_email' => get_option('admin_email'),
        'ferp_invoice_terms' => 'Payment is due within 30 days',
    ];
    
    foreach ($defaults as $key => $value) {
        if (get_option($key) === false) {
            add_option($key, $value);
        }
    }
}