<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Entries_For_WPforms_Geolocation
 */
Class Entries_For_WPforms_Geolocation {

	/**
	 * API endpoints for looking up user IP address.
	 *
	 * @var array
	 */
	private $ip_lookup_apis = array(
		'ipify'             => 'http://api.ipify.org/',
		'ipecho'            => 'http://ipecho.net/plain',
		'ident'             => 'http://ident.me',
		'whatismyipaddress' => 'http://bot.whatismyipaddress.com',
	);

	/**
	 * API endpoints for geolocating an IP address
	 *
	 * @var array
	 */
	private $geoip_apis = array(
		'ipapi.co'   => 'https://ipapi.co/%s/json',
		'ipinfo.io'  => 'https://ipinfo.io/%s/json',
		'ip-api.com' => 'http://ip-api.com/json/%s',
	);

	/**
	 * Entries_For_WPforms_Geolocation Constructor
	 */
	public function __construct() {

		// Process entry.
		add_action( 'wpforms_complete_process_entry', array( $this, 'save_geolocation_data' ), 100, 5 );
		add_action( 'wpforms_entries_entry_details_content', array( $this, 'display_geolocation_data'), 100, 2 );
	}

	public function save_geolocation_data( $entry_id, $form_fields, $entry, $form_id, $form_data ) {

		$disable_geolocation = get_option( 'entries_for_wpforms_disable_geolocation', '0' );
		$disable_geolocation = apply_filters( 'entries_for_wpforms_disable_geolocation', $disable_geolocation );

		// Return if geolocation is disabled via filter.
		if( '0' !== $disable_geolocation ) {
			return;
		}

		global $wpdb;

		if( empty( $entry_id ) ) {
			return;
		}

		$ip_address   = wpforms_entries_get_ip_address();
		$geo_location = $this->geolocate_ip( $ip_address, true );

		$entry_metadata = array(
				'entry_id'   => $entry_id,
				'meta_key'   => 'location',
				'meta_value' => json_encode( $geo_location ),
			);

		// Insert entry meta.
		$wpdb->insert( $wpdb->prefix . 'wpforms_entrymeta', $entry_metadata );
	}

	/**
	 * Use APIs to Geolocate the user.
	 *
	 * @param  string $ip_address IP address.
	 * @return string|bool
	 */
	public function geolocate_via_api( $ip_address ) {
		$location_data = get_transient( 'geoip_' . $ip_address );

		if ( false === $location_data ) {
			$geoip_data          = array();
			$geoip_services      = apply_filters( 'entries_for_wpforms_geolocation_geoip_apis', $this->geoip_apis );
			$geoip_services_keys = array_keys( $geoip_services );
			shuffle( $geoip_services_keys );

			foreach ( $geoip_services_keys as $service_name ) {
				$service_endpoint = $geoip_services[ $service_name ];
				$response         = wp_safe_remote_get( sprintf( $service_endpoint, $ip_address ), array( 'timeout' => 2 ) );

				if ( ! is_wp_error( $response ) && $response['body'] ) {
					switch ( $service_name ) {
						case 'ipinfo.io-':
							$data                       = json_decode( $response['body'] );
							$lat_log                    = isset( $data->loc ) ? explode( ',', $data->loc ) : array();
							$geoip_data['country']      = isset( $data->country ) ? $data->country : '';
							$geoip_data['country_code'] = isset( $data->country ) ? $data->country : '';
							$geoip_data['city']         = isset( $data->city ) ? $data->city : '';
							$geoip_data['region']       = isset( $data->region ) ? $data->region : '';
							$geoip_data['postal']       = isset( $data->postal ) ? $data->postal : '';
							$geoip_data['latitude']     = isset( $lat_log[0] ) ? $lat_log[0] : '';
							$geoip_data['longitude']    = isset( $lat_log[1] ) ? $lat_log[1] : '';
							break;
						case 'ip-api.com':
							$data                       = json_decode( $response['body'] );
							$geoip_data['country']      = isset( $data->country ) ? $data->country : '';
							$geoip_data['country_code'] = isset( $data->countryCode ) ? $data->countryCode : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
							$geoip_data['city']         = isset( $data->city ) ? $data->city : '';
							$geoip_data['region']       = isset( $data->regionName ) ? $data->regionName : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
							$geoip_data['postal']       = isset( $data->postal ) ? $data->postal : '';
							$geoip_data['latitude']     = isset( $data->lat ) ? $data->lat : '';
							$geoip_data['longitude']    = isset( $data->lon ) ? $data->lon : '';
							break;
						case 'ipapi.co-':
							$data                       = json_decode( $response['body'] );
							$geoip_data['country']      = isset( $data->country_name ) ? $data->country_name : '';
							$geoip_data['country_code'] = isset( $data->country ) ? $data->country : '';
							$geoip_data['city']         = isset( $data->city ) ? $data->city : '';
							$geoip_data['region']       = isset( $data->region ) ? $data->region : '';
							$geoip_data['postal']       = isset( $data->postal ) ? $data->postal : '';
							$geoip_data['latitude']     = isset( $data->latitude ) ? $data->latitude : '';
							$geoip_data['longitude']    = isset( $data->longitude ) ? $data->longitude : '';
							break;
						default:
							$geoip_data = apply_filters( 'entries_for_wpforms_geolocation_geoip_response_' . $service_name, array(), $response['body'] );
							break;
					}

					$location_data = array_map( 'sanitize_text_field', $geoip_data );

					if ( ! empty( $location_data['country'] ) ) {
						break;
					}
				}
			}

			set_transient( 'geoip_' . $ip_address, $location_data, WEEK_IN_SECONDS );
		}

		return $location_data;
	}

	/**
	 * Geolocate an IP address.
	 *
	 * @param  string $ip_address   IP Address.
	 * @param  bool   $fallback     If true, fallbacks to alternative IP detection (can be slower).
	 * @param  bool   $api_fallback If true, uses geolocation APIs if the database file doesn't exist (can be slower).
	 * @return array
	 */
	public function geolocate_ip( $ip_address = '', $fallback = true, $api_fallback = true ) {
		// Filter to allow custom geolocation of the IP address.
		$location_data = apply_filters( 'entries_for_wpforms_geolocate_ip', array(), $ip_address, $fallback, $api_fallback );

		if ( empty( $location_data ) ) {
			$ip_address = $ip_address ? $ip_address : $this->get_ip_address();

			if ( $api_fallback ) {
				$location_data = $this->geolocate_via_api( $ip_address );
			} else {
				$location_data = array();
			}

			if ( empty( $location_data['country'] ) && $fallback ) {
				// May be a local environment - find external IP.
				return $this->geolocate_ip( $this->get_external_ip_address(), false, $api_fallback );
			}
		}

		return $location_data;
	}

	/**
	 * Get current user IP Address.
	 *
	 * @return string
	 */
	public static function get_ip_address() {
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
	 * Get user IP Address using an external service.
	 * This is used mainly as a fallback for users on localhost where
	 * get_ip_address() will be a local IP and non-geolocatable.
	 *
	 * @return string
	 */
	public function get_external_ip_address() {
		$external_ip_address = '0.0.0.0';

		if ( '' !== $this->get_ip_address() ) {
			$transient_name      = 'external_ip_address_' . $this->get_ip_address();
			$external_ip_address = get_transient( $transient_name );
		}

		if ( false === $external_ip_address ) {
			$external_ip_address     = '0.0.0.0';
			$ip_lookup_services      = apply_filters( 'entries_for_wpforms_geolocation_ip_lookup_apis', $this->ip_lookup_apis );
			$ip_lookup_services_keys = array_keys( $ip_lookup_services );
			shuffle( $ip_lookup_services_keys );

			foreach ( $ip_lookup_services_keys as $service_name ) {
				$service_endpoint = $ip_lookup_services[ $service_name ];
				$response         = wp_safe_remote_get( $service_endpoint, array( 'timeout' => 2 ) );

				if ( ! is_wp_error( $response ) && rest_is_ip_address( $response['body'] ) ) {
					$external_ip_address = apply_filters( 'entries_for_wpforms_ip_lookup_api_response', $response['body'], $service_name );
					break;
				}
			}

			set_transient( $transient_name, $external_ip_address, WEEK_IN_SECONDS );
		}

		return $external_ip_address;
	}

	public function display_geolocation_data( $entry, $form_id ) {
		$location = isset( $entry->meta['location'] ) ? json_decode( $entry->meta['location'], true ) : array();

		$google_map_url = '';

		if ( ! empty( $location ) ) {
			$google_map_url = add_query_arg(
				array(
					'q'      => $location['city'] . ',' . isset( $location['region'] ) ? $location['region'] : $location['region'] ,
					'll'     => $location['latitude'] . ',' . $location['longitude'],
					'z'      => apply_filters( 'entries_for_wpforms_geolocation_map_zoom', '6' ),
					'output' => 'embed',
				),
				'https://maps.google.com/maps'
			);
		}
		?><iframe frameborder="0" src="<?php echo esc_url( $google_map_url ); ?>" style="margin-left:10px;width:100%;height:320px;"></iframe><?php
	}
}

new Entries_For_WPforms_Geolocation;
