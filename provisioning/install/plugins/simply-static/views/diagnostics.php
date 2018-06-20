<?php
namespace Simply_Static;
?>

<h1><?php _e( 'Simply Static &rsaquo; Diagnostics', Plugin::SLUG ); ?></h1>

<div class='wrap' id='diagnosticsPage'>

	<?php foreach ( $this->results as $title => $tests ) : ?>
		<table class='widefat striped'>
			<thead>
				<tr>
					<th colspan='2'><?php echo $title; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tests as $result ) : ?>
					<tr>
						<td class='label'><?php echo $result['label'] ?></td>
						<?php if ( $result['test'] ) : ?>
							<td class='test success'><?php echo $result['message'] ?></td>
						<?php else : ?>
							<td class='test error'><?php echo $result['message'] ?></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>

	<table class='widefat striped'>
		<thead>
			<tr>
				<th><?php _e( "Theme Name", 'simply-static' ); ?></th>
				<th><?php _e( "Theme URL", 'simply-static' ); ?></th>
				<th><?php _e( "Version", 'simply-static' ); ?></th>
				<th><?php _e( "Enabled", 'simply-static' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $this->themes as $theme ) : ?>
				<tr>
					<td width='20%'><?php echo $theme->get( 'Name'); ?></td>
					<td width='60%'><a href='<?php echo $theme->get( 'ThemeURI'); ?>'><?php echo $theme->get( 'ThemeURI'); ?></a></td>
					<td width='10%'><?php echo $theme->get( 'Version'); ?></td>
					<?php if ( $theme->get( 'Name') === $this->current_theme_name ) : ?>
						<td width='10%' class='enabled'><?php _e( "Yes", 'simply-static' ) ?></td>
					<?php else : ?>
						<td width='10%' class='disabled'><?php _e( "No", 'simply-static' ) ?></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<table class='widefat striped'>
		<thead>
			<tr>
				<th><?php _e( "Plugin Name", 'simply-static' ); ?></th>
				<th><?php _e( "Plugin URL", 'simply-static' ); ?></th>
				<th><?php _e( "Version", 'simply-static' ); ?></th>
				<th><?php _e( "Enabled", 'simply-static' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $this->plugins as $plugin_path => $plugin_data ) : ?>
				<tr>
					<td width='20%'><?php echo $plugin_data[ 'Name' ]; ?></td>
					<td width='60%'><a href='<?php echo $plugin_data[ 'PluginURI' ]; ?>'><?php echo $plugin_data[ 'PluginURI' ]; ?></a></td>
					<td width='10%'><?php echo $plugin_data[ 'Version' ]; ?></td>
					<?php if ( is_plugin_active( $plugin_path ) ) : ?>
						<td width='10%' class='enabled'><?php _e( "Yes", 'simply-static' ) ?></td>
					<?php else : ?>
						<td width='10%' class='disabled'><?php _e( "No", 'simply-static' ) ?></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h3 style='margin-top: 50px;'><?php _e( "Debugging Options", 'simply-static' ); ?></h3>

	<form id='diagnosticsForm' method='post' action=''>

		<?php wp_nonce_field( 'simply-static_diagnostics' ) ?>
		<input type='hidden' name='_diagnostics' value='1' />

		<table class='form-table'>
			<tbody>
				<tr>
					<th><?php _e( "Debugging Mode", 'simply-static' ); ?></th>
					<td>
						<label>
							<input aria-describedby='enableDebuggingHelpBlock' name='debugging_mode' id='debuggingMode' value='1' type='checkbox' <?php Util::checked_if( $this->debugging_mode === '1' ); ?> />
							<?php _e( "Enable debugging mode", 'simply-static' ); ?>
						</label>
						<p id='enableDebuggingHelpBlock' class='help-block'>
							<?php _e( "When enabled, a debug log will be created when generating static files.", 'simply-static' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<p class='submit'>
							<input class='button button-primary' type='submit' name='save' value='<?php _e( "Save Changes", 'simply-static' );?>' />
						</p>
					</td>
				</tr>
			</tbody>
		</table>

	</form>

	<form id='emailDebugLogForm' method='post' action=''>

		<?php wp_nonce_field( 'simply-static_email_debug_log' ) ?>
		<input type='hidden' name='_email_debug_log' value='1' />

		<table class='form-table'>
			<tbody>
				<tr>
					<th><?php _e( "View Debug Log", 'simply-static' ); ?></th>
					<td>
						<?php if ( $this->debug_file_exists ) : ?>
							<p><?php echo sprintf( __( "You have created <a href='%s'>a debug log</a>.", 'simply-static' ), $this->debug_file_url ); ?></p>
						<?php else : ?>
							<p><?php _e( "You have not created a debug log yet.", 'simply-static' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php _e( "Send Debug Log by Email", 'simply-static' ); ?></th>
					<td>
						<?php if ( $this->debug_file_exists ) : ?>
							<input type="email" name="email_address" id="emailAddress" value="support@simplystatic.co" />
							<input class="button" type="submit" id="sendEmail" value="<?php _e( "Send", 'simply-static' );?>"/>
						<?php else : ?>
							<p><?php _e( "You have not created a debug log yet.", 'simply-static' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

	</form>

</div>
<!-- .wrap -->
