<?php
namespace Simply_Static;
?>

<?php foreach ( $this->flashes as $flash ) : ?>
	<div class="fade <?php echo $flash['type']; ?>">
		<p><strong>
			<?php echo $flash['message']; ?>
		</strong></p>
	</div>
<?php endforeach; ?>

<div class="wrap">
	<div id="sistContainer">

		<div id="sistContent">
			<?php include $this->template; ?>
		</div>
		<!-- .sist-content -->

		<div id="sistSidebar">
			<div class="sidebar-container metabox-holder">
				<div class="postbox">
					<h3 class="wp-ui-primary"><?php _e( 'Like this plugin?', $this->slug ); ?></h3>
					<div class="inside">
						<div class="main">
							<p><?php _e( 'Join the mailing list to be notified when new features and plugins are released.', $this->slug ); ?></p>

							<!-- Begin MailChimp Signup Form -->
							<div id="mc_embed_signup">
							<form action="//codeofconduct.us8.list-manage.com/subscribe/post?u=add8381918934d53034b89daf&amp;id=7b053181a2" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
								<div id="mc_embed_signup_scroll">
								<input type="email" value="" name="EMAIL" class="email" id="mce-EMAIL" placeholder="<?php _e( 'email address', $this->slug ); ?>" required>
								<!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
								<div style="position: absolute; left: -5000px;"><input type="text" name="b_add8381918934d53034b89daf_7b053181a2" tabindex="-1" value=""></div>
								<div class="clear"><input type="submit" value="<?php _e( 'Subscribe', $this->slug ); ?>" name="subscribe" id="mc-embedded-subscribe" class="button-primary"></div>
								</div>
							</form>
							</div>
							<!--End mc_embed_signup-->

						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- .sist-sidebar -->

	</div>
	<!-- .sist-container -->
</div>
<!-- .wrap -->
