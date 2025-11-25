<?php
/**
 * DEBUG SCRIPT - Add this temporarily to check your invoices
 * Place this in wp-admin/debug-invoice-tokens.php
 * Then access: http://yourdomain.com/wp-admin/debug-invoice-tokens.php
 * DELETE THIS FILE after debugging!
 */

// Load WordPress
require_once('../wp-load.php');

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;
$table_invoices = $wpdb->prefix . 'ferp_invoices';

echo "<h1>Invoice Tokens Debug</h1>";
echo "<style>table { border-collapse: collapse; } td, th { border: 1px solid #ccc; padding: 8px; }</style>";

// Get all invoices
$invoices = $wpdb->get_results("SELECT id, invoice_number, access_token FROM {$table_invoices} ORDER BY id DESC LIMIT 20");

if (empty($invoices)) {
    echo "<p>No invoices found.</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Invoice Number</th><th>Access Token</th><th>Token Length</th><th>Action</th></tr>";
    
    foreach ($invoices as $invoice) {
        $token_length = strlen($invoice->access_token);
        $has_token = !empty($invoice->access_token);
        
        echo "<tr>";
        echo "<td>{$invoice->id}</td>";
        echo "<td>{$invoice->invoice_number}</td>";
        echo "<td style='color: " . ($has_token ? 'green' : 'red') . "'>";
        echo $has_token ? substr($invoice->access_token, 0, 20) . '...' : '<strong>MISSING!</strong>';
        echo "</td>";
        echo "<td>{$token_length}</td>";
        echo "<td>";
        
        if (!$has_token) {
            echo "<a href='?generate_token={$invoice->id}' style='color: blue;'>Generate Token</a>";
        } else {
            $url = home_url('invoice/' . urlencode($invoice->invoice_number) . '/' . $invoice->access_token);
            echo "<a href='{$url}' target='_blank'>View Invoice</a>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Handle token generation
if (isset($_GET['generate_token'])) {
    $invoice_id = intval($_GET['generate_token']);
    $new_token = wp_generate_password(32, false);
    
    $updated = $wpdb->update(
        $table_invoices,
        ['access_token' => $new_token],
        ['id' => $invoice_id],
        ['%s'],
        ['%d']
    );
    
    if ($updated) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "✓ Token generated for invoice #{$invoice_id}. <a href='?'>Refresh page</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 20px 0; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "✗ Failed to generate token for invoice #{$invoice_id}";
        echo "</div>";
    }
}

echo "<hr>";
echo "<h2>Generate Tokens for All Invoices</h2>";
echo "<form method='post'>";
echo "<button type='submit' name='generate_all' onclick='return confirm(\"Generate tokens for all invoices?\")'>Generate All Tokens</button>";
echo "</form>";

if (isset($_POST['generate_all'])) {
    $count = 0;
    $invoices = $wpdb->get_results("SELECT id FROM {$table_invoices} WHERE access_token IS NULL OR access_token = ''");
    
    foreach ($invoices as $invoice) {
        $new_token = wp_generate_password(32, false);
        $wpdb->update(
            $table_invoices,
            ['access_token' => $new_token],
            ['id' => $invoice->id]
        );
        $count++;
    }
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "✓ Generated tokens for {$count} invoices. <a href='?'>Refresh page</a>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Important:</strong> DELETE THIS FILE after debugging!</p>";