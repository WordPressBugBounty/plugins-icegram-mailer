<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Icegram_Mailer_Admin_Ajax_Handler' ) ) {

	class Icegram_Mailer_Admin_Ajax_Handler {

		public function __construct() {
			add_action( 'wp_ajax_icegram_mailer', [ $this, 'handle_ajax_action' ] );
		}

		public function handle_ajax_action() {
		
			check_ajax_referer( 'icegram-mailer-admin-ajax-nonce' );
	
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
	
			$current_task = isset( $_POST['current_task'] ) ? sanitize_text_field( wp_unslash( $_POST['current_task'] ) ) : '';
	
			if ( empty( $current_task ) || ! is_callable( [ $this, $current_task ] ) ) {
				return;
			}
	
			$data = isset( $_POST['data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : [];
	
			$response = call_user_func( array( $this, $current_task ), $data );
			
			wp_send_json( $response );
		}

		public function send_test_email( $data ) {
			$test_email = isset( $data['test_email'] ) ? sanitize_email( $data['test_email'] ) : '';
			if ( ! is_email( $test_email ) ) {
				$response['status']  = 'error';
				$response['message'] = __( 'Email is invalid.', 'icegram-mailer' );
				return $response;
			}

			$subject = icegram_mailer()->client->get_test_email_subject( $test_email );
			$charset = get_bloginfo( 'charset' );
			$content = icegram_mailer()->client->get_test_email_content();
			$headers = array(
				'Content-Type: text/html; charset="' . $charset . '"',
			);
			$email_sent = wp_mail( $test_email, $subject, $content, $headers );
			if ( $email_sent ) {
				$response['status'] = 'success';
			} else {
				global $phpmailer;

				if ( is_object( $phpmailer ) && $phpmailer->ErrorInfo ) {
					$message = wp_strip_all_tags( $phpmailer->ErrorInfo );
				} else {
					$message = __( 'WP Mail Error: Unknown', 'icegram-mailer' );
				}

				$response['status']  = 'error';
				$response['message'] = $message;
			}

			return $response;
		}
	}

	new Icegram_Mailer_Admin_Ajax_Handler();
}
