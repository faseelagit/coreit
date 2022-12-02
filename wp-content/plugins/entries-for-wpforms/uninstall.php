<?php
/**
 * Entries For WPForms Uninstall
 *
 * Uninstalls the plugin and associated data.
 *
 * @author   Sanjeev Aryal
 * @category Core
 * @package  Entries For WPForms
 * @version  1.4.7
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;


if ( '1' === get_option( 'entries_for_wpforms_uninstall' ) ) {

	// @TODO::Tables remove.
	// Entries_For_WPForms::drop_tables();

	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'entries_for_wpforms\_%';" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wpfe\_%';" );

	$users = get_users();

	foreach ( $users as $user ) {
		delete_user_meta( $user->ID, 'entries_for_wpforms_entries_per_page' );
	}

	// Clear any cached data that has been removed.
	wp_cache_flush();
}
