<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.7
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array    $VARS
	 * @var Freemius $fs
	 */
	$fs   = freemius( $VARS['id'] );
	$slug = $fs->get_slug();

	$is_pending_activation = $fs->is_pending_activation();
	$is_premium_only       = $fs->is_only_premium();
	$has_paid_plans        = $fs->has_paid_plan();
	$is_premium_code       = $fs->is_premium();
	$is_freemium           = $fs->is_freemium();

	$fs->_enqueue_connect_essentials();

	$current_user = Freemius::_get_current_wp_user();

	$first_name = $current_user->user_firstname;
	if ( empty( $first_name ) ) {
		$first_name = $current_user->nickname;
	}

	$site_url     = get_site_url();
	$protocol_pos = strpos( $site_url, '://' );
	if ( false !== $protocol_pos ) {
		$site_url = substr( $site_url, $protocol_pos + 3 );
	}

	$freemius_site_www = 'https://freemius.com';

	$freemius_site_url = $freemius_site_www . '/' . ( $fs->is_premium() ?
			'wordpress/' :
			// Insights platform information.
			'wordpress/usage-tracking/' . $fs->get_id() . "/{$slug}/" );

	if ( $fs->is_premium() ) {
		$freemius_site_url .= '?' . http_build_query( array(
				'id'   => $fs->get_id(),
				'slug' => $slug,
			) );
	}

	$freemius_link = '<a href="' . $freemius_site_url . '" target="_blank" tabindex="1">freemius.com</a>';

	$error = fs_request_get( 'error' );

	$require_license_key = $is_premium_only ||
	                       ( $is_freemium && $is_premium_code && fs_request_get_bool( 'require_license', true ) );

	if ( $is_pending_activation ) {
		$require_license_key = false;
	}

	if ( $require_license_key ) {
		$fs->_add_license_activation_dialog_box();
	}

	$is_optin_dialog = (
		$fs->is_theme() &&
		$fs->is_themes_page() &&
		( ! $fs->has_settings_menu() || $fs->is_free_wp_org_theme() )
	);

	if ( $is_optin_dialog ) {
		$show_close_button             = false;
		$previous_theme_activation_url = '';

		if ( ! $is_premium_code ) {
			$show_close_button = true;
		} else if ( $is_premium_only ) {
			$previous_theme_activation_url = $fs->get_previous_theme_activation_url();
			$show_close_button             = ( ! empty( $previous_theme_activation_url ) );
		}
	}

	$is_network_level_activation = (
		fs_is_network_admin() &&
		$fs->is_network_active() &&
		! $fs->is_network_delegated_connection()
	);

	$fs_user = Freemius::_get_user_by_email( $current_user->user_email );

	$activate_with_current_user = (
		is_object( $fs_user ) &&
		! $is_pending_activation &&
		// If requires a license for activation, use the user associated with the license for the opt-in.
		! $require_license_key &&
		! $is_network_level_activation
	);

    $optin_params = $fs->get_opt_in_params( array(), $is_network_level_activation );
    $sites        = isset( $optin_params['sites'] ) ? $optin_params['sites'] : array();

    $is_network_upgrade_mode = ( fs_is_network_admin() && $fs->is_network_upgrade_mode() );

    /* translators: %s: name (e.g. Hey John,) */
    $hey_x_text = esc_html( sprintf( fs_text_x_inline( 'Hey %s,', 'greeting', 'hey-x', $slug ), $first_name ) );

    $is_gdpr_required = ( ! $is_pending_activation && ! $require_license_key ) ?
	    FS_GDPR_Manager::instance()->is_required() :
        false;

    if ( is_null( $is_gdpr_required ) ) {
        $is_gdpr_required = $fs->fetch_and_store_current_user_gdpr_anonymously();
    }
