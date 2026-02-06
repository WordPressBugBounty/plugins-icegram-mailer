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


	}
}

Icegram_Mailer_Dashboard_Controller::get_instance();
