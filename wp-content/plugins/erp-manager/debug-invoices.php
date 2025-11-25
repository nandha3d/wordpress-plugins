<?php
/**
 * Invoice Debug Script
 * Save this as: wp-content/plugins/freelance-erp-manager/debug-invoices.php
 * Then visit: http://your-site.local/wp-content/plugins/freelance-erp-manager/debug-invoices.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        h2 { color: #4ec9b0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; background: #252526; }
        th, td { border: 1px solid #3e3e42; padding: 8px; text-align: left; }
        th { background: #2d2d30; color: #4ec9b0; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #ce9178; }
        pre { background: #252526; padding: 15px; border: 1px solid #3e3e42; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Invoice Module Debug</h1>

<?php
global $wpdb;

// Table names
$table_invoices = $wpdb->prefix . 'ferp_invoices';
$table_invoice_items = $wpdb->prefix . 'ferp_invoice_items';
$table_clients = $wpdb->prefix . 'ferp_clients';
$table_projects = $wpdb->prefix . 'ferp_projects';

echo "<h2>1. Database Tables Check</h2>";

// Check if tables exist
$tables = [
    'ferp_invoices' => $table_invoices,
    'ferp_invoice_items' => $table_invoice_items,
    'ferp_clients' => $table_clients,
    'ferp_projects' => $table_projects
];

echo "<table>";
echo "<tr><th>Table</th><th>Status</th><th>Row Count</th></tr>";
foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$table}") : 0;
    $status = $exists ? '<span class="success">✓ EXISTS</span>' : '<span class="error">✗ MISSING</span>';
    echo "<tr><td>{$name}</td><td>{$status}</td><td>{$count}</td></tr>";
}
echo "</table>";

// Get invoices data
echo "<h2>2. Raw Invoice Data</h2>";
$invoices = $wpdb->get_results("SELECT * FROM {$table_invoices}");

if (empty($invoices)) {
    echo "<p class='warning'>⚠ No invoices found in database</p>";
} else {
    echo "<p class='success'>✓ Found " . count($invoices) . " invoice(s)</p>";
    echo "<pre>" . print_r($invoices, true) . "</pre>";
}

// Get the query that the AJAX handler would run
echo "<h2>3. AJAX Query Simulation</h2>";
$ajax_query = "SELECT i.*, c.name as client_name, p.name as project_name
     FROM {$table_invoices} i
     LEFT JOIN {$table_clients} c ON i.client_id = c.id
     LEFT JOIN {$table_projects} p ON i.project_id = p.id
     ORDER BY i.created_at DESC";

echo "<p><strong>Query:</strong></p>";
echo "<pre>" . $ajax_query . "</pre>";

$results = $wpdb->get_results($ajax_query);

if ($wpdb->last_error) {
    echo "<p class='error'>✗ SQL Error: " . $wpdb->last_error . "</p>";
} else {
    echo "<p class='success'>✓ Query executed successfully</p>";
    if (empty($results)) {
        echo "<p class='warning'>⚠ Query returned no results</p>";
    } else {
        echo "<p class='success'>✓ Found " . count($results) . " result(s)</p>";
        echo "<pre>" . print_r($results, true) . "</pre>";
    }
}

// Get invoice items
echo "<h2>4. Invoice Items Check</h2>";
if (!empty($invoices)) {
    foreach ($invoices as $invoice) {
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_invoice_items} WHERE invoice_id = %d ORDER BY sort_order",
            $invoice->id
        ));
        echo "<p><strong>Invoice #{$invoice->invoice_number}:</strong> " . count($items) . " items</p>";
        echo "<pre>" . print_r($items, true) . "</pre>";
    }
}

// Check clients
echo "<h2>5. Clients Data</h2>";
$clients_exist = $wpdb->get_var("SHOW TABLES LIKE '{$table_clients}'");
if ($clients_exist) {
    $clients = $wpdb->get_results("SELECT * FROM {$table_clients}");
    echo "<p class='success'>✓ Found " . count($clients) . " client(s)</p>";
    if (!empty($clients)) {
        echo "<pre>" . print_r($clients, true) . "</pre>";
    }
} else {
    echo "<p class='error'>✗ Clients table doesn't exist</p>";
}

// Test AJAX nonce
echo "<h2>6. AJAX Configuration Test</h2>";
$nonce = wp_create_nonce('ferp_nonce');
echo "<p><strong>Nonce:</strong> " . $nonce . "</p>";
echo "<p><strong>AJAX URL:</strong> " . admin_url('admin-ajax.php') . "</p>";

// Test if AJAX handler is registered
echo "<h2>7. AJAX Handler Registration</h2>";
$ajax_actions = [
    'ferp_get_invoices',
    'ferp_get_clients',
    'ferp_get_projects',
    'ferp_get_invoice_stats',
];

echo "<table>";
echo "<tr><th>Action</th><th>Status</th></tr>";
foreach ($ajax_actions as $action) {
    $has_action = has_action('wp_ajax_' . $action);
    $status = $has_action ? '<span class="success">✓ Registered</span>' : '<span class="error">✗ Not Registered</span>';
    echo "<tr><td>{$action}</td><td>{$status}</td></tr>";
}
echo "</table>";

// Test actual AJAX call
echo "<h2>8. Test AJAX Call (ferp_get_invoices)</h2>";
echo "<button onclick='testAjax()' style='padding: 10px 20px; font-size: 14px; cursor: pointer;'>Run AJAX Test</button>";
echo "<pre id='ajax-result' style='margin-top: 10px;'>Click button to test...</pre>";

?>

<script>
function testAjax() {
    const resultDiv = document.getElementById('ajax-result');
    resultDiv.textContent = 'Testing AJAX call...';
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'ferp_get_invoices',
            nonce: '<?php echo $nonce; ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.textContent = 'AJAX Response:\n' + JSON.stringify(data, null, 2);
    })
    .catch(error => {
        resultDiv.textContent = 'ERROR: ' + error.message;
    });
}
</script>

<hr>
<p><a href="<?php echo admin_url('admin.php?page=ferp-invoices'); ?>" style="color: #4ec9b0;">← Back to Invoices</a></p>
</body>
</html>