?>
<?php
	if ( $is_optin_dialog ) { ?>
<div id="fs_theme_connect_wrapper">
	<?php
		if ( $show_close_button ) { ?>
			<button class="close dashicons dashicons-no"><span class="screen-reader-text">Close connect dialog</span>
			</button>
			<?php
		}
	?>
	<?php
		}
	?>
	<div id="fs_connect"
	     class="wrap<?php if ( ! fs_is_network_admin() && ( ! $fs->is_enable_anonymous() || $is_pending_activation || $require_license_key ) ) {
		     echo ' fs-anonymous-disabled';
	     } ?><?php echo $require_license_key ? ' require-license-key' : '' ?>">
		<div class="fs-visual">
			<b class="fs-site-icon"><i class="dashicons dashicons-wordpress"></i></b>
			<i class="dashicons dashicons-plus fs-first"></i>
			<?php
				$vars = array( 'id' => $fs->get_id() );
				fs_require_once_template( 'plugin-icon.php', $vars );
			?>
			<i class="dashicons dashicons-plus fs-second"></i>
			<img class="fs-connect-logo" width="80" height="80" src="//img.freemius.com/connect-logo.png"/>
		</div>
		<div class="fs-content">
			<?php if ( ! empty( $error ) ) : ?>
				<p class="fs-error"><?php echo esc_html( $error ) ?></p>
			<?php endif ?>
			<p><?php
					$button_label = fs_text_inline( 'Allow & Continue', 'opt-in-connect', $slug );
					$message = '';

					if ( $is_pending_activation ) {
						$button_label = fs_text_inline( 'Re-send activation email', 'resend-activation-email', $slug );

						$message = $fs->apply_filters( 'pending_activation_message', sprintf(
						    /* translators: %s: name (e.g. Thanks John!) */
							fs_text_inline( 'Thanks %s!', 'thanks-x', $slug ) . '<br>' .
							fs_text_inline( 'You should receive an activation email for %s to your mailbox at %s. Please make sure you click the activation button in that email to %s.', 'pending-activation-message', $slug ),
							$first_name,
							'<b>' . $fs->get_plugin_name() . '</b>',
							'<b>' . $current_user->user_email . '</b>',
							fs_text_inline( 'complete the install', 'complete-the-install', $slug )
						) );
					} else if ( $require_license_key ) {
						$button_label = $is_network_upgrade_mode ?
                            fs_text_inline( 'Activate License', 'agree-activate-license', $slug ) :
                            fs_text_inline( 'Agree & Activate License', 'agree-activate-license', $slug );

						$message = $fs->apply_filters(
						    'connect-message_on-premium',
                            ($is_network_upgrade_mode ?
                                '' :
                                /* translators: %s: name (e.g. Hey John,) */
                                $hey_x_text . '<br>'
                            ) .
							sprintf( fs_text_inline( 'Thanks for purchasing %s! To get started, please enter your license key:', 'thanks-for-purchasing', $slug ), '<b>' . $fs->get_plugin_name() . '</b>' ),
							$first_name,
							$fs->get_plugin_name()
						);
					} else {
						$filter                = 'connect_message';
						$default_optin_message = $is_gdpr_required ?
							fs_text_inline( 'Never miss an important update - opt in to our security & feature updates notifications, educational content, offers, and non-sensitive diagnostic tracking with %4$s.', 'connect-message', $slug) :
							fs_text_inline( 'Never miss an important update - opt in to our security and feature updates notifications, and non-sensitive diagnostic tracking with %4$s.', 'connect-message', $slug);

						if ( $fs->is_plugin_update() ) {
							// If Freemius was added on a plugin update, set different
							// opt-in message.
							$default_optin_message = $is_gdpr_required ?
								fs_text_inline( 'Never miss an important update - opt in to our security & feature updates notifications, educational content, offers, and non-sensitive diagnostic tracking with %4$s. If you skip this, that\'s okay! %1$s will still work just fine.', 'connect-message_on-update', $slug ) :
								fs_text_inline( 'Never miss an important update - opt in to our security & feature updates notifications, and non-sensitive diagnostic tracking with %4$s. If you skip this, that\'s okay! %1$s will still work just fine.', 'connect-message_on-update', $slug );

							// If user customized the opt-in message on update, use
							// that message. Otherwise, fallback to regular opt-in
							// custom message if exist.
							if ( $fs->has_filter( 'connect_message_on_update' ) ) {
								$filter = 'connect_message_on_update';
							}
						}

						$message = $fs->apply_filters(
						    $filter,
                            ($is_network_upgrade_mode ?
                                '' :
                                /* translators: %s: name (e.g. Hey John,) */
                                $hey_x_text . '<br>'
                            ) .
							sprintf(
								esc_html( $default_optin_message ),
								'<b>' . esc_html( $fs->get_plugin_name() ) . '</b>',
								'<b>' . $current_user->user_login . '</b>',
								'<a href="' . $site_url . '" target="_blank">' . $site_url . '</a>',
								$freemius_link
							),
							$first_name,
							$fs->get_plugin_name(),
							$current_user->user_login,
							'<a href="' . $site_url . '" target="_blank">' . $site_url . '</a>',
							$freemius_link,
							$is_gdpr_required
						);
					}

					if ( $is_network_upgrade_mode ) {
                        $network_integration_text = esc_html( fs_text_inline( 'We\'re excited to introduce the Freemius network-level integration.', 'connect_message_network_upgrade', $slug ) );

                        if ($is_premium_code){
                            $message = $network_integration_text . ' ' . sprintf( fs_text_inline( 'During the update process we detected %d site(s) that are still pending license activation.', 'connect_message_network_upgrade-premium', $slug ), count( $sites ) );

                            $message .= '<br><br>' . sprintf( fs_text_inline( 'If you\'d like to use the %s on those sites, please enter your license key below and click the activation button.', 'connect_message_network_upgrade-premium-activate-license', $slug ), $is_premium_only ? $fs->get_module_label( true ) : sprintf(
                                /* translators: %s: module type (plugin, theme, or add-on) */
                                    fs_text_inline( "%s's paid features", 'x-paid-features', $slug ),
                                    $fs->get_module_label( true )
                                ) );

                            /* translators: %s: module type (plugin, theme, or add-on) */
                            $message .= ' ' . sprintf( fs_text_inline( 'Alternatively, you can skip it for now and activate the license later, in your %s\'s network-level Account page.', 'connect_message_network_upgrade-premium-skip-license', $slug ), $fs->get_module_label( true ) );
                        }else {
                            $message = $network_integration_text . ' ' . sprintf( fs_text_inline( 'During the update process we detected %s site(s) in the network that are still pending your attention.', 'connect_message_network_upgrade-free', $slug ), count( $sites ) ) . '<br><br>' . ( fs_starts_with( $message, $hey_x_text . '<br>' ) ? substr( $message, strlen( $hey_x_text . '<br>' ) ) : $message );
                        }
                    }

					echo $message;
				?></p>
			<?php if ( $require_license_key ) : ?>
				<div class="fs-license-key-container">
					<input id="fs_license_key" name="fs_key" type="text" required maxlength="<?php echo $fs->apply_filters('license_key_maxlength', 32) ?>"
					       placeholder="<?php fs_esc_attr_echo_inline( 'License key', 'license-key', $slug ) ?>" tabindex="1"/>
					<i class="dashicons dashicons-admin-network"></i>
					<a class="show-license-resend-modal show-license-resend-modal-<?php echo $fs->get_unique_affix() ?>"
					   href="#"><?php fs_esc_html_echo_inline( "Can't find your license key?", 'cant-find-license-key', $slug ); ?></a>
				</div>

				<?php
				/**
				 * Allows developers to include custom HTML after the license input container.
				 *
				 * @author Vova Feldman
				 * @since 2.1.2
				 */
				 $fs->do_action( 'connect/after_license_input' );
				?>

                <?php
                    $send_updates_text = sprintf(
                        '%s<span class="action-description"> - %s</span>',
                        $fs->get_text_inline( 'Yes', 'yes' ),
                        $fs->get_text_inline( 'send me security & feature updates, educational content and offers.', 'send-updates' )
                    );

                    $do_not_send_updates_text = sprintf(
                        '%s<span class="action-description"> - %s</span>',
                        $fs->get_text_inline( 'No', 'no' ),
                        sprintf(
                            $fs->get_text_inline( 'do %sNOT%s send me security & feature updates, educational content and offers.', 'do-not-send-updates' ),
                            '<span class="underlined">',
                            '</span>'
                        )
                    );
                ?>
                <div id="fs_marketing_optin">
                    <span class="fs-message"><?php fs_echo_inline( "Please let us know if you'd like us to contact you for security & feature updates, educational content, and occasional offers:", 'contact-for-updates' ) ?></span>
                    <div class="fs-input-container">
                        <label>
                            <input type="radio" name="allow-marketing" value="true" tabindex="1" />
                            <span class="fs-input-label"><?php echo $send_updates_text ?></span>
                        </label>
                        <label>
                            <input type="radio" name="allow-marketing" value="false" tabindex="1" />
                            <span class="fs-input-label"><?php echo $do_not_send_updates_text ?></span>
                        </label>
                    </div>
                </div>
			<?php endif ?>
			<?php if ( $is_network_level_activation ) : ?>
            <?php
                $vars = array(
                    'id'                  => $fs->get_id(),
                    'sites'               => $sites,
                    'require_license_key' => $require_license_key
                );

                echo fs_get_template( 'partials/network-activation.php', $vars );
            ?>
			<?php endif ?>
		</div>
		<div class="fs-actions">
			<?php if ( $fs->is_enable_anonymous() && ! $is_pending_activation && ( ! $require_license_key || $is_network_upgrade_mode ) ) : ?>
				<a id="skip_activation" href="<?php echo fs_nonce_url( $fs->_get_admin_page_url( '', array( 'fs_action' => $fs->get_unique_affix() . '_skip_activation' ), $is_network_level_activation ), $fs->get_unique_affix() . '_skip_activation' ) ?>"
				   class="button button-secondary" tabindex="2"><?php fs_esc_html_echo_x_inline( 'Skip', 'verb', 'skip', $slug ) ?></a>
			<?php endif ?>
			<?php if ( $is_network_level_activation ) : ?>
				<a id="delegate_to_site_admins" class="fs-tooltip-trigger <?php echo is_rtl() ? ' rtl' : '' ?>" href="<?php echo fs_nonce_url( $fs->_get_admin_page_url( '', array( 'fs_action' => $fs->get_unique_affix() . '_delegate_activation' ) ), $fs->get_unique_affix() . '_delegate_activation' ) ?>"><?php fs_esc_html_echo_inline( 'Delegate to Site Admins', 'delegate-to-site-admins', $slug ) ?><span class="fs-tooltip"><?php fs_esc_html_echo_inline( 'If you click it, this decision will be delegated to the sites administrators.', 'delegate-sites-tooltip', $slug ) ?></span></a>
			<?php endif ?>
			<?php if ( $activate_with_current_user ) : ?>
				<form action="" method="POST">
					<input type="hidden" name="fs_action"
					       value="<?php echo $fs->get_unique_affix() ?>_activate_existing">
					<?php wp_nonce_field( 'activate_existing_' . $fs->get_public_key() ) ?>
					<button class="button button-primary" tabindex="1"
					        type="submit"><?php echo esc_html( $button_label ) ?></button>
				</form>
			<?php else : ?>
				<form method="post" action="<?php echo WP_FS__ADDRESS ?>/action/service/user/install/">
					<?php unset( $optin_params['sites']); ?>
					<?php foreach ( $optin_params as $name => $value ) : ?>
						<input type="hidden" name="<?php echo $name ?>" value="<?php echo esc_attr( $value ) ?>">
					<?php endforeach ?>
					<button class="button button-primary" tabindex="1"
					        type="submit"<?php if ( $require_license_key ) {
						echo ' disabled="disabled"';
					} ?>><?php echo esc_html( $button_label ) ?></button>
				</form>
			<?php endif ?>
		</div><?php

			// Set core permission list items.
			$permissions = array(
				'profile' => array(
					'icon-class' => 'dashicons dashicons-admin-users',
					'label'      => $fs->get_text_inline( 'Your Profile Overview', 'permissions-profile' ),
					'desc'       => $fs->get_text_inline( 'Name and email address', 'permissions-profile_desc' ),
					'priority'   => 5,
				),
				'site'    => array(
					'icon-class' => 'dashicons dashicons-admin-settings',
					'label'      => $fs->get_text_inline( 'Your Site Overview', 'permissions-site' ),
					'desc'       => $fs->get_text_inline( 'Site URL, WP version, PHP info, plugins & themes', 'permissions-site_desc' ),
					'priority'   => 10,
				),
				'notices' => array(
					'icon-class' => 'dashicons dashicons-testimonial',
					'label'      => $fs->get_text_inline( 'Admin Notices', 'permissions-admin-notices' ),
					'desc'       => $fs->get_text_inline( 'Updates, announcements, marketing, no spam', 'permissions-newsletter_desc' ),
					'priority'   => 13,
				),
				'events'  => array(
					'icon-class' => 'dashicons dashicons-admin-plugins',
					'label'      => sprintf( $fs->get_text_inline( 'Current %s Events', 'permissions-events' ), ucfirst( $fs->get_module_type() ) ),
					'desc'       => $fs->get_text_inline( 'Activation, deactivation and uninstall', 'permissions-events_desc' ),
					'priority'   => 20,
				),
			);

			// Add newsletter permissions if enabled.
			if ( $is_gdpr_required || $fs->is_permission_requested( 'newsletter' ) ) {
				$permissions['newsletter'] = array(
					'icon-class' => 'dashicons dashicons-email-alt',
					'label'      => $fs->get_text_inline( 'Newsletter', 'permissions-newsletter' ),
					'desc'       => $fs->get_text_inline( 'Updates, announcements, marketing, no spam', 'permissions-newsletter_desc' ),
					'priority'   => 15,
				);
			}

			// Allow filtering of the permissions list.
			$permissions = $fs->apply_filters( 'permission_list', $permissions );

			// Sort by priority.
			uasort( $permissions, 'fs_sort_by_priority' );

			if ( ! empty( $permissions ) ) : ?>
				<div class="fs-permissions">
					<?php if ( $require_license_key ) : ?>
						<p class="fs-license-sync-disclaimer"><?php
								echo sprintf(
									fs_esc_html_inline( 'The %1$s will be periodically sending data to %2$s to check for security and feature updates, and verify the validity of your license.', 'license-sync-disclaimer', $slug ),
									$fs->get_module_label( true ),
									$freemius_link
								) ?></p>
					<?php endif ?>
					<a class="fs-trigger" href="#" tabindex="1"><?php fs_esc_html_echo_inline( 'What permissions are being granted?', 'what-permissions', $slug ) ?></a>
					<ul><?php
							foreach ( $permissions as $id => $permission ) : ?>
								<li id="fs-permission-<?php echo esc_attr( $id ); ?>"
								    class="fs-permission fs-<?php echo esc_attr( $id ); ?>">
									<i class="<?php echo esc_attr( $permission['icon-class'] ); ?>"></i>

									<div>
										<span><?php echo esc_html( $permission['label'] ); ?></span>

										<p><?php echo esc_html( $permission['desc'] ); ?></p>
									</div>
								</li>
							<?php endforeach; ?>
					</ul>
				</div>
			<?php endif ?>
		<?php if ( $is_premium_code && $is_freemium ) : ?>
			<div class="fs-freemium-licensing">
				<p>
					<?php if ( $require_license_key ) : ?>
						<?php fs_esc_html_echo_inline( 'Don\'t have a license key?', 'dont-have-license-key', $slug ) ?>
						<a data-require-license="false" tabindex="1"><?php fs_esc_html_echo_inline( 'Activate Free Version', 'activate-free-version', $slug ) ?></a>
					<?php else : ?>
						<?php fs_echo_inline( 'Have a license key?', 'have-license-key', $slug ) ?>
						<a data-require-license="true" tabindex="1"><?php fs_esc_html_echo_inline( 'Activate License', 'activate-license', $slug ) ?></a>
					<?php endif ?>
				</p>
			</div>
		<?php endif ?>
		<div class="fs-terms">
			<a href="https://freemius.com/privacy/" target="_blank"
			   tabindex="1"><?php fs_esc_html_echo_inline( 'Privacy Policy', 'privacy-policy', $slug ) ?></a>
			&nbsp;&nbsp;-&nbsp;&nbsp;
			<a href="<?php echo $freemius_site_www ?>/terms/" target="_blank" tabindex="1"><?php fs_echo_inline( 'Terms of Service', 'tos', $slug ) ?></a>
		</div>
	</div>
	<?php
		if ( $is_optin_dialog ) { ?>
</div>
<?php
	}
