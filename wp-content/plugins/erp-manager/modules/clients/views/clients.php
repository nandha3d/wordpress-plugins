<?php
/**
 * Clients View - Feature Rich with Search, Filter, Export, Stats
 * File: modules/clients/views/clients.php
 */

if (!defined('ABSPATH')) exit;
?>

<style>
/* Enhanced Clients Styling */
.ferp-clients-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.ferp-clients-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
    font-weight: 700;
}

.ferp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.ferp-stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid #667eea;
}

.ferp-stat-label {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 5px;
}

.ferp-stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
}

.ferp-table-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}

.ferp-table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    flex-wrap: wrap;
    gap: 15px;
}

.ferp-search-filter {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.ferp-search-box {
    position: relative;
}

.ferp-search-box input {
    padding: 10px 40px 10px 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    width: 300px;
}

.ferp-search-box .dashicons {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.ferp-btn-primary {
    background: #667eea;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.ferp-btn-primary:hover {
    background: #5568d3;
}

.ferp-btn-secondary {
    background: #10b981;
    color: white;
}

.ferp-btn-secondary:hover {
    background: #059669;
}

#ferp-clients-table {
    width: 100%;
}

#ferp-clients-table th {
    background: #f9fafb;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

#ferp-clients-table td {
    padding: 15px;
    border-bottom: 1px solid #f3f4f6;
}

#ferp-clients-table tbody tr:hover {
    background: #f9fafb;
}

.ferp-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.7);
}

.ferp-modal-content {
    background-color: #fefefe;
    margin: 3% auto;
    padding: 30px;
    border: 1px solid #888;
    width: 90%;
    max-width: 700px;
    border-radius: 10px;
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
}

.ferp-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    line-height: 20px;
    cursor: pointer;
}

.ferp-modal-close:hover {
    color: #000;
}

.ferp-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.ferp-form-group {
    margin-bottom: 15px;
}

.ferp-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #374151;
}

.ferp-form-group input,
.ferp-form-group select,
.ferp-form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.ferp-form-group textarea {
    resize: vertical;
}

.client-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-active {
    background: #d1fae5;
    color: #065f46;
}

.badge-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.client-stats-mini {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #6b7280;
}

.client-stats-mini span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.action-btn {
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.action-btn:hover {
    background: #f3f4f6;
    border-color: #667eea;
    color: #667eea;
}

.action-btn.delete:hover {
    background: #fee2e2;
    border-color: #ef4444;
    color: #ef4444;
}

.phone-input-group {
    display: flex;
    gap: 10px;
}

.phone-input-group select {
    width: 180px;
}

.phone-input-group input {
    flex: 1;
}

.ferp-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.ferp-empty-state .dashicons {
    font-size: 80px;
    width: 80px;
    height: 80px;
    opacity: 0.3;
}

/* DataTables Custom Styling */
.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #d1d5db !important;
    border-radius: 6px !important;
    padding: 6px 10px !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 6px 12px !important;
    margin: 0 2px !important;
    border-radius: 6px !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #667eea !important;
    color: white !important;
    border: 1px solid #667eea !important;
}
</style>

