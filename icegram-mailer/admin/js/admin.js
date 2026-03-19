if ( 'undefined' !== typeof wp.i18n ) {
    var __ = wp.i18n.__;
} else {
    // Create a dummy fallback function incase i18n library isn't available.
    var __ = ( text, textDomain ) => {
        return text;
    }
}

jQuery(document).ready(function($) {
    jQuery(document).on('click', '#icegram-mailer-send-test-email', function (e) {
        e.preventDefault();
        var sendButton = $(this);
        var test_email = $('#icegram-mailer-test-email').val();
        if (test_email) {
            var params = {
                action: 'icegram-mailer',
                method: 'send_test_email',
                handler: 'settings',
                data: {
                    test_email,
                },
                _wpnonce: icegram_mailer_admin_js_data._wpnonce
            };
            $(sendButton).find('.loader').removeClass('hidden');
            $(sendButton).find('.button-text').html(__( 'Sending', 'icegram-mailer' ));
            jQuery.ajax({
                method: 'POST',
                url: ajaxurl,
                data: params,
                dataType: 'json',
                success: function (response) {
                    if (response && typeof response.status !== 'undefined' && response.status == "success") {
                        let successMessageHTML = '<span style="color:green">' + __( 'Email has been sent. Please check your inbox', 'icegram-mailer' ) + '</span>';
                        //$('#es-send-test').parent().find('.helper').html(successMessageHTML);
                        $('#icegram-mailer-send-result-message').html(successMessageHTML);	
                    } else {
                        let errorMessageHTML = '<span style="color:#e66060"><strong>' + __( 'Sending error', 'icegram-mailer' ) + '</strong>: ' + ( Array.isArray( response.message ) ? response.message.join() : response.message ) + '</span>';
                        //$('#icegram-mailer-send-result-message').parent().find('.helper').html(errorMessageHTML);
                        $('#icegram-mailer-send-result-message').html(errorMessageHTML);	
                    }

                    $(sendButton).find('.loader').addClass('hidden');
                    $(sendButton).find('.button-text').html(__( 'Send Email', 'icegram-mailer' ));
                },

                error: function (err) {
                    $(sendButton).find('.loader').addClass('hidden');
                    $(sendButton).find('.button-text').html(__( 'Send Email', 'icegram-mailer' ));
                }
            });
        } else {
            confirm(__('Add test email','icegram-mailer' ));
        }

    });

    jQuery(document).on('click', '.icegram-mailer-admin-notice.notice[data-notice-id] .notice-dismiss, .icegram-mailer-admin-notice.notice[data-notice-id] .icegram-mailer-dismiss-notice', function(e) {
        var notice   = jQuery(this).closest('.notice');
        var noticeId = notice.data('notice-id');
        
        jQuery.post(ajaxurl, {
            action: 'dismiss_' + noticeId + '_notice',
            _wpnonce: icegram_mailer_admin_js_data._wpnonce
        });

        jQuery(notice).fadeTo(100, 0, function() {
            jQuery(notice).slideUp(100, function() {
                jQuery(notice).remove()
            })
        });
    });

    // Custom modal functions
    var modalActions = {};
    
    function showLoadingModal(message) {
        var modalHtml = '<div class="icegram-mailer-modal-overlay icegram-mailer-loading-modal">' +
            '<div class="icegram-mailer-modal">' +
            '<div class="icegram-mailer-modal-body" style="text-align: center; padding: 32px 24px;">' +
            '<div class="icegram-mailer-spinner"></div>' +
            '<p style="margin-top: 16px; font-weight: 500;">' + message + '</p>' +
            '</div>' +
            '</div></div>';
        
        jQuery('body').append(modalHtml);
        
        setTimeout(function() {
            jQuery('.icegram-mailer-loading-modal').addClass('show');
        }, 10);
    }
    
    function hideLoadingModal() {
        jQuery('.icegram-mailer-loading-modal').removeClass('show');
        setTimeout(function() {
            jQuery('.icegram-mailer-loading-modal').remove();
        }, 300);
    }
    
    function showIcegramModal(title, message, type, buttons) {
        var modalId = 'modal-' + Date.now();
        var modalHtml = '<div class="icegram-mailer-modal-overlay" data-modal-id="' + modalId + '">' +
            '<div class="icegram-mailer-modal">' +
            '<div class="icegram-mailer-modal-header ' + type + '">' +
            '<h3>' + title + '</h3>' +
            '</div>' +
            '<div class="icegram-mailer-modal-body">' +
            '<p>' + message + '</p>' +
            '</div>' +
            '<div class="icegram-mailer-modal-footer">';
        
        modalActions[modalId] = {};
        buttons.forEach(function(btn, index) {
            var btnId = 'btn-' + index;
            modalActions[modalId][btnId] = btn.action;
            modalHtml += '<button class="' + btn.class + '" data-btn-id="' + btnId + '">' + btn.text + '</button>';
        });
        
        modalHtml += '</div></div></div>';
        
        jQuery('body').append(modalHtml);
        
        setTimeout(function() {
            jQuery('.icegram-mailer-modal-overlay[data-modal-id="' + modalId + '"]').addClass('show');
        }, 10);
    }
    
    function closeIcegramModal() {
        var modalId = jQuery('.icegram-mailer-modal-overlay').data('modal-id');
        if (modalId && modalActions[modalId]) {
            delete modalActions[modalId];
        }
        jQuery('.icegram-mailer-modal-overlay').removeClass('show');
        setTimeout(function() {
            jQuery('.icegram-mailer-modal-overlay').remove();
        }, 300);
    }
    
    // Handle modal button clicks
    jQuery(document).on('click', '.icegram-mailer-modal-footer button', function() {
        var btnId = jQuery(this).data('btn-id');
        var modalId = jQuery(this).closest('.icegram-mailer-modal-overlay').data('modal-id');
        
        if (modalId && modalActions[modalId] && modalActions[modalId][btnId]) {
            var action = modalActions[modalId][btnId];
            closeIcegramModal();
            if (action) {
                action();
            }
        } else {
            closeIcegramModal();
        }
    });
    
    // Close modal on overlay click
    jQuery(document).on('click', '.icegram-mailer-modal-overlay', function(e) {
        if (jQuery(e.target).hasClass('icegram-mailer-modal-overlay')) {
            closeIcegramModal();
        }
    });

    // Handle resend email action
    jQuery(document).on('click', '.icegram-mailer-resend-email', function(e) {
        e.preventDefault();
        
        var emailId = jQuery(this).data('email-id');
        
        // Show custom confirmation modal
        showIcegramModal(
            __('Confirm Resend', 'icegram-mailer'),
            __('Are you sure you want to resend this email?', 'icegram-mailer'),
            'confirm',
            [
                {
                    text: __('Cancel', 'icegram-mailer'),
                    class: 'icegram-mailer-modal-btn-secondary',
                    action: null
                },
                {
                    text: __('OK', 'icegram-mailer'),
                    class: 'icegram-mailer-modal-btn-primary',
                    action: function() {
                        // Show loading modal
                        showLoadingModal(__('Resending Email...', 'icegram-mailer'));
                        
                        jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: {
                                action: 'icegram-mailer',
                                handler: 'dashboard',
                                method: 'resend_email',
                                security: icegram_mailer_admin_js_data._wpnonce,
                                data: {
                                    email_id: emailId
                                }
                            },
                            dataType: 'json',
                            success: function(response) {
                                hideLoadingModal();
                                if (response && response.status === 'success') {
                                    showIcegramModal(
                                        __('Success', 'icegram-mailer'),
                                        __('Email resent successfully!', 'icegram-mailer'),
                                        'success',
                                        [
                                            {
                                                text: __('OK', 'icegram-mailer'),
                                                class: 'icegram-mailer-modal-btn-primary',
                                                action: function() {
                                                    location.reload();
                                                }
                                            }
                                        ]
                                    );
                                } else {
                                    showIcegramModal(
                                        __('Error', 'icegram-mailer'),
                                        (response.message || __('Unknown error occurred', 'icegram-mailer')),
                                        'error',
                                        [
                                            {
                                                text: __('OK', 'icegram-mailer'),
                                                class: 'icegram-mailer-modal-btn-primary',
                                                action: null
                                            }
                                        ]
                                    );
                                }
                            },
                            error: function(xhr, status, error) {
                                hideLoadingModal();
                                showIcegramModal(
                                    __('Error', 'icegram-mailer'),
                                    __('An error occurred. Please try again.', 'icegram-mailer'),
                                    'error',
                                    [
                                        {
                                            text: __('OK', 'icegram-mailer'),
                                            class: 'icegram-mailer-modal-btn-primary',
                                            action: null
                                        }
                                    ]
                                );
                            }
                        });
                    }
                }
            ]
        );
    });

    // Show success/error messages after bulk resend
    if (window.location.search.indexOf('resent=') > -1) {
        var urlParams = new URLSearchParams(window.location.search);
        var resentCount = urlParams.get('resent');
        var failedCount = urlParams.get('failed');
        
        var message = '';
        if (resentCount > 0) {
            message += resentCount + ' ' + __('email(s) resent successfully.', 'icegram-mailer');
        }
        if (failedCount > 0) {
            if (message) message += ' ';
            message += failedCount + ' ' + __('email(s) failed to resend.', 'icegram-mailer');
        }
        
        if (message) {
            var noticeClass = failedCount > 0 ? 'notice-warning' : 'notice-success';
            jQuery('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>')
                .insertAfter('.wp-header-end')
                .delay(5000)
                .fadeOut();
        }
        
        // Clean URL
        var cleanUrl = window.location.pathname + '?page=icegram_mailer_dashboard';
        window.history.replaceState({}, document.title, cleanUrl);
    }
});
