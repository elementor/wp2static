<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.1.9
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var Freemius $fs
	 */
	$fs   = freemius( $VARS['id'] );
	$slug = $fs->get_slug();

	$cant_find_license_key_text = fs_text_inline( "Can't find your license key?", 'cant-find-license-key', $slug );
	$message_above_input_field  = fs_text_inline( 'Please enter the license key that you received in the email right after the purchase:', 'activate-license-message', $slug );
	$message_below_input_field  = '';

	$header_title = $fs->is_free_plan() ?
		fs_text_inline( 'Activate License', 'activate-license', $slug ) :
		fs_text_inline( 'Update License', 'update-license', $slug );

	if ( $fs->is_registered() ) {
		$activate_button_text = $header_title;
	} else {
		$freemius_site_url = $fs->has_paid_plan() ?
			'https://freemius.com/wordpress/' :
			// Insights platform information.
			'https://freemius.com/wordpress/usage-tracking/';

		$freemius_link = '<a href="' . $freemius_site_url . '" target="_blank" tabindex="0">freemius.com</a>';

		$message_below_input_field = sprintf(
			fs_text_inline( 'The %1$s will be periodically sending data to %2$s to check for security and feature updates, and verify the validity of your license.', 'license-sync-disclaimer', $slug ),
			$fs->get_module_label( true ),
			$freemius_link
		);

		$activate_button_text = fs_text_inline( 'Agree & Activate License', 'agree-activate-license', $slug );
	}

	$license_key_text = fs_text_inline( 'License key', 'license-key' , $slug );

    $is_network_activation   = (
        $fs->is_network_active() &&
        fs_is_network_admin() &&
        ! $fs->is_delegated_connection()
    );
    $network_activation_html = '';

    $sites_details = array();
    if ( $is_network_activation ) {
        $all_sites = Freemius::get_sites();

        foreach ( $all_sites as $site ) {
            $site_details = $fs->get_site_info( $site );

            $blog_id = Freemius::get_site_blog_id( $site );
            $install = $fs->get_install_by_blog_id($blog_id);

            if ( is_object( $install ) && FS_Plugin_License::is_valid_id( $install->license_id ) ) {
                $site_details['license_id'] = $install->license_id;
            }

            $sites_details[] = $site_details;
        }

        if ( $is_network_activation ) {
            $vars = array(
                'id'                  => $fs->get_id(),
                'sites'               => $sites_details,
                'require_license_key' => true
            );

            $network_activation_html = fs_get_template( 'partials/network-activation.php', $vars );
        }
    }

    $premium_licenses   = $fs->get_available_premium_licenses();
    $available_licenses = array();
    foreach ( $premium_licenses as $premium_license ) {
        $activations_left = $premium_license->left();
        if ( ! ( $activations_left > 0 ) ) {
            continue;
        }

        $available_licenses[ $activations_left . '_' . $premium_license->id ] = $premium_license;
    }

    $total_available_licenses = count( $available_licenses );
    if ( $total_available_licenses > 0 ) {
        $license_input_html = <<< HTML
        <div id="license_options_container">
            <table>
                <tbody>
                    <tr id="available_license_key_container">
                        <td><input type="radio" name="license_type" value="available"></td>
                        <td>
HTML;

        if ( $total_available_licenses > 1 ) {
            // Sort the licenses by number of activations left in descending order.
            krsort( $available_licenses );

            $license_input_html .= '<select id="licenses">';

            /**
             * @var FS_Plugin_License $license
             */
            foreach ( $available_licenses as $license ) {
                $label = sprintf(
                    "%s-Site %s License - %s",
                     ( 1 == $license->quota ?
                         'Single' :
                         $license->quota
                     ),
                     $fs->_get_plan_by_id( $license->plan_id )->title,
                     ( htmlspecialchars( substr( $license->secret_key, 0, 6 ) ) .
                        str_pad( '', 23 * 6, '&bull;' ) .
                        htmlspecialchars( substr( $license->secret_key, - 3 ) ) )
                );

                $license_input_html .= "<option data-id='{$license->id}' value='{$license->secret_key}' data-left='{$license->left()}'>{$label}</option>";
            }

            $license_input_html .= '</select>';
        } else {
            $available_licenses = array_values( $available_licenses );

            /**
             * @var FS_Plugin_License $available_license
             */
            $available_license  = $available_licenses[0];
            $value              = sprintf(
                "%s-Site %s License - %s",
                ( 1 == $available_license->quota ?
                    'Single' :
                    $available_license->quota
                ),
                $fs->_get_plan_by_id( $available_license->plan_id )->title,
                ( htmlspecialchars( substr( $available_license->secret_key, 0, 6 ) ) .
                    str_pad( '', 23 * 6, '&bull;' ) .
                    htmlspecialchars( substr( $available_license->secret_key, - 3 ) ) )
            );

            $license_input_html .= <<< HTML
                <input
                    id="available_license_key"
                    type="text"
                    value="{$value}"
                    data-id="{$available_license->id}"
                    data-license-key="{$available_license->secret_key}"
                    data-left="{$available_license->left()}"
                    readonly />
HTML;
        }

        $license_input_html .= <<< HTML
                        </td>
                    </tr>
                    <tr>
                        <td><input type="radio" name="license_type" value="other"></td>
                        <td id="other_license_key_container">
                            <label for="other_license_key">Other: </label>
                            <div>
                                <input id="other_license_key" class="license_key" type="text" placeholder="Enter license key" tabindex="1">
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
HTML;
    } else {
        $license_input_html = "<input class='license_key' type='text' placeholder='{$license_key_text}' tabindex='1' />";
    }

	/**
	 * IMPORTANT:
	 *  DO NOT ADD MAXLENGTH OR LIMIT THE LICENSE KEY LENGTH SINCE
	 *  WE DO WANT TO ALLOW INPUT OF LONGER KEYS (E.G. WooCommerce Keys)
	 *  FOR MIGRATED MODULES.
	 */
	$modal_content_html = <<< HTML
	<div class="notice notice-error inline license-activation-message"><p></p></div>
	<p>{$message_above_input_field}</p>
	{$license_input_html}
	<a class="show-license-resend-modal show-license-resend-modal-{$fs->get_unique_affix()}" href="!#" tabindex="2">{$cant_find_license_key_text}</a>
	{$network_activation_html}
	<p>{$message_below_input_field}</p>
