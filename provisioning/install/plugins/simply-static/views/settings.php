<?php
namespace Simply_Static;
?>

<h1><?php _e( 'Simply Static &rsaquo; Settings', 'simply-static' ); ?></h1>

<div class='wrap' id='settingsPage'>

	<h2 id='sistTabs' class='nav-tab-wrapper'>
		<a class='nav-tab' id='general-tab' href='#tab-general'><?php _e( 'General', 'simply-static' ); ?></a>
		<a class='nav-tab' id='include-exclude-tab' href='#tab-include-exclude'><?php _e( 'Include/Exclude', 'simply-static' ); ?></a>
		<a class='nav-tab' id='advanced-tab' href='#tab-advanced'><?php _e( 'Advanced', 'simply-static' ); ?></a>
		<a class='nav-tab' id='reset-settings-tab' href='#tab-reset-settings'><?php _e( 'Reset', 'simply-static' ); ?></a>
	</h2>

	<form id='optionsForm' method='post' action=''>

		<?php wp_nonce_field( 'simply-static_settings' ) ?>
		<input type='hidden' name='_settings' value='1' />

		<div id='general' class='tab-pane'>
			<table class='form-table'>
				<tbody>
					<tr>
						<th>
							<label><?php _e( "Destination URLs", 'simply-static' );?></label>
						</th>
						<td>
							<p><?php _e( "When exporting your static site, any links to your WordPress site will be replaced by one of the following: absolute URLs, relative URLs, or URLs contructed for offline use.", 'simply-static' ); ?></p>
						</td>
					</tr>
					<tr>
						<th></th>
						<td class='url-dest-option'>
							<span>
								<input type="radio" name="destination_url_type" value="absolute" <?php Util::checked_if( $this->destination_url_type === 'absolute' ); ?>>
							</span>
							<span>
								<p><label><?php _e( "Use absolute URLs", 'simply-static' );?></label></p>
								<select id='destinationScheme' class='scheme-entry' name='destination_scheme'>
									<?php foreach ( array( 'http://', 'https://', '//' ) as $scheme ) : ?>
									<option value='<?php echo $scheme; ?>' <?php Util::selected_if( $this->destination_scheme === $scheme ) ?>><?php echo $scheme; ?></option>
									<?php endforeach; ?>
								</select><!--
							 --><input aria-describedby='destinationHostHelpBlock' type='text' id='destinationHost' class='host-entry' name='destination_host' placeholder='<?php _e( "www.example.com/", 'simply-static' ); ?>' value='<?php echo trailingslashit( esc_attr( $this->destination_host ) ); ?>' size='50' />
								<p id='destinationHostHelpBlock' class='help-block'><?php _e( "Convert all URLs for your WordPress site to absolute URLs at the domain specified above.", 'simply-static' ); ?></p>
							</span>
						</td>
					</tr>
					<tr>
						<th></th>
						<td class='url-dest-option'>
							<span>
								<input type="radio" name="destination_url_type" value="relative" <?php Util::checked_if( $this->destination_url_type === 'relative' ); ?>>
							</span>
							<span>
								<p><label><?php _e( "Use relative URLs", 'simply-static' );?></label></p>
								<input aria-describedby='relativePathHelpBlock' type='text' id='relativePath' name='relative_path' placeholder='/' value='<?php echo trailingslashit( esc_attr( $this->relative_path ) ); ?>' size='50' />
								<div id='relativePathHelpBlock' class='help-block'>
									<p><?php _e( "Convert all URLs for your WordPress site to relative URLs that will work at any domain. Optionally specify a path above if you intend to place the files in a subdirectory.", 'simply-static' ); ?></p>
									<p><?php _e( "Example: enter <code>/path/</code> above if you wanted to serve your files at <code>www.example.com<b>/path/</b></code>", 'simply-static' ); ?></p>
								</div>
							</span>
						</td>
					</tr>
					<tr>
						<th></th>
						<td class='url-dest-option'>
							<span>
								<input type="radio" name="destination_url_type" value="offline" <?php Util::checked_if( $this->destination_url_type === 'offline' ); ?>>
							</span>
							<span>
								<p><label><?php _e( "Save for offline use", 'simply-static' ); ?></label></p>
								<p class='help-block'>
									<?php _e( "Convert all URLs for your WordPress site so that you can browse the site locally on your own computer without hosting it on a web server.", 'simply-static' ); ?>
								</p>
							</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for='deliveryMethod'><?php _e( "Delivery Method", 'simply-static' ); ?></label></th>
						<td>
							<select name='delivery_method' id='deliveryMethod'>
								<option value='zip' <?php Util::selected_if( $this->delivery_method === 'zip' ) ?>><?php _e( "ZIP Archive", 'simply-static' ); ?></option>
								<option value='local' <?php Util::selected_if( $this->delivery_method === 'local' ) ?>><?php _e( "Local Directory", 'simply-static' ); ?></option>
							</select>
						</td>
					</tr>
					<tr class='delivery-method zip'>
						<th></th>
						<td>
							<p><?php _e( "Saving your static files to a ZIP archive is Simply Static's default delivery method. After generating your static files you will be provided with a link to download the ZIP archive.", 'simply-static' ); ?></p>
						</td>
					</tr>
					<tr class='delivery-method local'>
						<th></th>
						<td>
							<p><?php _e( "Saving your static files to a local directory is useful if you want to serve your static files from the same server as your WordPress installation. WordPress can live on a subdomain (e.g. wordpress.example.com) while your static files are served from your primary domain (e.g. www.example.com).", 'simply-static' ); ?></p>
						</td>
					</tr>
					<tr class='delivery-method local'>
						<th>
							<label for='local_dir'><?php _e( "Local Directory", 'simply-static' );?></label>
						</th>
						<td>
							<?php $example_local_dir = trailingslashit( untrailingslashit( get_home_path() ) . '_static' ); ?>
							<input aria-describedby='localDirHelpBlock' type='text' id='localDir' name='local_dir' value='<?php echo esc_attr( $this->local_dir ); ?>' class='widefat' />
							<div id='localDirHelpBlock' class='help-block'>
								<p><?php _e( "This is the directory where your static files will be saved. The directory must exist and be writeable by the webserver.", 'simply-static' ); ?></p>
								<p><?php echo sprintf( __( "Example: <code>%s</code>", 'simply-static' ), $example_local_dir ); ?></p>
							</div>
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
		</div>

		<div id='include-exclude' class='tab-pane'>
			<table class='form-table'>
				<tbody>
					<tr>
						<th>
							<label for='additionalUrls'><?php _e( "Additional URLs", 'simply-static' ); ?></label>
						</th>
						<td>
							<textarea aria-describedby='additionalUrlsHelpBlock' class='widefat' name='additional_urls' id='additionalUrls' rows='5' cols='10'><?php echo esc_textarea( $this->additional_urls ); ?></textarea>
							<div id='additionalUrlsHelpBlock' class='help-block'>
								<p><?php echo sprintf( __( "Simply Static will create a static copy of any page it can find a link to, starting at %s. If you want to create static copies of pages or files that <em>aren't</em> linked to, add the URLs here (one per line).", 'simply-static' ), trailingslashit( Util::origin_url() ) ); ?></p>
								<p><?php echo sprintf( __( "Examples: <code>%s</code> or <code>%s</code>", 'simply-static' ),
								Util::origin_url() . __( "/hidden-page/", 'simply-static' ),
								Util::origin_url() . __( "/images/secret.jpg", 'simply-static' ) ); ?></p>
							</div>
						</td>
					</tr>
					<tr>
						<th>
							<label for='additionalFiles'><?php _e( "Additional Files and Directories", 'simply-static' );?></label>
						</th>
						<td>
							<textarea aria-describedby='additionalFilesHelpBlock' class='widefat' name='additional_files' id='additionalFiles' rows='5' cols='10'><?php echo esc_textarea( $this->additional_files ); ?></textarea>
							<div id='additionalFilesHelpBlock' class='help-block'>
								<p><?php _e( "Sometimes you may want to include additional files (such as files referenced via AJAX) or directories. Add the paths to those files or directories here (one per line).", 'simply-static' ); ?></p>
								<p><?php echo sprintf( __( "Examples: <code>%s</code> or <code>%s</code>", 'simply-static' ),
								get_home_path() .  __( "additional-directory/", 'simply-static' ),
								trailingslashit( WP_CONTENT_DIR ) .  __( "fancy.pdf", 'simply-static' ) ); ?></p>
							</div>
						</td>
					</tr>
					<tr>
						<th>
							<label for='excludeUrls'><?php _e( "URLs to Exclude", 'simply-static' );?></label>
						</th>
						<td>
							<?php
								$urls_to_exclude = $this->urls_to_exclude;
								array_unshift( $urls_to_exclude, array(
									'url' => '',
									'do_not_save' => '1',
									'do_not_follow' => '1'
								) );
							?>
							<div id="excludableUrlRows">
							<?php foreach ( $urls_to_exclude as $index => $url_to_exclude ) : ?>
								<div class='excludable-url-row' <?php if ( $index === 0 ) echo "id='excludableUrlRowTemplate'"; ?>>
									<input type='text' name='excludable[<?php echo $index; ?>][url]' value='<?php echo esc_attr( $url_to_exclude['url'] ); ?>' size='40' />

									<label>
										<input name='excludable[<?php echo $index; ?>][do_not_save]' value='0' type='hidden' />
										<input name='excludable[<?php echo $index; ?>][do_not_save]' value='1' type='checkbox' <?php Util::checked_if( $url_to_exclude['do_not_save'] === '1' ); ?> />
										<?php _e( "Do not save", 'simply-static' ); ?>
									</label>

									<label>
										<input name='excludable[<?php echo $index; ?>][do_not_follow]' value='0' type='hidden' />
										<input name='excludable[<?php echo $index; ?>][do_not_follow]' value='1' type='checkbox' <?php Util::checked_if( $url_to_exclude['do_not_follow'] === '1' ); ?> />
										<?php _e( "Do not follow", 'simply-static' ); ?>
									</label>

									<input class='button remove-excludable-url-row' type='button' name='remove' value='<?php _e( "Remove", 'simply-static' );?>' />
								</div>
							<?php endforeach; ?>
							</div>

							<div>
								<input class='button' type='button' name='add_url_to_exclude' id="AddUrlToExclude" value='<?php _e( "Add URL to Exclude", 'simply-static' );?>' />
							</div>

							<div id='excludeUrlsHelpBlock' class='help-block'>
									<p><?php  _e( "In this section you can specify URLs, or parts of a URL, to exclude from Simply Static's processing. You may also use regex to specify a pattern to match.", 'simply-static' ); ?></p>
									<p><?php  _e( "<b>Do not save</b>: do not save a static copy of the page/file", 'simply-static' ); ?> &mdash; <?php  _e( "<b>Do not follow</b>: do not use this page to find additional URLs for processing", 'simply-static' ); ?></p>
									<p><?php echo sprintf( __( "Example: <code>%s</code> would exclude <code>%s</code> and other files containing <code>%s</code> from processing", 'simply-static' ),
									__( ".jpg", 'simply-static' ),
									Util::origin_url() . __( "/wp-content/uploads/image.jpg", 'simply-static' ),
									__( ".jpg", 'simply-static' ) ); ?></p>
							</div>
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
		</div>

		<div id='advanced' class='tab-pane'>
			<h2 class="title"><?php _e( "Temporary Files", 'simply-static' ); ?></h2>
			<p><?php _e( "Your static files are temporarily saved to a directory before being copied to their destination or creating a ZIP.", 'simply-static' ); ?></p>
			<table class='form-table'>
				<tbody>
					<tr>
						<th>
							<label for='tempFilesDir'><?php _e( "Temporary Files Directory", 'simply-static' );?></label>
						</th>
						<td>
							<?php $example_temp_files_dir = trailingslashit( plugin_dir_path( dirname( __FILE__ ) ) . 'static-files' ); ?>
							<input aria-describedby='tempFilesDirHelpBlock' type='text' id='tempFilesDir' name='temp_files_dir' value='<?php echo esc_attr( $this->temp_files_dir ) ?>' class='widefat' />
							<div id='tempFilesDirHelpBlock' class='help-block'>
								<p><?php _e( "Specify the directory to save your temporary files. This directory must exist and be writeable.", 'simply-static' ); ?></p>
								<p><?php echo sprintf( __( "Default: <code>%s</code>", 'simply-static' ), $example_temp_files_dir ); ?></p>
							</div>
						</td>
					</tr>
					<tr>
						<th>
							<label><?php _e( "Delete Temporary Files", 'simply-static' ); ?></label>
						</th>
						<td>
							<label>
								<input name='delete_temp_files' value='0' type='hidden' />
								<input aria-describedby='deleteTempFilesHelpBlock' name='delete_temp_files' id='deleteTempFiles' value='1' type='checkbox' <?php Util::checked_if( $this->delete_temp_files === '1' ); ?> />
								<?php _e( "Delete temporary files at the end of the job", 'simply-static' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>

			<h2 class="title"><?php _e( "HTTP Basic Authentication", 'simply-static' ); ?></h2>
			<p><?php _e( "If you've secured WordPress with HTTP Basic Auth you can specify the username and password to use below.", 'simply-static' ); ?></p>
			<?php if ( $this->http_basic_auth_digest != null ) : ?>
			<table class='form-table' id='basicAuthSet'>
				<tbody>
					<tr>
						<th>
							<label><?php _e( "Basic Auth", 'simply-static' ); ?></label>
						</th>
						<td>
							<p id='basicAuthCredentialsSaved'><?php _e( "Your basic auth credentials have been saved. To disable basic auth or set a new username/password, <a href='#'>click here</a>.", 'simply-static' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php endif; ?>
			<table class='form-table <?php if ( $this->http_basic_auth_digest != null ) echo 'hide' ?>' id='basicAuthUserPass'>
				<tbody>
					<tr>
						<th>
							<label for='basicAuthUsername'><?php _e( "Basic Auth Username", 'simply-static' ); ?></label>
						</th>
						<td>
							<input type='text' id='basicAuthUsername' name='basic_auth_username' value='' <?php if ( $this->http_basic_auth_digest != null ) echo 'disabled' ?> />
						</td>
					</tr>
					<tr>
						<th>
							<label for='basicAuthPassword'><?php _e( "Basic Auth Password", 'simply-static' ); ?></label>
						</th>
						<td>
							<input type='text' id='basicAuthPassword' name='basic_auth_password' value='' <?php if ( $this->http_basic_auth_digest != null ) echo 'disabled' ?> />
						</td>
					</tr>
				</tbody>
			</table>

			<table class='form-table'>
				<tbody>
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
		</div>
	</form>

	<form id='resetForm' method='post' action=''>

		<?php wp_nonce_field( 'simply-static_reset' ) ?>
		<input type='hidden' name='_reset' value='1' />

		<div id='reset-settings' class='tab-pane'>
			<table class='form-table'>
				<tbody>
					<tr>
						<th>
							<label for='resetSettings'><?php _e( "Reset Plugin Settings", 'simply-static' );?></label>
						</th>
						<td>
							<input aria-describedby='resetSettingsHelpBlock' id='resetSettings' class='button button-destroy' type='submit' name='reset_settings' value='<?php _e( "Reset Plugin Settings", 'simply-static' ); ?>' />
							<p id='resetSettingsHelpBlock' class='help-block'>
								<?php _e( "This will reset Simply Static's settings back to their defaults.", 'simply-static' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

	</form>

</div>
