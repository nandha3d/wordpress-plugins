<?php
/**
 * Public Invoice View Template
 * File: modules/invoices/views/invoice-public.php
 * 
 * This template is used for the public-facing invoice view
 * URL format: yourdomain.com/invoice/INV-1001/TOKEN
 */

if (!defined('ABSPATH')) exit;

// IMPORTANT: Ensure we have the access token from the invoice object
$access_token = isset($invoice->access_token) ? $invoice->access_token : '';

// Generate invoice QR code URL - use the actual invoice URL
$invoice_url = home_url('invoice/' . urlencode($invoice->invoice_number) . '/' . $access_token);
$invoice_qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($invoice_url);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Invoice <?php echo esc_html($invoice->invoice_number); ?> - <?php echo esc_html($company_name); ?></title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .ferp-invoice-modern {
            background: white;
            max-width: 900px;
            margin: 20px auto;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
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
            cursor: pointer;
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

        .invoice-actions-modern .button svg {
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
            text-decoration: none;
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
</head>
<body>
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
            <button onclick="window.print()" class="button">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Invoice
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
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Invoice
            </button>
        </div>
    </div>
</body>
</html>