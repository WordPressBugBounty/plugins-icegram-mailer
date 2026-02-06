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
});