HTML;

	fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
(function( $ ) {
	$( document ).ready(function() {
		var modalContentHtml = <?php echo json_encode($modal_content_html); ?>,
			modalHtml =
				'<div class="fs-modal fs-modal-license-activation">'
				+ '	<div class="fs-modal-dialog">'
				+ '		<div class="fs-modal-header">'
				+ '		    <h4><?php echo esc_js($header_title) ?></h4>'
				+ '         <a href="!#" class="fs-close"><i class="dashicons dashicons-no" title="<?php echo esc_js( fs_text_x_inline( 'Dismiss', 'as close a window', 'dismiss', $slug ) ) ?>"></i></a>'
				+ '		</div>'
				+ '		<div class="fs-modal-body">'
				+ '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				+ '		</div>'
				+ '		<div class="fs-modal-footer">'
				+ '			<button class="button button-secondary button-close" tabindex="4"><?php fs_esc_js_echo_inline( 'Cancel', 'cancel', $slug ) ?></button>'
				+ '			<button class="button button-primary button-activate-license"  tabindex="3"><?php echo esc_js( $activate_button_text ) ?></button>'
				+ '		</div>'
				+ '	</div>'
				+ '</div>',
			$modal = $(modalHtml),
			$activateLicenseLink      = $('span.activate-license.<?php echo $fs->get_unique_affix() ?> a, .activate-license-trigger.<?php echo $fs->get_unique_affix() ?>'),
			$activateLicenseButton    = $modal.find('.button-activate-license'),
			$licenseKeyInput          = $modal.find('input.license_key'),
			$licenseActivationMessage = $modal.find( '.license-activation-message' ),
            isNetworkActivation       = <?php echo $is_network_activation ? 'true' : 'false' ?>;

		$modal.appendTo($('body'));

        var
            $licensesDropdown    = $( '#licenses' ),
            $licenseTypes        = $( 'input[type="radio"][name="license_type"]' ),
            $applyOnAllSites     = $( '#apply_on_all_sites' ),
            $sitesListContainer  = $( '#sites_list_container' ),
            $availableLicenseKey = $( '#available_license_key' ),
            $otherLicenseKey     = $( '#other_license_key' ),
            $multisiteOptionsContainer = $( '#multisite_options_container' ),
            $activationsLeft     = null,
            hasLicensesDropdown  = ( $licensesDropdown.length > 0 ),
            hasLicenseTypes      = ( $licenseTypes.length > 0 ),
            maxSitesListHeight   = null,
            totalSites           = <?php echo count( $sites_details ) ?>,
            singleBlogID         = null;

		function registerEventHandlers() {
            var
                $otherLicenseKeyContainer = $( '#other_license_key_container' );

            if ( isNetworkActivation ) {
                $applyOnAllSites.click(function() {
                    var applyOnAllSites = $( this ).is( ':checked' );

                    $multisiteOptionsContainer.toggleClass( 'apply-on-all-sites', applyOnAllSites );

                    showSites( ! applyOnAllSites );

                    if ( hasValidLicenseKey() && ( applyOnAllSites || hasSelectedSite() ) ) {
                        enableActivateLicenseButton();
                    } else {
                        disableActivateLicenseButton();
                    }
                });

                $sitesListContainer.delegate( 'td:not(:first-child)', 'click', function() {
                    // If a site row is clicked, trigger a click on the checkbox.
                    $( this ).parent().find( 'td:first-child input' ).click();
                });

                $sitesListContainer.delegate( 'input[type="checkbox"]', 'click', function() {
                    enableDisableSitesSelection();

                    if ( hasValidLicenseKey() && hasSelectedSite() ) {
                        enableActivateLicenseButton();
                    } else {
                        disableActivateLicenseButton();
                    }
                });
            }

            if ( hasLicensesDropdown ) {
                $licensesDropdown.change(function() {
                    // When a license is selected, select the associated radio button.
                    $licenseTypes.filter( '[value="available"]' ).attr( 'checked', true );

                    if ( ! isNetworkActivation || $modal.hasClass( 'is-single-site-activation' ) ) {
                        enableActivateLicenseButton();
                        return true;
                    }

                    toggleActivationOnAllSites();
                })
            }

            if ( hasLicenseTypes ) {
                $licenseTypes.change(function() {
                    var
                        licenseKey              = $( 'input.license_key' ).val().trim(),
                        otherLicenseKeySelected = isOtherLicenseKeySelected();

                    if ( ( licenseKey.length > 0 || ( hasLicenseTypes && ! otherLicenseKeySelected ) ) &&
                        ( $modal.hasClass( 'is-single-site-activation' ) || ! isNetworkActivation || hasSelectedSite() )
                    ) {
                        /**
                         * If the "other" license is not empty or an available license is selected, enable the activate
                         * button.
                         *
                         * @author Leo Fajardo (@leorw)
                         */
                        enableActivateLicenseButton();
                    } else {
                        disableActivateLicenseButton();
                    }

                    if ( ! isNetworkActivation ) {
                        return;
                    }

                    if ( otherLicenseKeySelected ) {
                        $applyOnAllSites.attr( 'disabled', false );
                        enableDisableSitesSelection();
                        resetActivateLicenseCheckboxLabel();
                    } else if ( ! $modal.hasClass( 'is-single-site-activation' ) ) {
                        toggleActivationOnAllSites();
                    }
                });

                if ( ! hasLicensesDropdown ) {
                    $availableLicenseKey.click(function() {
                        $licenseTypes.filter( '[value="available"]' ).click();
                    });
                }

                $otherLicenseKeyContainer.click(function() {
                    $licenseTypes.filter( '[value="other"]' ).click();
                });
            }

            $activateLicenseLink.click(function (evt) {
				evt.preventDefault();

				showModal( evt );
			});

			$modal.on('input propertychange', 'input.license_key', function () {

				var licenseKey = $(this).val().trim();

				/**
				 * If license key is not empty, enable the license activation button.
				 */
				if ( licenseKey.length > 0 && ( $modal.hasClass( 'is-single-site-activation' ) || ! isNetworkActivation || hasSelectedSite() ) ) {
					enableActivateLicenseButton();
				}
			});

			$modal.on( 'blur', 'input.license_key', function( evt ) {
				var
                    licenseKey                  = $(this).val().trim(),
                    $focusedElement             = $( evt.relatedTarget ),
                    hasSelectedAvailableLicense = ( hasLicenseTypes && $focusedElement.parents( '#available_license_key_container' ).length > 0 );

                /**
                 * If license key is empty, disable the license activation button.
                 */
                if ( ( 0 === licenseKey.length && ( ! hasLicenseTypes || ! hasSelectedAvailableLicense ) ) ||
                   ( isNetworkActivation && ! hasSelectedSite() )
                ) {
                   disableActivateLicenseButton();
                }
			});

			$modal.on('click', '.button-activate-license', function (evt) {
				evt.preventDefault();

				if ($(this).hasClass('disabled')) {
					return;
				}

				var
                    licenseKey = '';

				if ( hasLicenseTypes ) {
				    if ( isOtherLicenseKeySelected() ) {
				        licenseKey = $otherLicenseKey.val();
                    } else {
				        if ( ! hasLicensesDropdown ) {
                            licenseKey = $availableLicenseKey.data( 'license-key' );
                        } else {
                            licenseKey = $licensesDropdown.val();
                        }
                    }
                } else {
                    licenseKey = $licenseKeyInput.val().trim();
                }

				disableActivateLicenseButton();

				if (0 === licenseKey.length) {
					return;
				}

                var data = {
                    action     : '<?php echo $fs->get_ajax_action( 'activate_license' ) ?>',
                    security   : '<?php echo $fs->get_ajax_security( 'activate_license' ) ?>',
                    license_key: licenseKey,
                    module_id  : '<?php echo $fs->get_id() ?>'
                };

                if ( isNetworkActivation ) {
                    var
                        sites = [];

                    if ( null === singleBlogID ) {
                        var
                            applyOnAllSites = $applyOnAllSites.is( ':checked' );

                        $sitesListContainer.find( 'tr' ).each(function() {
                            var
                                $this       = $( this ),
                                includeSite = ( applyOnAllSites || $this.find( 'input' ).is( ':checked' ) );

                            if ( ! includeSite )
                                return;

                            var site = {
                                uid     : $this.find( '.uid' ).val(),
                                url     : $this.find( '.url' ).val(),
                                title   : $this.find( '.title' ).val(),
                                language: $this.find( '.language' ).val(),
                                charset : $this.find( '.charset' ).val(),
                                blog_id : $this.find( '.blog-id' ).find( 'span' ).text()
                            };

                            sites.push( site );
                        });
                    } else {
                        data.blog_id = singleBlogID;
                    }

                    data.sites = sites;
                }

				$.ajax({
					url: ajaxurl,
					method: 'POST',
                    data: data,
					beforeSend: function () {
						$activateLicenseButton.text( '<?php fs_esc_js_echo_inline( 'Activating license', 'activating-license', $slug ) ?>...' );
					},
					success: function( result ) {
						var resultObj = $.parseJSON( result );
						if ( resultObj.success ) {
							closeModal();

							// Redirect to the "Account" page and sync the license.
							window.location.href = resultObj.next_page;
						} else {
							showError( resultObj.error.message ? resultObj.error.message : resultObj.error );
							resetActivateLicenseButton();
						}
					}
				});
			});

			// If the user has clicked outside the window, close the modal.
			$modal.on('click', '.fs-close, .button-secondary', function () {
				closeModal();
				return false;
			});
		}

		registerEventHandlers();

        $('body').trigger('licenseActivationLoaded');

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.0.0
         */
		function enableDisableSitesSelection() {
            var
                canApplyOnAllSites    = $applyOnAllSites.is( ':enabled' ),
                disableSitesSelection = null;

            if ( ! canApplyOnAllSites ) {
                var
                    selectedSites         = $sitesListContainer.find( 'input[type="checkbox"]:checked' ).length,
                    activationsLeft       = Math.max( 0, $activationsLeft.data( 'left' ) - selectedSites );

                    disableSitesSelection = ( 0 === activationsLeft );

                    $activationsLeft.text( activationsLeft );
            } else {
                disableSitesSelection = false;
            }

            $sitesListContainer
                .find( 'input[type="checkbox"]:not(:checked)' )
                .attr( 'disabled', disableSitesSelection );
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.0.0
         *
         * @returns {Boolean}
         */
        function isOtherLicenseKeySelected() {
            return ( hasLicenseTypes && 'other' === $licenseTypes.filter( ':checked' ).val() );
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.0.0
         *
         * @returns {Boolean}
         */
        function hasValidLicenseKey() {
            var licenseKey = '';
            if ( hasLicenseTypes ) {
                if ( 'available' === $licenseTypes.filter( ':checked' ).val() ) {
                    return true;
                } else {
                    licenseKey = $otherLicenseKey.val();
                }
            } else {
                licenseKey = $( 'input.license_key' ).val();
            }

            return ( licenseKey.trim().length > 0 );
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.0.0
         *
         * @returns {Boolean}
         */
        function hasSelectedSite() {
            return ( $applyOnAllSites.is( ':checked' ) ||
                $sitesListContainer.find( 'input[type="checkbox"]:checked:not(:disabled)' ).length > 0 );
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.0.0
         */
        function toggleActivationOnAllSites() {
            var activationsLeft,
                licenseID;

            if (hasLicensesDropdown) {
                var $selectedOption = $licensesDropdown.find( ':selected' );
                activationsLeft = $selectedOption.data('left');
                licenseID = $selectedOption.data('id');
            } else {
                activationsLeft = $availableLicenseKey.data('left');
                licenseID = $availableLicenseKey.data('id');
            }

            // Cleanup previously auto-selected site.
            $('#sites_list_container input[type=checkbox]:disabled')
                .attr('disabled', false)
                .attr('checked', false);

            var $blogsWithActiveLicense = $('#sites_list_container tr[data-license-id=' + licenseID + '] input[type=checkbox]');

            if ($blogsWithActiveLicense.length > 0) {
                $blogsWithActiveLicense.attr('checked', true)
                    .attr('disabled', true);

                activationsLeft += $blogsWithActiveLicense.length;
            }

            if ( activationsLeft >= totalSites ) {
                $applyOnAllSites.attr( 'disabled', false );
                enableDisableSitesSelection();

                resetActivateLicenseCheckboxLabel();

                return;
            }

            $applyOnAllSites.attr( 'checked', false );
            $applyOnAllSites.attr( 'disabled', true );

            showSites( true );

            var
                activateLicenseCheckboxLabel = '<?php fs_esc_js_echo_inline( 'Choose up to %s site(s) to activate the license on.', 'choose-up-to-n-sites-to-activate-the-license-on', $slug ) ?>';

            activateLicenseCheckboxLabel = activateLicenseCheckboxLabel.replace( '%s', '<span data-left="' + activationsLeft + '" class="activations-left">' + activationsLeft + '</span>' );

            // Update the label of the "Activate license on all sites" checkbox.
            $applyOnAllSites.parent().find( 'span' ).html( activateLicenseCheckboxLabel );
            $activationsLeft = $( '.activations-left' );

            if ( hasSelectedSite() ) {
                enableActivateLicenseButton();
                enableDisableSitesSelection();
            } else {
                disableActivateLicenseButton();
            }
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.0.0
         */
        function resetActivateLicenseCheckboxLabel() {
            var activateLicenseCheckboxLabel = '<?php fs_esc_js_echo_inline( 'Activate license on all sites in the network.', 'activate-license-on-all-sites-in-the-network', $slug ) ?>';
            $applyOnAllSites.parent().find( 'span' ).text( activateLicenseCheckboxLabel );
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.0.0
         *
         * @param {Boolean} show
         */
		function showSites( show ) {
            $sitesListContainer.toggle( show );
            if ( show && null === maxSitesListHeight ) {
                /**
                 * Set the visible number of rows to 5 (5 * height of the first row).
                 *
                 * @author Leo Fajardo (@leorw)
                 */
                maxSitesListHeight = ( 5 * $sitesListContainer.find( 'tr:first' ).height() );
                $sitesListContainer.css( 'max-height', maxSitesListHeight );
            }
        }

		function showModal( evt ) {
			resetModal();

			// Display the dialog box.
			$modal.addClass('active');
			$('body').addClass('has-fs-modal');

            var
                $singleInstallDetails  = $( evt.target ).parents( 'tr.fs-install-details' ),
                isSingleSiteActivation = ( $singleInstallDetails.length > 0 );

            $modal.toggleClass( 'is-single-site-activation', isSingleSiteActivation );

            singleBlogID = isSingleSiteActivation ?
                $singleInstallDetails.prev().data( 'blog-id' ) :
                null;

            $multisiteOptionsContainer.toggle( isNetworkActivation && ! isSingleSiteActivation );

            if ( hasLicenseTypes ) {
                $licenseTypes.attr( 'checked', false );

                if ( hasLicensesDropdown ) {
                    $licensesDropdown.find( 'option:first' ).attr( 'selected', true ).trigger( 'change' );
                } else {
                    $licenseTypes.filter( '[value="available"]' ).click();
                }

                $otherLicenseKey.val( '' );
            } else {
                $licenseKeyInput.val( '' );
                $licenseKeyInput.focus();
            }
		}

		function closeModal() {
			$modal.removeClass('active');
			$('body').removeClass('has-fs-modal');
		}

		function resetActivateLicenseButton() {
			enableActivateLicenseButton();
			$activateLicenseButton.text( <?php echo json_encode( $activate_button_text ) ?> );
		}

		function resetModal() {
			hideError();
			resetActivateLicenseButton();
		}

		function enableActivateLicenseButton() {
			$activateLicenseButton.removeClass( 'disabled' );
		}

		function disableActivateLicenseButton() {
			$activateLicenseButton.addClass( 'disabled' );
		}

		function hideError() {
			$licenseActivationMessage.hide();
		}

		function showError( msg ) {
			$licenseActivationMessage.find( ' > p' ).html( msg );
			$licenseActivationMessage.show();
		}
	});
})( jQuery );
</script>