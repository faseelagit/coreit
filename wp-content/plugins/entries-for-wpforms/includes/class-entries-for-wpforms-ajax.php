<?php
/**
 * AJAX actions for both admin and frontend side.
 *
 * @since  1.4.1
 *
 * @class Entries_for_wpforms_frontend
 */
Class Entries_For_Wpforms_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action( 'wp_ajax_entries_for_wpforms_deactivation_notice', array( $this, 'deactivation_notice') );
		add_action( 'wp_ajax_entries_for_wpforms_entry_email_modal', array( $this, 'entry_email_modal') );
		add_action( 'wp_ajax_entries_for_wpforms_send_entry_email', array( $this, 'send_entry_email') );
		add_action( 'wp_ajax_entries_for_wpforms_send_deactivation_email', array( $this, 'send_deactivation_email') );
		add_action( 'wp_ajax_entries_for_wpforms_dismiss_review_notice', array( $this, 'dismiss_review_notice') );
		add_action( 'wp_ajax_entries_for_wpforms_star', array( $this, 'star') );
		add_action( 'wp_ajax_entries_for_wpforms_read', array( $this, 'read') );
	}

	/**
	 * Plugin deactivation notice.
	 *
	 * @since  1.1.2
	 */
	public static function deactivation_notice() {

		check_ajax_referer( 'deactivation-notice', 'security' );

		ob_start();
		global $status, $page, $s;

		$reason_deactivation_url = 'http://sanjeebaryal.com.np/contact';
		$deactivate_url = wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . WPFORMS_ENTRIES_PLUGIN_FILE . '&amp;plugin_status=' . $status . '&amp;paged=' . $page . '&amp;s=' . $s, 'deactivate-plugin_' . WPFORMS_ENTRIES_PLUGIN_FILE );

		?>
						<!-- The Modal -->
			<div id="entries-for-wpforms-modal" class="entries-for-wpforms-modal">

				 <!-- Modal content -->
				 <div class="entries-for-wpforms-modal-content">
				    <div class="entries-for-wpforms-modal-header">
				    </div>

				    <div class="entries-for-wpforms-modal-body">
						<div class="container">
						  	<form method="post" id="entries-for-wpforms-send-deactivation-email">

								<div class="row">
										<h3 for=""><?php echo __( 'Would you care to let me know the deactivation reason so that I can improve it for you?', 'entries-for-wpforms');?></h3>
									<div class="col-75">
										<textarea id="message" name="message" placeholder="Deactivation Reason?" style="height:150px"></textarea>
									</div>
								</div>
								<div class="row">
										<?php wp_nonce_field( 'entries_for_wpforms_send_deactivation_email', 'entries_for_wpforms_send_deactivation_email' ); ?>
										<a href="<?php echo $deactivate_url;?>"><?php echo __( 'Skip and deactivate', 'entries-for-wpforms' );?>
										<input type="submit" id="wpfe-send-deactivation-email" value="Deactivate">
								</div>
						  </form>
						</div>

				    <div class="entries-for-wpforms-modal-footer">
				    </div>
				 </div>
			</div>

		<?php

		$content = ob_get_clean();
		wp_send_json( $content ); // WPCS: XSS OK.
	}

	/**
	 * Deactivation Email.
	 *
	 * @since  1.4.3
	 *
	 * @return void
	 */
	public function send_deactivation_email() {

		check_ajax_referer( 'entries_for_wpforms_send_deactivation_email', 'security' );

		$message = sanitize_textarea_field( $_POST['message'] );

		if( ! empty( $message ) ) {
			wp_mail( 'sanzeeb.aryal@gmail.com', 'Entries For WPForms Deactivation', $message );
		}

		deactivate_plugins( WPFORMS_ENTRIES_PLUGIN_FILE );
	}

	/**
	 * Entry email on click.
	 *
	 * @return void.
	 */
	public function entry_email_modal() {
		check_ajax_referer( 'entry-email-nonce', 'security' );

		$entry_id = isset( $_POST['entry_id'] ) ? (int) $_POST['entry_id'] : 0;
		$settings = '<a href="'. admin_url( '/admin.php?page=wpforms-settings&view=email' ) .'">' . __( 'Settings', 'entries-for-wpforms' ) . '</a>';
		$entry 	  		= wpfe_get_entry( $entry_id );
		$form_id  		= $entry->form_id;
		$emails_field_id 	 = wpfe_get_email_field( $form_id );
		$emails_field_values = array();

		foreach( $emails_field_id as $email_id ) {

			$email_value	= isset( $entry->meta['entries_for_wpforms_field_id_'.$email_id ] ) ? $entry->meta['entries_for_wpforms_field_id_'.$email_id]: '';

			if( !empty( $email_value ) ) {
				$emails_field_values[] = $email_value;
			}
		}

		$emails = implode( '; ', $emails_field_values );

		ob_start();


		?>
			<!-- The Modal -->
			<div id="entries-for-wpforms-modal" class="entries-for-wpforms-modal">

				 <!-- Modal content -->
				 <div class="entries-for-wpforms-modal-content">
				    <div class="entries-for-wpforms-modal-header">
				    	<span class="close">&times;</span>
				    	<p><?php echo sprintf( esc_html__( 'You can customize the email stylings from %s.', 'entries-for-wpforms' ), $settings );
				    	?></p>
				    </div>

				    <div class="entries-for-wpforms-modal-body">
						<div class="container">
						  	<form method="post" id="entries-for-wpforms-send-email">
								<div class="row">
									<div class="col-25">
										<label for="from_address"><?php echo __( 'Sending email from:', 'entries-for-wpforms' );?></label>
									</div>

									<div class="col-75">
										<label for="from_address"><?php echo get_bloginfo('name'). ' ('.get_option('admin_email') .')'; ?></label>
									</div>
								</div>
								<div class="row">
									<div class="col-25">
										<label for="toaddress"><?php echo __( 'Sending email to:', 'entries-for-wpforms' );?></label>
									</div>

									<div class="col-75">
										<input type="text" id="to_address" name="to_address" value="<?php echo $emails;?>" placeholder="<?php echo 'abc@example.com';?>" />
									</div>

								</div>
								<div class="row">
									<div class="col-25">
										<label for="subject"><?php echo __( 'Email Subject:', 'entries-for-wpforms' );?></label>
									</div>

									<div class="col-75">
										<input type="text" id="subject" name="subject" value="<?php echo __( 'Form Entries', 'entries-for-wpforms');?>" placeholder="<?php echo __( 'Form Entries', 'entries-for-wpforms');?>" />
									</div>

								</div>

								<div class="row">
									<div class="col-25">
										<label for="subject"><?php echo __( 'Email Message:', 'entries-for-wpforms');?></label>
									</div>
									<div class="col-75">
										<textarea id="message" name="message" placeholder="{all_fields}" style="height:150px">{all_fields}</textarea>
									</div>
								</div>
								<div class="row">
										<?php wp_nonce_field( 'entries_for_wpforms_send_entry_email', 'entries_for_wpforms_send_email' ); ?>
										<input type="hidden" id="entry_id" value="<?php echo $entry_id;?>">
										<input type="hidden" id="form_id" value="<?php echo $form_id;?>">
										<input type="submit" id="wpfe-send-email" value="Send">
								</div>
						  </form>
						  	<li>{all_fields} <?php echo __( 'smart tag will send the entries data of the respective user.', 'entries-for-wpforms');?></li>
							<li><?php echo __( 'Use semi-colon (;) to send emails to multiple addresses.', 'entries-for-wpforms');?>
							</li>
						</div>


				    <div class="entries-for-wpforms-modal-footer">
				    </div>
				 </div>
			</div>
		<?php

		$content = ob_get_clean();
		wp_send_json( $content ); // WPCS: XSS OK.

	}

	/**
	 *	Sends email to the entry submitter.
	 *
	 *	@return  void.
	 */
	public function send_entry_email() {
		check_ajax_referer( 'entries_for_wpforms_send_entry_email', 'security' );

		$to_address = isset( $_POST['to_address'] ) ? $_POST['to_address'] : '';
		$subject    = isset( $_POST['subject'] ) ? $_POST['subject'] : '';
		$message    = isset( $_POST['message'] ) ? $_POST['message'] : '';
		$entry_id   = isset( $_POST['entry_id'] ) ? $_POST['entry_id'] : '';
		$form_id    = isset( $_POST['form_id'] ) ? $_POST['form_id'] : '';
		$addresses 	= explode( ';', $to_address );
		$form       = wpforms()->form->get( $form_id );

		if( empty( $to_address ) ) {
			wp_send_json_error( array(
				'message' => __( 'Empty receiver address!', 'entries-for-wpforms' ),
			) );
		}

		foreach( $addresses as $address ) {

			if( ! is_email( trim( $address ) ) ) {
				wp_send_json_error( array(
					'message' => __( 'Invalid receiver address!', 'entries-for-wpforms' ),
				) );
			}
		}

		// Validate form is real and active (published).
		if ( ! $form || 'publish' !== $form->post_status ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid Form!', 'entries-for-wpforms' ),
			) );

		}

		// Formatted form data for hooks.
		$form_data = wpforms_decode( $form->post_content );
		$fields    = wpfe_prepare_fields( $entry_id, $form_data );


		$emails 	= new WPForms_WP_Emails();
		$emails->__set( 'entry_id', $entry_id );
		$emails->__set( 'fields', $fields );
		$emails->__set( 'from_name', get_bloginfo('name') );
		$emails->__set( 'from_address', get_option('admin_email') );
		$emails->__set( 'reply_to', get_option('admin_email') );
		$emails->__set( 'form_data', $form_data );

		foreach( $addresses as $address ) {
			$emails->send( trim( $address ), $subject, $message );
		}
	}

	/**
	 * Dismiss the reveiw notice on dissmiss click
	 *
	 * @since 1.4.5
	 */
	public function dismiss_review_notice() {

		check_admin_referer( 'review-nonce', 'security' );

        if ( ! empty( $_POST['dismissed'] ) ) {
            update_option( 'wpfe_review_notice_dismissed', 'yes' );
        }
	}

	/**
	 * Star Entries.
	 *
	 * @since  1.4.7
	 *
	 * @return void.
	 */
	public function star() {

		check_admin_referer( 'star-nonce', 'security' );
		global $wpdb;

		$entry_id = absint( $_POST['entry_id'] );
		$filled	  = absint( $_POST['filled'] );

		if( $entry_id ) {

			$entry_metadata = array(
				'entry_id'   => $entry_id,
				'meta_key'   => 'star',
				'meta_value' => $filled,
			);

			// Insert entry meta.
			$wpdb->insert( $wpdb->prefix . 'wpforms_entrymeta', $entry_metadata );
		}
	}

	/**
	 * Read Entries.
	 *
	 * @since  1.4.7
	 *
	 * @return  void.
	 */
	public function read() {

		check_admin_referer( 'read-nonce', 'security' );
		global $wpdb;

		$entry_id = absint( $_POST['entry_id'] );
		$read	  = absint( $_POST['read'] );

		if( $entry_id ) {

			$entry_metadata = array(
				'entry_id'   => $entry_id,
				'meta_key'   => 'read',
				'meta_value' => $read,
			);

			// Insert entry meta.
			$wpdb->insert( $wpdb->prefix . 'wpforms_entrymeta', $entry_metadata );
		}
	}
}

new Entries_For_Wpforms_Ajax;
