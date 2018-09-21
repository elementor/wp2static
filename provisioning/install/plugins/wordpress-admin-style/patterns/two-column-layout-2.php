<h2><?php esc_attr_e( '2 Columns Layout: static (px)', 'WpAdminStyle' ); ?></h2>

<div class="wrap">

	<div id="icon-options-general" class="icon32"></div>
	<h1><?php esc_attr_e( 'Heading', 'WpAdminStyle' ); ?></h1>

	<div id="poststuff">

		<div id="post-body" class="metabox-holder columns-2">

			<!-- main content -->
			<div id="post-body-content">

				<div class="meta-box-sortables ui-sortable">

					<div class="postbox">

						<button type="button" class="handlediv" aria-expanded="true" >
							<span class="screen-reader-text">Toggle panel</span>
							<span class="toggle-indicator" aria-hidden="true"></span>
						</button>
						<!-- Toggle -->

						<h2 class="hndle"><span><?php esc_attr_e( 'Main Content Header', 'WpAdminStyle' ); ?></span>
						</h2>

						<div class="inside">
							<p><?php esc_attr_e( 'WordPress started in 2003 with a single bit of code to enhance the typography of everyday writing and with fewer users than you can count on your fingers and toes. Since then it has grown to be the largest self-hosted blogging tool in the world, used on millions of sites and seen by tens of millions of people every day.',
							                     'WpAdminStyle' ); ?></p>
						</div>
						<!-- .inside -->

					</div>
					<!-- .postbox -->

				</div>
				<!-- .meta-box-sortables .ui-sortable -->

			</div>
			<!-- post-body-content -->

			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">

				<div class="meta-box-sortables">

					<div class="postbox">

						<button type="button" class="handlediv" aria-expanded="true" >
							<span class="screen-reader-text">Toggle panel</span>
							<span class="toggle-indicator" aria-hidden="true"></span>
						</button>
						<!-- Toggle -->

						<h2 class="hndle"><span><?php esc_attr_e(
									'Sidebar Content Header', 'WpAdminStyle'
								); ?></span></h2>

						<div class="inside">
							<p><?php esc_attr_e( 'Everything you see here, from the documentation to the code itself, was created by and for the community. WordPress is an Open Source project, which means there are hundreds of people all over the world working on it. (More than most commercial platforms.) It also means you are free to use it for anything from your cat’s home page to a Fortune 500 web site without paying anyone a license fee and a number of other important freedoms.',
							                     'WpAdminStyle' ); ?></p>
						</div>
						<!-- .inside -->

					</div>
					<!-- .postbox -->

				</div>
				<!-- .meta-box-sortables -->

			</div>
			<!-- #postbox-container-1 .postbox-container -->

		</div>
		<!-- #post-body .metabox-holder .columns-2 -->

		<br class="clear">
	</div>
	<!-- #poststuff -->

</div> <!-- .wrap -->
