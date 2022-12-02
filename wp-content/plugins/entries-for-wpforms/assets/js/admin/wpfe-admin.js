/* global wpfe_plugins_params
 *
 * Modal is adapted from w3schools.
 *
 * @link https://www.w3schools.com/howto/howto_css_modals.asp
*/
jQuery( function( $ ) {

	jQuery( function( $ ) {

   		// Review notice.
    	jQuery('body').on('click', '#entries-for-wpforms-review-notice .notice-dismiss', function(e) {
    	    e.preventDefault();

	        jQuery("#entries-for-wpforms-review-notice").hide();

			var data = {
				action: 'entries_for_wpforms_dismiss_review_notice',
				security: wpfe_plugins_params.review_nonce,
				dismissed: true,
			};

			$.post( wpfe_plugins_params.ajax_url, data, function( response ) {
				// Success. Do nothing. Silence is golden.
        	});
    	});
	});

	jQuery('body').on('click', '.wpfe-star', function(e) {

		if( $(this).hasClass( 'dashicons-star-empty' ) ) {
			$(this).removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
			var filled = 1;
		} else {
			$(this).removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
			var filled = 0;
		}

		var entry_id = $(this).attr('data-id');

		var data = {
			action: 'entries_for_wpforms_star',
			security: wpfe_plugins_params.star_nonce,
			filled: filled,
			entry_id: entry_id,
		}

		$.post( wpfe_plugins_params.ajax_url, data, function( response ) {
			// Success. Do nothing. Silence is golden.
        });
	});

	jQuery('body').on('click', '.wpfe-read-unread', function(e) {

		if( $(this).hasClass( 'wpfe-unread' ) ) {
			$(this).removeClass('wpfe-unread').addClass('wpfe-read');
			$(this).removeAttr('title').attr( 'title', wpfe_plugins_params.mark_unread );
			var read = 1;
		} else {
			$(this).removeClass('wpfe-read').addClass('wpfe-unread');
			$(this).removeAttr('title').attr( 'title', wpfe_plugins_params.mark_read );
			var read = 0;
		}

		var entry_id = $(this).attr('data-id');

		var data = {
			action: 'entries_for_wpforms_read',
			security: wpfe_plugins_params.read_nonce,
			read: read,
			entry_id: entry_id,
		}

		$.post( wpfe_plugins_params.ajax_url, data, function( response ) {
			// Success. Do nothing. Silence is golden.
        });
	});

	var submenu_li = $('#toplevel_page_wpforms-overview').find('.entries-for-wpforms-submenu').closest('li');

	// Deactivation feedback.
 	$( document.body ).on( 'click' ,'tr[data-plugin="entries-for-wpforms/entries-for-wpforms.php"] span.deactivate a', function( e ) {
		e.preventDefault();

		var data = {
			action: 'entries_for_wpforms_deactivation_notice',
			security: wpfe_plugins_params.deactivation_nonce
		};

		$.post( wpfe_plugins_params.ajax_url, data, function( response ) {
			jQuery('#wpbody-content .wrap').append( response );
			var modal = document.getElementById('entries-for-wpforms-modal');

	  		// Open the modal.
	  		modal.style.display = "block";

	  		// On click on send email button on the modal.
		    $("#wpfe-send-deactivation-email").click( function( e ) {
		    	e.preventDefault();

		    	this.value 		= wpfe_plugins_params.deactivating;
		    	var form 		= $("#entries-for-wpforms-send-deactivation-email");

				var message		= form.find( ".row .col-75 textarea#message" ).val();
				var nonce 		= form.find( ".row #entries_for_wpforms_send_deactivation_email").val();

				var data = {
					action: 'entries_for_wpforms_send_deactivation_email',
					security: nonce,
					message: message,
				}

				$.post( wpfe_plugins_params.ajax_url, data, function( response ) {

					if( response.success === false ) {
						swal( wpfe_plugins_params.error, response.data.message, "error" );
					} else {
						swal( {title: wpfe_plugins_params.deactivated, text: wpfe_plugins_params.sad_to_see, icon: "success", allowOutsideClick: false, closeOnClickOutside: false });
						$('.swal-button--confirm').click( function (e) {
							location.reload();
						});
					}

					modal.remove();
				}).fail( function( xhr ) {
					swal( wpfe_plugins_params.error, wpfe_plugins_params.wrong, "error" );
				});

		    });

		}).fail( function( xhr ) {
			window.console.log( xhr.responseText );
		});
   });

   // Entry email Modal.
   $(".wpfe-email").click( function( e ) {
		e.preventDefault();
	  		var entry_id = $( this ).attr('entry-id');

	  	var data = {
	  		action: 'entries_for_wpforms_entry_email_modal',
		  	security: wpfe_plugins_params.entry_email_nonce,
		  	entry_id: entry_id
	  	};

	  	$.post( wpfe_plugins_params.ajax_url, data, function( response ) {
	  		jQuery('#wpbody-content .wrap').append( response );

			var modal = document.getElementById('entries-for-wpforms-modal');

	  		// Open the modal.
	  		modal.style.display = "block";

			// Get the <span> element that closes the modal
			var span = document.getElementsByClassName("close")[0];

			// When the user clicks on <span> (x), close the modal
			span.onclick = function() {
			  modal.remove();
			}

			// When the user clicks anywhere outside of the modal, close it
			window.onclick = function(event) {
			  if (event.target == modal) {
			    modal.remove();
			  }
			}

			// On click on send email button on the modal.
		    $("#wpfe-send-email").click( function( e ) {
		    	e.preventDefault();

		    	this.value 		= wpfe_plugins_params.sending;
		    	var form 		= $("#entries-for-wpforms-send-email");
		    	var to_address	= form.find( ".row .col-75 input#to_address" ).val();
				var subject		= form.find( ".row .col-75 input#subject" ).val();
				var message		= form.find( ".row .col-75 textarea#message" ).val();
				var entry_id	= form.find( ".row #entry_id" ).val();
				var form_id 	= form.find( ".row #form_id" ).val();
				var nonce 		= form.find( ".row #entries_for_wpforms_send_email").val();

				var data = {
					action: 'entries_for_wpforms_send_entry_email',
					security: nonce,
					entry_id: entry_id,
					form_id: form_id,
					to_address: to_address,
					subject: subject,
					message: message,
				}

				$.post( wpfe_plugins_params.ajax_url, data, function( response ) {

					if( response.success === false ) {
						swal( wpfe_plugins_params.error, response.data.message, "error" );
					} else {
						swal( wpfe_plugins_params.success, wpfe_plugins_params.sent, "success" );
					}

					modal.remove();
				}).fail( function( xhr ) {
					swal( wpfe_plugins_params.error, wpfe_plugins_params.wrong, "error" );
				});

		    });
	  	}).fail( function( xhr ) {
			swal( wpfe_plugins_params.error, wpfe_plugins_params.wrong, "error" );
		});
	});
});
