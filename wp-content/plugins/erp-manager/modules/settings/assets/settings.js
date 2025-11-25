/**
 * FERP Settings Module - JavaScript
 * File: modules/settings/assets/settings.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    let mediaUploader;
    let qrUploader;
    
    /**
     * Upload Company Logo
     */
    $('#upload-logo-btn').on('click', function(e) {
        e.preventDefault();
        
        // If the uploader exists, open it
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Create a new media uploader
        mediaUploader = wp.media({
            title: 'Select Company Logo',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When an image is selected
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Update preview
            $('#company-logo-preview').html(
                '<img src="' + attachment.url + '" alt="Company Logo">'
            );
            
            // Update hidden field
            $('#company-logo-url').val(attachment.url);
            
            // Show remove button
            if ($('#remove-logo-btn').length === 0) {
                $('.ferp-logo-buttons').append(
                    '<button type="button" class="button" id="remove-logo-btn">' +
                    '<span class="dashicons dashicons-no"></span> Remove Logo</button>'
                );
            }
        });
        
        mediaUploader.open();
    });
    
    /**
     * Remove Company Logo
     */
    $(document).on('click', '#remove-logo-btn', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to remove the logo?')) {
            return;
        }
        
        // Reset preview
        $('#company-logo-preview').html(
            '<div class="ferp-logo-placeholder">' +
            '<span class="dashicons dashicons-format-image"></span>' +
            '<p>No logo uploaded</p>' +
            '</div>'
        );
        
        // Clear hidden field
        $('#company-logo-url').val('');
        
        // Remove button
        $(this).remove();
    });
    
    /**
     * Upload Payment QR Code
     */
    $('#upload-qr-btn').on('click', function(e) {
        e.preventDefault();
        
        // If the uploader exists, open it
        if (qrUploader) {
            qrUploader.open();
            return;
        }
        
        // Create a new media uploader
        qrUploader = wp.media({
            title: 'Select Payment QR Code',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When an image is selected
        qrUploader.on('select', function() {
            const attachment = qrUploader.state().get('selection').first().toJSON();
            
            // Update preview
            $('#payment-qr-preview').html(
                '<img src="' + attachment.url + '" alt="Payment QR Code">' +
                '<p>Scan to pay with UPI app</p>'
            );
            
            // Update hidden field
            $('#payment-qrcode-url').val(attachment.url);
            
            // Show remove button
            if ($('#remove-qr-btn').length === 0) {
                $('.ferp-qr-buttons').append(
                    '<button type="button" class="button" id="remove-qr-btn">' +
                    '<span class="dashicons dashicons-no"></span> Remove QR Code</button>'
                );
            }
        });
        
        qrUploader.open();
    });
    
    /**
     * Remove Payment QR Code
     */
    $(document).on('click', '#remove-qr-btn', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to remove the QR code?')) {
            return;
        }
        
        // Reset preview
        $('#payment-qr-preview').html(
            '<div class="ferp-qr-placeholder">' +
            '<span class="dashicons dashicons-smartphone"></span>' +
            '<p>No QR code uploaded</p>' +
            '</div>'
        );
        
        // Clear hidden field
        $('#payment-qrcode-url').val('');
        
        // Remove button
        $(this).remove();
    });
    
    /**
     * Save Settings Form
     */
    $('#ferp-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $('#save-settings-btn');
        const formData = new FormData(this);
        
        // Add AJAX action
        formData.append('action', 'ferp_save_settings');
        formData.append('nonce', FERP.nonce);
        
        // Disable button
        $btn.prop('disabled', true)
            .html('<span class="dashicons dashicons-update-alt spin"></span> Saving...');
        
        $.ajax({
            url: FERP.ajax,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    
                    // Update logo URL if changed
                    if (response.data.logo_url) {
                        $('#company-logo-url').val(response.data.logo_url);
                    }
                } else {
                    showNotice('error', response.data.message || 'Error saving settings');
                }
            },
            error: function() {
                showNotice('error', 'Connection error. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false)
                    .html('<span class="dashicons dashicons-saved"></span> Save Settings');
            }
        });
    });
    
    /**
     * Show Notice
     */
    function showNotice(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $(
            '<div class="notice ' + noticeClass + ' is-dismissible ferp-settings-notice">' +
            '<p>' + message + '</p>' +
            '</div>'
        );
        
        $('body').append($notice);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Manual dismiss
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Add spinning animation for dashicons
     */
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .dashicons.spin {
            animation: spin 1s linear infinite;
        }
    `;
    document.head.appendChild(style);
});