<?php
/**
 * Custom shortcode to display form entries.
 *
 * Usage [wpforms_entries id="FORMID"]
 *
 * @link https://gist.github.com/jaredatch/934515afe3a047559e9d092923a9320c
 *
 * @class Entries_for_wpforms_frontend
 */
Class Entries_for_wpforms_frontend {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'wpforms_entries', array( $this, 'wpfe_entries_table' ) );
	}

	/**
	 * Renders the contents for the shortcode
	 * @param  array $atts attributes with the shortcode
	 * @return mixed
	 */
	public function wpfe_entries_table( $atts ) {

		$atts = shortcode_atts( array(
			'id' => ''
		), $atts );

		if ( empty( $atts['id'] ) || ! function_exists( 'wpforms' ) ) {
			return;
		}

		$form = wpforms()->form->get( absint( $atts['id'] ) );

		if ( empty( $form ) ) {
			return;
		}

		$form_data = ! empty( $form->post_content ) ? wpforms_decode( $form->post_content ) : '';

		$entry_ids   = wpfe_get_entries_ids( absint( $atts['id'] ) );
		$entries = array();

	  	foreach( $entry_ids as $entry_id ) {
	        $entries[] = wpfe_get_entry( $entry_id );
	     }

		$disallow  = apply_filters( 'wpforms_frontend_entries_table_disallow', array( 'divider', 'html', 'pagebreak', 'captcha' ) );
		$ids       = array();
		$rows      = array();

		ob_start();
		echo '<table class="wpforms-frontend-entries">';
			echo '<thead><tr>';

				foreach( $form_data['fields'] as $key => $field ) {

					if ( ! in_array( $field['type'], $disallow ) ) {
						$ids[ $key ] = $field['label'];
						echo '<th>' . sanitize_text_field( $field['label'] ) . '</th>';
					}
				}

			echo '</tr></thead>';

			echo '<tbody>';
				foreach( $entries as $entry ) {
					echo '<tr>';
					$fields = $entry->meta;
					foreach( $fields as $key => $field ) {
						$field_id = explode( 'entries_for_wpforms_field_id_', $key );
						if( is_array( $field_id ) && isset( $field_id[1] ) ) {
							if( isset( $ids[ $field_id[1] ] ) ) {
								echo '<td>' . apply_filters( 'wpforms_html_field_value', wp_strip_all_tags( $field ), $field, $form_data, 'entry-frontend-table' );
								echo '</td>';
							}
						}
					}
					echo '</tr>';
				}
			echo '</tbody>';

		echo '</table>';

		$output = ob_get_clean();

		return $output;
	}
}

new Entries_for_wpforms_frontend();
