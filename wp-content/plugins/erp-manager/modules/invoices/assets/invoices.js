/**
 * FERP Invoices Module - JavaScript (UPDATED)
 * File: modules/invoices/assets/invoices.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    let invoicesData = [];
    let clientsData = [];
    let projectsData = [];
    let currentFilter = '';
    let currentSearch = '';
    
    // Load invoices on page load
    loadInvoices();
    loadClients();
    loadProjects();
    loadStats();
    
    /**
     * Load all invoices
     */
    function loadInvoices() {
        console.log('Loading invoices...');
        
        $.post(FERP.ajax, {
            action: 'ferp_get_invoices',
            nonce: FERP.nonce
        }, function(response) {
            console.log('Invoices response:', response);
            
            if (response.success) {
                invoicesData = response.data || [];
                console.log('Invoices loaded:', invoicesData.length);
                renderInvoices();
            } else {
                console.error('Failed to load invoices:', response);
                showError(response.data?.message || 'Failed to load invoices');
                $('#ferp-invoices-list').html(
                    '<tr><td colspan="8" style="text-align:center;padding:40px;color:#ef4444;">' +
                    'Error loading invoices. Please refresh the page.' +
                    '</td></tr>'
                );
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            showError('Connection error: ' + error);
            $('#ferp-invoices-list').html(
                '<tr><td colspan="8" style="text-align:center;padding:40px;color:#ef4444;">' +
                'Connection error. Please check your internet connection.' +
                '</td></tr>'
            );
        });
    }
    
    /**
     * Load clients for dropdown
     */
    function loadClients() {
        console.log('Loading clients...');
        
        $.post(FERP.ajax, {
            action: 'ferp_get_clients',
            nonce: FERP.nonce
        }, function(response) {
            console.log('Clients response:', response);
            
            if (response.success) {
                clientsData = response.data || [];
                console.log('Clients loaded:', clientsData.length);
                
                let options = '<option value="">Select Client</option>';
                clientsData.forEach(function(client) {
                    options += '<option value="' + client.id + '">' + escapeHtml(client.name) + '</option>';
                });
                $('#invoice-client').html(options);
            } else {
                console.error('Failed to load clients:', response);
                showError(response.data?.message || 'Failed to load clients. Make sure Clients module is active.');
            }
        }).fail(function(xhr, status, error) {
            console.error('Clients AJAX error:', status, error);
        });
    }
    
    /**
     * Load projects for dropdown
     */
    function loadProjects() {
        console.log('Loading projects...');
        
        $.post(FERP.ajax, {
            action: 'ferp_get_projects',
            nonce: FERP.nonce
        }, function(response) {
            console.log('Projects response:', response);
            
            if (response.success) {
                projectsData = response.data || [];
                console.log('Projects loaded:', projectsData.length);
                
                let options = '<option value="">None (Optional)</option>';
                projectsData.forEach(function(project) {
                    options += '<option value="' + project.id + '">' + escapeHtml(project.name);
                    if (project.client_name) {
                        options += ' (' + escapeHtml(project.client_name) + ')';
                    }
                    options += '</option>';
                });
                $('#invoice-project').html(options);
            }
        }).fail(function(xhr, status, error) {
            console.error('Projects AJAX error:', status, error);
        });
    }
    
    /**
     * Load statistics
     */
    function loadStats() {
        $.post(FERP.ajax, {
            action: 'ferp_get_invoice_stats',
            nonce: FERP.nonce
        }, function(response) {
            if (response.success) {
                const stats = response.data;
                $('#stat-total-invoices').text(stats.total_invoices || 0);
                $('#stat-paid-amount').text(formatCurrency(stats.paid_amount || 0));
                $('#stat-pending-amount').text(formatCurrency(stats.pending_amount || 0));
                $('#stat-overdue-count').text(stats.overdue_count || 0);
            }
        });
    }
    
    /**
     * Render invoices table
     */
    function renderInvoices() {
        console.log('Rendering invoices. Total:', invoicesData.length);
        
        let filteredData = invoicesData;
        
        // Apply status filter
        if (currentFilter) {
            filteredData = filteredData.filter(inv => inv.status === currentFilter);
            console.log('After status filter:', filteredData.length);
        }
        
        // Apply search filter
        if (currentSearch) {
            const search = currentSearch.toLowerCase();
            filteredData = filteredData.filter(inv => 
                (inv.invoice_number && inv.invoice_number.toLowerCase().includes(search)) ||
                (inv.client_name && inv.client_name.toLowerCase().includes(search)) ||
                (inv.project_name && inv.project_name.toLowerCase().includes(search))
            );
            console.log('After search filter:', filteredData.length);
        }
        
        if (filteredData.length === 0) {
            const message = invoicesData.length === 0 
                ? 'No invoices found. Click "Add New Invoice" to create your first invoice.'
                : 'No invoices match your filters.';
            
            $('#ferp-invoices-list').html(
                '<tr><td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">' +
                message +
                '</td></tr>'
            );
            return;
        }
        
        let html = '';
        filteredData.forEach(function(invoice) {
            console.log('Rendering invoice:', invoice.invoice_number);
            
            html += '<tr data-id="' + invoice.id + '">';
            html += '<td><strong>' + escapeHtml(invoice.invoice_number || '') + '</strong></td>';
            html += '<td>' + escapeHtml(invoice.client_name || '-') + '</td>';
            html += '<td>' + escapeHtml(invoice.project_name || '-') + '</td>';
            html += '<td>' + formatDate(invoice.issue_date) + '</td>';
            html += '<td>' + formatDate(invoice.due_date) + '</td>';
            html += '<td><strong>' + formatCurrency(invoice.total || 0) + '</strong></td>';
            html += '<td><span class="status-badge badge-' + (invoice.status || 'draft') + '">' + ucfirst(invoice.status || 'draft') + '</span></td>';
            html += '<td class="button-group">';
            html += '<button class="button button-small ferp-view-invoice" data-id="' + invoice.id + '" data-invoice-number="' + escapeHtml(invoice.invoice_number) + '">';
            html += '<span class="dashicons dashicons-visibility"></span> View</button>';
            html += '<button class="button button-small ferp-edit-invoice" data-id="' + invoice.id + '">';
            html += '<span class="dashicons dashicons-edit"></span> Edit</button>';
            html += '<button class="button button-small ferp-send-invoice" data-id="' + invoice.id + '" title="Send Email">';
            html += '<span class="dashicons dashicons-email-alt"></span></button>';
            html += '<button class="button button-small ferp-delete-invoice" data-id="' + invoice.id + '" title="Delete">';
            html += '<span class="dashicons dashicons-trash"></span></button>';
            html += '</td>';
            html += '</tr>';
        });
        
        console.log('HTML generated, updating DOM');
        $('#ferp-invoices-list').html(html);
        console.log('Rendering complete');
    }
    
    /**
     * View invoice in new tab (public link)
     */
    $(document).on('click', '.ferp-view-invoice', function() {
        const invoiceId = $(this).data('id');
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Loading...');
        
        // Get the public invoice URL
        $.post(FERP.ajax, {
            action: 'ferp_get_invoice',
            nonce: FERP.nonce,
            invoice_id: invoiceId
        }, function(response) {
            if (response.success && response.data) {
                const invoice = response.data;
                
                // Build the public URL: yourdomain.com/invoice/INV-1001/TOKEN
                const publicUrl = FERP.home_url + '/invoice/' + 
                                encodeURIComponent(invoice.invoice_number) + '/' + 
                                invoice.access_token;
                
                // Open in new tab
                window.open(publicUrl, '_blank');
            } else {
                alert('Error loading invoice URL');
            }
        }).fail(function() {
            alert('Connection error');
        }).always(function() {
            $btn.prop('disabled', false).html(originalHtml);
        });
    });
    
    /**
     * Open new invoice modal
     */
    $('#ferp-add-invoice').on('click', function(e) {
        e.preventDefault();
        openNewInvoiceModal();
    });
    
    function openNewInvoiceModal() {
        $('#ferp-invoice-form')[0].reset();
        $('#invoice-id').val('');
        $('#ferp-invoice-modal-title').text('Create New Invoice');
        $('#invoice-status').val('draft');
        
        // Get next invoice number
        $.post(FERP.ajax, {
            action: 'ferp_get_next_invoice_number',
            nonce: FERP.nonce
        }, function(response) {
            if (response.success) {
                $('#invoice-number').val(response.data);
                $('#preview-invoice-number').text(response.data);
            }
        });
        
        // Set default dates
        const today = new Date().toISOString().split('T')[0];
        const dueDate = new Date();
        dueDate.setDate(dueDate.getDate() + 30);
        const dueDateStr = dueDate.toISOString().split('T')[0];
        
        $('#invoice-issue-date').val(today);
        $('#invoice-due-date').val(dueDateStr);
        updatePreview();
        
        // Reset items
        $('#invoice-items-list').html('');
        addInvoiceItem('', 1, 0);
        
        calculateTotal();
        $('#ferp-invoice-modal').fadeIn(200);
    }
    
    /**
     * Edit invoice
     */
    $(document).on('click', '.ferp-edit-invoice', function() {
        const invoiceId = $(this).data('id');
        loadInvoiceForEdit(invoiceId);
    });
    
    function loadInvoiceForEdit(invoiceId) {
        $.post(FERP.ajax, {
            action: 'ferp_get_invoice',
            nonce: FERP.nonce,
            invoice_id: invoiceId
        }, function(response) {
            if (response.success) {
                const invoice = response.data;
                
                $('#ferp-invoice-modal-title').text('Edit Invoice');
                $('#invoice-id').val(invoice.id);
                $('#invoice-number').val(invoice.invoice_number);
                $('#invoice-client').val(invoice.client_id);
                $('#invoice-project').val(invoice.project_id || '');
                $('#invoice-issue-date').val(invoice.issue_date);
                $('#invoice-due-date').val(invoice.due_date);
                $('#invoice-status').val(invoice.status);
                $('#invoice-notes').val(invoice.notes || '');
                
                // Calculate tax rate
                const taxRate = invoice.subtotal > 0 ? 
                    ((invoice.tax / invoice.subtotal) * 100).toFixed(2) : 0;
                $('#invoice-tax-rate').val(taxRate);
                
                updatePreview();
                
                // Load items
                $('#invoice-items-list').html('');
                if (invoice.items && invoice.items.length > 0) {
                    invoice.items.forEach(function(item) {
                        addInvoiceItem(item.description, item.quantity, item.rate);
                    });
                } else {
                    addInvoiceItem('', 1, 0);
                }
                
                calculateTotal();
                $('#ferp-invoice-modal').fadeIn(200);
            } else {
                alert(response.data?.message || 'Error loading invoice');
            }
        });
    }
    
    /**
     * Add invoice item row
     */
    $('#add-invoice-item').on('click', function() {
        addInvoiceItem('', 1, 0);
    });
    
    function addInvoiceItem(description, quantity, rate) {
        const amount = quantity * rate;
        const row = $('<tr class="invoice-item-row"></tr>');
        row.html(
            '<td><input type="text" class="item-description" value="' + escapeHtml(description) + '" placeholder="Item description" required></td>' +
            '<td><input type="number" class="item-quantity" value="' + quantity + '" step="0.01" min="0" required></td>' +
            '<td><input type="number" class="item-rate" value="' + rate + '" step="0.01" min="0" required></td>' +
            '<td><input type="number" class="item-amount" value="' + amount.toFixed(2) + '" step="0.01" readonly></td>' +
            '<td><button type="button" class="remove-item-btn" title="Remove"><span class="dashicons dashicons-no"></span></button></td>'
        );
        $('#invoice-items-list').append(row);
        calculateTotal();
    }
    
    /**
     * Remove item
     */
    $(document).on('click', '.remove-item-btn', function() {
        if ($('.invoice-item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        } else {
            alert('At least one item is required');
        }
    });
    
    /**
     * Calculate item amounts and totals
     */
    $(document).on('input', '.item-quantity, .item-rate, #invoice-tax-rate', function() {
        const row = $(this).closest('tr');
        if (row.length) {
            const quantity = parseFloat(row.find('.item-quantity').val()) || 0;
            const rate = parseFloat(row.find('.item-rate').val()) || 0;
            const amount = quantity * rate;
            row.find('.item-amount').val(amount.toFixed(2));
        }
        calculateTotal();
    });
    
    function calculateTotal() {
        let subtotal = 0;
        $('.invoice-item-row').each(function() {
            const amount = parseFloat($(this).find('.item-amount').val()) || 0;
            subtotal += amount;
        });
        
        const taxRate = parseFloat($('#invoice-tax-rate').val()) || 0;
        const tax = (subtotal * taxRate) / 100;
        const total = subtotal + tax;
        
        $('#invoice-subtotal').text(formatCurrency(subtotal));
        $('#invoice-tax').text(formatCurrency(tax));
        $('#invoice-total').text(formatCurrency(total));
    }
    
    /**
     * Update preview
     */
    $('#invoice-client, #invoice-issue-date, #invoice-due-date, #invoice-status').on('change', updatePreview);
    
    function updatePreview() {
        $('#preview-invoice-number').text($('#invoice-number').val() || '-');
        
        const clientId = $('#invoice-client').val();
        const client = clientsData.find(c => c.id == clientId);
        $('#preview-client-name').text(client ? client.name : '-');
        
        $('#preview-issue-date').text($('#invoice-issue-date').val() || '-');
        $('#preview-due-date').text($('#invoice-due-date').val() || '-');
        
        const status = $('#invoice-status').val();
        $('#preview-status').text(ucfirst(status))
            .removeClass('badge-draft badge-sent badge-paid badge-overdue')
            .addClass('badge-' + status);
    }
    
    /**
     * Save invoice
     */
    $('#ferp-invoice-form').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Saving invoice...');
        
        // Collect items
        const items = [];
        $('.invoice-item-row').each(function() {
            const description = $(this).find('.item-description').val();
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const rate = parseFloat($(this).find('.item-rate').val()) || 0;
            const amount = parseFloat($(this).find('.item-amount').val()) || 0;
            
            if (description && quantity > 0) {
                items.push({
                    description: description,
                    quantity: quantity,
                    rate: rate,
                    amount: amount
                });
            }
        });
        
        console.log('Items collected:', items.length);
        
        if (items.length === 0) {
            alert('Please add at least one invoice item');
            return;
        }
        
        if (!$('#invoice-client').val()) {
            alert('Please select a client');
            return;
        }
        
        const subtotalText = $('#invoice-subtotal').text().replace(FERP.currency_symbol, '').replace(/,/g, '');
        const taxText = $('#invoice-tax').text().replace(FERP.currency_symbol, '').replace(/,/g, '');
        const totalText = $('#invoice-total').text().replace(FERP.currency_symbol, '').replace(/,/g, '');
        
        const invoiceData = {
            id: $('#invoice-id').val(),
            client_id: $('#invoice-client').val(),
            project_id: $('#invoice-project').val() || null,
            invoice_number: $('#invoice-number').val(),
            issue_date: $('#invoice-issue-date').val(),
            due_date: $('#invoice-due-date').val(),
            status: $('#invoice-status').val(),
            subtotal: parseFloat(subtotalText),
            tax: parseFloat(taxText),
            total: parseFloat(totalText),
            notes: $('#invoice-notes').val(),
            items: items
        };
        
        console.log('Invoice data:', invoiceData);
        
        const $btn = $('#save-invoice-btn');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Saving...');
        
        $.post(FERP.ajax, {
            action: 'ferp_save_invoice',
            nonce: FERP.nonce,
            invoice: invoiceData
        }, function(response) {
            console.log('Save response:', response);
            
            if (response.success) {
                $('#ferp-invoice-modal').fadeOut(200);
                showSuccess(response.data.message);
                
                // Reload everything
                console.log('Reloading invoices and stats...');
                loadInvoices();
                loadStats();
            } else {
                console.error('Save failed:', response);
                alert(response.data?.message || 'Error saving invoice');
            }
        }).fail(function(xhr, status, error) {
            console.error('Save AJAX error:', status, error, xhr.responseText);
            alert('Connection error. Please try again.');
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Invoice');
        });
    });
    
    /**
     * Delete invoice
     */
    $(document).on('click', '.ferp-delete-invoice', function() {
        if (!confirm(FERP.i18n.confirm_delete)) {
            return;
        }
        
        const invoiceId = $(this).data('id');
        const $row = $(this).closest('tr');
        
        $.post(FERP.ajax, {
            action: 'ferp_delete_invoice',
            nonce: FERP.nonce,
            id: invoiceId
        }, function(response) {
            if (response.success) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                });
                showSuccess(response.data.message);
                loadInvoices();
                loadStats();
            } else {
                alert(response.data?.message || 'Error deleting invoice');
            }
        });
    });
    
    /**
     * Send invoice email
     */
    $(document).on('click', '.ferp-send-invoice', function() {
        if (!confirm('Send this invoice to the client via email?')) {
            return;
        }
        
        const invoiceId = $(this).data('id');
        const $btn = $(this);
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span>');
        
        $.post(FERP.ajax, {
            action: 'ferp_send_invoice_email',
            nonce: FERP.nonce,
            invoice_id: invoiceId
        }, function(response) {
            if (response.success) {
                showSuccess(response.data.message);
                loadInvoices();
                loadStats();
            } else {
                alert(response.data?.message || 'Error sending email');
            }
        }).fail(function() {
            alert('Connection error');
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-email-alt"></span>');
        });
    });
    
    /**
     * Filter by status
     */
    $('#filter-status').on('change', function() {
        currentFilter = $(this).val();
        renderInvoices();
    });
    
    /**
     * Search invoices
     */
    $('#search-invoices').on('input', function() {
        currentSearch = $(this).val();
        renderInvoices();
    });
    
    /**
     * Close modal
     */
    $('.ferp-modal-close').on('click', function() {
        $('.ferp-modal').fadeOut(200);
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('ferp-modal')) {
            $('.ferp-modal').fadeOut(200);
        }
    });
    
    /**
     * Helper functions
     */
    function formatCurrency(amount) {
        return FERP.currency_symbol + parseFloat(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr + 'T00:00:00'); // Add time to avoid timezone issues
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
    
    function showSuccess(message) {
        console.log('Success:', message);
        const $notice = $('<div class="notice notice-success is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
        $('.ferp-invoices-wrap').prepend($notice);
        setTimeout(() => $notice.fadeOut(300, () => $notice.remove()), 5000);
    }
    
    function showError(message) {
        console.error('Error:', message);
        const $notice = $('<div class="notice notice-error is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
        $('.ferp-invoices-wrap').prepend($notice);
        setTimeout(() => $notice.fadeOut(300, () => $notice.remove()), 5000);
    }
});