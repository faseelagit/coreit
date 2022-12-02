<?php
/**
 * Plugin Name: Entries For WPForms
 * Description: Store Form Entries From WPForms Lite Plugin.
 * Version: 1.4.7
 * Author: Sanjeev Aryal
 * Author URI: http://www.sanjeebaryal.com.np
 * Text Domain: entries-for-wpforms
 * Domain Path: /languages/
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define WPFORMS_ENTRIES_PLUGIN_FILE.
if ( ! defined( 'WPFORMS_ENTRIES_PLUGIN_FILE' ) ) {
	define( 'WPFORMS_ENTRIES_PLUGIN_FILE', __FILE__ );
}

// Include the main WPForms_Entries class.
if ( ! class_exists( 'Entries_For_WPForms' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-entries-for-wpforms.php';
}

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'Entries_For_WPForms', 'get_instance' ) );