<div class="wrap">
    <div class="ferp-clients-header">
        <h1><?php _e('👥 Client Management', 'freelance-erp-manager'); ?></h1>
        <p><?php _e('Manage your clients with comprehensive contact information and statistics', 'freelance-erp-manager'); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="ferp-stats-grid">
        <div class="ferp-stat-card">
            <div class="ferp-stat-label"><?php _e('Total Clients', 'freelance-erp-manager'); ?></div>
            <div class="ferp-stat-value" id="stat-total-clients">0</div>
        </div>
        <div class="ferp-stat-card">
            <div class="ferp-stat-label"><?php _e('Active Projects', 'freelance-erp-manager'); ?></div>
            <div class="ferp-stat-value" id="stat-active-projects">0</div>
        </div>
        <div class="ferp-stat-card">
            <div class="ferp-stat-label"><?php _e('Total Revenue', 'freelance-erp-manager'); ?></div>
            <div class="ferp-stat-value" id="stat-total-revenue">₹0</div>
        </div>
        <div class="ferp-stat-card">
            <div class="ferp-stat-label"><?php _e('Pending Amount', 'freelance-erp-manager'); ?></div>
            <div class="ferp-stat-value" id="stat-pending-amount">₹0</div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="ferp-table-container">
        <div class="ferp-table-header">
            <h2 style="margin: 0;"><?php _e('All Clients', 'freelance-erp-manager'); ?></h2>
            <div class="ferp-search-filter">
                <button class="ferp-btn-primary" id="ferp-add-client">
                    <span class="dashicons dashicons-plus-alt"></span> <?php _e('Add Client', 'freelance-erp-manager'); ?>
                </button>
                <button class="ferp-btn-primary ferp-btn-secondary" id="ferp-export-clients">
                    <span class="dashicons dashicons-download"></span> <?php _e('Export CSV', 'freelance-erp-manager'); ?>
                </button>
            </div>
        </div>

        <div style="padding: 20px;">
            <table id="ferp-clients-table" class="display">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'freelance-erp-manager'); ?></th>
                        <th><?php _e('Email', 'freelance-erp-manager'); ?></th>
                        <th><?php _e('Phone', 'freelance-erp-manager'); ?></th>
                        <th><?php _e('Company', 'freelance-erp-manager'); ?></th>
                        <th><?php _e('Stats', 'freelance-erp-manager'); ?></th>
                        <th><?php _e('Actions', 'freelance-erp-manager'); ?></th>
                    </tr>
                </thead>
                <tbody id="ferp-clients-list">
                    <tr>
                        <td colspan="6" class="ferp-loading"><?php _e('Loading...', 'freelance-erp-manager'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Client Modal -->
<div id="ferp-client-modal" class="ferp-modal" style="display:none;">
    <div class="ferp-modal-content">
        <span class="ferp-modal-close">&times;</span>
        <h2 id="ferp-client-modal-title"><?php _e('Add Client', 'freelance-erp-manager'); ?></h2>
        
        <form id="ferp-client-form">
            <input type="hidden" name="id" id="client-id">
            
            <div class="ferp-form-grid">
                <div class="ferp-form-group">
                    <label for="client-name"><?php _e('Name', 'freelance-erp-manager'); ?> *</label>
                    <input type="text" name="name" id="client-name" required>
                </div>
                
                <div class="ferp-form-group">
                    <label for="client-email"><?php _e('Email', 'freelance-erp-manager'); ?> *</label>
                    <input type="email" name="email" id="client-email" required>
                </div>
            </div>
            
            <div class="ferp-form-group">
                <label for="client-phone"><?php _e('Phone', 'freelance-erp-manager'); ?></label>
                <div class="phone-input-group">
                    <select name="phone_country_code" id="client-phone-country-code">
                        <option value="+91" selected>🇮🇳 India (+91)</option>
                        <option value="+1">🇺🇸 USA (+1)</option>
                        <option value="+44">🇬🇧 UK (+44)</option>
                        <option value="+61">🇦🇺 Australia (+61)</option>
                        <option value="+81">🇯🇵 Japan (+81)</option>
                        <option value="+86">🇨🇳 China (+86)</option>
                        <option value="+49">🇩🇪 Germany (+49)</option>
                        <option value="+33">🇫🇷 France (+33)</option>
                        <option value="+39">🇮🇹 Italy (+39)</option>
                        <option value="+34">🇪🇸 Spain (+34)</option>
                        <option value="+7">🇷🇺 Russia (+7)</option>
                        <option value="+82">🇰🇷 South Korea (+82)</option>
                        <option value="+55">🇧🇷 Brazil (+55)</option>
                        <option value="+52">🇲🇽 Mexico (+52)</option>
                        <option value="+27">🇿🇦 South Africa (+27)</option>
                        <option value="+971">🇦🇪 UAE (+971)</option>
                        <option value="+966">🇸🇦 Saudi Arabia (+966)</option>
                        <option value="+65">🇸🇬 Singapore (+65)</option>
                        <option value="+60">🇲🇾 Malaysia (+60)</option>
                        <option value="+62">🇮🇩 Indonesia (+62)</option>
                    </select>
                    <input type="text" name="phone_number" id="client-phone" placeholder="<?php _e('Phone number', 'freelance-erp-manager'); ?>">
                </div>
            </div>
            
            <div class="ferp-form-group">
                <label for="client-company"><?php _e('Company', 'freelance-erp-manager'); ?></label>
                <input type="text" name="company" id="client-company">
            </div>
            
            <div class="ferp-form-group">
                <label for="client-address"><?php _e('Address', 'freelance-erp-manager'); ?></label>
                <textarea name="address" id="client-address" rows="3"></textarea>
            </div>
            
            <div class="ferp-form-group">
                <label for="client-notes"><?php _e('Notes', 'freelance-erp-manager'); ?></label>
                <textarea name="notes" id="client-notes" rows="3"></textarea>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="ferp-btn-primary">
                    <span class="dashicons dashicons-yes"></span> <?php _e('Save Client', 'freelance-erp-manager'); ?>
                </button>
                <button type="button" class="button ferp-modal-close">
                    <?php _e('Cancel', 'freelance-erp-manager'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>

jQuery(document).ready(function($) {
    var clientsData = [];
    var clientsTable = null;
    var currencySymbol = '₹';
    
    // Load clients
    function loadClients() {
        $.post(FERP.ajax, {
            action: 'ferp_get_clients',
            nonce: FERP.nonce
        }, function(response) {
            if (response.success) {
                clientsData = response.data;
                updateStatistics();
                renderClients();
            } else {
                console.error('Error loading clients:', response);
                $('#ferp-clients-list').html('<tr><td colspan="6">Error loading clients</td></tr>');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            $('#ferp-clients-list').html('<tr><td colspan="6">Error loading clients</td></tr>');
        });
    }
    
    // Update statistics
    function updateStatistics() {
        var totalClients = clientsData.length;
        var totalProjects = 0;
        var totalRevenue = 0;
        var pendingAmount = 0;
        
        clientsData.forEach(function(client) {
            totalProjects += parseInt(client.total_projects) || 0;
            totalRevenue += parseFloat(client.total_revenue) || 0;
            pendingAmount += parseFloat(client.pending_amount) || 0;
        });
        
        $('#stat-total-clients').text(totalClients);
        $('#stat-active-projects').text(totalProjects);
        $('#stat-total-revenue').text(currencySymbol + totalRevenue.toFixed(2));
        $('#stat-pending-amount').text(currencySymbol + pendingAmount.toFixed(2));
    }
    
  // Render clients with DataTables
    function renderClients() {
        if (clientsTable) {
            clientsTable.destroy();
        }
        
        var html = '';
        if (clientsData.length === 0) {
            html = '<tr><td colspan="6"><div class="ferp-empty-state">';
            html += '<span class="dashicons dashicons-groups"></span>';
            html += '<h3>No clients found</h3>';
            html += '<p>Click "Add Client" to create your first client</p>';
            html += '</div></td></tr>';
            $('#ferp-clients-list').html(html);
            return;
        }
        
        clientsData.forEach(function(client) {
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(client.name) + '</strong></td>';
            html += '<td>' + escapeHtml(client.email) + '</td>';
            html += '<td>' + (client.phone || '-') + '</td>';
            html += '<td>' + (client.company || '-') + '</td>';
            html += '<td><div class="client-stats-mini">';
            html += '<span><span class="dashicons dashicons-portfolio"></span> ' + (client.total_projects || 0) + ' projects</span>';
            html += '<span><span class="dashicons dashicons-money-alt"></span> ' + currencySymbol + parseFloat(client.total_revenue || 0).toFixed(0) + '</span>';
            html += '</div></td>';
            html += '<td><div class="action-buttons">';
            html += '<button class="action-btn ferp-edit-client" data-id="' + client.id + '" title="Edit"><span class="dashicons dashicons-edit"></span></button>';
            html += '<button class="action-btn delete ferp-delete-client" data-id="' + client.id + '" title="Delete"><span class="dashicons dashicons-trash"></span></button>';
            html += '</div></td>';
            html += '</tr>';
        });
        
        $('#ferp-clients-list').html(html);
        
        // Initialize DataTable
        clientsTable = $('#ferp-clients-table').DataTable({
            pageLength: 10,
            order: [[0, 'asc']],
            language: {
                search: "Search clients:",
                lengthMenu: "Show _MENU_ clients per page",
                info: "Showing _START_ to _END_ of _TOTAL_ clients",
                infoEmpty: "No clients available",
                zeroRecords: "No matching clients found"
            }
        });
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Add client
    $('#ferp-add-client').click(function(e) {
        e.preventDefault();
        $('#ferp-client-form')[0].reset();
        $('#client-id').val('');
        $('#client-phone-country-code').val('+91');
        $('#ferp-client-modal-title').text('Add Client');
        $('#ferp-client-modal').fadeIn();
    });
    
    // Edit client
    $(document).on('click', '.ferp-edit-client', function() {
        var clientId = $(this).data('id');
        var client = clientsData.find(c => c.id == clientId);
        
        if (client) {
            $('#client-id').val(client.id);
            $('#client-name').val(client.name);
            $('#client-email').val(client.email);
            
            // Parse phone number
            var phone = client.phone || '';
            var countryCode = '+91';
            var phoneNumber = phone;
            
            // Extract country code if present
            if (phone) {
                var phoneMatch = phone.match(/^(\+\d+)\s*(.*)$/);
                if (phoneMatch) {
                    countryCode = phoneMatch[1];
                    phoneNumber = phoneMatch[2];
                } else {
                    // Check each option to find matching code
                    $('#client-phone-country-code option').each(function() {
                        var code = $(this).val();
                        if (phone.startsWith(code)) {
                            countryCode = code;
                            phoneNumber = phone.substring(code.length).trim();
                            return false;
                        }
                    });
                }
            }
            
            $('#client-phone-country-code').val(countryCode);
            $('#client-phone').val(phoneNumber);
            $('#client-company').val(client.company || '');
            $('#client-address').val(client.address || '');
            $('#client-notes').val(client.notes || '');
            
            $('#ferp-client-modal-title').text('Edit Client');
            $('#ferp-client-modal').fadeIn();
        }
    });
    
    // Save client - FIXED VERSION
    $('#ferp-client-form').submit(function(e) {
        e.preventDefault();
        
        // Disable submit button to prevent double submission
        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Saving...');
        
        // Get phone values
        var countryCode = $('#client-phone-country-code').val() || '';
        var phoneNumber = $('#client-phone').val() || '';
        var fullPhone = '';
        
        // Only combine if phone number exists
        if (phoneNumber.trim()) {
            fullPhone = countryCode + ' ' + phoneNumber.trim();
        }
        
        var clientData = {
            id: $('#client-id').val(),
            name: $('#client-name').val().trim(),
            email: $('#client-email').val().trim(),
            phone: fullPhone,
            company: $('#client-company').val().trim(),
            address: $('#client-address').val().trim(),
            notes: $('#client-notes').val().trim()
        };
        
        console.log('Saving client data:', clientData);
        
        $.post(FERP.ajax, {
            action: 'ferp_save_client',
            nonce: FERP.nonce,
            client: clientData
        }, function(response) {
            console.log('Save response:', response);
            
            if (response.success) {
                $('#ferp-client-modal').fadeOut();
                $('#ferp-client-form')[0].reset();
                loadClients();
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data.message || 'Error saving client', 'error');
            }
            
            // Re-enable submit button
            $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Client');
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            showNotice('Failed to save client. Please check console for details.', 'error');
            
            // Re-enable submit button
            $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Client');
        });
    });
    
    // Delete client
    $(document).on('click', '.ferp-delete-client', function() {
        if (!confirm('Are you sure you want to delete this client?')) return;
        
        var clientId = $(this).data('id');
        $.post(FERP.ajax, {
            action: 'ferp_delete_client',
            nonce: FERP.nonce,
            id: clientId
        }, function(response) {
            if (response.success) {
                loadClients();
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data.message, 'error');
            }
        }).fail(function(xhr, status, error) {
            console.error('Delete error:', error);
            showNotice('Failed to delete client', 'error');
        });
    });
    
    // Export clients
    $('#ferp-export-clients').click(function() {
        $.post(FERP.ajax, {
            action: 'ferp_export_clients',
            nonce: FERP.nonce
        }, function(response) {
            if (response.success) {
                // Create download link
                var blob = new Blob([response.data.csv], { type: 'text/csv' });
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = response.data.filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                showNotice('Clients exported successfully', 'success');
            } else {
                showNotice('Export failed', 'error');
            }
        }).fail(function() {
            showNotice('Export failed', 'error');
        });
    });
    
    // Close modal
    $('.ferp-modal-close').click(function() {
        $('.ferp-modal').fadeOut();
        $('#ferp-client-form')[0].reset();
    });
    
    $(window).click(function(event) {
        if ($(event.target).hasClass('ferp-modal')) {
            $('.ferp-modal').fadeOut();
            $('#ferp-client-form')[0].reset();
        }
    });
    
    // Show notice
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap').prepend(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Initial load
    loadClients();
});
</script>