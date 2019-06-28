<div id="folder_settings_block_production">

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Destination URL', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <?php $tpl->displayTextfield( $this, 'baseUrl-folderProduction', 'http://mystaticsite.com', '', '' ); ?>

    <p><em><?php echo __( "Set this to the URL you intend to host your static exported site on, ie http://mystaticsite.com. Do not set this to the same URL as the WordPress site you're currently using (the address in your browser above). This plugin will rewrite all URLs in the exported static html from your current WordPress URL to what you set here. Supports http, https and protocol relative URLs.", 'static-html-output-plugin' ); ?></em></p>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Target Directory', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <input id="targetFolderProduction" name="targetFolderProduction" class="regular-text" style="width:100%;" value="<?php echo $this->options->targetFolderProduction ? $this->options->targetFolderProduction : $this->site_info['site_path'] . 'mystaticsite'; ?>" />

    <p>By exporting to a directory on your current server, you can check how it will look when published and make any adjustments needed. If you put this in a publicly accessible path and the links have been rewritten to support it, you may use this method to easily preview your static site without needing to leave your browser.</p>

    <p>As a safeguard, this plugin will only allow you to export to a new directory, an empty directory, or one that contains a file named <code>.wp2static_safety</code> inside. You can write to any existing, populated directories, by placing a file named as such within.</p>
  </div>
</section>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Test Directory', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <p>This will check the folder exists, else try to create it, along with a test file and directory inside it. It will also create the <code>.wp2static_safety</code> file within.</p>

    <button id="folder-test-buttonProduction" type="button" class="wp2static-btn btn-sm">Test Folder is Writable</button>
  </div>
</section>

</div>
