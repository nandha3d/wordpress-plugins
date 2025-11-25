<?php
/**
 * Single Invoice View - Modern Design (Like Screenshot)
 * File: modules/invoices/views/invoice-single.php
 */

if (!defined('ABSPATH')) exit;

// Get settings from Settings module
$company_name = get_option('ferp_company_name', get_bloginfo('name'));
$company_logo_url = get_option('ferp_company_logo_url', '');
$company_email = get_option('ferp_company_email', get_option('admin_email'));
$company_phone = get_option('ferp_company_phone', '');
$company_address = get_option('ferp_company_address', '');
$company_website = get_option('ferp_company_website', '');
$gst_number = get_option('ferp_gst_number', '');
$currency = get_option('ferp_currency_symbol', '₹');
$payment_terms = get_option('ferp_payment_terms', 'Payment is due within 30 days');
$show_invoice_qr = get_option('ferp_show_invoice_qr', '0');

// Payment details
$payment_qrcode_url = get_option('ferp_payment_qrcode_url', '');
$bank_account_name = get_option('ferp_bank_account_name', '');
$bank_account_number = get_option('ferp_bank_account_number', '');
$bank_name = get_option('ferp_bank_name', '');
$ifsc_code = get_option('ferp_ifsc_code', '');

// Generate invoice QR code URL
$invoice_url = admin_url('admin.php?page=ferp-invoices&action=view&id=' . (isset($invoice->id) ? $invoice->id : 0));
$invoice_qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($invoice_url);

// Get client GST if available
$client_gst = isset($invoice->client_gst) && !empty($invoice->client_gst) ? $invoice->client_gst : '';?>

<style>
body {
    background: #f5f5f5;
}

.ferp-invoice-modern {
    background: white;
    max-width: 900px;
    margin: 20px auto;
    box-shadow: 0 0 30px rgba(0,0,0,0.1);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header - Blue gradient with logo */
.invoice-header-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    position: relative;
}

.header-left {
    flex: 1;
}

.company-logo-section {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}

.company-logo-img {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 10px;
    padding: 10px;
    object-fit: contain;
}

.company-info-header h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: 700;
    color: white;
}

.company-details {
    font-size: 13px;
    line-height: 1.8;
    opacity: 0.95;
}

.header-right {
    text-align: right;
}

.invoice-title {
    font-size: 48px;
    font-weight: 700;
    margin: 0 0 20px 0;
    letter-spacing: 2px;
}

.invoice-qr-top {
    width: 100px;
    height: 100px;
    background: white;
    padding: 8px;
    border-radius: 8px;
}

/* Action Buttons */
.invoice-actions-modern {
    padding: 20px 40px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    gap: 10px;
    justify-content: center;
}

.invoice-actions-modern .button {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #d1d5db;
    background: white;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
}

.invoice-actions-modern .button:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.invoice-actions-modern .button-primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.invoice-actions-modern .button-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
}

.invoice-actions-modern .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

@media print {
    .invoice-actions-modern {
        display: none !important;
    }
}

/* Body Section */
.invoice-body-modern {
    padding: 40px;
}

