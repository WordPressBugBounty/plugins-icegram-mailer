<?php

if ( ! class_exists( 'Icegram_Mailer_Dashboard_Controller' ) ) {

	class Icegram_Mailer_Dashboard_Controller {

	/**
	 * Class instance.
	 *
	 * @var Onboarding instance
	 */
		protected static $instance = null;


		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public static function get_lists( $args = array()) {

			$do_count_only = ! empty( $args['do_count_only'] );
			return icegram_mailer()->email_logs_table->get_logs( $args, ARRAY_A, $do_count_only );

		}

		/**
		 * Resend email from logs
		 *
		 * @param array $data Contains email_id.
		 * @return array Response with status and message.
		 * @since 1.0.8
		 */
		public static function resend_email( $data ) {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Unable to resend email.', 'icegram-mailer' ),
			);

			// Validate email ID.
			$email_id = isset( $data['email_id'] ) ? absint( $data['email_id'] ) : 0;
			if ( empty( $email_id ) ) {
				$response['message'] = __( 'Invalid email ID.', 'icegram-mailer' );
				return $response;
			}

			// Get email from logs.
			$email_log = icegram_mailer()->email_logs_table->get( $email_id );

			if ( empty( $email_log ) ) {
				$response['message'] = __( 'Email not found in logs.', 'icegram-mailer' );
				return $response;
			}

			// Prepare email data for resending.
			$to      = $email_log['to'];
			$subject = $email_log['subject'];
			$body    = $email_log['body'];
			$headers = ! empty( $email_log['headers'] ) && icegram_mailer_is_valid_json( $email_log['headers'] )
				? json_decode( $email_log['headers'], true )
				: $email_log['headers'];
			$attachments = ! empty( $email_log['attachments'] ) && icegram_mailer_is_valid_json( $email_log['attachments'] )
				? json_decode( $email_log['attachments'], true )
				: array();

			// Build message.
			$email = array(
				'to'          => $to,
				'subject'     => $subject,
				'message'     => $body,
				'headers'     => $headers,
				'attachments' => $attachments,
			);

			$message = icegram_mailer()->client->build_message( $email );

			// Store the original tracking ID to update the same log entry.
			$original_tracking_id = $email_log['tracking_id'];
			$message->tracking_id = $original_tracking_id;

			// Temporarily disable logging of new entry.
			remove_action( 'icegram_mailer_after_email_processed', array( icegram_mailer()->client, 'log_email' ), 10 );

			// Send email.
			$mailer = new Icegram_Mailer_ESS_Mailer();
			$result = $mailer->send( $message );

			// Re-enable logging.
			add_action( 'icegram_mailer_after_email_processed', array( icegram_mailer()->client, 'log_email' ), 10, 2 );

			if ( ! is_wp_error( $result ) ) {
				// Update the existing log entry.
				icegram_mailer()->email_logs_table->update(
					$email_id,
					array(
						'status'     => 'sent',
						'error'      => '',
						'updated_at' => icegram_mailer_get_current_gmt_timestamp(),
					)
				);

				$response['status']  = 'success';
				$response['message'] = __( 'Email resent successfully!', 'icegram-mailer' );
			} else {
				// Update with new error if resend also failed.
				icegram_mailer()->email_logs_table->update(
					$email_id,
					array(
						'status'     => 'failed',
						'error'      => $result->get_error_message(),
						'updated_at' => icegram_mailer_get_current_gmt_timestamp(),
					)
				);

				$response['message'] = $result->get_error_message();
			}

			return $response;
		}

		/**
		 * Get email details for preview
		 *
		 * @param array $data Contains email_id.
		 * @return array Response with email details.
		 * @since 1.0.8
		 */
		public static function get_email_details( $data ) {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Unable to retrieve email.', 'icegram-mailer' ),
			);

			$email_id = isset( $data['email_id'] ) ? absint( $data['email_id'] ) : 0;
			if ( empty( $email_id ) ) {
				return $response;
			}

			$email_log = icegram_mailer()->email_logs_table->get( $email_id );

			if ( ! empty( $email_log ) ) {
				$response['status'] = 'success';
				$response['data']   = $email_log;
			}

			return $response;
		}


	}
}

Icegram_Mailer_Dashboard_Controller::get_instance();
