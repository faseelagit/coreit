<?php
/**
 * Entries For WPForms Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @package Entries For WPForms/Functions
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'in_admin_header', 'entries_for_wpforms_admin_header', 100 );
add_filter( 'admin_footer_text', 'entries_for_wpforms_admin_footer' );
add_action( 'wpforms_process_complete', 'process_entry', 5, 4 );
add_action( 'admin_notices', 'wpfe_recommended_plugin' );
add_action( 'admin_init', 'wpfe_update_dismiss_notification' );
add_action( 'admin_print_scripts', 'wpfe_hide_unrelated_notices' );

/**
 * Updates the dismiss notification by adding wpfe_notice_skipped.
 *
 * @return Void.
 */
function wpfe_update_dismiss_notification() {

	if( isset( $_GET['wpfe_dismissed'] ) ) {
		update_option( 'wpfe_notice_skipped', 'yes' );
	}
}

/**
 * Notice for recommending Fancy Fields For WPForms plugin.
 *
 * @return void
 */
function wpfe_recommended_plugin() {

	$skipped = get_option( 'wpfe_notice_skipped');

	if( ! defined( 'FFWP_VERSION' ) && $skipped !== 'yes' ) {
		/* translators: %s: Fancy Fields For WPForms  plugin link */
		echo '<div class="updated notice is-dismissible"><p>' . sprintf( esc_html__( 'Thank you for using Entries For WPForms! %s plugin is recommended!', 'entries-for-wpforms' ), '<a href="https://wordpress.org/plugins/fancy-fields-for-wpforms/" target="_blank">' . esc_html__( 'Fancy Fields For WPForms Including File Upload', 'entries-for-wpforms' ) . '</a>' ) . '
			</p>
			<a href="https://downloads.wordpress.org/plugin/fancy-fields-for-wpforms.zip">Download Plugin</a>
			<a class="wpfe_notice_skip" href="'.esc_url_raw( add_query_arg( array( 'wpfe_dismissed' => 1 ), admin_url( 'plugins.php' ) ) ).'">Dismiss Notice</a>
			<p></p></div>';
	}
}

/**
 * Rate on wordpress.org text on footer
 *
 * @param  $text
 * @return string
 */
