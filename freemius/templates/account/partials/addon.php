<?php
    /**
     * @var array    $VARS
     * @var Freemius $fs
     */
    $fs       = $VARS['parent_fs'];
    $addon_id = $VARS['addon_id'];
    $odd      = $VARS['odd'];
    $slug     = $fs->get_slug();


    $addon              = $fs->get_addon( $addon_id );
    $is_addon_activated = $fs->is_addon_activated( $addon_id );
    $is_addon_connected = $fs->is_addon_connected( $addon_id );

    $fs_addon = $is_addon_connected ?
        freemius( $addon_id ) :
        false;

    // Aliases.
    $download_latest_text              = fs_text_x_inline( 'Download Latest', 'as download latest version', 'download-latest', $slug );
    $downgrade_x_confirm_text          = fs_text_inline( 'Downgrading your plan will immediately stop all future recurring payments and your %s plan license will expire in %s.', 'downgrade-x-confirm', $slug );
    $cancel_trial_confirm_text         = fs_text_inline( 'Cancelling the trial will immediately block access to all premium features. Are you sure?', 'cancel-trial-confirm', $slug );
    $after_downgrade_non_blocking_text = fs_text_inline( 'You can still enjoy all %s features but you will not have access to %s updates and support.', 'after-downgrade-non-blocking', $slug );
    $after_downgrade_blocking_text     = fs_text_inline( 'Once your license expires you can still use the Free version but you will NOT have access to the %s features.', 'after-downgrade-blocking', $slug );
    /* translators: %s: Plan title (e.g. "Professional") */
    $activate_plan_text = fs_text_inline( 'Activate %s Plan', 'activate-x-plan', $slug );
    $version_text       = fs_text_x_inline( 'Version', 'product version', 'version', $slug );
    /* translators: %s: Time period (e.g. Auto renews in "2 months") */
    $renews_in_text = fs_text_inline( 'Auto renews in %s', 'renews-in', $slug );
    /* translators: %s: Time period (e.g. Expires in "2 months") */
    $expires_in_text   = fs_text_inline( 'Expires in %s', 'expires-in', $slug );
    $sync_license_text = fs_text_x_inline( 'Sync License', 'as synchronize license', 'sync-license', $slug );
    $cancel_trial_text = fs_text_inline( 'Cancel Trial', 'cancel-trial', $slug );
    $change_plan_text  = fs_text_inline( 'Change Plan', 'change-plan', $slug );
    $upgrade_text      = fs_text_x_inline( 'Upgrade', 'verb', 'upgrade', $slug );
    $addons_text       = fs_text_inline( 'Add-Ons', 'add-ons', $slug );
    $downgrade_text    = fs_text_x_inline( 'Downgrade', 'verb', 'downgrade', $slug );
    $trial_text        = fs_text_x_inline( 'Trial', 'trial period', 'trial', $slug );
    $free_text         = fs_text_inline( 'Free', 'free', $slug );
    $activate_text     = fs_text_inline( 'Activate', 'activate', $slug );
    $plan_text         = fs_text_x_inline( 'Plan', 'as product pricing plan', 'plan', $slug );

    // Defaults.
    $plan                   = null;
    $is_paid_trial          = false;
    $license                = null;
    $site                   = null;
    $is_active_subscription = false;
    $subscription           = null;
    $is_paying              = false;

    if ( is_object( $fs_addon ) ) {
        $is_paying                  = $fs_addon->is_paying();
        $user                       = $fs_addon->get_user();
        $site                       = $fs_addon->get_site();
        $license                    = $fs_addon->_get_license();
        $subscription               = ( is_object( $license ) ?
            $fs_addon->_get_subscription( $license->id ) :
            null );
        $plan                       = $fs_addon->get_plan();
        $is_active_subscription     = ( is_object( $subscription ) && $subscription->is_active() );
        $is_paid_trial              = $fs_addon->is_paid_trial();
        $show_upgrade               = ( ! $is_paying && ! $is_paid_trial && ! $fs_addon->_has_premium_license() );
        $is_current_license_expired = is_object( $license ) && $license->is_expired();
    }
