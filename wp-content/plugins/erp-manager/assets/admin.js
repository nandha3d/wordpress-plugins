/**
 * FERP Modular Manager - Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // FORCE REMOVE ALL ADMIN NOTICES ON MODULE PAGE
    $(window).on('load', function() {
        $('#wpbody-content > .notice, #wpbody-content > .updated, #wpbody-content > .error, .update-nag').each(function() {
            if (!$(this).closest('.ferp-modular-wrap').length) {
                $(this).remove();
            }
        });
        
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                $(mutation.addedNodes).each(function() {
                    if ($(this).hasClass('notice') || $(this).hasClass('updated') || $(this).hasClass('error')) {
                        if (!$(this).closest('.ferp-modular-wrap').length) {
                            $(this).remove();
                        }
                    }
                });
            });
        });
        
        observer.observe(document.getElementById('wpbody-content'), {
            childList: true,
            subtree: false
        });
    });
    
    // Toggle All Modules
    $('#toggle-all-modules').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.module-toggle-checkbox').each(function() {
            if ($(this).prop('checked') !== isChecked) {
                $(this).prop('checked', isChecked).trigger('change');
            }
        });
    });
    
    // Filter Tabs
    $('.ferp-tab-button').on('click', function() {
        $('.ferp-tab-button').removeClass('active');
        $(this).addClass('active');
        
        var filter = $(this).data('filter');
        
        if (filter === 'all') {
            $('.ferp-module-toggle-item').show();
        } else {
            $('.ferp-module-toggle-item').each(function() {
                // You can add data-category attribute to modules for filtering
                var category = $(this).data('category') || 'core';
                if (category === filter) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });
    
    // Toggle individual module
    $('.module-toggle-checkbox').on('change', function() {
        var $checkbox = $(this);
        var module = $checkbox.data('module');
        var isChecked = $checkbox.prop('checked');
        var action = isChecked ? 'activate' : 'deactivate';
        
        console.log('Toggle module:', module, action);
        
        // Disable checkbox during processing
        $checkbox.prop('disabled', true);
        
        $.post(ferpModular.ajax, {
            action: 'ferp_toggle_module',
            nonce: ferpModular.nonce,
            module: module,
            toggle_action: action
        }, function(response) {
            console.log('Response:', response);
            
            if (response.success) {
                showNotice(response.data.message, 'success');
                
                // Update the data-action attribute
                $checkbox.data('action', isChecked ? 'deactivate' : 'activate');
                
                // Re-enable checkbox
                $checkbox.prop('disabled', false);
                
                // Optional: Reload page after short delay
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showNotice(response.data.message || 'Unknown error', 'error');
                
                // Revert checkbox state
                $checkbox.prop('checked', !isChecked);
                $checkbox.prop('disabled', false);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            showNotice('An error occurred: ' + error, 'error');
            
            // Revert checkbox state
            $checkbox.prop('checked', !isChecked);
            $checkbox.prop('disabled', false);
        });
    });
    
    // Delete module
    $('.ferp-module-delete-btn').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(ferpModular.i18n.confirm_delete)) {
            return;
        }
        
        var $button = $(this);
        var module = $button.data('module');
        var $item = $button.closest('.ferp-module-toggle-item');
        
        $button.prop('disabled', true);
        $item.css('opacity', '0.5');
        
        $.post(ferpModular.ajax, {
            action: 'ferp_delete_module',
            nonce: ferpModular.nonce,
            module: module
        }, function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                $item.fadeOut(300, function() {
                    $(this).remove();
                    
                    if ($('.ferp-module-toggle-item').length === 0) {
                        location.reload();
                    }
                });
            } else {
                showNotice(response.data.message, 'error');
                $button.prop('disabled', false);
                $item.css('opacity', '1');
            }
        }).fail(function() {
            showNotice('An error occurred. Please try again.', 'error');
            $button.prop('disabled', false);
            $item.css('opacity', '1');
        });
    });
    
    // File upload handling
    var $uploadArea = $('#ferp-upload-area');
    var $fileInput = $('#module-zip-input');
    
    $('#browse-button').on('click', function() {
        $fileInput.click();
    });
    
    $uploadArea.on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('ferp-upload-icon') || $(e.target).hasClass('dashicons')) {
            $fileInput.click();
        }
    });
    
    $fileInput.on('change', function() {
        if (this.files.length > 0) {
            uploadModule(this.files[0]);
        }
    });
    
    $uploadArea.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    $uploadArea.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });
    
    $uploadArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            uploadModule(files[0]);
        }
    });
    
    function uploadModule(file) {
        if (!file.name.endsWith('.zip')) {
            showNotice('Please upload a ZIP file', 'error');
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'ferp_install_module');
        formData.append('nonce', ferpModular.nonce);
        formData.append('module_zip', file);
        
        var originalContent = $uploadArea.html();
        $uploadArea.html(
            '<div class="ferp-upload-icon"><span class="dashicons dashicons-update-alt"></span></div>' +
            '<p>' + ferpModular.i18n.uploading + '</p>'
        );
        $uploadArea.css('pointer-events', 'none');
        
        var $spinner = $uploadArea.find('.dashicons');
        var rotation = 0;
        var spinInterval = setInterval(function() {
            rotation += 45;
            $spinner.css('transform', 'rotate(' + rotation + 'deg)');
        }, 100);
        
        $.ajax({
            url: ferpModular.ajax,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                clearInterval(spinInterval);
                
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice(response.data.message, 'error');
                    $uploadArea.html(originalContent);
                    $uploadArea.css('pointer-events', 'auto');
                }
            },
            error: function() {
                clearInterval(spinInterval);
                showNotice('Upload failed. Please try again.', 'error');
                $uploadArea.html(originalContent);
                $uploadArea.css('pointer-events', 'auto');
            }
        });
    }
    
    function showNotice(message, type) {
        type = type || 'info';
        
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.ferp-modular-wrap').prepend($notice);
        
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        $('html, body').animate({
            scrollTop: $notice.offset().top - 100
        }, 300);
    }
});