/* Two Column Layout */
.info-grid-modern {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.info-box-modern {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
}

.info-box-modern h3 {
    margin: 0 0 15px 0;
    font-size: 14px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.info-box-modern .client-name {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
}

.info-box-modern p {
    margin: 5px 0;
    color: #4b5563;
    line-height: 1.6;
}

/* Invoice Details Table */
.invoice-details-table {
    width: 100%;
    border-collapse: collapse;
}

.invoice-details-table tr {
    border-bottom: 1px solid #e5e7eb;
}

.invoice-details-table tr:last-child {
    border-bottom: none;
}

.invoice-details-table td {
    padding: 10px 0;
}

.invoice-details-table td:first-child {
    font-weight: 600;
    color: #6b7280;
    width: 40%;
}

.invoice-details-table td:last-child {
    text-align: right;
    color: #1f2937;
}

.status-badge-modern {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.status-paid {
    background: #d1fae5;
    color: #065f46;
}

.status-sent {
    background: #dbeafe;
    color: #1e40af;
}

.status-draft {
    background: #f3f4f6;
    color: #6b7280;
}

.status-overdue {
    background: #fee2e2;
    color: #991b1b;
}

/* Items Table */
.items-table-modern {
    width: 100%;
    border-collapse: collapse;
    margin: 30px 0;
}

.items-table-modern thead {
    background: #f8f9fa;
    border-bottom: 2px solid #667eea;
}

.items-table-modern th {
    padding: 15px 10px;
    text-align: left;
    font-weight: 600;
    color: #4b5563;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.items-table-modern td {
    padding: 15px 10px;
    border-bottom: 1px solid #e5e7eb;
    color: #4b5563;
}

.items-table-modern tbody tr:last-child td {
    border-bottom: 2px solid #667eea;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

/* Summary Section */
.summary-section-modern {
    display: flex;
    justify-content: flex-end;
    margin: 30px 0;
}

.summary-box-modern {
    width: 100%;
    max-width: 350px;
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
}

.summary-row-modern {
    display: flex;
    justify-content: space-between;
    padding: 12px 20px;
    border-bottom: 1px solid #e5e7eb;
}

.summary-row-modern:last-child {
    border-bottom: none;
}

.summary-total-modern {
    background: #1f2937;
    color: white;
    font-size: 18px;
    font-weight: 700;
    padding: 18px 20px;
}

/* Bank Details Section */
.bank-section-modern {
    background: #f0f9ff;
    border: 2px solid #3b82f6;
    border-radius: 12px;
    padding: 30px;
    margin: 40px 0;
}

.bank-section-modern h3 {
    margin: 0 0 20px 0;
    color: #1e40af;
    font-size: 16px;
    font-weight: 700;
}

.bank-grid-modern {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 30px;
    align-items: start;
}

.bank-qr-section {
    text-align: center;
}

.bank-qr-section img {
    width: 180px;
    height: 180px;
    border: 2px solid #bfdbfe;
    border-radius: 8px;
    padding: 10px;
    background: white;
}

.bank-qr-label {
    margin-top: 10px;
    font-size: 12px;
    color: #1e40af;
    font-weight: 600;
}

.bank-details-table {
    width: 100%;
    border-collapse: collapse;
}

.bank-details-table tr {
    border-bottom: 1px solid #bfdbfe;
}

.bank-details-table tr:last-child {
    border-bottom: none;
}

.bank-details-table td {
    padding: 12px 0;
}

.bank-details-table td:first-child {
    font-weight: 600;
    color: #1e40af;
    width: 45%;
}

.bank-details-table td:last-child {
    color: #1f2937;
}

/* Action Buttons at Bottom */
.invoice-bottom-actions {
    padding: 30px 40px;
    background: #f8f9fa;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-print-modern,
.btn-download-modern {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-print-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-print-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-download-modern {
    background: #10b981;
    color: white;
}

.btn-download-modern:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
}

@media print {
    .invoice-bottom-actions {
        display: none !important;
    }
    body {
        background: white;
    }
    .ferp-invoice-modern {
        box-shadow: none;
        margin: 0;
    }
}

@media (max-width: 768px) {
    .invoice-header-modern {
        flex-direction: column;
        gap: 20px;
    }
    
    .header-right {
        text-align: left;
    }
    
    .info-grid-modern,
    .bank-grid-modern {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="wrap">
    <div class="ferp-invoice-modern">
        <!-- Header -->
        <div class="invoice-header-modern">
            <div class="header-left">
                <div class="company-logo-section">
                    <?php if ($company_logo_url): ?>
                        <img src="<?php echo esc_url($company_logo_url); ?>" alt="Logo" class="company-logo-img">
                    <?php endif; ?>
                    <div class="company-info-header">
                        <h1><?php echo esc_html($company_name); ?></h1>
                        <div class="company-details">
                            <?php if ($company_address): ?>
                                <?php echo nl2br(esc_html($company_address)); ?><br>
                            <?php endif; ?>
                            <?php if ($company_email): ?>
                                <?php echo esc_html($company_email); ?><br>
                            <?php endif; ?>
                            <?php if ($company_phone): ?>
                                <?php echo esc_html($company_phone); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <img src="<?php echo esc_url($invoice_qr_url); ?>" alt="Invoice QR" class="invoice-qr-top">
            </div>
        </div>
        
        <!-- Actions (Hidden on Print) -->
        <div class="invoice-actions-modern">
            <a href="<?php echo admin_url('admin.php?page=ferp-invoices'); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt2"></span> Back to Invoices
            </a>
            <a href="<?php echo admin_url('admin.php?page=ferp-invoices&action=edit&id=' . $invoice->id); ?>" class="button button-primary">
                <span class="dashicons dashicons-edit"></span> Edit Invoice
            </a>
            <button class="button" id="send-email-btn" data-invoice-id="<?php echo $invoice->id; ?>">
                <span class="dashicons dashicons-email-alt"></span> Send Email
            </button>
        </div>
        
        <!-- Body -->
        <div class="invoice-body-modern">
            <!-- Invoice Info Grid -->
            <div class="info-grid-modern">
                <!-- Invoice Information -->
                <div class="info-box-modern">
                    <h3>Invoice Information</h3>
                    <table class="invoice-details-table">
                        <tr>
                            <td>Invoice #:</td>
                            <td><strong><?php echo esc_html($invoice->invoice_number); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Date:</td>
                            <td><?php echo date('M d, Y', strtotime($invoice->issue_date)); ?></td>
                        </tr>
                        <tr>
                            <td>Due Date:</td>
                            <td><?php echo date('M d, Y', strtotime($invoice->due_date)); ?></td>
                        </tr>
                        <tr>
                            <td>Status:</td>
                            <td>
                                <span class="status-badge-modern status-<?php echo esc_attr($invoice->status); ?>">
                                    <?php echo strtoupper($invoice->status); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Currency:</td>
                            <td><?php echo esc_html($currency); ?> (INR)</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Bill To -->
                <div class="info-box-modern">
                    <h3>Bill To</h3>
                    <div class="client-name"><?php echo esc_html($invoice->client_name); ?></div>
                    <?php if ($invoice->email): ?>
                        <p><?php echo esc_html($invoice->email); ?></p>
                    <?php endif; ?>
                    <?php if ($invoice->address): ?>
                        <p><?php echo nl2br(esc_html($invoice->address)); ?></p>
                    <?php endif; ?>
<?php if (isset($client_gst) && !empty($client_gst)): ?>
    <p><strong>GST:</strong> <?php echo esc_html($client_gst); ?></p>
<?php endif; ?>
                </div>
            </div>
            
            <!-- Items Table -->
            <table class="items-table-modern">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 15%;" class="text-center">Quantity</th>
                        <th style="width: 17%;" class="text-right">Rate</th>
                        <th style="width: 18%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo nl2br(esc_html($item->description)); ?></td>
                        <td class="text-center"><?php echo number_format($item->quantity, 2); ?></td>
                        <td class="text-right"><?php echo $currency . number_format($item->rate, 2); ?></td>
                        <td class="text-right"><strong><?php echo $currency . number_format($item->amount, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Summary -->
            <div class="summary-section-modern">
                <div class="summary-box-modern">
                    <div class="summary-row-modern">
                        <span>Subtotal:</span>
                        <span><?php echo $currency . number_format($invoice->subtotal, 2); ?></span>
                    </div>
                    <div class="summary-total-modern">
                        <span>TOTAL:</span>
                        <span><?php echo $currency . number_format($invoice->total, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Bank Details -->
            <?php if ($payment_qrcode_url || $bank_account_number): ?>
            <div class="bank-section-modern">
                <h3>Bank Details</h3>
                <div class="bank-grid-modern">
                    <?php if ($payment_qrcode_url): ?>
                    <div class="bank-qr-section">
                        <div style="font-weight: 600; color: #1e40af; margin-bottom: 10px;">Scan to Pay</div>
                        <img src="<?php echo esc_url($payment_qrcode_url); ?>" alt="Payment QR">
                        <div class="bank-qr-label">Scan to pay with any UPI app</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($bank_account_number): ?>
                    <div>
                        <table class="bank-details-table">
                            <?php if ($bank_account_name): ?>
                            <tr>
                                <td>Account Name:</td>
                                <td><?php echo esc_html($bank_account_name); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td>Account Number:</td>
                                <td><?php echo esc_html($bank_account_number); ?></td>
                            </tr>
                            <?php if ($bank_name): ?>
                            <tr>
                                <td>Bank Name:</td>
                                <td><?php echo esc_html($bank_name); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($ifsc_code): ?>
                            <tr>
                                <td>IFSC Code:</td>
                                <td><?php echo esc_html($ifsc_code); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Bottom Actions -->
        <div class="invoice-bottom-actions">
            <button onclick="window.print()" class="btn-print-modern">
                <span class="dashicons dashicons-printer"></span>
                Print Invoice
            </button>
            <button class="btn-download-modern" id="download-pdf-btn" data-invoice-id="<?php echo $invoice->id; ?>">
                <span class="dashicons dashicons-pdf"></span>
                Download PDF
            </button>
        </div>
    </div>
</div>

<script>
// PDF download button handler - UPDATED VERSION
// Replace the existing PDF download handler in invoice-single.php

jQuery(document).ready(function($) {
    // PDF download
    $('#download-pdf-btn').on('click', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const invoiceId = $btn.data('invoice-id');
        const originalHtml = $btn.html();
        
        console.log('Downloading PDF for invoice:', invoiceId);
        
        // Disable button
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Generating PDF...');
        
        // Create download URL with parameters
        const downloadUrl = ajaxurl + 
            '?action=ferp_download_invoice_pdf' +
            '&invoice_id=' + invoiceId +
            '&nonce=' + '<?php echo wp_create_nonce('ferp_nonce'); ?>';
        
        console.log('Download URL:', downloadUrl);
        
        // Method 1: Try direct download via hidden iframe
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = downloadUrl;
        document.body.appendChild(iframe);
        
        // Alternative Method 2: If iframe fails, use fetch
        setTimeout(function() {
            fetch(downloadUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Download failed: ' + response.statusText);
                    }
                    return response.blob();
                })
                .then(blob => {
                    // Create download link
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = 'Invoice-<?php echo esc_js($invoice->invoice_number); ?>.pdf';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    console.log('PDF downloaded successfully');
                })
                .catch(error => {
                    console.error('Download error:', error);
                    alert('Error downloading PDF: ' + error.message + '\n\nPlease try again or contact support.');
                })
                .finally(() => {
                    // Re-enable button
                    $btn.prop('disabled', false).html(originalHtml);
                    
                    // Clean up iframe after a delay
                    setTimeout(() => {
                        if (iframe.parentNode) {
                            iframe.parentNode.removeChild(iframe);
                        }
                    }, 3000);
                });
        }, 500);
    });
    
    // Send email button
    $('#send-email-btn').on('click', function() {
        if (!confirm('Send this invoice to <?php echo esc_js($invoice->client_name); ?> (<?php echo esc_js($invoice->email); ?>)?')) {
            return;
        }
        
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Sending...');
        
        $.post(ajaxurl, {
            action: 'ferp_send_invoice_email',
            nonce: '<?php echo wp_create_nonce('ferp_nonce'); ?>',
            invoice_id: <?php echo $invoice->id; ?>
        }, function(response) {
            if (response.success) {
                alert('✓ ' + response.data.message);
                location.reload();
            } else {
                alert('✗ ' + (response.data?.message || 'Failed to send email'));
            }
        }).fail(function() {
            alert('Connection error');
        }).always(function() {
            $btn.prop('disabled', false).html(originalHtml);
        });
    });
});
</script>