?>
<script type="text/javascript">
	(function ($) {
		var $html = $('html');

		<?php
		if ( $is_optin_dialog ) {
		if ( $show_close_button ) { ?>
		var $themeConnectWrapper = $('#fs_theme_connect_wrapper');

		$themeConnectWrapper.find('button.close').on('click', function () {
			<?php if ( ! empty( $previous_theme_activation_url ) ) { ?>
			location.href = '<?php echo html_entity_decode( $previous_theme_activation_url ); ?>';
			<?php } else { ?>
			$themeConnectWrapper.remove();
			$html.css({overflow: $html.attr('fs-optin-overflow')});
			<?php } ?>
		});
		<?php
		}
		?>

		$html.attr('fs-optin-overflow', $html.css('overflow'));
		$html.css({overflow: 'hidden'});

		<?php
		}
		?>

		var $primaryCta          = $('.fs-actions .button.button-primary'),
            primaryCtaLabel      = $primaryCta.html(),
		    $form                = $('.fs-actions form'),
		    isNetworkActive      = <?php echo $is_network_level_activation ? 'true' : 'false' ?>,
		    requireLicenseKey    = <?php echo $require_license_key ? 'true' : 'false' ?>,
		    hasContextUser       = <?php echo $activate_with_current_user ? 'true' : 'false' ?>,
		    isNetworkUpgradeMode = <?php echo $is_network_upgrade_mode ? 'true' : 'false' ?>,
		    $licenseSecret,
		    $licenseKeyInput     = $('#fs_license_key'),
            pauseCtaLabelUpdate  = false,
            /**
             * @author Leo Fajardo (@leorw)
             * @since 2.1.0
             */
            resetLoadingMode = function() {
                // Reset loading mode.
                $primaryCta.html(primaryCtaLabel);
                $primaryCta.prop('disabled', false);
                $(document.body).css({'cursor': 'auto'});
                $('.fs-loading').removeClass('fs-loading');

                console.log('resetLoadingMode - Primary button was enabled');
            },
			setLoadingMode = function () {
				$(document.body).css({'cursor': 'wait'});
			};

		$('.fs-actions .button').on('click', function () {
			setLoadingMode();

			var $this = $(this);

			setTimeout(function () {
			    if ( ! requireLicenseKey || ! $marketingOptin.hasClass( 'error' ) ) {
                    $this.attr('disabled', 'disabled');
                }
			}, 200);
		});

		if ( isNetworkActive ) {
			var
				$multisiteOptionsContainer  = $( '#multisite_options_container' ),
				$allSitesOptions            = $( '#all_sites_options' ),
				$applyOnAllSites            = $( '#apply_on_all_sites' ),
				$sitesListContainer         = $( '#sites_list_container' ),
				totalSites                  = <?php echo count( $sites ) ?>,
				maxSitesListHeight          = null,
				$skipActivationButton       = $( '#skip_activation' ),
				$delegateToSiteAdminsButton = $( '#delegate_to_site_admins' );

			$applyOnAllSites.click(function() {
				var isChecked = $( this ).is( ':checked' );

				if ( isChecked ) {
					$multisiteOptionsContainer.find( '.action' ).removeClass( 'selected' );
					updatePrimaryCtaText( 'allow' );
				}

				$multisiteOptionsContainer.find( '.action-allow' ).addClass( 'selected' );

				$skipActivationButton.toggle();

				$delegateToSiteAdminsButton.toggle();

				$multisiteOptionsContainer.toggleClass( 'apply-on-all-sites', isChecked );

				$sitesListContainer.toggle( ! isChecked );
				if ( ! isChecked && null === maxSitesListHeight ) {
					/**
					 * Set the visible number of rows to 5 (5 * height of the first row).
					 *
					 * @author Leo Fajardo (@leorw)
					 */
					maxSitesListHeight = ( 5 * $sitesListContainer.find( 'tr:first' ).height() );
					$sitesListContainer.css( 'max-height', maxSitesListHeight );
				}
			});

			$allSitesOptions.find( '.action' ).click(function( evt ) {
				var actionType = $( evt.target ).data( 'action-type' );

				$multisiteOptionsContainer.find( '.action' ).removeClass( 'selected' );
				$multisiteOptionsContainer.find( '.action-' + actionType ).toggleClass( 'selected' );

				updatePrimaryCtaText( actionType );
			});

			$sitesListContainer.delegate( '.action', 'click', function( evt ) {
				var $this = $( evt.target );
				if ( $this.hasClass( 'selected' ) ) {
					return false;
				}

				$this.parents( 'tr:first' ).find( '.action' ).removeClass( 'selected' );
				$this.toggleClass( 'selected' );

				var
					singleSiteActionType = $this.data( 'action-type' ),
					totalSelected        = $sitesListContainer.find( '.action-' + singleSiteActionType + '.selected' ).length;

				$allSitesOptions.find( '.action.selected' ).removeClass( 'selected' );

				if ( totalSelected === totalSites ) {
					$allSitesOptions.find( '.action-' + singleSiteActionType ).addClass( 'selected' );

					updatePrimaryCtaText( singleSiteActionType );
				} else {
					updatePrimaryCtaText( 'mixed' );
				}
			});

            if (isNetworkUpgradeMode) {
                $skipActivationButton.click(function(){
                    $delegateToSiteAdminsButton.hide();

                    $skipActivationButton.html('<?php fs_esc_js_echo_inline( 'Skipping, please wait', 'skipping-wait', $slug ) ?>...');

                    pauseCtaLabelUpdate = true;

                    // Check all sites to be skipped.
                    $allSitesOptions.find('.action.action-skip').click();

                    $form.submit();

                    pauseCtaLabelUpdate = false;

                    return false;
                });

                $delegateToSiteAdminsButton.click(function(){
                    $delegateToSiteAdminsButton.html('<?php fs_esc_js_echo_inline( 'Delegating, please wait', 'delegating-wait', $slug ) ?>...');

                    pauseCtaLabelUpdate = true;

                    // Check all sites to be skipped.
                    $allSitesOptions.find('.action.action-delegate').click();

                    $form.submit();

                    pauseCtaLabelUpdate = false;

                    return false;
                });
            }
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 */
		function updatePrimaryCtaText( actionType ) {
            if (pauseCtaLabelUpdate)
                return;

			var text = '<?php fs_esc_js_echo_inline( 'Continue', 'continue', $slug ) ?>';

			switch ( actionType ) {
				case 'allow':
					text = '<?php fs_esc_js_echo_inline( 'Allow & Continue', 'opt-in-connect', $slug ) ?>';
					break;
				case 'delegate':
					text = '<?php fs_esc_js_echo_inline( 'Delegate to Site Admins & Continue', 'delegate-to-site-admins-and-continue', $slug ) ?>';
					break;
				case 'skip':
					text = '<?php fs_esc_js_echo_x_inline( 'Skip', 'verb', 'skip', $slug ) ?>';
					break;
			}

			$primaryCta.html( text );
		}

		var ajaxOptin = ( requireLicenseKey || isNetworkActive );

		$form.on('submit', function () {
			/**
			 * @author Vova Feldman (@svovaf)
			 * @since 1.1.9
			 */
			if ( ajaxOptin ) {
				if (!hasContextUser || isNetworkUpgradeMode) {
					<?php $action = $require_license_key ? 'activate_license' : 'network_activate' ?>

					$('.fs-error').remove();

					var
                        licenseKey = $licenseKeyInput.val(),
                        data       = {
                            action     : '<?php echo $fs->get_ajax_action( $action ) ?>',
                            security   : '<?php echo $fs->get_ajax_security( $action ) ?>',
                            license_key: licenseKey,
                            module_id  : '<?php echo $fs->get_id() ?>'
                        };

					if (requireLicenseKey &&
                        isMarketingAllowedByLicense.hasOwnProperty(licenseKey)
                    ) {
                        var
                            isMarketingAllowed = null,
                            $isMarketingAllowed   = $marketingOptin.find( 'input[type="radio"][name="allow-marketing"]:checked');


                        if ($isMarketingAllowed.length > 0)
                            isMarketingAllowed = ('true' == $isMarketingAllowed.val());

                        if ( null == isMarketingAllowedByLicense[ licenseKey ] &&
                            null == isMarketingAllowed
                        ) {
                            $marketingOptin.addClass( 'error' ).show();
                            resetLoadingMode();
                            return false;
                        } else if ( null == isMarketingAllowed ) {
                            isMarketingAllowed = isMarketingAllowedByLicense[ licenseKey ];
                        }

                        data.is_marketing_allowed = isMarketingAllowed;
                    }

                    $marketingOptin.removeClass( 'error' );

					if ( isNetworkActive ) {
						var
							sites           = [],
							applyOnAllSites = $applyOnAllSites.is( ':checked' );

						$sitesListContainer.find( 'tr' ).each(function() {
							var
								$this       = $( this ),
								includeSite = ( ! requireLicenseKey || applyOnAllSites || $this.find( 'input' ).is( ':checked' ) );

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

							if ( ! requireLicenseKey) {
                                site.action = $this.find('.action.selected').data('action-type');
                            }

							sites.push( site );
						});

						data.sites = sites;
					}

					/**
					 * Use the AJAX opt-in when license key is required to potentially
					 * process the after install failure hook.
					 *
					 * @author Vova Feldman (@svovaf)
					 * @since 1.2.1.5
					 */
					$.ajax({
						url    : ajaxurl,
						method : 'POST',
						data   : data,
						success: function (result) {
							var resultObj = $.parseJSON(result);
							if (resultObj.success) {
								// Redirect to the "Account" page and sync the license.
								window.location.href = resultObj.next_page;
							} else {
								resetLoadingMode();

								// Show error.
								$('.fs-content').prepend('<p class="fs-error">' + (resultObj.error.message ?  resultObj.error.message : resultObj.error) + '</p>');
							}
						},
						error: function () {
							resetLoadingMode();
						}
					});

					return false;
				}
				else {
					if (null == $licenseSecret) {
						$licenseSecret = $('<input type="hidden" name="license_secret_key" value="" />');
						$form.append($licenseSecret);
					}

					// Update secret key if premium only plugin.
					$licenseSecret.val($licenseKeyInput.val());
				}
			}

			return true;
		});

		$primaryCta.on('click', function () {
			console.log('Primary button was clicked');

			$(this).addClass('fs-loading');
			$(this).html('<?php echo esc_js( $is_pending_activation ?
				fs_text_x_inline( 'Sending email', 'as in the process of sending an email', 'sending-email', $slug ) :
				fs_text_x_inline( 'Activating', 'as activating plugin', 'activating', $slug )
			) ?>...');
		});

		$('.fs-permissions .fs-trigger').on('click', function () {
			$('.fs-permissions').toggleClass('fs-open');

			return false;
		});

		if (requireLicenseKey) {
			/**
			 * Submit license key on enter.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since 1.1.9
			 */
			$licenseKeyInput.keypress(function (e) {
				if (e.which == 13) {
					if ('' !== $(this).val()) {
						$primaryCta.click();
						return false;
					}
				}
			});

			/**
			 * Disable activation button when empty license key.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since 1.1.9
			 */
			$licenseKeyInput.on('keyup paste delete cut', function () {
				setTimeout(function () {
                    var key = $licenseKeyInput.val();

                    if (key == previousLicenseKey){
                        return;
                    }

					if ('' === key) {
						$primaryCta.attr('disabled', 'disabled');
                        $marketingOptin.hide();
					} else {
                        $primaryCta.prop('disabled', false);

                        if (32 <= key.length){
                            fetchIsMarketingAllowedFlagAndToggleOptin();
                        } else {
                            $marketingOptin.hide();
                        }
					}

                    previousLicenseKey = key;
				}, 100);
			}).focus();
		}

		/**
		 * Set license mode trigger URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.1.9
		 */
		var
			$connectLicenseModeTrigger = $('#fs_connect .fs-freemium-licensing a'),
			href                       = window.location.href;

		if (href.indexOf('?') > 0) {
			href += '&';
		} else {
			href += '?';
		}

		if ($connectLicenseModeTrigger.length > 0) {
			$connectLicenseModeTrigger.attr(
				'href',
				href + 'require_license=' + $connectLicenseModeTrigger.attr('data-require-license')
			);
		}

		//--------------------------------------------------------------------------------
		//region GDPR
		//--------------------------------------------------------------------------------
        var isMarketingAllowedByLicense = {},
            $marketingOptin = $('#fs_marketing_optin'),
            previousLicenseKey = null;

		if (requireLicenseKey) {

			    var
                    afterMarketingFlagLoaded = function () {
                        var licenseKey = $licenseKeyInput.val();

                        if (null == isMarketingAllowedByLicense[licenseKey]) {
                            $marketingOptin.show();

                            if ($marketingOptin.find('input[type=radio]:checked').length > 0){
                                // Focus on button if GDPR opt-in already selected is already selected.
                                $primaryCta.focus();
                            } else {
                                // Focus on the GDPR opt-in radio button.
                                $($marketingOptin.find('input[type=radio]')[0]).focus();
                            }
                        } else {
                            $marketingOptin.hide();
                            $primaryCta.focus();
                        }
                    },
                    /**
                     * @author Leo Fajardo (@leorw)
                     * @since 2.1.0
                     */
                    fetchIsMarketingAllowedFlagAndToggleOptin = function () {
                        var licenseKey = $licenseKeyInput.val();

                        if (licenseKey.length < 32) {
                            $marketingOptin.hide();
                            return;
                        }

                        if (isMarketingAllowedByLicense.hasOwnProperty(licenseKey)) {
                            afterMarketingFlagLoaded();
                            return;
                        }

                        $marketingOptin.hide();

                        setLoadingMode();

                        $primaryCta.addClass('fs-loading');
                        $primaryCta.attr('disabled', 'disabled');
                        $primaryCta.html('<?php fs_esc_js_echo_inline( 'Please wait', 'please-wait', $slug ) ?>...');

                        $.ajax({
                            url    : ajaxurl,
                            method : 'POST',
                            data   : {
                                action     : '<?php echo $fs->get_ajax_action( 'fetch_is_marketing_required_flag_value' ) ?>',
                                security   : '<?php echo $fs->get_ajax_security( 'fetch_is_marketing_required_flag_value' ) ?>',
                                license_key: licenseKey,
                                module_id  : '<?php echo $fs->get_id() ?>'
                            },
                            success: function (result) {
                                resetLoadingMode();

                                if (result.success) {
                                    result = result.data;

                                    // Cache result.
                                    isMarketingAllowedByLicense[licenseKey] = result.is_marketing_allowed;
                                }

                                afterMarketingFlagLoaded();
                            }
                        });
                    };

			$marketingOptin.find( 'input' ).click(function() {
				$marketingOptin.removeClass( 'error' );
			});
		}

		//endregion
	})(jQuery);
</script>