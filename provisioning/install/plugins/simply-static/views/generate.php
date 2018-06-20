<?php
namespace Simply_Static;
?>

<h1><?php _e( 'Simply Static &rsaquo; Generate', 'simply-static' ); ?></h1>

<div class='wrap' id='generatePage'>

	<?php wp_nonce_field( 'simply-static_generate' ) ?>

	<div class='actions'>
		<input id='generate' class='button button-primary button-hero <?php if ( ! $this->archive_generation_done ) { echo 'hide'; } ?>' type='submit' name='generate' value='<?php _e( "Generate Static Files", 'simply-static' ); ?>' />

		<input id='cancel' class='button button-cancel button-hero <?php if ( $this->archive_generation_done ) { echo 'hide'; } ?>' type='submit' name='cancel' value='<?php _e( "Cancel", 'simply-static' ); ?>' />

		<span class='spinner <?php if ( ! $this->archive_generation_done ) { echo 'is-active'; } ?>'></span>
	</div>

	<h3><?php _e( "Activity Log", 'simply-static' ); ?></h3>
	<div id='activityLog'>
		<?php echo $this->activity_log; ?>
	</div>

	<h3><?php _e( "Export Log", 'simply-static' ); ?></h3>
	<div id='exportLog'>
		<?php echo $this->export_log; ?>
	</div>

</div>
