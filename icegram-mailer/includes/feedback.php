<?php
function ig_mailer_add_deactivation_reasons( $options ) {

	$existing_options = array();
	foreach ( $options as $option ) {
		if ( isset( $option['slug'] ) ) {
			$existing_options[ $option['slug'] ] = $option;
		}
	}

	$new_slugs = array(
		'i-no-longer-need-the-plugin' => __( 'I no longer need the plugin', 'icegram-mailer' ),
		'i-could-not-get-the-plugin-to-work' => __( 'I couldn\'t get the plugin to work', 'icegram-mailer' ),
		'my-free-email-limit-has-been-exhausted' => __( 'My free email limit has been exhausted', 'icegram-mailer' ),
		'i-am-switching-to-a-different-plugin' => __( 'I\'m switching to a different plugin', 'icegram-mailer' ),
		'installed-plugin-unknowingly' => __( 'Installed plugin unknowingly', 'icegram-mailer' ),
		'other' => __( 'Other', 'icegram-mailer' ),
	);

	$new_options = array();
	foreach ( $new_slugs as $slug => $title ) {
		if ( isset( $existing_options[ $slug ] ) ) {
			$option = $existing_options[ $slug ];
			$option['title'] = $title;
		} else {
			$option = array(
				'title' => $title,
				'slug'  => $slug,
			);
		}

		$new_options[] = $option;
	}

	return $new_options;
}
add_filter( 'ig_mailer_deactivation_reasons', 'ig_mailer_add_deactivation_reasons' );

/**
 * Get additional system & plugin specific information for feedback
 */
if ( ! function_exists( 'ig_mailer_get_additional_info' ) ) {

	function ig_mailer_get_additional_info( $additional_info = array(), $system_info = false ) {
		global $icegram_mailer_tracker;

		$additional_info['version'] = ICEGRAM_MAILER_VERSION;

		if ( $system_info ) {
			$additional_info['active_plugins']   = implode( ', ', $icegram_mailer_tracker::get_active_plugins() );
			$additional_info['inactive_plugins'] = implode( ', ', $icegram_mailer_tracker::get_inactive_plugins() );
			$additional_info['current_theme']    = $icegram_mailer_tracker::get_current_theme_info();
			$additional_info['wp_info']          = $icegram_mailer_tracker::get_wp_info();
			$additional_info['server_info']      = $icegram_mailer_tracker::get_server_info();
		}

		$admin_email = get_option( 'admin_email' );
		$user        = get_user_by( 'email', $admin_email );
		$admin_name  = '';
		if ( $user instanceof WP_User ) {
			$admin_name = $user->display_name;
		}

		$additional_info['email'] = $admin_email;
		$additional_info['name']  = $admin_name;

		return $additional_info;
	}
}

add_filter( 'ig_mailer_additional_feedback_meta_info', 'ig_mailer_get_additional_info', 10, 2 );


if ( ! function_exists( 'ig_mailer_subscribe_to_plugin_deactivation_list' ) ) {
	function ig_mailer_subscribe_to_plugin_deactivation_list( $data ) {
		
		$admin_email = get_bloginfo( 'admin_email' );
		$user        = get_user_by( 'email', $admin_email );
		$admin_name  = '';
		if ( $user instanceof WP_User ) {
			$admin_name = $user->display_name;
		}

		$email = $admin_email;
		$name  = $admin_name;

		switch ( $data['feedback']['value'] ) {
			case 'i-could-not-get-the-plugin-to-work':
				$list = '6a7aacc98417';
				break;
			
			case 'my-free-email-limit-has-been-exhausted':
				$list = '043753cf0041';
				break;
			
			case 'i-am-switching-to-a-different-plugin':
				$list = 'd0d2b6bb23c7';
				break;
			
			case 'installed-plugin-unknowingly':
				$list = 'c7ce22022ce5';
				break;

			default:
				$list = '';
				break;
		}

		if ( ! empty( $list ) && is_email( $email ) ) {

			$url_params = array(
			'ig_es_external_action' => 'subscribe',
			'name'                  => $name,
			'email'                 => $email,
			'list'                  => $list,
			);

			$ip_address = icegram_mailer_get_ip();
			if ( ! empty( $ip_address ) && 'UNKNOWN' !== $ip_address ) {
				$url_params['ip_address'] = $ip_address;
			}

			$ig_url = 'https://www.icegram.com/';
			$ig_url = add_query_arg( $url_params, $ig_url );

			$args = array(
			'timeout' => 15,
			'blocking'  => false,
			);

			// Make a get request.
			wp_remote_get( $ig_url, $args );
		}
	}
}
add_action( 'ig_mailer_deactivation_feedback_submitted', 'ig_mailer_subscribe_to_plugin_deactivation_list' );