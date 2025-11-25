<?php
/**
 * Clients Module Activation
 * File: modules/clients/activate.php
 */

if (!defined('ABSPATH')) exit;

function clients_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Create clients table
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ferp_clients (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(50) DEFAULT NULL,
        company varchar(255) DEFAULT NULL,
        address text DEFAULT NULL,
        notes text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Log success
    error_log('FERP: Clients module activated - table created');
}