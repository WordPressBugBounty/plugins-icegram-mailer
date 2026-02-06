<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Icegram_Mailer_List_Table extends WP_List_Table {

	/**
	 * Hide top pagination
	 *
	 * @param string $which
	 *
	 * @since 4.6.6
	 */
	public function pagination( $which ) {

		if ( 'bottom' == $which ) {
			parent::pagination( $which );
		}
	}

}
