<?php
/**
 * Invoices View - Main list and management page
 * File: modules/invoices/views/invoices-view.php
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap ferp-invoices-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-media-spreadsheet" style="font-size: 28px; width: 28px; height: 28px; vertical-align: middle;"></span>
        <?php _e('Invoices', 'ferp-modular'); ?>
    </h1>
    <a href="#" class="page-title-action" id="ferp-add-invoice">
        <span class="dashicons dashicons-plus-alt"></span>
        <?php _e('Add New Invoice', 'ferp-modular'); ?>
    </a>
    <hr class="wp-header-end">
    
    <!-- Statistics Cards -->
    <div class="ferp-stats-grid">
        <div class="ferp-stat-card">
            <div class="ferp-stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="ferp-stat-content">
                <div class="ferp-stat-label"><?php _e('Total Invoices', 'ferp-modular'); ?></div>
                <div class="ferp-stat-value" id="stat-total-invoices">0</div>
            </div>
        </div>
        
        <div class="ferp-stat-card">
            <div class="ferp-stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="ferp-stat-content">
                <div class="ferp-stat-label"><?php _e('Paid', 'ferp-modular'); ?></div>
                <div class="ferp-stat-value" id="stat-paid-amount">$0.00</div>
            </div>
        </div>
        
        <div class="ferp-stat-card">
            <div class="ferp-stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="ferp-stat-content">
                <div class="ferp-stat-label"><?php _e('Pending', 'ferp-modular'); ?></div>
                <div class="ferp-stat-value" id="stat-pending-amount">$0.00</div>
            </div>
        </div>
        
        <div class="ferp-stat-card">
            <div class="ferp-stat-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="ferp-stat-content">
                <div class="ferp-stat-label"><?php _e('Overdue', 'ferp-modular'); ?></div>
                <div class="ferp-stat-value" id="stat-overdue-count">0</div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="ferp-filters-bar">
        <div class="ferp-filter-group">
            <label><?php _e('Status:', 'ferp-modular'); ?></label>
            <select id="filter-status">
                <option value=""><?php _e('All Statuses', 'ferp-modular'); ?></option>
                <option value="draft"><?php _e('Draft', 'ferp-modular'); ?></option>
                <option value="sent"><?php _e('Sent', 'ferp-modular'); ?></option>
                <option value="paid"><?php _e('Paid', 'ferp-modular'); ?></option>
                <option value="overdue"><?php _e('Overdue', 'ferp-modular'); ?></option>
            </select>
        </div>
        <div class="ferp-filter-group">
            <label><?php _e('Search:', 'ferp-modular'); ?></label>
            <input type="text" id="search-invoices" placeholder="<?php _e('Search invoices...', 'ferp-modular'); ?>">
        </div>
    </div>
    
    <!-- Invoices Table -->
    <div class="ferp-table-container">
        <table class="wp-list-table widefat fixed striped" id="ferp-invoices-table">
            <thead>
                <tr>
                    <th style="width: 12%;"><?php _e('Invoice #', 'ferp-modular'); ?></th>
                    <th style="width: 18%;"><?php _e('Client', 'ferp-modular'); ?></th>
                    <th style="width: 15%;"><?php _e('Project', 'ferp-modular'); ?></th>
                    <th style="width: 10%;"><?php _e('Date', 'ferp-modular'); ?></th>
                    <th style="width: 10%;"><?php _e('Due Date', 'ferp-modular'); ?></th>
                    <th style="width: 12%;"><?php _e('Amount', 'ferp-modular'); ?></th>
                    <th style="width: 10%;"><?php _e('Status', 'ferp-modular'); ?></th>
                    <th style="width: 13%;"><?php _e('Actions', 'ferp-modular'); ?></th>
                </tr>
            </thead>
            <tbody id="ferp-invoices-list">
                <tr>
                    <td colspan="8" class="ferp-loading">
                        <span class="spinner is-active" style="float: none;"></span>
                        <?php _e('Loading invoices...', 'ferp-modular'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Invoice Modal -->
<div id="ferp-invoice-modal" class="ferp-modal" style="display:none;">
    <div class="ferp-modal-content ferp-modal-xlarge">
        <div class="ferp-modal-header">
            <h2 id="ferp-invoice-modal-title"><?php _e('Create Invoice', 'ferp-modular'); ?></h2>
            <span class="ferp-modal-close">&times;</span>
        </div>
        
        <form id="ferp-invoice-form">
            <input type="hidden" name="id" id="invoice-id">
            
            <div class="ferp-invoice-grid">
                <div class="ferp-invoice-details">
                    <h3><?php _e('Invoice Details', 'ferp-modular'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="invoice-number"><?php _e('Invoice Number', 'ferp-modular'); ?></label></th>
                            <td><input type="text" name="invoice_number" id="invoice-number" class="regular-text" readonly></td>
                        </tr>
                        <tr>
                            <th><label for="invoice-client"><?php _e('Client', 'ferp-modular'); ?> *</label></th>
                            <td>
                                <select name="client_id" id="invoice-client" class="regular-text" required>
                                    <option value=""><?php _e('Select Client', 'ferp-modular'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="invoice-project"><?php _e('Project', 'ferp-modular'); ?></label></th>
                            <td>
                                <select name="project_id" id="invoice-project" class="regular-text">
                                    <option value=""><?php _e('None (Optional)', 'ferp-modular'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="invoice-issue-date"><?php _e('Issue Date', 'ferp-modular'); ?> *</label></th>
                            <td><input type="date" name="issue_date" id="invoice-issue-date" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="invoice-due-date"><?php _e('Due Date', 'ferp-modular'); ?> *</label></th>
                            <td><input type="date" name="due_date" id="invoice-due-date" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="invoice-status"><?php _e('Status', 'ferp-modular'); ?></label></th>
                            <td>
                                <select name="status" id="invoice-status" class="regular-text">
                                    <option value="draft"><?php _e('Draft', 'ferp-modular'); ?></option>
                                    <option value="sent"><?php _e('Sent', 'ferp-modular'); ?></option>
                                    <option value="paid"><?php _e('Paid', 'ferp-modular'); ?></option>
                                    <option value="overdue"><?php _e('Overdue', 'ferp-modular'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="ferp-invoice-preview">
                    <h3><?php _e('Preview', 'ferp-modular'); ?></h3>
                    <div class="preview-box">
                        <p><strong><?php _e('Invoice #:', 'ferp-modular'); ?></strong> <span id="preview-invoice-number">-</span></p>
                        <p><strong><?php _e('Client:', 'ferp-modular'); ?></strong> <span id="preview-client-name">-</span></p>
                        <p><strong><?php _e('Issue Date:', 'ferp-modular'); ?></strong> <span id="preview-issue-date">-</span></p>
                        <p><strong><?php _e('Due Date:', 'ferp-modular'); ?></strong> <span id="preview-due-date">-</span></p>
                        <p><strong><?php _e('Status:', 'ferp-modular'); ?></strong> <span id="preview-status" class="status-badge">Draft</span></p>
                    </div>
                </div>
            </div>
            
            <h3><?php _e('Invoice Items', 'ferp-modular'); ?></h3>
            <table class="wp-list-table widefat" id="invoice-items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;"><?php _e('Description', 'ferp-modular'); ?></th>
                        <th style="width: 12%;"><?php _e('Quantity', 'ferp-modular'); ?></th>
                        <th style="width: 15%;"><?php _e('Rate', 'ferp-modular'); ?></th>
                        <th style="width: 18%;"><?php _e('Amount', 'ferp-modular'); ?></th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="invoice-items-list">
                </tbody>
            </table>
            <button type="button" id="add-invoice-item" class="button">
                <span class="dashicons dashicons-plus"></span>
                <?php _e('Add Item', 'ferp-modular'); ?>
            </button>
            
            <div class="ferp-invoice-footer-grid">
                <div class="ferp-notes-section">
                    <h3><?php _e('Notes', 'ferp-modular'); ?></h3>
                    <textarea name="notes" id="invoice-notes" class="large-text" rows="4" placeholder="<?php _e('Additional notes or payment terms...', 'ferp-modular'); ?>"></textarea>
                </div>
                
                <div class="ferp-summary-section">
                    <h3><?php _e('Invoice Summary', 'ferp-modular'); ?></h3>
                    <table class="ferp-summary-table">
                        <tr>
                            <th><?php _e('Subtotal:', 'ferp-modular'); ?></th>
                            <td id="invoice-subtotal">0.00</td>
                        </tr>
                        <tr>
                            <th>
                                <?php _e('Tax', 'ferp-modular'); ?> 
                                (<input type="number" name="tax_rate" id="invoice-tax-rate" value="<?php echo get_option('ferp_tax_rate', 0); ?>" step="0.01" min="0" style="width: 60px;">%):
                            </th>
                            <td id="invoice-tax">0.00</td>
                        </tr>
                        <tr class="total-row">
                            <th><?php _e('Total:', 'ferp-modular'); ?></th>
                            <td id="invoice-total">0.00</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="ferp-modal-footer">
                <button type="submit" class="button button-primary button-large" id="save-invoice-btn">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save Invoice', 'ferp-modular'); ?>
                </button>
                <button type="button" class="button button-large ferp-modal-close"><?php _e('Cancel', 'ferp-modular'); ?></button>
            </div>
        </form>
    </div>
</div>