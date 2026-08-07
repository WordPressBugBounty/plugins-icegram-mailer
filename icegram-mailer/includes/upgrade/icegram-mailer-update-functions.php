<?php
/**
 * Functions for updating data, used by the background updater.
 */
// phpcs:disable
defined( 'ABSPATH' ) || exit;

/* --------------------- IG Mailer 1.0.14 (Start)--------------------------- */
/**
 * To add source column in log table
 * IG Mailer version 1.0.14 onwards
 */
function icegram_mailer_update_1014_add_source_column() {
    
    global $wpdb;

    $table_name = $wpdb->prefix . 'icegram_mailer_email_logs';

 	$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table_name}", 0 );

 	if ( ! in_array( 'source', $columns, true ) ) {
		$wpdb->query(
			"ALTER TABLE {$table_name}
			ADD COLUMN `source` VARCHAR(255) DEFAULT 'unknown' AFTER `error`"
		);
	}
 
}

function icegram_mailer_update_1014_db_version() {
	Icegram_Mailer_Installer::update_db_version( '1.0.14' );
} 
