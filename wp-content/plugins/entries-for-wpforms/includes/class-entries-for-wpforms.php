<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Entries_For_WPForms Class.
 *
 * @class   Entries_For_WPForms
 * @version 1.0.0
 */
final class Entries_For_WPForms {

	/**
	 * Plugin version.
	 * @var string
	 */
	public $version = '1.4.7';


	/**
	 * Instance of this class.
	 * @var object
	 */
	protected static $_instance = null;

	/*
	 * Return an instance of this class.
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'entries-for-wpforms' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'entries-for-wpforms' ), '1.0' );
	}

	/**
	 * Entries For WPForms Constructor.
	 */
	public function __construct() {

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		if ( defined( 'WPFORMS_VERSION' ) && version_compare( WPFORMS_VERSION, '1.1', '>=' ) ) {
			$this->define_constants();
			$this->init();
			$this->includes();

		} else {
			add_action( 'admin_notices', array( $this, 'wpforms_missing_notice' ) );
		}

		do_action( 'wpforms_entries_loaded' );
	}

	/**
	 * Define FT Constants.
	 */
	private function define_constants() {
		$this->define( 'WPFE_ABSPATH', dirname( WPFORMS_ENTRIES_PLUGIN_FILE ) . '/' );
		$this->define( 'WPFE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'WPFE_VERSION', $this->version );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name
	 * @param string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 *
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/entries-for-wpforms/entries-for-wpforms-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/entries-for-wpforms-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'entries-for-wpforms' );

		load_textdomain( 'entries-for-wpforms', WP_LANG_DIR . '/entries-for-wpforms/entries-for-wpforms-' . $locale . '.mo' );
		load_plugin_textdomain( 'entries-for-wpforms', false, plugin_basename( dirname( WPFORMS_ENTRIES_PLUGIN_FILE ) ) . '/languages' );
	}


	/**
	 * Includes.
	 */
	private function includes() {
		include_once WPFE_ABSPATH . '/includes/functions-wpfe-core.php';
		include_once WPFE_ABSPATH . '/includes/class-entries-for-wpforms-frontend.php';
		include_once WPFE_ABSPATH . '/includes/class-entries-for-wpforms-ajax.php';

		$disable_geolocation = get_option( 'entries_for_wpforms_disable_geolocation', '0' );
		$disable_geolocation = apply_filters( 'entries_for_wpforms_disable_geolocation', $disable_geolocation );

		if( '0' === $disable_geolocation ) {
			include_once WPFE_ABSPATH . '/includes/class-entries-for-wpforms-geolocation.php';
		}

		if ( $this->is_request( 'admin' ) ) {

			if( ! class_exists( 'WP_List_Table' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
			}

			include_once dirname( __FILE__ ) . '/admin/functions-wpfe-admin.php';
			include_once dirname( __FILE__ ) . '/admin/class-wpforms-list-table.php';
		}
	}

	/**
	 * Init WPForms Entres when WordPress Initialises.
	 */
	public function init() {

		// Before init action.
		do_action( 'before_wp_forms_entries_init' );

		//create tables on init
		$this->create_tables();

		// Init action.
		do_action( 'wp_forms_entries_init' );
	}

	// Create tables for entries
	public function create_tables() {
		global $wpdb;

		$charset_collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$charset_collate = $wpdb->get_charset_collate();
		}

		$tables = array( "
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpforms_entries (
  entry_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  form_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  user_device varchar(100) NOT NULL,
  user_ip_address VARCHAR(100) NULL DEFAULT '',
  referer text NOT NULL,
  status varchar(20) NOT NULL,
  date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (entry_id),
  KEY form_id (form_id)
) $charset_collate",

"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpforms_entrymeta (
  meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY entry_id (entry_id),
  KEY meta_key (meta_key(32))
) $charset_collate;
"
);
		foreach( $tables as $table ) {
			$wpdb->get_results( $table );
		}
		return $tables;
	}

	/**
	 * Return a list of WPForms Entries tables. Used to make sure all WPForms tables are dropped when uninstalling the plugin
	 * in a single site or multi site environment.
	 *
	 * @return array WPforms Entries tables.
	 */
	public static function get_tables() {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}wpforms_entries",
			"{$wpdb->prefix}wpforms_entrymeta",
		);

		return $tables;
	}

	/**
	 * Drop WPForms Entries tables.
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // WPCS: unprepared SQL ok.
		}
	}

	/**
	 * wpforms compatibility notice.
	 */
	public function wpforms_missing_notice() {
		echo '<div class="error notice is-dismissible"><p>' . sprintf( esc_html__( 'Please install WPForms plugin to use Entries For WPForms.',  'entries-for-wpforms' ) ) .'</div>';
	}
}
