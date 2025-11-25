<?php
/**
 * Settings View - Main settings page
 * File: modules/settings/views/settings.php
 */

if (!defined('ABSPATH')) exit;

// Get current settings
$company_name = get_option('ferp_company_name', get_bloginfo('name'));
$company_logo_url = get_option('ferp_company_logo_url', '');
$company_address = get_option('ferp_company_address', '');
$company_phone = get_option('ferp_company_phone', '');
$company_email = get_option('ferp_company_email', get_option('admin_email'));
$company_website = get_option('ferp_company_website', '');
$gst_number = get_option('ferp_gst_number', '');

$invoice_prefix = get_option('ferp_invoice_prefix', 'INV-');
$currency_symbol = get_option('ferp_currency_symbol', '$');
$currency = get_option('ferp_currency', 'USD');
$tax_rate = get_option('ferp_tax_rate', 0);
$payment_terms = get_option('ferp_payment_terms', 'Payment is due within 30 days');
$show_invoice_qr = get_option('ferp_show_invoice_qr', '0');

$payment_qrcode_url = get_option('ferp_payment_qrcode_url', '');
$bank_account_name = get_option('ferp_bank_account_name', '');
$bank_account_number = get_option('ferp_bank_account_number', '');
$bank_name = get_option('ferp_bank_name', '');
$ifsc_code = get_option('ferp_ifsc_code', '');
?>

