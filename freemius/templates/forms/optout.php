<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.2.1.5
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 * @var Freemius $fs
	 */
	$fs   = freemius( $VARS['id'] );
	$slug = $fs->get_slug();

	$action = $fs->is_tracking_allowed() ?
		'stop_tracking' :
		'allow_tracking';

	$reconnect_url = $fs->get_activation_url( array(
		'nonce'     => wp_create_nonce( $fs->get_unique_affix() . '_reconnect' ),
		'fs_action' => ( $fs->get_unique_affix() . '_reconnect' ),
	) );

	$plugin_title                   = "<strong>{$fs->get_plugin()->title}</strong>";
	$opt_out_text                   = fs_text_x_inline( 'Opt Out', 'verb', 'opt-out', $slug );
	$opt_in_text                    = fs_text_x_inline( 'Opt In', 'verb', 'opt-in', $slug );
	$opt_out_message_appreciation   = sprintf( fs_text_inline( 'We appreciate your help in making the %s better by letting us track some usage data.', 'opt-out-message-appreciation', $slug ), $fs->get_module_type() );
	$opt_out_message_usage_tracking = sprintf( fs_text_inline( "Usage tracking is done in the name of making %s better. Making a better user experience, prioritizing new features, and more good things. We'd really appreciate if you'll reconsider letting us continue with the tracking.", 'opt-out-message-usage-tracking', $slug ), $plugin_title );
	$opt_out_message_clicking_opt_out = sprintf(
		fs_text_inline( 'By clicking "Opt Out", we will no longer be sending any data from %s to %s.', 'opt-out-message-clicking-opt-out', $slug ),
		$plugin_title,
		sprintf(
			'<a href="%s" target="_blank">%s</a>',
			'https://freemius.com',
			'freemius.com'
		)
	);

	$admin_notice_params = array(
		'id'      => '',
		'slug'    => $fs->get_id(),
		'type'    => 'success',
		'sticky'  => false,
		'plugin'  => $fs->get_plugin()->title,
		'message' => $opt_out_message_appreciation
	);

	$admin_notice_html = fs_get_template( 'admin-notice.php', $admin_notice_params );

	$modal_content_html = <<< HTML
		<h2>{$opt_out_message_appreciation}</h2>
		<div class="notice notice-error inline opt-out-error-message"><p></p></div>
		<p>{$opt_out_message_usage_tracking}</p>
		<p>{$opt_out_message_clicking_opt_out}</p>
HTML;

	fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
	fs_enqueue_local_style( 'fs_common', '/admin/common.css' );
