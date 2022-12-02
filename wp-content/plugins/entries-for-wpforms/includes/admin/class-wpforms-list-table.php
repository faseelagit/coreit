<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'wpfe_add_menu_page' );
add_action( 'admin_enqueue_scripts', 'wpfe_load_assets' );

/**
 * Load all admin assets of the plugin.
 * @return void.
 */
function wpfe_load_assets() {

	// Register and enqueue Styles.
	wp_enqueue_style( 'wpfe-style', plugins_url( 'assets/css/wpfe.css', WPFORMS_ENTRIES_PLUGIN_FILE ), array(), WPFE_VERSION, $media = 'all' );
	wp_enqueue_script( 'entries-for-wpforms-js', plugins_url( 'assets/js/admin/wpfe-admin.js', WPFORMS_ENTRIES_PLUGIN_FILE ), array(), WPFE_VERSION, false );
	wp_enqueue_script( 'sweetalert', plugins_url( 'assets/js/admin/sweetalert.min.js', WPFORMS_ENTRIES_PLUGIN_FILE ), array(), WPFE_VERSION, false );
	wp_localize_script( 'entries-for-wpforms-js', 'wpfe_plugins_params', array(
		'ajax_url'           => admin_url( 'admin-ajax.php' ),
		'deactivation_nonce' => wp_create_nonce( 'deactivation-notice' ),
		'review_nonce'		 => wp_create_nonce( 'review-nonce' ),
		'entry_email_nonce'	 => wp_create_nonce( 'entry-email-nonce' ),
		'star_nonce'		 => wp_create_nonce( 'star-nonce' ),
		'read_nonce'		 => wp_create_nonce( 'read-nonce' ),
		'deactivating'		 => __( 'Deactivating...', 'entries-for-wpforms' ),
		'error'				 => __( 'Error!', 'entries-for-wpforms' ),
		'success'			 => __( 'Success!', 'entries-for-wpforms' ),
		'deactivated'		 => __( 'Plugin Deactivated!', 'entries-for-wpforms' ),
		'sad_to_see'		 => __( 'Sad to see you leave!', 'entries-for-wpforms' ),
		'wrong'				 => __( 'Oops! Something went wrong', 'entries-for-wpforms' ),
		'sent'				 => __( 'Email Sent Successfully!', 'entries-for-wpforms' ),
		'sending'			 => __( 'Sending...', 'entries-for-wpforms' ),
		'mark_read'			 => __( 'Mark as read', 'entries-for-wpforms' ),
		'mark_unread'		 => __( 'Mark as unread', 'entries-for-wpforms' ),
	));
}

/**
 * Add submenu page on parent wpforms-overview page.
 *
 * @return void
 */
function wpfe_add_menu_page() {

	$menu_cap = function_exists( 'wpforms_get_capability_manage_options' ) ? wpforms_get_capability_manage_options() : '';
	$hook = add_menu_page(
		esc_html__( 'Entries For WPForms', 'entries-for-wpforms' ),
		esc_html__( 'Entries For WPForms', 'entries-for-wpforms' ),
		! empty( $menu_cap ) ? $menu_cap : 'manage_options',
		'wpfe-entires-list-table',
	    'entries_for_wpforms_render',
		'dashicons-tickets',
		'57.7'
	);

  add_action( "load-$hook", 'wpfe_add_options' );
}

/**
 * Add screen options at the top
 *
 * @return void
 */
function wpfe_add_options() {

	global $wpforms_entries_table;

	$option = 'per_page';
	$args = array(
		 'label' => __( 'Entries', 'entries-for-wpforms' ),
		 'default' => 20,
		 'option' => 'entries_for_wpforms_entries_per_page'
	 );

	add_screen_option( $option, $args );
	$wpforms_entries_table = new WPForms_Entries_List_Table();
}

/**
 * Rendering of wpforms entries page
 * @return
 */