function entries_for_wpforms_admin_footer( $text ) {
	global $current_screen;
	if ( ! empty( $current_screen->id ) && $current_screen->id === 'wpforms_page_wpfe-entires-list-table' ) {
		if ( ! empty( $current_screen->id ) && $current_screen->id === 'wpforms_page_wpfe-entires-list-table' ) {
			return;		// For now, Don't ask.

			$url  = 'https://wordpress.org/support/plugin/entries-for-wpforms/reviews/?filter=5#new-post';
			$text = sprintf(
				wp_kses(
					/* translators: $1$s - Entries For WPForms plugin name; $2$s - WP.org review link; $3$s - WP.org review link. */
					__('Please rate %1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%3$s" target="_blank" rel="noopener">WordPress.org</a> to help us spread the word. Thank you!', 'entries-for-wpforms' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				),
				'<strong>Entries For WPForms</strong>',
				$url,
				$url
			);
		}
		echo "<div style='margin-left:10px' class='entries-for-wpforms-rate'>";
			echo $text;
		echo "</div>";
	}
}

/**
 * Outputs the WPForms admin header.
 *
 * @since 1.3.0
 */
function entries_for_wpforms_admin_header() {

	global $current_screen;

	// Show only to Admins
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice_dismissed = get_option( 'wpfe_review_notice_dismissed', 'no' );

	if ( 'yes' == $notice_dismissed ) {
		return;
	}

	if ( ! empty( $current_screen->id ) && $current_screen->id !== 'wpforms_page_wpfe-entires-list-table' ) {
		return;
	}

	//
	if( isset( $_GET['section'] ) && $_GET['section'] === 'settings' ) {
		return;
	}

	global $wpforms_entries_table;

	$entry_ids = wpfe_get_entries_ids( $wpforms_entries_table->form_id );

	if( count( $entry_ids ) < 10 ) {
		return;
	}

	?>
		<div id="entries-for-wpforms-review-notice" class="notice notice-info entries-for-wpforms-review-notice">
			<div class="entries-for-wpforms-review-thumbnail">
				<img src="<?php echo plugins_url( 'assets/img/logo.jpg', WPFORMS_ENTRIES_PLUGIN_FILE ); ?>" alt="">
			</div>
			<div class="entries-for-wpforms-review-text">

					<h3><?php _e( 'Whoopee! 😀', 'entries-for-wpforms' ); ?></h3>
					<p><?php _e( 'Entries For WPForms already stored '. count( $entry_ids ).'+ submitted entries from '. get_the_title( $wpforms_entries_table->form_id ) .' . Would you do us some favour and leave a <a href="https://wordpress.org/support/plugin/entries-for-wpforms/reviews/?filter=5#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> review on <a href="https://wordpress.org/support/plugin/entries-for-wpforms/reviews/?filter=5#new-post" target="_blank"><strong>WordPress.org</strong></a>? to help us spread the word and boost our motivation.', 'entries-for-wpforms' ); ?></p>

				<ul class="entries-for-wpforms-review-ul">
					<li><a class="button button-primary" href="https://wordpress.org/support/plugin/entries-for-wpforms/reviews/?filter=5#new-post" target="_blank"><span class="dashicons dashicons-external"></span><?php _e( 'Sure, I\'d love to!', 'entries-for-wpforms' ); ?></a></li>
					<li><a href="#" class="button button-secondary notice-dismiss"><span  class="dashicons dashicons-smiley"></span><?php _e( 'I already did!', 'entries-for-wpforms' ); ?></a></li>
					<li><a href="#" class="button button-link notice-dismiss"><span class="dashicons dashicons-dismiss"></span><?php _e( 'Never show again', 'entries-for-wpforms' ); ?></a></li>
				 </ul>
			</div>
		</div>
	<?php
}

/**
 * Get first all email fields.
 *
 * @param  int $form_id Form ID.
 * @return mixed
 */
function wpfe_get_email_field( $form_id ) {
	$get_post     = get_post( $form_id );

	if( ! isset( $get_post->post_content ) ) {
		return;
	}

	$post_content = json_decode( $get_post->post_content, true ) ;
	$form_fields  = isset( $post_content['fields'] ) ? $post_content['fields'] : array();
	$emails       = array();

	if ( ! empty( $form_fields ) ) {
		foreach( $form_fields as $field ) {

			if( isset( $field['type'] ) && $field['type'] === 'email' ) {
				if( isset( $field['id'] ) ) {
					$emails[] = $field['id'];
				}
			}
		}
	}

	return $emails;
}

/**
 * Get all field's label by form_id
 * @param  $form_id Form ID
 * @return array
 */
function wpfe_get_all_fields_labels( $form_id ) {

	$get_post     = get_post( $form_id );

	if( ! isset( $get_post->post_content ) ) {
		return;
	}

	$post_content = json_decode( $get_post->post_content, true ) ;
	$form_fields  = isset( $post_content['fields'] ) ? $post_content['fields'] : array();
	$labels       = array();

	if ( ! empty( $form_fields ) ) {
		foreach( $form_fields as $field ) {
			if( $field['type'] === 'Divider' || $field['type'] === 'divider' ) {
				continue;
			}

			$labels[ $field['id'] ] = $field['label'];
		}
	}

	return array_map( 'trim', $labels );
 }

/**
 * Get all forms.
 *
 * @return array of form data.
 */
function wpforms_get_all_forms() {

	$args        = apply_filters( 'entries_for_wpforms_query_forms_args', array(
		'post_type' 		=> 'wpforms',
		'status'    		=> 'publish',
		'posts_per_page'	=> 10,
	) );

	$posts_array = get_posts( $args );

	foreach ( $posts_array as $post ) {

		$all_forms[ $post->ID ] = $post->post_title;
	}

	return isset( $all_forms ) ? $all_forms : array();
}

/**
 * Stores the form data into the database.
 * @param  array  $form_fields Form Fields.
 * @param  array  $entry       Entry.
 * @param  array  $form_data   Form Data.
 * @param  int    $entry_id    Entry ID.
 * @return void
 */
function process_entry( $form_fields, $entry, $form_data, $entry_id ) {

	global $wpdb;

	$form_id     = $form_data['id'];
	$browser     = wpforms_entries_get_browser();
	$user_device = $user_ip = '';


	$disable_geolocation = get_option( 'entries_for_wpforms_disable_geolocation', '0' );
	$disable_geolocation = apply_filters( 'entries_for_wpforms_disable_geolocation', $disable_geolocation );

	// Donot store if geolocation is disabled via filter.
	if( '0' === $disable_geolocation ) {
		$user_device = $browser['name'] . '/' . $browser['platform'];
		$user_ip     = wpforms_entries_get_ip_address();
	}

	$entry_data = array(
		'form_id'         => $form_id,
		'user_id'         => get_current_user_id(),
		'user_device'     => $user_device,
		'user_ip_address' => $user_ip,
		'status'          => 'publish',
		'referer'         => $_SERVER['HTTP_REFERER'],
		'date_created'    => current_time( 'mysql' )
	);

	if ( ! $entry_data['form_id'] ) {
		return new WP_Error( 'no-form-id', __( 'No form ID was found.', 'entries-for-wpforms' ) );
	}

	// Create entry.
	$success = $wpdb->insert( $wpdb->prefix . 'wpforms_entries', $entry_data );

	if ( is_wp_error( $success ) || ! $success ) {
		return new WP_Error( 'could-not-create', __( 'Could not create an entry', 'entries-for-wpforms' ) );
	}

	$entry_id = $wpdb->insert_id;

	// Create meta data.
	if ( $entry_id ) {

		foreach ( $form_fields as $field ) {

			if( $field['type'] === 'Divider' || $field['type'] === 'divider' ) {
				continue;
			}

			$field          = apply_filters( 'wpforms_process_entry_field', $field, $form_data, $entry_id );

			$field_value    = isset( $field['value'] ) ? $field['value'] : '';

			$field_value    = is_array( $field['value'] ) ? serialize( $field['value'] ) : $field['value'];

			$entry_metadata = array(
				'entry_id'   => $entry_id,
				'meta_key'   => 'entries_for_wpforms_field_id_'.$field['id'],
				'meta_value' => $field_value,
			);

			// Insert entry meta.
			$wpdb->insert( $wpdb->prefix . 'wpforms_entrymeta', $entry_metadata );
		}
	}

	do_action( 'wpforms_complete_process_entry', $entry_id, $form_fields, $entry, $form_id, $form_data );

	return $entry_id;
}

/**
 * Get current user IP Address.
 *
 * @return string
 */
function wpforms_entries_get_ip_address() {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) { // WPCS: input var ok, CSRF ok.
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );  // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // WPCS: input var ok, CSRF ok.
		// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
		// Make sure we always only send through the first IP in the list which should always be the client IP.
		return (string) rest_is_ip_address( trim( current( preg_split( '/[,:]/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) ); // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) { // @codingStandardsIgnoreLine
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ); // @codingStandardsIgnoreLine
	}
	return '';
}

/**
 * Get User Agent browser and OS type
 *
 * @since  1.1.0
 * @return array
 */
function wpforms_entries_get_browser() {

	$u_agent  = $_SERVER['HTTP_USER_AGENT'];
	$bname    = 'Unknown';
	$platform = 'Unknown';
	$version  = '';

	// First get the platform.
	if ( preg_match( '/linux/i', $u_agent ) ) {
		$platform = 'Linux';
	} elseif ( preg_match( '/macintosh|mac os x/i', $u_agent ) ) {
		$platform = 'MAC OS';
	} elseif ( preg_match( '/windows|win32/i', $u_agent ) ) {
		$platform = 'Windows';
	}

	// Next get the name of the useragent yes seperately and for good reason.
	if ( preg_match( '/MSIE/i',$u_agent ) && ! preg_match( '/Opera/i',$u_agent ) ) {
		$bname = 'Internet Explorer';
		$ub    = 'MSIE';
	} elseif ( preg_match( '/Trident/i',$u_agent ) ) {
		// this condition is for IE11
		$bname = 'Internet Explorer';
		$ub = 'rv';
	} elseif ( preg_match( '/Firefox/i',$u_agent ) ) {
		$bname = 'Mozilla Firefox';
		$ub = 'Firefox';
	} elseif ( preg_match( '/Chrome/i',$u_agent ) ) {
		$bname = 'Google Chrome';
		$ub = 'Chrome';
	} elseif ( preg_match( '/Safari/i',$u_agent ) ) {
		$bname = 'Apple Safari';
		$ub = 'Safari';
	} elseif ( preg_match( '/Opera/i',$u_agent ) ) {
		$bname = 'Opera';
		$ub = 'Opera';
	} elseif ( preg_match( '/Netscape/i',$u_agent ) ) {
		$bname = 'Netscape';
		$ub = 'Netscape';
	}

	// Finally get the correct version number.
	// Added "|:"
	$known = array( 'Version', $ub, 'other' );
	$pattern = '#(?<browser>' . join( '|', $known ) .
	 ')[/|: ]+(?<version>[0-9.|a-zA-Z.]*)#';
	if ( ! preg_match_all( $pattern, $u_agent, $matches ) ) {
		// We have no matching number just continue.
	}

	// See how many we have.
	$i = count( $matches['browser'] );

	if ( $i != 1 ) {
		// we will have two since we are not using 'other' argument yet.
		// see if version is before or after the name.
		if ( strripos( $u_agent,'Version' ) < strripos( $u_agent,$ub ) ) {
			$version = $matches['version'][0];
		} else {
			$version = $matches['version'][1];
		}
	} else {
		$version = $matches['version'][0];
	}

	// Check if we have a number.
	if ( $version == null || $version == '' ) {
		$version = '';
	}

	return array(
		'userAgent' => $u_agent,
		'name'      => $bname,
		'version'   => $version,
		'platform'  => $platform,
		'pattern'   => $pattern
	);
}

/**
 * Get entry by entry id
 * @param  integer entry id
 * @return mixed
 */
function wpfe_get_entry( $id ) {
	global $wpdb;

	$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpforms_entries WHERE entry_id = %d LIMIT 1;", $id ) ); // WPCS: cache ok, DB call ok.

	if ( apply_filters( 'wpforms_get_entry_metadata', true ) ) {
		$results     = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key,meta_value FROM {$wpdb->prefix}wpforms_entrymeta WHERE entry_id = %d", $id ), ARRAY_A );
		$entry->meta = wp_list_pluck( $results, 'meta_value', 'meta_key' );
	}

	return 0 !== $entry ? $entry : null;
}

/**
 * Get all entries IDs.
 *
 * @param  int $form_id Form ID.
 * @return int[]
 */
function wpfe_get_entries_ids( $form_id ) {
	global $wpdb;

	$results = $wpdb->get_results( $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}wpforms_entries WHERE form_id = %d", $form_id ) ); // WPCS: cache ok, DB call ok.

	return array_map( 'intval', wp_list_pluck( $results, 'entry_id' ) );
}

/**
 * Checks if the string is valid.
 * @param  string $raw_json
 * @return boolean
 * @see  https://stackoverflow.com/a/25540509/5608921
 */
function wpfe_is_valid_json( $raw_json ) {
	return ( json_decode( $raw_json , true ) == NULL ) ? false : true ; // Yes! thats it.
}

/**
 * Filter shorthands
 * @param  $shorthands Shorthand to filter
 * @return string Actual String
 */
function wpfe_geolocation_filter_shorthands( $shorthands ) {

	switch( $shorthands ) {
		case 'country':
			return 'Country';
		case 'country_code':
			return 'Country Code';
		case 'region':
			return 'Region';
		case 'postal':
			return 'Postal Code';
		case 'latitude':
			return 'Latitude';
		case 'longitude':
			return 'Longitude';
		case 'city':
			return 'City';
		case 'state':
			return 'State';
		default:
			return $shorthands;
	}

	return $shorthands;
}

/**
 * Prepare fields for entry id.
 * @param  id $entry_id Entry ID.
 * @return array Fields.
 */
function wpfe_prepare_fields( $entry_id, $form_data ) {
	$entry  = wpfe_get_entry( $entry_id );
	$fields = array();

	foreach( $form_data['fields'] as $data ) {
		$fields[] = array(
			'name' 	=> $data['label'],
			'id'	=> $data['id'],
			'value'	=> '',
			'type'	=> $data['type']
		);
	}

	$ret_fields = array();
	foreach( $entry->meta as $key => $meta ) {
		foreach( $fields as $field ) {

			if( $key === 'entries_for_wpforms_field_id_'.$field['id'] ) {
				$field['value'] = $meta;
				$ret_fields[]   = $field;
			}
		}
	}

	return $ret_fields;
}

/**
 * Hides the admin notices on entries for wpforms page.
 *
 * @Since v1.4.6
 *
 * @return array.
 */
function wpfe_hide_unrelated_notices() {

	global $wp_filter;

	// Return on other than user registraion builder page.
	if ( empty( $_REQUEST['page'] ) || 'wpfe-entires-list-table' !== $_REQUEST['page'] ) {
		return;
	}

	foreach ( array( 'user_admin_notices', 'admin_notices', 'all_admin_notices' ) as $wp_notice ) {
		if ( ! empty( $wp_filter[ $wp_notice ]->callbacks ) && is_array( $wp_filter[ $wp_notice ]->callbacks ) ) {
			foreach ( $wp_filter[ $wp_notice ]->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
				}
			}
		}
	}
}
