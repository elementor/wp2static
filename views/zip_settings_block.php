<div class="zip_settings_block" style="display:none;">

<?php if ( ! extension_loaded( 'zip' ) ) : ?>
<section class="wp2static-content">

<div class="notice notice-error inline wp2static-notice">
  <h2 class="title">WARNING: ZIP extension missing</h2>

  <p>This can be a common issue but easy to fix. It means that the installation of the PHP programming language on your web host does not currently have support for reading and generating compressed ZIP files. This is needed for the plugin to be able to generate a .zip archive of your static website. Here's some hosting company and platform specific guides on how to fix:</p>

  <ul>
      <li><a target="_blank" href="https://godaddy.com/help/enable-custom-php-modules-12036">GoDaddy</a></li>
      <li><a target="_blank" href="https://stackoverflow.com/questions/23564138/how-to-enable-zip-dll-in-xampp">XAMPP</a></li>
      <li><a target="_blank" href="https://stackoverflow.com/questions/38104348/install-php-zip-on-php-5-6-on-ubuntu">Ubuntu</a></li>
      <li><a target="_blank" href="https://www.digitalocean.com/community/questions/php-7-0-ziparchive-library-is-missing-or-disabled">DigitalOcean</a></li>
      <li><a target="_blank" href="https://lmgtfy.com/?q=how+to+get+zip+extension+php">Ask Dr. Google</a></li>
  </ul>

  <p>After installing/enabling the ZIP extension for PHP, you will likely also need to restart your webserver (Apache or nginx) for it to be usable within WordPress and this plugin.</p>

</div>

</section>
<?php endif; ?>

<section class="wp2static-content wp2static-flex">
  <div class="content" style="max-width:30%">
    <h2><?php echo __( 'Destination URL', 'static-html-output-plugin' ); ?></h2>
  </div>

  <div class="content">
    <?php $tpl->displayTextfield( $this, 'baseUrl-zip', 'http://mystaticsite.com', '', '' ); ?>

    <p><em><?php echo __( "Set this to the URL you intend to host your static exported site on, ie http://mystaticsite.com. Do not set this to the same URL as the WordPress site you're currently using (the address in your browser above). This plugin will rewrite all URLs in the exported static html from your current WordPress URL to what you set here. Supports http, https and protocol relative URLs.", 'static-html-output-plugin' ); ?></em></p>
  </div>
</section>

</div>