function entries_for_wpforms_render() {
	  global $wpforms_entries_table;

	  	/**
	  	 * Single Entry View
	  	 */
	 	if ( isset( $_GET['action'] ) && $_GET['action'] === 'view-entry' && isset( $_GET['view-entry'] ) ) {
			$form_id  = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // WPCS: input var okay, CSRF ok.
			$entry_id = isset( $_GET['view-entry'] ) ? absint( $_GET['view-entry'] ) : 0; // WPCS: input var okay, CSRF ok.
			$entry    = wpfe_get_entry( $entry_id );
			wpfe_entries_single_entry_view( $form_id, $entry_id, $entry );
			return;
		}

        $wpforms_entries_table->prepare_items();
		wpfe_entries_all_actions();

        ?>
        <div class="wrap">
        	<?php

        		$settings_active    = isset( $_GET['section'] ) && 'settings' === $_GET['section'] ? 'nav-tab-active' : '';
        		$entries_active     = empty( $settings_active ) ? 'nav-tab-active' : '';

        		$template  = '';
        		$template .= '<h2 class="nav-tab-wrapper">
					<a href="'. esc_url( admin_url( 'admin.php?page=wpfe-entires-list-table' ) ) .'" class="nav-tab '. $entries_active .'">Entries</a>
					<a href="'. esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpfe-entires-list-table&section=settings' ), 'entries-for-wpforms-settings' ) ) .'" class="nav-tab '. $settings_active .'">'. esc_html__( 'Settings', 'entries-for-wpforms' ).'</a>
					</h2>';

			echo $template;

			// Settings tab.
			if( isset( $_GET['section'] ) && 'settings' === $_GET['section'] ) {
				check_admin_referer( 'entries-for-wpforms-settings' );
				wpfe_settings_page_html();
				return;
			}
		?>

            <h2 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'entries-for-wpforms' ); ?></h2>
            <hr class="wp-header-end">

            <?php

				$all_forms 		= wpforms_get_all_forms();
				$forms 			= $all_forms;
				$latest         = key( $forms );

				if( isset( $_POST['form_id'] ) ) {
					$selected = absint( $_POST['form_id'] );
				} elseif( isset( $_GET['form_id'] ) && isset( $_GET['view'] ) && 'all' === $_GET['view'] ) {
					$selected = absint( $_GET['form_id'] );
				} else {
					$selected = $latest;
				}

            ?>
                <form id="entries-select" method="POST">
                    <select id = "form-select" name ="form_id">
                        <?php
                            foreach( $all_forms as $key => $form ) {
                                echo '<option value="'. $key .'" '. selected( $selected, $key ) .'>'. $form .'</option>';
                            }
                        ?>
                    </select>
                    <?php wp_nonce_field( 'wpfe_form_filter', 'wpfe_form_filter_nonce' );?>
                    <button type="submit" name ="select-form"><?php echo __( 'Filter', 'entries-for-wpforms' );?></button>
                    <button type="submit" name ="export-csv"><span style="height:100%; font-size:18px"  class="dashicons dashicons-migrate"></span><?php echo __('Export CSV', 'entries-for-wpforms' );?></button>
                </form>

            <form id="entries-filter" method="post">
                <input type="hidden" name="page" value="wpfe-entires-list-table">

                <?php
                    $wpforms_entries_table->views();
            		$wpforms_entries_table->search_box( __( 'Search Entries', 'entries-for-wpforms' ), 'entries-for-wpforms' );
                    $wpforms_entries_table->display();

                    wp_nonce_field( 'wpfe_table', 'wpfe_table_nonce' );
                ?>

            </form>
        </div>
        <?php
}


