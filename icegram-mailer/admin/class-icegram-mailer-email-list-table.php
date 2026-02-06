<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Icegram_Mailer_Email_List_Table extends Icegram_Mailer_List_Table {
	/**
	 * Contact lists status array
	 *
	 * @since 4.0.0
	 * @var array
	 */
	public $contact_lists_statuses = array();

	/**
	 * Number of contacts per page
	 *
	 * @since 4.2.1
	 *
	 * @var string
	 */
	public static $option_per_page = 'emails_per_page';

	/**
	 * Array of list ids
	 *
	 * @since 4.0.0
	 * @var array
	 */
	public $list_ids = array();

	/**
	 * List name mapped to id
	 *
	 * @since 4.0.0
	 * @var array
	 */
	public $lists_id_name_map = array();

	/**
	 * Last opened at
	 *
	 * @since 4.6.5
	 * @var array
	 */
	public $items_data = array();

	/**
	 * Contacts database object
	 *
	 * @var object|ES_DB_Contacts
	 */
	public $db;

	/**
	 * ES_Contacts_Table constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Email Log', 'icegram-mailer' ),
				'plural'   => __( 'Email Logs', 'icegram-mailer' ),
				'ajax'     => false,
				'screen'   => 'icegram_mailer_dashboard',
			)
		);

		$this->db = new Icegram_Mailer_Logs_Table();
	}

	/**
	 * Add Screen Option
	 *
	 * @since 4.2.1
	 */
	public static function screen_options() {

		if ( empty( $action ) ) {

			$option = 'per_page';
			$args   = array(
				'label'   => __( 'Number of email logs per page', 'icegram-mailer' ),
				'default' => 100,
				'option'  => self::$option_per_page,
			);

			add_screen_option( $option, $args );
		}

	}

	/**
	 * Render Audience View
	 *
	 * @since 4.2.1
	 */
	public function render() {
		?>
		<div class="font-sans">
			<div id="poststuff" class="icegram-mailer-email-log-list icegram-mailer-items-list">
				<div id="post-body" class="metabox-holder column-1">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="get">
								<?php
								// Display search field and other available filter fields.
								$this->prepare_items();
								?>
							</form>
							<form method ='post'>
								<?php
								$this->display();
								?>
							</form>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
			<?php
	}

	/**
	 * Retrieve subscribers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function get_lists( $per_page = 5, $page_number = 1, $do_count_only = false ) {
		$args = array(
			'per_page'    => $per_page,
			'page_number' => $page_number,
			'do_count_only' => $do_count_only
		);

		return Icegram_Mailer_Dashboard_Controller::get_lists( $args );
		
	}


	/**
	 * No contacts available
	 *
	 * @since 4.0.0
	 */
	public function no_items() {
		esc_html_e( 'No email logs available.', 'icegram-mailer' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 *
	 * @since 4.0.0
	 */
	public function column_default( $item, $column_name ) {
		$item = apply_filters( 'icegram_mailer_email_logs_data', $item, $column_name, $this );
		switch ( $column_name ) {
			case 'created_at':
				return icegram_mailer_convert_gmt_timestamp_to_local_date( $item[ 'created_at' ] );
			case 'opened_at':
				return ! empty( $item[ 'opened_at' ] ) ? icegram_mailer_convert_gmt_timestamp_to_local_date( $item[ 'opened_at' ] ) : '-';
			case 'status':
				$status = $item[ 'status' ];
				return ! empty( $status ) ? ( '<span class="icegram-mailer-email-' . $item['status'] . '" ' . ( 'failed' === $status ? 'title="' . $item['error'] . '"' : '' ) . '>' . ucfirst( $status ) . '</span>' ) : '-';
			default:
				$column_data = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '-';
				return apply_filters( 'icegram_mailer_contact_column_data', $column_data, $column_name, $item, $this );
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 *
	 * @since 4.0.0
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" class="checkbox" name="subscribers[]" value="%s"/>',
			$item['id']
		);
	}

	/**
	 * Associative array of columns
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_columns() {

		$columns = array(
			'subject' => __( 'Subject', 'icegram-mailer' ),
			'to' => __( 'To', 'icegram-mailer' ),
			'status' => __( 'Status', 'icegram-mailer' ),
			'created_at' => __( 'Sent at', 'icegram-mailer' ),
			'opened_at' => __( 'Opened at', 'icegram-mailer' ),
		);

		return $columns;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 *
	 * @since 4.0.0
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		$per_page     = $this->get_items_per_page( self::$option_per_page, 20 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$contacts = $this->get_lists( $per_page, $current_page );

		$this->items = $contacts;
	}
}
