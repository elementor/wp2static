<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.1.2
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$fs   = freemius( $VARS['id'] );
	$slug = $fs->get_slug();

	$confirmation_message = $fs->apply_filters( 'uninstall_confirmation_message', '' );

	$reasons = $VARS['reasons'];

	$reasons_list_items_html = '';

	foreach ( $reasons as $reason ) {
		$list_item_classes    = 'reason' . ( ! empty( $reason['input_type'] ) ? ' has-input' : '' );

		if ( isset( $reason['internal_message'] ) && ! empty( $reason['internal_message'] ) ) {
			$list_item_classes .= ' has-internal-message';
			$reason_internal_message = $reason['internal_message'];
		} else {
			$reason_internal_message = '';
		}
		
		$reason_input_type = ( ! empty( $reason['input_type'] ) ? $reason['input_type'] : '' );
        $reason_input_placeholder = ( ! empty( $reason['input_placeholder'] ) ? $reason['input_placeholder'] : '' );
			
		$reason_list_item_html = <<< HTML
			<li class="{$list_item_classes}"
			 	data-input-type="{$reason_input_type}"
			 	data-input-placeholder="{$reason_input_placeholder}">
	            <label>
	            	<span>
	            		<input type="radio" name="selected-reason" value="{$reason['id']}"/>
                    </span>
                    <span>{$reason['text']}</span>
                </label>
                <div class="internal-message">{$reason_internal_message}</div>
            </li>
HTML;

		$reasons_list_items_html .= $reason_list_item_html;
	}

	$is_anonymous = ( ! $fs->is_registered() );
	if ( $is_anonymous ) {
		$anonymous_feedback_checkbox_html = sprintf(
			'<label class="anonymous-feedback-label"><input type="checkbox" class="anonymous-feedback-checkbox"> %s</label>',
			fs_esc_html_inline( 'Anonymous feedback', 'anonymous-feedback', $slug )
		);
	} else {
		$anonymous_feedback_checkbox_html = '';
	}

	// Aliases.
	$deactivate_text = fs_text_inline( 'Deactivate', 'deactivate', $slug );
	$theme_text      = fs_text_inline( 'Theme', 'theme', $slug );
	$activate_x_text = fs_text_inline( 'Activate %s', 'activate-x', $slug );

	fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
