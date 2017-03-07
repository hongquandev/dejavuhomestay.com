<?php
/**
 * WP_List_Table_Helper Class File.
 * @package Core
 * @author Flipper Code <hello@flippercode.com>
 */

if ( ! class_exists( 'WP_List_Table_Frontend_Helper' ) ) {

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ); }

	/**
	 * Extend WP_LIST_TABLE to simplify table listing.
	 * @package Core
	 * @author Flipper Code <hello@flippercode.com>
	 */
	class WP_List_Table_Frontend_Helper extends WP_List_Table {

		/**
* Table name.
* @var string
*/
		var $table;
		/**
* Custom SQL Query to fetch records.
* @var string
*/
		var $sql;
		/**
* Action over records.
* @var array
*/
		var $actions = array( 'edit','delete' );
		/**
* Timestamp Column in table.
* @var string
*/
		var $currenttimestamp_field;
		/**
* Text Domain for multilingual.
* @var string
*/
		var $textdomain;
		/**
* Singular label.
* @var string
*/
		var $singular_label;
		/**
* Plural lable.
* @var string
*/
		var $plural_label;
		/**
* Show add navigation at the top.
* @var boolean
*/
		var $show_add_button = true;
		/**
* Ajax based listing
* @var boolean
*/
		var $ajax = false;
		/**
* Columns to be displayed.
* @var array
*/
		var $columns;
		/**
* Columns to be sortable.
* @var array
*/
		var $sortable;
		/**
* Fields to be hide.
* @var  array
*/
		var $hidden;
		/**
* Records per page.
* @var integer
*/
		var $per_page = 10;
		/**
* Slug for the manage page.
* @var string
*/
		var $admin_listing_page_name;
		/**
* Slug for the add or edit page.
* @var string
*/
		var $admin_add_page_name;
		/**
* Response
* @var string
*/
		var $response;
		/**
* Display string at the top of the table.
* @var string
*/
		var $toptext;
		/**
* Display string at the bottom of the table.
* @var [type]
*/
		var $bottomtext;
		/**
* Primary column of the table.
* @var string
*/
		var $primary_col;
		/**
* Column where to display actions navigation.
* @var string
*/
		var $col_showing_links;
		/**
* Call external function when actions executed.
* @var array
*/
		var $extra_processing_on_actions;
		/**
* Current action name.
* @var string
*/
		var $now_action;
		/**
* Table prefix.
* @var string
*/
		var $prefix;
		/**
* Current page's records.
* @var array
*/
		var $found_data;
		/**
* Total # of records.
* @var int
*/
		var $items;
		/**
* All Records.
* @var array
*/
		var $data;
		/**
* Columns to be excluded in search.
* @var array
*/
		var $searchExclude;
		/**
* Actions executed in bulk action.
* @var array
*/
		var $bulk_actions;

		/**
		 * Constructer method
		 * @param array $tableinfo Listing configurations.
		 */
		public function __construct($tableinfo) {

			global $wpdb;
			$this->prefix = $wpdb->prefix;

			foreach ( $tableinfo as $key => $value ) {    // Initialise constuctor based provided values to class variables.
				$this->$key = $tableinfo[ $key ];
			}

			parent::__construct( array(
				'singular'  => __( $this->singular_label, $this->textdomain ),
				'plural'    => __( $this->plural_label,   $this->textdomain ),
				'ajax'      => $this->ajax,
			) );

			$this->init_listing();

		}
		/**
		 * Initialize table listing.
		 */
		public function init_listing() {

			$this->prepare_items();

			if ( ! empty( $_GET['doopt'] ) and ! empty( $_GET[ $this->primary_col ] ) ) {
				$this->now_action = $function_name = sanitize_text_field( wp_unslash( $_GET['doopt'] ) );
				if ( false != strpos( sanitize_text_field( wp_unslash( $_GET['doopt'] ) ), '-' ) ) {
					$function_name = str_replace( '-','',$function_name ); }
				$this->$function_name();

			} else {
				$this->listing();
			}

		}
		/**
		 * Edit action.
		 */
		public function edit() {
			$this->listing();
		}
		/**
		 * Delete action.
		 */
		public function delete() {

			global $wpdb;

			$id = intval( wp_unslash( $_GET[ $this->primary_col ] ) );
			if ( isset( $_GET['doopt'] ) ) {
				check_admin_referer( sanitize_text_field( $_GET['doopt'] ).'_'.$id );
			}
			$query = "DELETE FROM {$this->table} WHERE {$this->primary_col} = ".$id;
			$del = $wpdb->query( $query );
			$this->prepare_items();
			$this->response['success'] = __( 'Selected '.ucwords( $this->singular_label ).' Deleted Successfully.', $this->textdomain );
			$this->listing();

		}
		/**
		 * Display records listing.
		 */
		public function listing() {
		?>
		<div class="fngmp_front_table">
		<?php $this->show_notification( $this->response ); ?>
	<?php
	$this->display();
	?>
	<?php wp_nonce_field( 'wpgmp-nonce','_wpnonce',true,true ); ?>
</div>
<?php
		}
		/**
		 * Reset primary column ID.
		 */
		public function unset_id_field() {

			if ( array_key_exists( $this->primary_col, $this->columns ) ) { unset( $this->columns[ $this->primary_col ] );	}
		}
		/**
		 * Get sortable columns.
		 * @return array Sortable columns names.
		 */
		function get_sortable_columns() {

			if ( empty( $this->sortable ) ) {

				$sortable_columns[ $this->primary_col ] = array( $this->primary_col,false );
			} else {

				foreach ( $this->sortable as $sortable ) {
					$sortable_columns[ $sortable ] = array( $sortable,false );
				}
			}
			return $sortable_columns;
		}
		/**
		 * Get columns to be displayed.
		 * @return array Columns names.
		 */
		function get_columns() {

			$columns = array();

			if ( ! empty( $this->sql ) ) {
				global $wpdb;
				$results = $wpdb->get_results( $this->sql );
				if(is_array($results) and !empty($results)) {
					foreach ( $results[0] as $column_name => $column_value ) {    // Get all columns by provided returned by sql query(Preparing Columns Array).
					if(array_key_exists($column_name, $this->columns)) {
						$this->columns[ $column_name ] = $this->columns[$column_name];
					} else {
						$this->columns[ $column_name ] = $column_name;
					}
				}
				}
			} else {
				if ( empty( $this->columns ) ) {
					global $wpdb;
					foreach ( $wpdb->get_col( 'DESC ' . $this->table, 0 ) as $column_name ) {  // Query all column name usind DESC (Preparing Columns Array).
						$this->columns[ $column_name ] = $column_name;
					}
				}
			}

			$this->unset_id_field(); // Preventing Id field to showup in Listing.

			// This is how we initialise all columns dynamically instead of statically (normally we write each column name here) in get_columns function definition :).
			foreach ( $this->columns as $dbcolname => $collabel ) {
				$columns[ $dbcolname ] = __( $collabel, $this->textdomain );
			}

			return $columns;
		}
		/**
		 * Column where to display actions.
		 * @param  array  $item        Record.
		 * @param  string $column_name Column name.
		 * @return string              Column output.
		 */
		function column_default( $item, $column_name ) {

			// Return Default values from db except current timestamp field. If currenttimestamp_field is encountered return formatted value.
			if ( ! empty( $this->currenttimestamp_field ) and $column_name == $this->currenttimestamp_field ) {
				$return = date( 'F j, Y',strtotime( $item->$column_name ) );
			} else if ( $column_name == $this->col_showing_links ) {

				$actions = array();
				foreach ( $this->actions as $action ) {
					$action_slug = sanitize_title( $action );
					$action_label = ucwords( $action ); // $action_slug $item->{$this->primary_col}

					$bare_url = add_query_arg( array( 'doopt' => $action_slug, $this->primary_col => $item->{$this->primary_col} ) );

					$complete_url = wp_nonce_url( $bare_url, $action_slug.'_'.$item->{$this->primary_col} );
					$actions[ $action_slug ] = $complete_url;
					$actions[ $action_slug ] = '<a href="'.$complete_url.'">'.$action_label.'</a>';

				}
				return sprintf( '%1$s %2$s', $item->{$this->col_showing_links}, $this->row_actions( $actions ) );

			} else {
				$return = $item->$column_name;
			}
			return $return;
		}

		/**
		 * Get records from ids.
		 * @return array Records ID.
		 */
		function get_user_selected_records() {

			$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();
			if ( is_array( $ids ) ) { $ids = implode( ',', $ids ); }
			if ( ! empty( $ids ) ) {
				return $ids; }
		}
		/**
		 * Process bulk actions.
		 */
		function process_bulk_action() {

			global $wpdb;
			$this->now_action = $this->current_action();
			$ids = $this->get_user_selected_records();
			if ( 'delete' === $this->current_action() and ! empty( $ids ) ) {

				$query = "DELETE FROM {$this->table} WHERE {$this->primary_col} IN($ids)";
				$del = $wpdb->query( $query );
				$this->response['success'] = (strpos( $ids, ',' ) !== false) ?  __( "Selected {$this->plural_label} Deleted Successfully.", $this->textdomain ) : __( 'Selected '.ucwords( ucwords( $this->singular_label ) ).' Deleted Successfully.', $this->textdomain );

			}
		}
		/**
		 * Show notification message based on response.
		 * @param  array $response Response.
		 */
		public function show_notification($response) {

			if ( ! empty( $response['error'] ) ) {
				$this->show_message( $response['error'],true ); } else if ( ! empty( $response['success'] ) ) {
				$this->show_message( $response['success'] ); }

		}
		/**
		 * Message html element.
		 * @param  string  $message  Message.
		 * @param  boolean $errormsg Error or not.
		 * @return string           Message element.
		 */
		public function show_message($message, $errormsg = false) {

			if ( empty( $message ) ) {
				return; }
			if ( $errormsg ) {
				echo "<div class='fc-error'>{$message}</div>";
			} else { 		echo "<div class='fc-success'>{$message}</div>"; }

		}
		/**
		 * Prepare records before print.
		 */
		function prepare_items() {

			global $wpdb;
			$columns  = $this->get_columns();
			$hidden   = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->process_bulk_action();
			// Check whether query must be build through table name or an sql is provided by developer.
			$query = (empty( $this->sql )) ? 'SELECT * FROM '.$this->table : $this->sql;
			if ( $this->admin_listing_page_name == @$_GET['page'] && '' != @$_REQUEST['s'] ) {

				$s = @$_REQUEST['s'];
				$first_column;
				$remaining_columns = array();
				$basic_search_query = '';
				foreach ( $this->columns as $column_name => $columnlabel ) {

					if ( "{$this->primary_col}" == $column_name ) {
						continue;
					} else {
						if ( empty( $first_column ) ) {
							$first_column = $column_name;
							$basic_search_query = " WHERE {$column_name} LIKE '%".$s."%'";
						} else {
							$remaining_columns[] = $column_name;
							if ( ! @in_array( $column_name,$this->searchExclude ) ) {
								$basic_search_query .= " or {$column_name} LIKE '%".$s."%'"; }
						}
					}
				}

				$query_to_run = $query.$basic_search_query;
				$query_to_run .= " order by {$this->primary_col} desc";

			} else if ( ! empty( $_GET['orderby'] ) and ! empty( $_GET['order'] ) ) {
				$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : $this->primary_col;
				$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';
				$query_to_run = $query;
				$query_to_run .= " order by {$orderby} {$order}";
			} else {
				$query_to_run = $query;
				if ( ! empty( $this->currenttimestamp_field ) ) {
					$query_to_run = $this->filter_query( $query_to_run ); }
				$query_to_run .= " order by {$this->primary_col} desc";
			}
			$this->data = $wpdb->get_results( $query_to_run );
			$current_page = $this->get_pagenum();
			$total_items = count( $this->data );
			$this->found_data = @array_slice( $this->data,( ( $current_page -1 ) * $this->per_page ), $this->per_page );
			$this->found_data = $this->data;
			$this->items = $this->found_data;

		}

	}
}
