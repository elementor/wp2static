<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 * @var Freemius
	 */
	$fs = freemius( $VARS['id'] );

	$slug = $fs->get_slug();

	$open_addon_slug = fs_request_get( 'slug' );

	$open_addon = false;

	/**
	 * @var FS_Plugin[]
	 */
	$addons = $fs->get_addons();

	$has_addons = ( is_array( $addons ) && 0 < count( $addons ) );

	$has_tabs = $fs->_add_tabs_before_content();
?>
	<div id="fs_addons" class="wrap fs-section">
		<?php if ( ! $has_tabs ) : ?>
		<h2><?php echo esc_html( sprintf( fs_text_inline( 'Add Ons for %s', 'add-ons-for-x', $slug ), $fs->get_plugin_name() ) ) ?></h2>
		<?php endif ?>

		<div id="poststuff">
			<?php if ( ! $has_addons ) : ?>
				<h3><?php echo esc_html( sprintf(
						'%s... %s',
						fs_text_x_inline( 'Oops', 'exclamation', 'oops', $slug ),
						fs_text_inline( 'We could\'nt load the add-ons list. It\'s probably an issue on our side, please try to come back in few minutes.', 'add-ons-missing', $slug )
					) ) ?></h3>
			<?php endif ?>
			<ul class="fs-cards-list">
				<?php if ( $has_addons ) : ?>
					<?php foreach ( $addons as $addon ) : ?>
						<?php
						$open_addon = ( $open_addon || ( $open_addon_slug === $addon->slug ) );

						$price     = 0;
						$has_trial = false;
						$has_free_plan = false;
						$has_paid_plan = false;

						$result    = $fs->get_api_plugin_scope()->get( "/addons/{$addon->id}/pricing.json?type=visible" );
						if ( ! isset( $result->error ) ) {
							$plans = $result->plans;

							if ( is_array( $plans ) && 0 < count( $plans ) ) {
								foreach ( $plans as $plan ) {
									if ( ! isset( $plan->pricing ) ||
									     ! is_array( $plan->pricing ) ||
									     0 == count( $plan->pricing )
									) {
										// No pricing means a free plan.
										$has_free_plan = true;
										continue;
									}


									$has_paid_plan = true;
									$has_trial     = $has_trial || ( is_numeric( $plan->trial_period ) && ( $plan->trial_period > 0 ) );

									$min_price = 999999;
									foreach ( $plan->pricing as $pricing ) {
										if ( ! is_null( $pricing->annual_price ) && $pricing->annual_price > 0 ) {
											$min_price = min( $min_price, $pricing->annual_price );
										} else if ( ! is_null( $pricing->monthly_price ) && $pricing->monthly_price > 0 ) {
											$min_price = min( $min_price, 12 * $pricing->monthly_price );
										}
									}

									if ( $min_price < 999999 ) {
										$price = $min_price;
									}

								}
							}
						}
						?>
						<li class="fs-card fs-addon" data-slug="<?php echo $addon->slug ?>">
							<?php
								echo sprintf( '<a href="%s" class="thickbox fs-overlay" aria-label="%s" data-title="%s"></a>',
									esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&parent_plugin_id=' . $fs->get_id() . '&plugin=' . $addon->slug .
									                            '&TB_iframe=true&width=600&height=550' ) ),
									esc_attr( sprintf( fs_text_inline( 'More information about %s', 'more-information-about-x', $slug ), $addon->title ) ),
									esc_attr( $addon->title )
								);
							?>
							<?php
								if ( is_null( $addon->info ) ) {
									$addon->info = new stdClass();
								}
								if ( ! isset( $addon->info->card_banner_url ) ) {
									$addon->info->card_banner_url = '//dashboard.freemius.com/assets/img/marketing/blueprint-300x100.jpg';
								}
								if ( ! isset( $addon->info->short_description ) ) {
									$addon->info->short_description = 'What\'s the one thing your add-on does really, really well?';
								}
							?>
							<div class="fs-inner">
								<ul>
									<li class="fs-card-banner"
									    style="background-image: url('<?php echo $addon->info->card_banner_url ?>');"></li>
									<!-- <li class="fs-tag"></li> -->
									<li class="fs-title"><?php echo $addon->title ?></li>
									<li class="fs-offer">
									<span
										class="fs-price"><?php
											$descriptors = array();

											if ($has_free_plan)
												$descriptors[] = fs_text_inline( 'Free', 'free', $slug );
											if ($has_paid_plan && $price > 0)
												$descriptors[] = '$' . number_format( $price, 2 );
											if ($has_trial)
												$descriptors[] = fs_text_x_inline( 'Trial', 'trial period',  'trial', $slug );

											echo implode(' - ', $descriptors) ?></span>
									</li>
									<li class="fs-description"><?php echo ! empty( $addon->info->short_description ) ? $addon->info->short_description : 'SHORT DESCRIPTION' ?></li>
									<li class="fs-cta"><a class="button"><?php fs_esc_html_echo_inline( 'View details', 'view-details', $slug ) ?></a></li>
								</ul>
							</div>
						</li>
					<?php endforeach ?>
				<?php endif ?>
			</ul>
		</div>
	</div>
	<script type="text/javascript">
		(function ($) {
			<?php if ( $open_addon ) : ?>

			var interval = setInterval(function () {
				// Open add-on information page.
				<?php
				/**
				 * @author Vova Feldman
				 *
				 * This code does NOT expose an XSS vulnerability because:
				 *  1. This page only renders for admins, so if an attacker manage to get
				 *     admin access, they can do more harm.
				 *  2. This code won't be rendered unless $open_addon_slug matches any of
				 *     the plugin's add-ons slugs.
				 */
				?>
				$('.fs-card[data-slug=<?php echo $open_addon_slug ?>] a').click();
				if ($('#TB_iframeContent').length > 0) {
					clearInterval(interval);
					interval = null;
				}
			}, 200);

			<?php else : ?>


			$('.fs-card.fs-addon')
				.mouseover(function () {
					$(this).find('.fs-cta .button').addClass('button-primary');
				}).mouseout(function () {
					$(this).find('.fs-cta .button').removeClass('button-primary');
				});

			<?php endif ?>
		})(jQuery);
	</script>
<?php
	if ( $has_tabs ) {
		$fs->_add_tabs_after_content();
	}

	$params = array(
		'page'           => 'addons',
		'module_id'      => $fs->get_id(),
		'module_type'    => $fs->get_module_type(),
		'module_slug'    => $slug,
		'module_version' => $fs->get_plugin_version(),
	);
	fs_require_template( 'powered-by.php', $params );