(function ($) {
	var reasonsHtml = <?php echo json_encode( $reasons_list_items_html ); ?>,
	    modalHtml =
		    '<div class="fs-modal fs-modal-deactivation-feedback<?php echo empty( $confirmation_message ) ? ' no-confirmation-message' : ''; ?>">'
		    + '	<div class="fs-modal-dialog">'
		    + '		<div class="fs-modal-header">'
		    + '		    <h4><?php fs_esc_attr_echo_inline( 'Quick feedback', 'quick-feedback' , $slug ) ?></h4>'
		    + '		</div>'
		    + '		<div class="fs-modal-body">'
		    + '			<div class="fs-modal-panel" data-panel-id="confirm"><p><?php echo $confirmation_message; ?></p></div>'
		    + '			<div class="fs-modal-panel active" data-panel-id="reasons"><h3><strong><?php echo esc_js( sprintf( fs_text_inline( 'If you have a moment, please let us know why you are %s', 'deactivation-share-reason' , $slug ), ( $fs->is_plugin() ? fs_text_inline( 'deactivating', 'deactivating', $slug ) : fs_text_inline( 'switching', 'switching', $slug ) ) ) ) ?>:</strong></h3><ul id="reasons-list">' + reasonsHtml + '</ul></div>'
		    + '		</div>'
		    + '		<div class="fs-modal-footer">'
			+ '         <?php echo $anonymous_feedback_checkbox_html ?>'
		    + '			<a href="#" class="button button-secondary button-deactivate"></a>'
		    + '			<a href="#" class="button button-primary button-close"><?php fs_esc_js_echo_inline( 'Cancel', 'cancel' , $slug ) ?></a>'
		    + '		</div>'
		    + '	</div>'
		    + '</div>',
	    $modal = $(modalHtml),
	    $deactivateLink = $('#the-list .deactivate > [data-module-id=<?php echo $fs->get_id() ?>].fs-module-id').prev(),
	    selectedReasonID = false,
	    redirectLink = '',
		$anonymousFeedback    = $modal.find( '.anonymous-feedback-label' ),
		isAnonymous           = <?php echo ( $is_anonymous ? 'true' : 'false' ); ?>,
		otherReasonID         = <?php echo Freemius::REASON_OTHER; ?>,
		dontShareDataReasonID = <?php echo Freemius::REASON_DONT_LIKE_TO_SHARE_MY_INFORMATION; ?>,
        deleteThemeUpdateData = <?php echo $fs->is_theme() && $fs->is_premium() && ! $fs->has_active_valid_license() ? 'true' : 'false' ?>;

	$modal.appendTo($('body'));

	registerEventHandlers();

	function registerEventHandlers() {
		<?php
		if ( $fs->is_plugin() ) { ?>
		$deactivateLink.click(function (evt) {
			evt.preventDefault();

			redirectLink = $(this).attr('href');

			showModal();
		});
		<?php
		/**
		 * For "theme" module type, the modal is shown when the current user clicks on
		 * the "Activate" button of any other theme. The "Activate" button is actually
		 * a link to the "Themes" page (/wp-admin/themes.php) containing query params
		 * that tell WordPress to deactivate the current theme and activate a different theme.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since 1.2.2
		 *        
		 * @since 1.2.2.7 Don't trigger the deactivation feedback form if activating the premium version of the theme.
		 */
		} else { ?>
		$('body').on('click', '.theme-browser .theme:not([data-slug=<?php echo $slug ?>-premium]) .theme-actions .button.activate', function (evt) {
			evt.preventDefault();

			redirectLink = $(this).attr('href');

			showModal();
		});
		<?php
		} ?>

		$modal.on('input propertychange', '.reason-input input', function () {
			if (!isOtherReasonSelected()) {
				return;
			}

			var reason = $(this).val().trim();

			/**
			 * If reason is not empty, remove the error-message class of the message container
			 * to change the message color back to default.
			 */
			if (reason.length > 0) {
				$('.message').removeClass('error-message');
				enableDeactivateButton();
			}
		});

		$modal.on('blur', '.reason-input input', function () {
			var $userReason = $(this);

			setTimeout(function () {
				if (!isOtherReasonSelected()) {
					return;
				}

				/**
				 * If reason is empty, add the error-message class to the message container
				 * to change the message color to red.
				 */
				if (0 === $userReason.val().trim().length) {
					$('.message').addClass('error-message');
					disableDeactivateButton();
				}
			}, 150);
		});

		$modal.on('click', '.fs-modal-footer .button', function (evt) {
			evt.preventDefault();

			if ($(this).hasClass('disabled')) {
				return;
			}

			var _parent = $(this).parents('.fs-modal:first');
			var _this = $(this);

			if (_this.hasClass('allow-deactivate')) {
				var $radio = $('input[type="radio"]:checked');

				if (0 === $radio.length) {
				    if ( ! deleteThemeUpdateData ) {
                        // If no selected reason, just deactivate the plugin.
                        window.location.href = redirectLink;
                    } else {
                        $.ajax({
                            url       : ajaxurl,
                            method    : 'POST',
                            data      : {
                                action   : '<?php echo $fs->get_ajax_action( 'delete_theme_update_data' ) ?>',
                                security : '<?php echo $fs->get_ajax_security( 'delete_theme_update_data' ) ?>',
                                module_id: '<?php echo $fs->get_id() ?>'
                            },
                            beforeSend: function() {
                                _parent.find( '.fs-modal-footer .button' ).addClass( 'disabled' );
                                _parent.find( '.fs-modal-footer .button-secondary' ).text( 'Processing...' );
                            },
                            complete  : function() {
                                window.location.href = redirectLink;
                            }
                        });
                    }

					return;
				}

				var $selected_reason = $radio.parents('li:first'),
				    $input = $selected_reason.find('textarea, input[type="text"]'),
				    userReason = ( 0 !== $input.length ) ? $input.val().trim() : '';

				if (isOtherReasonSelected() && ( '' === userReason )) {
					return;
				}

				$.ajax({
					url       : ajaxurl,
					method    : 'POST',
					data      : {
						action      : '<?php echo $fs->get_ajax_action( 'submit_uninstall_reason' ) ?>',
						security    : '<?php echo $fs->get_ajax_security( 'submit_uninstall_reason' ) ?>',
						module_id   : '<?php echo $fs->get_id() ?>',
						reason_id   : $radio.val(),
						reason_info : userReason,
						is_anonymous: isAnonymousFeedback()
					},
					beforeSend: function () {
						_parent.find('.fs-modal-footer .button').addClass('disabled');
						_parent.find('.fs-modal-footer .button-secondary').text('Processing...');
					},
					complete  : function () {
						// Do not show the dialog box, deactivate the plugin.
						window.location.href = redirectLink;
					}
				});
			} else if (_this.hasClass('button-deactivate')) {
				// Change the Deactivate button's text and show the reasons panel.
				_parent.find('.button-deactivate').addClass('allow-deactivate');

				showPanel('reasons');
			}
		});

		$modal.on('click', 'input[type="radio"]', function () {
			var $selectedReasonOption = $(this);

			// If the selection has not changed, do not proceed.
			if (selectedReasonID === $selectedReasonOption.val())
				return;

			selectedReasonID = $selectedReasonOption.val();

			if ( isAnonymous ) {
				if ( isReasonSelected( dontShareDataReasonID ) ) {
					$anonymousFeedback.hide();
				} else {
					$anonymousFeedback.show();
				}
			}

			var _parent = $(this).parents('li:first');

			$modal.find('.reason-input').remove();
			$modal.find( '.internal-message' ).hide();
			$modal.find('.button-deactivate').html('<?php echo esc_js( sprintf(
				fs_text_inline( 'Submit & %s', 'deactivation-modal-button-submit' , $slug ),
				$fs->is_plugin() ?
					$deactivate_text :
					sprintf( $activate_x_text, $theme_text )
			) ) ?>');

			enableDeactivateButton();

			if ( _parent.hasClass( 'has-internal-message' ) ) {
				_parent.find( '.internal-message' ).show();
			}

			if (_parent.hasClass('has-input')) {
				var inputType = _parent.data('input-type'),
				    inputPlaceholder = _parent.data('input-placeholder'),
				    reasonInputHtml = '<div class="reason-input"><span class="message"></span>' + ( ( 'textfield' === inputType ) ? '<input type="text" />' : '<textarea rows="5"></textarea>' ) + '</div>';

				_parent.append($(reasonInputHtml));
				_parent.find('input, textarea').attr('placeholder', inputPlaceholder).focus();

				if (isOtherReasonSelected()) {
					showMessage('<?php echo esc_js( fs_text_inline( 'Kindly tell us the reason so we can improve.', 'ask-for-reason-message' , $slug ) ); ?>');
					disableDeactivateButton();
				}
			}
		});

		// If the user has clicked outside the window, cancel it.
		$modal.on('click', function (evt) {
			var $target = $(evt.target);

			// If the user has clicked anywhere in the modal dialog, just return.
			if ($target.hasClass('fs-modal-body') || $target.hasClass('fs-modal-footer')) {
				return;
			}

			// If the user has not clicked the close button and the clicked element is inside the modal dialog, just return.
			if (!$target.hasClass('button-close') && ( $target.parents('.fs-modal-body').length > 0 || $target.parents('.fs-modal-footer').length > 0 )) {
				return;
			}

			closeModal();
			return false;
		});
	}

	function isAnonymousFeedback() {
		if ( ! isAnonymous ) {
			return false;
		}

		return ( isReasonSelected( dontShareDataReasonID ) || $anonymousFeedback.find( 'input' ).prop( 'checked' ) );
	}

	function isReasonSelected( reasonID ) {
		// Get the selected radio input element.
		var $selectedReasonOption = $modal.find('input[type="radio"]:checked');

		return ( reasonID == $selectedReasonOption.val() );
	}

	function isOtherReasonSelected() {
		return isReasonSelected( otherReasonID );
	}

	function showModal() {
		resetModal();

		// Display the dialog box.
		$modal.addClass('active');

		$('body').addClass('has-fs-modal');
	}

	function closeModal() {
		$modal.removeClass('active');

		$('body').removeClass('has-fs-modal');
	}

	function resetModal() {
		selectedReasonID = false;

		enableDeactivateButton();

		// Uncheck all radio buttons.
		$modal.find('input[type="radio"]').prop('checked', false);

		// Remove all input fields ( textfield, textarea ).
		$modal.find('.reason-input').remove();

		$modal.find('.message').hide();

		if ( isAnonymous ) {
			$anonymousFeedback.find( 'input' ).prop( 'checked', false );

			// Hide, since by default there is no selected reason.
			$anonymousFeedback.hide();
		}

		var $deactivateButton = $modal.find('.button-deactivate');

		/*
		 * If the modal dialog has no confirmation message, that is, it has only one panel, then ensure
		 * that clicking the deactivate button will actually deactivate the plugin.
		 */
		if ($modal.hasClass('no-confirmation-message')) {
			$deactivateButton.addClass('allow-deactivate');

			showPanel('reasons');
		} else {
			$deactivateButton.removeClass('allow-deactivate');

			showPanel('confirm');
		}
	}

	function showMessage(message) {
		$modal.find('.message').text(message).show();
	}

	function enableDeactivateButton() {
		$modal.find('.button-deactivate').removeClass('disabled');
	}

	function disableDeactivateButton() {
		$modal.find('.button-deactivate').addClass('disabled');
	}

	function showPanel(panelType) {
		$modal.find('.fs-modal-panel').removeClass('active ');
		$modal.find('[data-panel-id="' + panelType + '"]').addClass('active');

		updateButtonLabels();
	}

	function updateButtonLabels() {
		var $deactivateButton = $modal.find('.button-deactivate');

		// Reset the deactivate button's text.
		if ('confirm' === getCurrentPanel()) {
			$deactivateButton.text('<?php echo esc_js( sprintf(
				fs_text_inline( 'Yes - %s', 'deactivation-modal-button-confirm' , $slug ),
				$fs->is_plugin() ?
					$deactivate_text :
					sprintf( $activate_x_text, $theme_text )
			) ) ?>');
		} else {
			$deactivateButton.html('<?php echo esc_js( sprintf(
				fs_text_inline('Skip & %s', 'skip-and-x', $slug ),
				$fs->is_plugin() ?
					$deactivate_text :
					sprintf( $activate_x_text, $theme_text )
			) ) ?>');
		}
	}

	function getCurrentPanel() {
		return $modal.find('.fs-modal-panel.active').attr('data-panel-id');
	}
})(jQuery);
</script>