<div class="wrap ferp-settings-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings" style="font-size: 28px; width: 28px; height: 28px; vertical-align: middle;"></span>
        <?php _e('Invoice Settings', 'freelance-erp-manager'); ?>
    </h1>
    <hr class="wp-header-end">
    
    <div class="ferp-settings-container">
        <form id="ferp-settings-form" enctype="multipart/form-data">
            <?php wp_nonce_field('ferp_settings_nonce', 'ferp_settings_nonce'); ?>
            
            <!-- Company Information -->
            <div class="ferp-settings-section">
                <h2><?php _e('Company Information', 'freelance-erp-manager'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="company-name"><?php _e('Company Name', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="company-name" name="company_name" value="<?php echo esc_attr($company_name); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Company Logo', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <div class="ferp-logo-upload">
                                <div id="company-logo-preview" class="ferp-logo-preview">
                                    <?php if ($company_logo_url): ?>
                                        <img src="<?php echo esc_url($company_logo_url); ?>" alt="Company Logo">
                                    <?php else: ?>
                                        <div class="ferp-logo-placeholder">
                                            <span class="dashicons dashicons-format-image"></span>
                                            <p><?php _e('No logo uploaded', 'freelance-erp-manager'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="company-logo-url" name="company_logo_url" value="<?php echo esc_url($company_logo_url); ?>">
                                <div class="ferp-logo-buttons">
                                    <button type="button" class="button button-primary" id="upload-logo-btn">
                                        <span class="dashicons dashicons-upload"></span>
                                        <?php _e('Upload/Select Logo', 'freelance-erp-manager'); ?>
                                    </button>
                                    <?php if ($company_logo_url): ?>
                                        <button type="button" class="button" id="remove-logo-btn">
                                            <span class="dashicons dashicons-no"></span>
                                            <?php _e('Remove Logo', 'freelance-erp-manager'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <p class="description"><?php _e('Recommended size: 300x100px (PNG or JPG)', 'freelance-erp-manager'); ?></p>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company-address"><?php _e('Address', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="company-address" name="company_address" rows="3" class="large-text"><?php echo esc_textarea($company_address); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company-phone"><?php _e('Phone', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="company-phone" name="company_phone" value="<?php echo esc_attr($company_phone); ?>" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company-email"><?php _e('Email', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="company-email" name="company_email" value="<?php echo esc_attr($company_email); ?>" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company-website"><?php _e('Website', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="company-website" name="company_website" value="<?php echo esc_attr($company_website); ?>" class="regular-text" placeholder="https://">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="gst-number"><?php _e('GST/Tax Number', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gst-number" name="gst_number" value="<?php echo esc_attr($gst_number); ?>" class="regular-text">
                            <p class="description"><?php _e('e.g., GST Number, VAT ID, Tax ID', 'freelance-erp-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Invoice & Payment Settings -->
            <div class="ferp-settings-section">
                <h2><?php _e('Invoice & Payment Settings', 'freelance-erp-manager'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="invoice-prefix"><?php _e('Invoice Prefix', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="invoice-prefix" name="invoice_prefix" value="<?php echo esc_attr($invoice_prefix); ?>" class="small-text">
                            <p class="description"><?php _e('e.g., INV-, BILL-', 'freelance-erp-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="currency-symbol"><?php _e('Currency Symbol', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="currency-symbol" name="currency_symbol" value="<?php echo esc_attr($currency_symbol); ?>" class="small-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="currency"><?php _e('Default Currency', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="currency" name="currency" value="<?php echo esc_attr($currency); ?>" class="small-text">
                            <p class="description"><?php _e('e.g., USD, INR, EUR', 'freelance-erp-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tax-rate"><?php _e('Default Tax Rate (%)', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="tax-rate" name="tax_rate" value="<?php echo esc_attr($tax_rate); ?>" class="small-text" step="0.01" min="0">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="payment-terms"><?php _e('Payment Terms', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="payment-terms" name="payment_terms" rows="3" class="large-text"><?php echo esc_textarea($payment_terms); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="show-invoice-qr"><?php _e('Show Invoice QR', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="show-invoice-qr" name="show_invoice_qr" value="1" <?php checked($show_invoice_qr, '1'); ?>>
                                <?php _e('Display Invoice URL QR code for easy access.', 'freelance-erp-manager'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Payment QR Code & Bank Details -->
            <div class="ferp-settings-section">
                <h2><?php _e('Payment QR Code & Bank Details', 'freelance-erp-manager'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Payment QR Code', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <div class="ferp-qr-upload">
                                <div id="payment-qr-preview" class="ferp-qr-preview">
                                    <?php if ($payment_qrcode_url): ?>
                                        <img src="<?php echo esc_url($payment_qrcode_url); ?>" alt="Payment QR Code">
                                        <p><?php _e('Scan to pay with UPI app', 'freelance-erp-manager'); ?></p>
                                    <?php else: ?>
                                        <div class="ferp-qr-placeholder">
                                            <span class="dashicons dashicons-smartphone"></span>
                                            <p><?php _e('No QR code uploaded', 'freelance-erp-manager'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="payment-qrcode-url" name="payment_qrcode_url" value="<?php echo esc_url($payment_qrcode_url); ?>">
                                <div class="ferp-qr-buttons">
                                    <button type="button" class="button button-primary" id="upload-qr-btn">
                                        <span class="dashicons dashicons-upload"></span>
                                        <?php _e('Upload/Select QR Code', 'freelance-erp-manager'); ?>
                                    </button>
                                    <?php if ($payment_qrcode_url): ?>
                                        <button type="button" class="button" id="remove-qr-btn">
                                            <span class="dashicons dashicons-no"></span>
                                            <?php _e('Remove QR Code', 'freelance-erp-manager'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <p class="description"><?php _e('Upload your GPay or other payment QR code image.', 'freelance-erp-manager'); ?></p>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bank-account-name"><?php _e('Bank Account Name', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bank-account-name" name="bank_account_name" value="<?php echo esc_attr($bank_account_name); ?>" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bank-account-number"><?php _e('Bank Account Number', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bank-account-number" name="bank_account_number" value="<?php echo esc_attr($bank_account_number); ?>" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bank-name"><?php _e('Bank Name', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bank-name" name="bank_name" value="<?php echo esc_attr($bank_name); ?>" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ifsc-code"><?php _e('IFSC Code', 'freelance-erp-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ifsc-code" name="ifsc_code" value="<?php echo esc_attr($ifsc_code); ?>" class="regular-text">
                            <p class="description"><?php _e('For Indian banks. Use routing number or SWIFT code for other countries.', 'freelance-erp-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <button type="submit" class="button button-primary button-large" id="save-settings-btn">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save Settings', 'freelance-erp-manager'); ?>
                </button>
            </p>
        </form>
    </div>
</div>

<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #999; font-size: 12px;">
    <p><?php _e('Thank you for creating with', 'freelance-erp-manager'); ?> <a href="https://wordpress.org" target="_blank">WordPress</a>.</p>
</div>