?>
<tr<?php if ( $odd ) {
    echo ' class="alternate"';
} ?>>
    <td>
        <!-- Title -->
        <?php echo $addon->title ?>
    </td>
    <?php if ( $is_addon_connected ) : ?>
        <!-- ID -->
        <td><?php echo $site->id ?></td>
        <!--/ ID -->

        <!-- Version -->
        <td><?php echo $fs_addon->get_plugin_version() ?></td>
        <!--/ Version -->

        <!-- Plan Title -->
        <td><?php echo strtoupper( is_string( $plan->name ) ? $plan->title : $free_text ) ?></td>
        <!--/ Plan Title -->

        <?php if ( $fs_addon->is_trial() || is_object( $license ) ) : ?>

        <!-- Expiration -->
        <td>
            <?php
                $tags = array();

                if ( $fs_addon->is_trial() ) {
                    $tags[] = array( 'label' => $trial_text, 'type' => 'success' );

                    $tags[] = array(
                        'label' => sprintf(
                            ( $is_paid_trial ?
                                $renews_in_text :
                                $expires_in_text ),
                            human_time_diff( time(), strtotime( $site->trial_ends ) )
                        ),
                        'type'  => ( $is_paid_trial ? 'success' : 'warn' )
                    );
                } else {
                    if ( is_object( $license ) ) {
                        if ( $license->is_cancelled ) {
                            $tags[] = array(
                                'label' => fs_text_inline( 'Cancelled', 'cancelled', $slug ),
                                'type'  => 'error'
                            );
                        } else if ( $license->is_expired() ) {
                            $tags[] = array(
                                'label' => fs_text_inline( 'Expired', 'expired', $slug ),
                                'type'  => 'error'
                            );
                        } else if ( $license->is_lifetime() ) {
                            $tags[] = array(
                                'label' => fs_text_inline( 'No expiration', 'no-expiration', $slug ),
                                'type'  => 'success'
                            );
                        } else if ( ! $is_active_subscription && ! $license->is_first_payment_pending() ) {
                            $tags[] = array(
                                'label' => sprintf( $expires_in_text, human_time_diff( time(), strtotime( $license->expiration ) ) ),
                                'type'  => 'warn'
                            );
                        } else if ( $is_active_subscription && ! $subscription->is_first_payment_pending() ) {
                            $tags[] = array(
                                'label' => sprintf( $renews_in_text, human_time_diff( time(), strtotime( $subscription->next_payment ) ) ),
                                'type'  => 'success'
                            );
                        }
                    }
                }

                foreach ( $tags as $t ) {
                    printf( '<label class="fs-tag fs-%s">%s</label>' . "\n", $t['type'], $t['label'] );
                }
            ?>
        </td>
        <!--/ Expiration -->

        <?php endif ?>

        <?php
        $buttons = array();
        if ( $is_addon_activated ) {
            if ( $is_paying ) {
                $buttons[] = fs_ui_get_action_button(
                    $fs->get_id(),
                    'account',
                    'deactivate_license',
                    fs_text_inline( 'Deactivate License', 'deactivate-license', $slug ),
                    '',
                    array( 'plugin_id' => $addon_id ),
                    false
                );

                $human_readable_license_expiration = human_time_diff( time(), strtotime( $license->expiration ) );
                $downgrade_confirmation_message    = sprintf(
                    $downgrade_x_confirm_text,
                    $plan->title,
                    $human_readable_license_expiration
                );

                $after_downgrade_message = ! $license->is_block_features ?
                    sprintf( $after_downgrade_non_blocking_text, $plan->title, $fs_addon->get_module_label( true ) ) :
                    sprintf( $after_downgrade_blocking_text, $plan->title );

                if ( ! $license->is_lifetime() && $is_active_subscription ) {
                    $buttons[] = fs_ui_get_action_button(
                        $fs->get_id(),
                        'account',
                        'downgrade_account',
                        esc_html( $fs_addon->is_only_premium() ? fs_text_inline( 'Cancel Subscription', 'cancel-subscription', $slug ) : $downgrade_text ),
                        '',
                        array( 'plugin_id' => $addon_id ),
                        false,
                        false,
                        false,
                        ( $downgrade_confirmation_message . ' ' . $after_downgrade_message ),
                        'POST'
                    );
                }
            } else if ( $is_paid_trial ) {
                $buttons[] = fs_ui_get_action_button(
                    $fs->get_id(),
                    'account',
                    'cancel_trial',
                    esc_html( $cancel_trial_text ),
                    '',
                    array( 'plugin_id' => $addon_id ),
                    false,
                    false,
                    'dashicons dashicons-download',
                    $cancel_trial_confirm_text,
                    'POST'
                );
            } else {
                $premium_license = $fs_addon->_get_available_premium_license();

                if ( is_object( $premium_license ) ) {
                    $premium_plan = $fs_addon->_get_plan_by_id( $premium_license->plan_id );
                    $site         = $fs_addon->get_site();

                    $buttons[] = fs_ui_get_action_button(
                        $fs->get_id(),
                        'account',
                        'activate_license',
                        esc_html( sprintf( $activate_plan_text, $premium_plan->title, ( $site->is_localhost() && $premium_license->is_free_localhost ) ? '[localhost]' : ( 1 < $premium_license->left() ? $premium_license->left() . ' left' : '' ) ) ),
                        '',
                        array(
                            'plugin_id'  => $addon_id,
                            'license_id' => $premium_license->id,
                        )
                    );
                }
            }

            if ( 0 == count( $buttons ) ) {
                if ( $fs_addon->is_premium() ) {
                    $fs_addon->_add_license_activation_dialog_box();

                    $buttons[] = fs_ui_get_action_button(
                        $fs->get_id(),
                        'account',
                        'activate_license',
                        fs_esc_html_inline( 'Activate License', 'activate-license', $slug ),
                        'activate-license-trigger ' . $fs_addon->get_unique_affix(),
                        array(
                            'plugin_id' => $addon_id,
                        ),
                        false,
                        true
                    );
                }

                // Add sync license only if non of the other CTAs are visible.
                $buttons[] = fs_ui_get_action_button(
                    $fs->get_id(),
                    'account',
                    $fs->get_unique_affix() . '_sync_license',
                    esc_html( $sync_license_text ),
                    '',
                    array( 'plugin_id' => $addon_id ),
                    false,
                    true
                );

            }
        } else if ( ! $show_upgrade ) {
            if ( $fs->is_addon_installed( $addon_id ) ) {
                $addon_file = $fs->get_addon_basename( $addon_id );
                $buttons[]  = sprintf(
                    '<a class="button button-primary edit" href="%s" title="%s">%s</a>',
                    wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $addon_file, 'activate-plugin_' . $addon_file ),
                    fs_esc_attr_inline( 'Activate this add-on', 'activate-this-addon', $slug ),
                    $activate_text
                );
            } else {
                if ( $fs->is_allowed_to_install() ) {
                    $buttons[] = sprintf(
                        '<a class="button button-primary edit" href="%s">%s</a>',
                        wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $addon->slug ), 'install-plugin_' . $addon->slug ),
                        fs_text_inline( 'Install Now', 'install-now', $slug )
                    );
                } else {
                    $buttons[] = sprintf(
                        '<a target="_blank" class="button button-primary edit" href="%s">%s</a>',
                        $fs->_get_latest_download_local_url( $addon_id ),
                        esc_html( $download_latest_text )
                    );
                }
            }
        }

        if ( $show_upgrade ) {
            $buttons[] = sprintf( '<a href="%s" class="thickbox button button-small button-primary" aria-label="%s" data-title="%s"><i class="dashicons dashicons-cart"></i> %s</a>',
                esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&parent_plugin_id=' . $fs->get_id() . '&plugin=' . $addon->slug .
                                            '&TB_iframe=true&width=600&height=550' ) ),
                esc_attr( sprintf( fs_text_inline( 'More information about %s', 'more-information-about-x', $slug ), $addon->title ) ),
                esc_attr( $addon->title ),
                ( $fs_addon->has_free_plan() ?
                    $upgrade_text :
                    fs_text_x_inline( 'Purchase', 'verb', 'purchase', $slug ) )
            );
        }

        $buttons_count = count( $buttons );
        ?>

        <!-- Actions -->
        <td><?php if ( $buttons_count > 1 ) : ?>
            <div class="button-group"><?php endif ?>
                <?php foreach ( $buttons as $button ) {
                        echo $button;
                    } ?>
                <?php if ( $buttons_count > 1 ) : ?></div><?php endif ?></td>
        <!--/ Actions -->

    <?php else : ?>
        <?php // Add-on NOT Installed or was never connected.
        ?>
        <!-- Action -->
        <td colspan="4">
            <?php if ( $fs->is_addon_installed( $addon_id ) ) : ?>
                <?php $addon_file = $fs->get_addon_basename( $addon_id ) ?>
                <a class="button button-primary"
                   href="<?php echo wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $addon_file, 'activate-plugin_' . $addon_file ) ?>"
                   title="<?php fs_esc_attr_echo_inline( 'Activate this add-on', 'activate-this-addon', $slug ) ?>"
                   class="edit"><?php echo esc_html( $activate_text ) ?></a>
            <?php else : ?>
                <?php if ( $fs->is_allowed_to_install() ) : ?>
                    <a class="button button-primary"
                       href="<?php echo wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $addon->slug ), 'install-plugin_' . $addon->slug ) ?>"><?php fs_esc_html_echo_inline( 'Install Now', 'install-now', $slug ) ?></a>
                <?php else : ?>
                    <a target="_blank" class="button button-primary"
                       href="<?php echo $fs->_get_latest_download_local_url( $addon_id ) ?>"><?php echo esc_html( $download_latest_text ) ?></a>
                <?php endif ?>
            <?php endif ?>
        </td>
        <!--/ Action -->
    <?php endif ?>
    <?php if ( ! $is_paying && WP_FS__DEV_MODE ) : ?>
        <!-- Optional Delete Action -->
        <td>
            <?php
                if ( $is_addon_activated ) {
                    fs_ui_action_button(
                        $fs->get_id(), 'account',
                        'delete_account',
                        fs_text_x_inline( 'Delete', 'verb', 'delete', $slug ),
                        '',
                        array( 'plugin_id' => $addon_id ),
                        false,
                        $show_upgrade
                    );
                }
            ?>
        </td>
        <!--/ Optional Delete Action -->
    <?php endif ?>
</tr>