?>
<script type="text/javascript">
	(function( $ ) {
		$( document ).ready(function() {
			var modalContentHtml = <?php echo json_encode( $modal_content_html ) ?>,
			    modalHtml =
				    '<div class="fs-modal fs-modal-opt-out">'
				    + '	<div class="fs-modal-dialog">'
				    + '		<div class="fs-modal-header">'
				    + '		    <h4><?php echo esc_js( $opt_out_text ) ?></h4>'
				    + '		</div>'
				    + '		<div class="fs-modal-body">'
				    + '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				    + '		</div>'
				    + '		<div class="fs-modal-footer">'
				    + '			<button class="button button-secondary button-opt-out" tabindex="1"><?php echo esc_js( $opt_out_text ) ?></button>'
				    + '			<button class="button button-primary button-close" tabindex="2"><?php fs_esc_js_echo_inline( 'On second thought - I want to continue helping', 'opt-out-cancel', $slug ) ?></button>'
				    + '		</div>'
				    + '	</div>'
				    + '</div>',
			    $modal              = $( modalHtml ),
			    $adminNotice        = $( <?php echo json_encode( $admin_notice_html ) ?> ),
			    action              = '<?php echo $action ?>',
			    $actionLink         = $( 'span.opt-in-or-opt-out.<?php echo $slug ?> a' ),
			    $optOutButton       = $modal.find( '.button-opt-out' ),
			    $optOutErrorMessage = $modal.find( '.opt-out-error-message' ),
			    moduleID            = '<?php echo $fs->get_id() ?>';

			$actionLink.attr( 'data-action', action );
			$modal.appendTo( $( 'body' ) );

			function registerActionLinkClick() {
				$actionLink.click(function( evt ) {
					evt.preventDefault();

					if ( 'stop_tracking' == $actionLink.attr( 'data-action' ) ) {
						showModal();
					} else {
						optIn();
					}

					return false;
				});
			}

			function registerEventHandlers() {
				registerActionLinkClick();

				$modal.on( 'click', '.button-opt-out', function( evt ) {
					evt.preventDefault();

					if ( $( this ).hasClass( 'disabled' ) ) {
						return;
					}

					disableOptOutButton();
					optOut();
				});

				// If the user has clicked outside the window, close the modal.
				$modal.on( 'click', '.fs-close, .button-close', function() {
					closeModal();
					return false;
				});
			}

			registerEventHandlers();

			function showModal() {
				resetModal();

				// Display the dialog box.
				$modal.addClass( 'active' );
				$( 'body' ).addClass( 'has-fs-modal' );
			}

			function closeModal() {
				$modal.removeClass( 'active' );
				$( 'body' ).removeClass( 'has-fs-modal' );
			}

			function resetOptOutButton() {
				enableOptOutButton();
				$optOutButton.text( <?php echo json_encode( $opt_out_text ) ?> );
			}

			function resetModal() {
				hideError();
				resetOptOutButton();
			}

			function optIn() {
				sendRequest();
			}

			function optOut() {
				sendRequest();
			}

			function sendRequest() {
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action   : ( 'stop_tracking' == action ?
								'<?php echo $fs->get_ajax_action( 'stop_tracking' ) ?>' :
								'<?php echo $fs->get_ajax_action( 'allow_tracking' ) ?>'
						),
						security : ( 'stop_tracking' == action ?
								'<?php echo $fs->get_ajax_security( 'stop_tracking' ) ?>' :
								'<?php echo $fs->get_ajax_security( 'allow_tracking' ) ?>'
						),
						module_id: moduleID
					},
					beforeSend: function() {
						if ( 'opt-in' == action ) {
							$actionLink.text( '<?php fs_esc_js_echo_inline( 'Opting in', 'opting-in', $slug ) ?>...' );
						} else {
							$optOutButton.text( '<?php fs_esc_js_echo_inline( 'Opting out', 'opting-out', $slug ) ?>...' );
						}
					},
					success: function( resultObj ) {
						if ( resultObj.success ) {
							if ( 'allow_tracking' == action ) {
								action = 'stop_tracking';
								$actionLink.text( '<?php echo esc_js( $opt_out_text ) ?>' );
								showOptInAppreciationMessageAndScrollToTop();
							} else {
								action = 'allow_tracking';
								$actionLink.text( '<?php echo esc_js( $opt_in_text ) ?>' );
								closeModal();

								if ( $adminNotice.length > 0 ) {
									$adminNotice.remove();
								}
							}

							$actionLink.attr( 'data-action', action );
						} else {
							showError( resultObj.error );
							resetOptOutButton();
						}
					}
				});
			}

			function enableOptOutButton() {
				$optOutButton.removeClass( 'disabled' );
			}

			function disableOptOutButton() {
				$optOutButton.addClass( 'disabled' );
			}

			function hideError() {
				$optOutErrorMessage.hide();
			}

			function showOptInAppreciationMessageAndScrollToTop() {
				$adminNotice.insertAfter( $( '#wpbody-content' ).find( ' > .wrap > h1' ) );
				window.scrollTo(0, 0);
			}

			function showError( msg ) {
				$optOutErrorMessage.find( ' > p' ).html( msg );
				$optOutErrorMessage.show();
			}

			<?php if ( $fs->is_theme() ) : ?>
			/**
			 * Add opt-in/out button to the active theme's buttons collection
			 * in the theme's extended details overlay.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since 1.2.2.7
			 */
			$('.theme-overlay').contentChange(function () {
				if (!$(this).find('.theme-overlay').hasClass('active')) {
					// Add opt-in/out button only to the currently active theme.
					return;
				}

				if ($('#fs_theme_opt_in_out').length > 0){
					// Button already there.
					return;
				}

				var label = (('stop_tracking' == action) ?
					    '<?php echo esc_js( $opt_out_text ) ?>' :
				        '<?php echo esc_js( $opt_in_text ) ?>'),
				    href = (('stop_tracking' != action) ?
					    '<?php echo esc_js( $reconnect_url ) ?>' :
					    '');

				$actionLink = $('<a id="fs_theme_opt_in_out" href="' + encodeURI(href) + '" class="button" data-action="' + action + '">' + label + '</a>');

				$('.theme-wrap .theme-actions .active-theme').append($actionLink);

				registerActionLinkClick();
			});
			<?php endif ?>
		});
	})( jQuery );
</script>