if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPForms_Entries_List_Table extends WP_List_Table {

	public $form_id;

	/**
	 * Constructor
	 */
    public function __construct() {
        parent::__construct( array(
            'singular'  => __( 'wpforms_entry', 'entries-for-wpforms' ),
            'plural'    => __( 'wpforms_entries', 'entries-for-wpforms' ),
            'ajax'      => false
    	));

	    $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;

	    $all_forms     = wpforms_get_all_forms();
        $latest        = key( $all_forms );
		$form_id       = isset( $_REQUEST['form_id'] ) ? $_REQUEST['form_id'] : $latest;

		$this->form_id = $form_id;

	    if( 'wpfe-entires-list-table' != $page )
	    return;

	}

	/**
	 * Text for no entries.
	 * @return
	 */
	public function no_items() {
    	_e( 'No entries found! Check out another form.', 'entries-for-wpforms' );
  	}

	public function get_columns() {

		$columns = array(
			'cb'        	=> '<input type="checkbox" />',
			'id'     		=> __( 'ID', 'entries-for-wpforms' ),
		);
		$columns['read']	= '';

		$columns            = apply_filters( 'entries_for_wpforms_list_table_dynamic_columns', $this->get_dynamic_columns( $columns ), $this->form_data, $this->form_id );

		$columns['date'] 	= __( 'Entry Date', 'entries-for-wpforms' );
		$columns['actions'] = __( 'Action', 'entries-for-wpforms' );
		$columns['star']	= __( 'Star', 'entries-for-wpforms' );

		return apply_filters( 'entries_for_wpforms_list_table_columns', $columns, $this->form_data );
	}

	/**
	 * Column cb.
	 *
	 * @param  array $item
	 *
	 * @return string
	 */
	public function column_cb( $items ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $items->entry_id );
	}

	/**
	 * Return id column.
	 *
	 * @param  object $item
	 *
	 * @return string
	 */
	public function column_id( $items ) {

		$actions = array(
            'view'      => '<a href="'. esc_url ( wp_nonce_url( admin_url ( 'admin.php?page='. $_REQUEST['page'] .'&action=view-entry&form_id='. $items->form_id.'&view-entry='. $items->entry_id ), 'wpfe_single_entry_view' ) ) .'">'. esc_html__( 'View', 'entries-for-wpforms' ) .'</a>',
            'trash'    => '<a href="'. esc_url( wp_nonce_url( admin_url( 'admin.php?page='. $_REQUEST['page'] .'&action=trash&id='. $items->entry_id ), 'wpfe_single_entry_trash') ) .'">'. esc_html__( 'Trash', 'entries-for-wpforms' ). '</a>',
        );

        if( isset( $_GET['status'] ) && $_GET['status'] == 'trash' ) {
        	$actions = array(
				'view'      => '<a href="'. esc_url ( wp_nonce_url( admin_url ( 'admin.php??page='. $_REQUEST['page'] .'&action=view-entry&form_id='. $items->form_id.'&view-entry='. $items->entry_id ), 'wpfe_single_entry_view' ) ) .'">'. esc_html__( 'View', 'entries-for-wpforms' ) .'</a>',
 				'delete'    => '<a href="'. esc_url( wp_nonce_url( admin_url( 'admin.php?page='. $_REQUEST['page'] .'&action=delete&id='. $items->entry_id ), 'wpfe_single_entry_delete') ) .'">'. esc_html__( 'Delete', 'entries-for-wpforms' ). '</a>',
 				'untrash'    => '<a href="'. esc_url( wp_nonce_url( admin_url( 'admin.php?page='. $_REQUEST['page'] .'&action=untrash&id='. $items->entry_id ), 'wpfe_single_entry_restore') ) .'">'. esc_html__( 'Restore', 'entries-for-wpforms' ). '</a>'
    	    );
        }
  		return sprintf('%1$s %2$s', $items->entry_id, $this->row_actions($actions) );
	}

	/**
	 * Read/Unread Entries Column
	 *
	 * @param  object 	$items
	 * @return string
	 */
	public function column_read( $items ) {

		$read_class 	= ( isset( $items->read ) && $items->read === '1' ) ? 'wpfe-read' : 'wpfe-unread';
		$tooltip_text	= ( isset( $items->read ) && $items->read === '1' ) ? __( 'Mark as unread', 'entries-for-wpforms' ) : __( 'Mark as read', 'entries-for-wpforms' );

		$read_icon   = '<span title="'. $tooltip_text.'" data-id="'. $items->entry_id .'" class="wpfe-read-unread dashicons dashicons-marker '. $read_class .'">
						</span>';

		return $read_icon;
	}

	/**
	 * Return date column.
	 *
	 * @param  object $posts
	 *
	 * @return string
	 */
	public function column_date( $items ) {
		return isset( $items->date_created ) ? date( 'Y:m:d', strtotime( $items->date_created ) ) : '';
	}

	/**
	 * Return action column.
	 *
	 * @param  object $posts
	 *
	 * @return string
	 */
	public function column_actions( $items ) {
		$action = '<a href=" '. wp_nonce_url( admin_url('admin.php?page=wpfe-entires-list-table&action=view-entry&form_id='. $items->form_id .'&view-entry='. $items->entry_id .' '), 'wpfe_single_entry_view' ) .'">'. __('Details','entries-for-wpforms').'</a>';
		$action .= ' | ';
		$action .= '<a href="" entry-id="'. $items->entry_id .'"class="wpfe-email">' . esc_html__( 'Send Email', 'entries-for-wpforms' ) . '</a>';

		return $action;
	}

	/**
	 * Return Star column.
	 *
	 * @param  object $posts
	 *
	 * @return string
	 */
	public function column_star( $items ) {

		$star_class = ( isset( $items->star ) && $items->star === '1' ) ? 'dashicons-star-filled' : 'dashicons-star-empty';
		$star_icon   = '<span data-id="'. $items->entry_id .'" class="wpfe-star dashicons '. $star_class .' "></span>';

		return $star_icon;
	}


	protected function get_views() {
		global $wpdb;

		$status_links = array();
		$all_forms    = wpforms_get_all_forms();
        $latest       = key( $all_forms );
		$form_id      = ( isset( $_POST['select-form'] ) && isset( $_POST['form_id'] ) ) ? $_POST['form_id'] : $latest;

	    if( ( isset( $form_id )  ) && is_numeric( $form_id )  && $form_id !== 0) {

			$query = $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}wpforms_entries WHERE form_id = %d AND status = 'publish' ", $form_id );
			$query_1 = $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}wpforms_entries WHERE form_id = %d AND status = 'trash' ", $form_id );

		} else {
			$query = $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}wpforms_entries WHERE status = %s", 'publish' );
			$query_1 = $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}wpforms_entries WHERE status = %s", 'trash' );;
		}

	   	$results = $wpdb->get_results( $query );

	   	$total_items = count( $results );

	   	$results = $wpdb->get_results( $query_1 );

	   	$total_trash_items = count( $results );

		$status_links = array();

		$trash_status = isset( $_GET['status'] ) && $_GET['status'] === 'trash' ? '<b>'. __( 'Trash', 'entries-for-wpforms' ) . '</b>' : __( 'Trash', 'entries-for-wpforms' );
		$all_status   = isset( $_GET['status'] ) && $_GET['status'] === 'trash' ? __( 'All', 'entries-for-wpforms' ) : '<b>' . __( 'All', 'entries-for-wpforms' ). '</b>';

		/* translators: %s: count */
		$status_links['all'] = "<a href='admin.php?page=wpfe-entires-list-table&status=all'>" . sprintf( _nx( ''. $all_status .' <span class="count">(%s)</span>', ''. $all_status .' <span class="count">(%s)</span>', $total_items, 'entries', 'entries-for-wpforms' ), number_format_i18n( $total_items ) ) . '</a>';

		$status_links['trash'] = "<a href='admin.php?page=wpfe-entires-list-table&status=trash'>" . sprintf( _nx( ''. $trash_status .' <span class="count">(%s)</span>', ''. $trash_status .' <span class="count">(%s)</span>', $total_trash_items, 'entries', 'entries-for-wpforms' ), number_format_i18n( $total_trash_items ) ) . '</a>';
		return $status_links;
	}

	/**
	 * Get the status label for licenses.
	 *
	 * @param  string   $status_name
	 * @param  stdClass $status
	 *
	 * @return array
	 */
	private function get_status_label( $status_name, $status ) {
		switch ( $status_name ) {
			case 'publish' :
				/* translators: %s: count */
				$label = array(
					'singular' => __( 'Published <span class="count">(%s)</span>', 'entries-for-wpforms' ),
					'plural'   => __( 'Published <span class="count">(%s)</span>', 'entries-for-wpforms' ),
					'context'  => '',
					'domain'   => 'entries-for-wpforms',
				);
				break;
			case 'draft' :
				/* translators: %s: count */
				$label = array(
					'singular' => __( 'Draft <span class="count">(%s)</span>', 'entries-for-wpforms' ),
					'plural'   => __( 'Draft <span class="count">(%s)</span>', 'entries-for-wpforms' ),
					'context'  => '',
					'domain'   => 'entries-for-wpforms',
				);
				break;
			case 'pending' :
				/* translators: %s: count */
				$label = array(
					'singular' => __( 'Pending <span class="count">(%s)</span>', 'entries-for-wpforms' ),
					'plural'   => __( 'Pending <span class="count">(%s)</span>', 'entries-for-wpforms' ),
					'context'  => '',
					'domain'   => 'entries-for-wpforms',
				);
				break;
			default:
				$label = $status->label_count;
				break;
		}
		return $label;
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'id'  			  => array( 'id', false ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {

		if ( isset( $_GET['status'] ) && 'trash' == $_GET['status'] ) {
			return array(
				'untrash' => __( 'Restore', 'entries-for-wpforms' ),
				'delete'  => __( 'Delete permanently', 'entries-for-wpforms' ),
			);
		}
		return array(
			'trash' => __( 'Move to trash', 'entries-for-wpforms' ),
		);
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {

		if ( 'top' == $which && isset( $_GET['status'] ) && 'trash' == $_GET['status'] && current_user_can( 'delete_posts' ) ) {
			echo '<div class="alignleft actions"><a id="delete_all" class="button apply" href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpfe-entires-list-table&status=trash&empty_trash=1' ), 'empty_trash' ) ) . '">' . __( 'Empty trash', 'entries-for-wpforms' ) . '</a></div>';
		}
	}
	/**
	 * Get a list of hidden columns.
	 *
	 * @return array
	 */
	protected function get_hidden_columns() {
		return get_hidden_columns( $this->screen );
	}

	/**
	 * Set _column_headers property for table list
	 */
	protected function prepare_column_headers() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

		/**
	 * Prepare table list items.
	 *
	 * @global wpdb $wpdb
	 */
	public function prepare_items( $args = array() ) {

		global $wpdb;
		$columns 	= $this->get_columns();
		$hidden   	= $this->get_hidden_columns();
		$sortable 	= $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

        $all_forms 		= wpforms_get_all_forms();
        $latest 		= key( $all_forms );
	    $latest 		= (int) $latest;
	    $form_id 		= ( isset( $_POST['select-form'] ) && isset( $_POST['form_id'] ) ) ? (int) $_POST['form_id'] : $latest;

	    if( isset( $_POST['select-form'] ) && isset( $_POST['form_id'] ) )  {

			wp_redirect( esc_url_raw( add_query_arg( array( 'form_id' => $form_id, 'view' => 'all' ), admin_url( 'admin.php?page=wpfe-entires-list-table' ) ) ) );
			exit();
	    }

	    if( isset( $_GET['form_id'] ) && isset( $_GET['view'] ) && 'all' === $_GET['view'] )  {
			$form_id = absint( $_GET['form_id'] );
		}

		$query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpforms_entries INNER JOIN {$wpdb->prefix}wpforms_entrymeta WHERE form_id = %d AND {$wpdb->prefix}wpforms_entries.entry_id = {$wpdb->prefix}wpforms_entrymeta.entry_id AND status = %s", $form_id, 'publish' );

	    if( ( isset( $_GET['status'] ) && $_GET['status'] == 'trash') ) {

	    	if( isset( $_GET['empty_trash'] ) && $_GET['empty_trash'] == 1 ) {
	    		check_admin_referer( 'empty_trash' );
				$query = "DELETE FROM {$wpdb->prefix}wpforms_entries";
				$wpdb->get_results( $query );
	    	}

	    	$query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpforms_entries INNER JOIN {$wpdb->prefix}wpforms_entrymeta WHERE {$wpdb->prefix}wpforms_entries.entry_id = {$wpdb->prefix}wpforms_entrymeta.entry_id AND status = %s ", 'trash' );
	    }

	   	$results = $wpdb->get_results( $query );

		$array = array();

		foreach( $results as $val ) {
		    $array[ $val->entry_id ]['entry_id'] = ( ! isset( $array[ $val->entry_id ]['entry_id'] ) ) ? $val->entry_id : $array[ $val->entry_id ]['entry_id'];
		    $array[ $val->entry_id ]['date_created'] = (! isset( $array[ $val->entry_id ]['date_created'] ) ) ? $val->date_created : $array[ $val->entry_id ]['date_created'];
		    $array[ $val->entry_id ]['form_id'] = (! isset( $array[ $val->entry_id ]['form_id'] ) ) ? $val->form_id : $array[ $val->entry_id ]['form_id'];
		    $array[ $val->entry_id ][ $val->meta_key ] =  $val->meta_value;
		}

		$array = json_decode( json_encode( array_values( $array ) ) );

		$this->items = $array;
		$this->items = wpfe_entries_order_default( $this->items );

		$total_items = count( $this->items );

 		$current_page = $this->get_pagenum();
 		$per_page 	= $this->get_items_per_page( 'entries_for_wpforms_entries_per_page', 20 );

		// Searched Items.
		if(  isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'wpfe-entires-list-table' &&  ! empty( $_REQUEST['s'] ) ) {
			$this->items = wpfe_entries_search( $this->items );
		}

		// Order Items.
		if( isset( $_GET['orderby'] ) ) {
			$this->items = wpfe_entries_order( $this->items );
		}

		// Update screen options in user_meta table. @TODO:: Needs refactor. Seems like there is a built-in option for this purpose.
		if( isset( $_REQUEST['wp_screen_options'] ) && isset( $_REQUEST['wp_screen_options']['option'] ) && 'entries_for_wpforms_entries_per_page' === $_REQUEST['wp_screen_options']['option'] )  {

			check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

			$per_page = $_REQUEST['wp_screen_options']['value'];
			$user_id = get_current_user_id();

			update_user_meta( $user_id, 'entries_for_wpforms_entries_per_page', $per_page );
		}

		$this->items = array_slice( $this->items,( ( $current_page - 1 ) * $per_page ),$per_page );

		$this->set_pagination_args( array(
    		'total_items' => $total_items,
    		'per_page'    => $per_page
 		) );
	}

	/**
	 * Get dynamic columns only. Columns from form fields
	 *
	 * @param  $columns
	 * @param  $display Number of displaying columns.
	 * @return Array.
	 */
	public function get_dynamic_columns( $columns = array(), $display = 5 ) {

		$form_labels = wpfe_get_all_fields_labels( $this->form_id );

		$form_labels = empty( $form_labels ) ? array() : $form_labels;

		$strip_form_labels = array();

		$count = 1;

		foreach( $form_labels as $key => $label ) {

			if( $count == $display-1 ) {
				break;
			}

			$strip_form_labels[ $key ] = $label;
			$count++;
		}

		$columns     = $columns + $strip_form_labels;

		return $columns;
	}


	/**
	 * Renders the dynamic column values.
	 *
	 * @param  object $entry prepared data.
	 * @param  string $column_name
	 * @return string
	 */
	public function column_default( $entry, $column_name ) {

		$field_name = 'entries_for_wpforms_field_id_'.$column_name;

		if( isset( $entry->$field_name ) ) {
			$value = $entry->$field_name;
		}

		return isset( $value ) ? $value : '--';
	}
}

?>
