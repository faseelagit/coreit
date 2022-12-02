<?php
ob_start();

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All Get and Post method actions
 * @return void
 */
function wpfe_entries_all_actions() {
	global $wpdb;

	if( isset( $_POST['export-csv'] ) ) {

		if ( ! isset( $_POST['wpfe_form_filter_nonce'] )
			|| ! wp_verify_nonce( $_POST['wpfe_form_filter_nonce'], 'wpfe_form_filter' )
		) {
		   print 'Nonce Failed!';
		   exit;
		}

		$form_id = isset( $_POST['form_id'] ) ? $_POST['form_id'] : 0;

		wpfe_csv_export( $form_id );
	}

	if( isset( $_POST['action'] ) ) {

		if( in_array( $_POST['action'], array( 'trash', 'untrash', 'delete' ) ) ) {

			if ( ! isset( $_POST['wpfe_table_nonce'] )
				|| ! wp_verify_nonce( $_POST['wpfe_table_nonce'], 'wpfe_table' )
			) {
			   print 'Nonce Failed!';
			   exit;
			}
		}

		switch( $_POST['action'] ) {

			case 'trash':

			$entries = isset( $_POST['wpforms_entry'] ) ? $_POST['wpforms_entry'] : array();

			foreach( $entries as $entry ) {

				$query = $wpdb->prepare( "UPDATE {$wpdb->prefix}wpforms_entries SET status = 'trash' WHERE entry_id = %d", $entry );
				$wpdb->get_results( $query );
			}
			break;

			case 'untrash':
			$entries = isset( $_POST['wpforms_entry'] ) ? $_POST['wpforms_entry'] : array();

			foreach( $entries as $entry ) {
				$query = $wpdb->prepare( "UPDATE {$wpdb->prefix}wpforms_entries SET status = 'publish' WHERE entry_id = %d", $entry );
				$wpdb->get_results( $query );
			}

			break;

			case 'delete':

			$entries = isset( $_POST['wpforms_entry'] ) ? $_POST['wpforms_entry'] : array();

			foreach( $entries as $entry ) {
				$query = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpforms_entries WHERE entry_id = %d", $entry );
				$wpdb->get_results( $query );
				$query = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpforms_entrymeta WHERE entry_id = %d", $entry );
				$wpdb->get_results( $query );
			}
			break;
		}

		if( in_array( $_POST['action'], array( 'trash', 'untrash', 'delete' ) ) ) {
		  	wp_safe_redirect('?page=wpfe-entires-list-table');
		}
	}

	elseif( isset( $_GET['action'] ) ) {
		$entry_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		switch( $_GET['action'] ) {
			case 'trash':
			check_admin_referer( 'wpfe_single_entry_trash' );
			$query = $wpdb->prepare( "UPDATE {$wpdb->prefix}wpforms_entries SET status = 'trash' WHERE entry_id = %d", $entry_id );
			$wpdb->get_results( $query );

			break;

			case 'delete':
			check_admin_referer( 'wpfe_single_entry_delete' );
			$query = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpforms_entries WHERE entry_id = %d", $entry_id );
			$wpdb->get_results( $query );
			$query = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpforms_entrymeta WHERE entry_id = %d", $entry_id );
			$wpdb->get_results( $query );

			break;

			case 'untrash':
			check_admin_referer( 'wpfe_single_entry_restore' );
			$query = $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpforms_entries` SET status = 'publish' WHERE entry_id = %d", $entry_id );
			$wpdb->get_results( $query );
			break;
		}

		if( in_array( $_GET['action'], array( 'trash', 'untrash', 'delete', 'empty_trash' ) ) ) {
		  	wp_safe_redirect('?page=wpfe-entires-list-table');
		}
	}
}

/**
 * Entries VIew
 * @param  integer $form_id entry Form ID
 * @param  integer $entry_id Entry id
 * @param  array $entry
 * @return void
 */
function wpfe_entries_single_entry_view( $form_id, $entry_id, $entry ) {

	check_admin_referer( 'wpfe_single_entry_view' );

	global $wpdb;

	// Insert read status on db on details view.
	if ( $entry_id ) {

		$entry_metadata = array(
			'entry_id'   => $entry_id,
			'meta_key'   => 'read',
			'meta_value' => '1',
		);

		// Insert entry meta.
		$wpdb->insert( $wpdb->prefix . 'wpforms_entrymeta', $entry_metadata );
	}

	?>
		<div class="wrap entries-for-wpforms">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'View Entry', 'entries-for-wpforms' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpfe-entires-list-table' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to All Entries', 'entries-for-wpforms' ); ?></a>
			<hr class="wp-header-end">
			<div class="entries-for-wpforms-entry">
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<!-- Entry Fields metabox -->
						<div id="post-body-content" style="position: relative;">
							<div id="entries-for-wpforms-entry-fields" class="postbox">
								<h2 class="hndle">
									<span><?php printf( __( '%s : Entry #%s', 'entries-for-wpforms' ), esc_html( _draft_or_post_title( $form_id ) ), absint( $entry_id ) ); ?></span>
								</h2>
								<div class="inside">
									<table class="wp-list-table widefat fixed striped posts">
										<tbody>
										<?php
											$entry_meta = apply_filters( 'wpforms_entries_entry_single_data', $entry->meta );

											if ( empty( $entry_meta ) ) {
												// Whoops, no fields! This shouldn't happen under normal use cases.
												echo '<p class="no-fields">' . esc_html__( 'This entry does not have any fields.', 'entries-for-wpforms' ) . '</p>';
											} else {
												// Display the fields and their values.
												foreach ( $entry_meta as $meta_key => $meta_value ) {
													// Check if hidden fields exists.
													if ( in_array( $meta_key, apply_filters( 'wpforms_entries_hidden_entry_fields', array() ), true ) ) {
														continue;
													}

													$meta_value  = is_serialized( $meta_value ) ? $meta_value : wp_strip_all_tags( $meta_value );
													$field_value = apply_filters( 'wpforms_entries_html_field_value', $meta_value, $entry_meta[ $meta_key ], $entry_meta, 'entry-single' );

													// Field name.
													echo '<tr class="entries-for-wpforms-entry-field"><th><strong>';

														$field_key = explode( 'entries_for_wpforms_field_id_', $meta_key );

														$field_id  = isset( $field_key[1] ) ? $field_key[1] : $field_key[0];

														$get_all_labels = wpfe_get_all_fields_labels( $form_id );

														$value = isset( $get_all_labels[ $field_id ] ) ? $get_all_labels[ $field_id ] : $meta_key;

														if( $meta_key === 'location' ) {
															$value = __( 'Location', 'entries-for-wpforms' );
														}

														if( 'read' === $meta_key || 'star' === $meta_key ) {
															continue;
														}

														if ( $value ) {
															echo esc_html( $value );
														} else {
															esc_html_e( 'Field ID', 'entries-for-wpforms' );
														}
													echo '</strong></th></tr>';

													// Field value.
													echo '<tr class="entries-for-wpforms-entry-field field-value"><td>';
														if ( ! empty( $field_value ) ) {
															if ( is_serialized( $field_value ) ) {
																$field_value = maybe_unserialize( $field_value );

																foreach ( $field_value as $field => $value ) {
																	echo '<span class="list">' . wp_strip_all_tags( $value ) . '</span>';
																}
															} elseif(  wpfe_is_valid_json( $field_value ) === true  ) {
																$dec_values = json_decode( $field_value );

																if( is_array( $dec_values ) || is_object( $dec_values ) ) {

																	echo '<table>';
																		foreach( $dec_values as $index => $dec_val ) {
																			echo '<tr><td>'. wpfe_geolocation_filter_shorthands( $index ) . '</td><td>' . $dec_val . '</td></tr>';
																		}
																	echo '</table>';
																} else {
																	echo $dec_values;
																}
															}
															else {
																echo nl2br( make_clickable( $field_value ) );
															}
														} else {
															esc_html_e( 'Empty', 'entries-for-wpforms' );
														}
													echo '</td></tr>';
												}
											}
										?>
										</tbody>
									</table>
								</div>
							</div>

							<?php do_action( 'wpforms_entries_entry_details_content', $entry, $form_id ); ?>
						</div>
						<!-- Entry Details metabox -->
						<div id="postbox-container-1" class="postbox-container">
							<div id="entries-for-wpforms-entry-details" class="postbox">
								<h2 class="hndle">
									<span><?php esc_html_e( 'Entry Details' , 'entries-for-wpforms' ); ?></span>
								</h2>
								<div class="inside">
									<div class="entries-for-wpforms-entry-details-meta">
										<p class="entries-for-wpforms-entry-id">
											<span class="dashicons dashicons-admin-network"></span>
											<?php esc_html_e( 'Entry ID:', 'entries-for-wpforms' ); ?>
											<strong><?php echo absint( $entry_id ); ?></strong>
										</p>

										<p class="entries-for-wpforms-entry-date">
											<span class="dashicons dashicons-calendar"></span>
											<?php esc_html_e( 'Submitted:', 'entries-for-wpforms' ); ?>
											<strong><?php echo date_i18n( esc_html__( 'M j, Y @ g:ia', 'entries-for-wpforms' ), strtotime( $entry->date_created ) + ( get_option( 'gmt_offset' ) * 3600 ) ); ?> </strong>
										</p>

										<?php if ( ! empty( $entry->user_id ) && 0 !== $entry->user_id ) : ?>
											<p class="entries-for-wpforms-entry-user">
												<span class="dashicons dashicons-admin-users"></span>
												<?php
												esc_html_e( 'User:', 'entries-for-wpforms' );
												$user      = get_userdata( $entry->user_id );
												$user_name = esc_html( ! empty( $user->display_name ) ? $user->display_name : $user->user_login );
												$user_url = esc_url(
													add_query_arg(
														array(
															'user_id' => absint( $user->ID ),
														),
														admin_url( 'user-edit.php' )
													)
												);
												?>
												<strong><a href="<?php echo $user_url; ?>"><?php echo $user_name; ?></a></strong>
											</p>
										<?php endif; ?>

										<?php if ( ! empty( $entry->user_ip_address ) ) : ?>
											<p class="entries-for-wpforms-entry-ip">
												<span class="dashicons dashicons-location"></span>
												<?php esc_html_e( 'User IP:', 'entries-for-wpforms' ); ?>
												<strong><?php echo esc_html( $entry->user_ip_address ); ?></strong>
											</p>
										<?php endif; ?>

										<?php if ( apply_filters( 'wpforms_entries_entry_details_sidebar_details_status', false, $entry ) ) : ?>
											<p class="entries-for-wpforms-entry-status">
												<span class="dashicons dashicons-category"></span>
												<?php esc_html_e( 'Status:', 'entries-for-wpforms' ); ?>
												<strong><?php echo ! empty( $entry->status ) ? ucwords( sanitize_text_field( $entry->status ) ) : esc_html__( 'Completed', 'entries-for-wpforms' ); ?></strong>
											</p>
										<?php endif; ?>

										<?php do_action( 'wpforms_entries_entry_details_sidebar_details', $entry, $entry_meta ); ?>
									</div>

									<div id="major-publishing-actions">
										<div id="delete-action">
											<?php echo '<a href="'. esc_url( wp_nonce_url( admin_url( 'admin.php?page='. $_REQUEST['page'] .'&action=trash&id='. $entry_id ), 'wpfe_single_entry_trash') ) .'">'. esc_html__( 'Move To Trash', 'entries-for-wpforms' ). '</a>';?>
										</div>
										<div class="clear"></div>
									</div>
								</div>
							</div>
							<?php do_action( 'entries_for_wpforms_entry_details', $entry, $entry_meta ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
}

/**
 * Exports entries in CSV Format.
 * @param  integer $form_id Form ID
 * @return void
 */
function wpfe_csv_export( $form_id ) {

	if( ! $form_id ) {
		return;
	}

	if( $form_id === 'All Forms' ) {
		?>
			<script>
				alert( 'Select a form to export entires!' )
			</script>
		<?php
	}

	else {

		$form_id         = absint( $form_id );

		$entries       = array();
		$entry_ids     = wpfe_get_entries_ids( $form_id );

		if ( empty( $entry_ids ) ) {
			return;
		}

		// Get Entry ID column.
		$entry_column       =  array( 'entry_id' => __( 'Entry ID','entries-for-wpforms' ) );

		// Get all fields columns.
		$meta_columns       =  wpfe_get_all_fields_labels( $form_id );
		$meta_columns_ord   =  array();

		// Set key to meta columns
		foreach( $meta_columns as $id => $meta_column ) {
			$meta_columns_ord[ 'entries_for_wpforms_field_id_'.$id ] = $meta_column;
		}

		// Merge Entry ID column and meta columns.
		$extra_columns     = array_replace( $entry_column, $meta_columns_ord );

		$exclude_columns = apply_filters( 'wpforms_entries_exclude_columns', array( 'form_id', 'user_id', 'status', 'referer', 'location' ) );
		$default_columns = array(
			'user_ip_address'   => __( 'IP Address', 'entries-for-wpforms' ),
			'user_device'       => __( 'User Device', 'entries-for-wpforms' ),
			'date_created'      => __( 'Date Created', 'entries-for-wpforms' ),
		);

		$columns = array_replace( $extra_columns, $default_columns );

		$rows = array();

		foreach( $entry_ids as $entry_id ) {
			$entries[] = wpfe_get_entry( $entry_id );
		}

		foreach( $entries as $entry ) {
			$entry = (array) $entry;
			$entry['meta'] = ! empty ( $entry['meta'] ) ? $entry['meta'] : array();

			foreach( $entry['meta'] as $key => $meta ) {
				if ( is_serialized( $meta ) ) {
					$array_values = unserialize( $meta );
					$meta         = implode( ',', $array_values );
				}

				$entry[ $key ] = $meta;
				unset( $entry[ 'meta' ]);
				foreach( $exclude_columns as $exclude_column ) {
					unset( $entry[ $exclude_column ]);
				}
			}

			// Order the row depending on columns meta key.
			$ordered_rows = array_merge( array_fill_keys ( array_keys( $columns ), '' ), $entry );
			$rows[]       = $ordered_rows;
		}

		$form_name   = strtolower( str_replace( " ", "-", get_the_title( $form_id ) ) );
		$file_name   = 'wpforms-entries-of-' . $form_name .'.csv';

		if ( ob_get_contents() ) {
			ob_clean();
		}

		set_time_limit (0);

		// disable caching
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");

		header( "Content-Type: application/force-download" );
		header( "Content-Type: application/octet-stream" );
		header( "Content-Type: application/download" );

		// Disposition / Encoding on response body
		header( "Content-Type: text/csv; charset=utf-8" );
		header( "Content-Disposition: attachment;filename={$file_name}" );
		header( "Content-Transfer-Encoding: binary" );
		$handle = fopen( "php://output", 'w' );

		// Handle UTF-8 chars conversion for CSV.
		fprintf( $handle, chr(0xEF).chr(0xBB).chr(0xBF) );

		// Put the column headers.
		fputcsv( $handle, array_values( $columns ) );

		// Put the entry values.
		foreach ( $rows as $row ) {
			fputcsv( $handle, $row );
		}

		fclose( $handle );

		exit;
	}
}

/**
 * Search entries functionality.
 *
 * @since  1.4.6
 * @return array
 */
function wpfe_entries_search( $items ) {
	// Verify Nonce.
	if ( ! isset( $_POST['wpfe_table_nonce'] )
				|| ! wp_verify_nonce( $_POST['wpfe_table_nonce'], 'wpfe_table' )
	) {
	   print 'Nonce Failed!';
	   exit;
	}

	$search 	 = $_REQUEST['s'];
	$s_items	 = array();


	foreach( $items as $item ) {
		foreach( $item as $value ) {
			if ( strpos( $value, $search ) !== false) {
				$s_items[] = $item;
	 		}
		}
	}

	return $s_items;	// Searched Items.
}

/**
 * Sortable ID column.
 *
 * @param  obj $items Items to be displayed on list table.
 * @return obj        Items to be displayed after sort.
 *
 * @since  1.4.6
 */
function wpfe_entries_order( $items ) {

	//@TODO:: Nonce Check;
	if( $_GET['orderby'] == 'id' && 'asc' == $_GET['order'] ) {

		usort( $items, function( $a, $b ) {
	    	return $a->entry_id - $b->entry_id;
		});
	}

	return $items;
}

/**
 * Order by desc by default
 *
 * @param  obj $items Items to be displayed on list table.
 * @return obj        Items to be displayed after sort.
 *
 * @since  1.4.7
 */
function wpfe_entries_order_default( $items ) {
	usort( $items, function( $a, $b ) {
	    	return $b->entry_id - $a->entry_id;
		});

	return $items;
}

/**
 * Settings page view
 *
 * @return void.
 */
function wpfe_settings_page_html() {
	?><h2 class="wp-heading-inline"><?php esc_html_e( 'Settings', 'entries-for-wpforms' ); ?></h2>
        <hr class="wp-header-end">


	<form method="post">

	    <table class="form-table">
	        <tr valign="top">
	        	<th scope="row"><?php echo esc_html__( 'Disable Geolocation', 'entries-for-wpforms' );?></th>
	        		<td><input type="checkbox" name="entries_for_wpforms_disable_geolocation" <?php checked( esc_attr( get_option('entries_for_wpforms_disable_geolocation') ), '1' ); ?> />
	        			<i class="desc"><?php echo esc_html__( 'Check this if you would like to disable storing geolocation data of the users.', 'entries-for-wpforms' );?></i>
	        		</td>
	        </tr>
<?php
/**           <tr valign="top">
    	       	<th scope="row"><?php echo esc_html__( 'Remove data on plugin uninstallation', 'entries-for-wpforms' );?></th>
	        		<td><input type="checkbox" name="entries_for_wpforms_uninstall" <?php checked( esc_attr( get_option('entries_for_wpforms_uninstall') ), '1' ); ?> />
	        			<i class="desc"><?php echo esc_html__( 'Check this if you would like to remove all stored data from your database on plugin uninstallation.', 'entries-for-wpforms' );?></i>
	        		</td>
	        </tr>
**/
?>
	        <?php do_action( 'entries_for_wpforms_inside_settings' );?>
            <?php wp_nonce_field( 'entries_for_wpforms_settings', 'entries_for_wpforms_settings_nonce' );?>

	    </table>

	    <?php submit_button(); ?>

	</form>
    <?php
}

/**
 * Save settings.
 *
 * @since  1.4.7
 *
 * @return array
 */
function wpfe_save_settings() {

	if( isset( $_GET['section'] ) && 'settings' === $_GET['section'] ) {
		if( isset( $_POST['entries_for_wpforms_settings_nonce'] ) ) {

			if ( ! isset( $_POST['entries_for_wpforms_settings_nonce'] )
			|| ! wp_verify_nonce( $_POST['entries_for_wpforms_settings_nonce'], 'entries_for_wpforms_settings' )
				) {
				   print 'Nonce Failed!';
				   exit;
			}

			$options = array( 'entries_for_wpforms_disable_geolocation', 'entries_for_wpforms_uninstall' );

			foreach( $options as $option ) {
				if( isset( $_POST[ $option ] ) ) {
					update_option( $option, 1 );
				} else {
					update_option( $option, 0 );
				}
			}
		}
	}
}


add_action( 'admin_init', 'wpfe_save_settings' );
