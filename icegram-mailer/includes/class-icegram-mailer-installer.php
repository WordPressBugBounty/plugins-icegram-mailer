<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Icegram_Mailer_Installer' ) ) {

	/**
	 * Icegram_Mailer_Installer Class.
	 */
	class Icegram_Mailer_Installer {

		/**
		 * Background update class.
		 *
		 * @var object
		 */
		public static $logger;

		/**
		 * Added Logger Context
		 *
		 * @since 4.2.0
		 * @var array
		 */
		public static $logger_context = [
			'source' => 'icegram_mailer_db_updates',
		];

		/**
		 * DB updates and callbacks that need to be run per version.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private static $db_updates = [
			'1.0.14' => array(
				'icegram_mailer_update_1014_add_source_column',
				'icegram_mailer_update_1014_db_version',
			),
		];

		/**
		 * Init Install/ Update Process
		 *
		 * @since 4.0.0
		 */
		public static function init() {

			if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

				add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
				add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
			}
		}

		/**
		 * Install if required
		 *
		 * @since 4.0.0
		 */
		public static function check_version() {

			$current_db_version = get_option( 'icegram_mailer_db_version', '1.0.0' );

			// Get latest available DB update version
			$latest_db_version_to_update = self::get_latest_db_version_to_update();

			if ( version_compare( $current_db_version, $latest_db_version_to_update, '<' ) ) {

				error_log( 'Require to run updater..Start Installation/ Update process');

				self::install();
			}
			
		}


		/**
		 * Admin action triggers for manual database updates
		 *
		 * @since 1.0.14
		 */
		public static function install_actions() {
			if ( ! empty( $_GET['do_update_icegram_mailer'] ) ) {
				check_admin_referer( 'icegram_mailer_db_update', 'icegram_mailer_db_update_nonce' );
				$from_db_version = ! empty( $_GET['from_db_version'] ) ? sanitize_text_field( $_GET['from_db_version'] ) : '';

				delete_transient( 'icegram_mailer_installing' );

				if ( ! empty( $from_db_version ) ) {
					error_log( 'Forcefully update database from: ' . $from_db_version );
					self::update_db_version( $from_db_version );
				}

				self::update();
			}

			if ( ! empty( $_GET['force_update_icegram_mailer'] ) ) {
				check_admin_referer( 'icegram_mailer_force_db_update', 'icegram_mailer_force_db_update_nonce' );
				
				delete_transient( 'icegram_mailer_installing' );
				self::update();

				wp_safe_redirect( admin_url( 'admin.php?page=icegram_mailer_dashboard' ) );
				exit;
			}
		}


		/**
		 * Begin Installation
		 *
		 * @since 4.0.0
		 */
		public static function install() {

			// Create Files
			self::create_files();

			if ( ! is_blog_installed() ) {
				return;
			}

			// Check if we are not already running this routine.
			if ( 'yes' === get_transient( 'icegram_mailer_installing' ) ) {

				return;
			}

			if ( self::is_new_install() ) {

				// If we made it till here nothing is running yet, lets set the transient now.
				set_transient( 'icegram_mailer_installing', 'yes', MINUTE_IN_SECONDS * 10 );

				icegram_mailer_maybe_define_constant( 'ICEGRAM_MAILER_INSTALLING', true );

				// Create Tables
				self::create_tables();

				// Create Default Option
				self::create_options();
			}

			self::maybe_update_db_version(); 
			delete_transient( 'icegram_mailer_installing' );

		}

		/**
		 * Is this new Installation?
		 *
		 * @return bool
		 *
		 * @since 4.0.0
		 */
		public static function is_new_install() {
			return is_null( get_option( 'icegram_mailer_db_version', null ) );
		}

		/**
		 * Require DB updates?
		 *
		 * @return bool
		 *
		 * @since 4.0.0
		 */
		private static function needs_db_update() { 

			$current_db_version = icegram_mailer_get_db_version();
			
		    $latest_db_version_to_update = self::get_latest_db_version_to_update(); 
			  
			return ! is_null( $current_db_version ) && version_compare( $current_db_version, $latest_db_version_to_update, '<' );
		}


		/**
		 * Push all needed DB updates to the queue for processing.
		 *
		 * @since 1.0.14
		 */
		private static function update() {
			$current_db_version = icegram_mailer_get_db_version();

			foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
				if ( version_compare( $current_db_version, $version, '<' ) ) {
					foreach ( $update_callbacks as $update_callback ) {
						if ( function_exists( $update_callback ) ) {
							call_user_func( $update_callback );
						}
					}
				}
			}
		}

        /**
		 * Check whether database update require? If require do update.
		 *
		 * @since 4.0.0
		 */
		private static function maybe_update_db_version() {
            
			if ( self::needs_db_update() ) {
				
				if ( apply_filters( 'icegram_mailer_enable_auto_update_db', true ) ) { 
					self::update();
				}  
			} 
		}
		
		/**
		 * Get latest db version based on available updates.
		 *
		 * @return mixed
		 *
		 * @since 4.0.0
		 */
		public static function get_latest_db_version_to_update() {
			$updates         = self::get_db_update_callbacks();
			$update_versions = array_keys( $updates );
			usort( $update_versions, 'version_compare' );

			return end( $update_versions );
		}

		/**
		 * Get all database updates
		 *
		 * @return array
		 *
		 * @since 4.0.0
		 */
		public static function get_db_update_callbacks() {
			return self::$db_updates;
		}


		/**
		 * Update DB Version & DB Update history
		 *
		 * @param null $version
		 *
		 * @since 4.0.0
		 */
		public static function update_db_version( $version = null ) {

			$latest_db_version_to_update = self::get_latest_db_version_to_update();

			update_option( 'icegram_mailer_db_version', is_null( $version ) ? $latest_db_version_to_update : $version );

			if ( ! is_null( $version ) ) {
				$db_update_history_option = 'db_update_history';

				$ig_es_db_update_history_data = Icegram_Mailer_Common::get_icegram_mailer_option( $db_update_history_option, array() );

				$ig_es_db_update_history_data[ $version ] = icegram_mailer_get_current_date_time();

				Icegram_Mailer_Common::set_icegram_mailer_option( $db_update_history_option, $ig_es_db_update_history_data );
			}
		}

		/**
		 * Create files/ directory
		 *
		 * @since 4.1.13
		 */
		public static function create_files() {

			// Want to bypass creation of files?
			if ( apply_filters( 'icegram_mailer_install_skip_create_files', false ) ) {
				return;
			}

			$files = array(
				array(
					'base'    => ICEGRAM_MAILER_LOG_DIR,
					'file'    => '.htaccess',
					'content' => 'deny from all',
				),
				array(
					'base'    => ICEGRAM_MAILER_LOG_DIR,
					'file'    => 'index.html',
					'content' => '',
				),
			);

			foreach ( $files as $file ) {
				if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
					$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' );
					if ( $file_handle ) {
						fwrite( $file_handle, $file['content'] );
						fclose( $file_handle );
						error_log( 'Icegram Mailer: Created file ' . $file['file'] );
						
					}
				}
			}
		}
		
		/**
		 * Create default options while installing
		 *
		 * @since 4.0.0
		 */
		private static function create_options() {
			$options = self::get_options();
			foreach ( $options as $option => $values ) {
				add_option( $option, $values, '', false );
			}
		}

		/**
		 * Get default options
		 *
		 * @return array
		 *
		 * @since 4.0.0
		 */
		public static function get_options() {

			// We are setting latest_db_version as a icegram_mailer_db_version option while installation
			// So, we don't need to run the upgrade process again.
			$db_version = icegram_mailer_get_db_version();

			$options = array(
				'icegram_mailer_db_version' => $db_version,
			);

			return $options;
		}

		/**
		 * Create tables
		 *
		 * @param null $version
		 *
		 * @since 4.0.0
		 *
		 * @modify 4.4.9
		 */
		public static function create_tables( $version = null ) {

			global $wpdb;

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}
			
			$schema_fn = 'get_schema';

			$wpdb->hide_errors();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( self::$schema_fn( $collate ) );
		}

		/**
		 * Add new table
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @since 4.2.1
		 */
		public static function get_email_logs_table_schema( $collate = '' ) {

			global $wpdb;

			$tables = "
			CREATE TABLE `{$wpdb->prefix}icegram_mailer_email_logs` (
			  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			  `tracking_id` VARCHAR(255) NOT NULL,
			  `to` text NOT NULL,
			  `subject` text DEFAULT NULL,
			  `headers` text DEFAULT NULL,
			  `body` longtext DEFAULT NULL,
			  `attachments` varchar(255) DEFAULT NULL,
			  `status` varchar(255),
			  `error` varchar(255), 
			  `source` varchar(255) DEFAULT 'unknown',
			  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
			  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
			  `opened_at` int(11) UNSIGNED DEFAULT NULL DEFAULT 0,
			  PRIMARY KEY (id)
			) $collate;
		";

			return $tables;
		}

		/**
		 * Collect multiple version schema
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @since 4.2.0
		 */
		private static function get_schema( $collate = '' ) {

			$tables = self::get_email_logs_table_schema( $collate );

			return $tables;
		}
	}

	Icegram_Mailer_Installer::init();
}
